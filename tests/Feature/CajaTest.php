<?php
namespace Tests\Feature;
use App\Models\{CajaTurno,CajaMovimiento,Pago,User,Espacio,Abono,Abonado};
use App\Services\{CajaService,AbonoService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CajaTest extends TestCase {
    use RefreshDatabase;
    private function cobrar(User $staff,string $medio,int $soles): Pago {
        return DB::transaction(function()use($staff,$medio,$soles){
            $pago=Pago::create(['codigo_pago'=>'TEST-'.Str::uuid(),'metodo_pago'=>$medio,'monto'=>$soles,'estado'=>'aprobado','pagado_at'=>now()]);
            app(CajaService::class)->registrarCobro($pago,$staff);return $pago;
        });
    }
    public function test_single_open_shift_and_cash_digital_separation_and_immutable_close(): void {
        $staff=User::factory()->create(['role'=>'operador']);$service=app(CajaService::class);
        $turno=$service->abrir(['fondo'=>50],$staff);
        try{$service->abrir(['fondo'=>0],$staff);$this->fail();}catch(ValidationException $e){}
        $pago=$this->cobrar($staff,'efectivo',10);$this->cobrar($staff,'yape',20);$this->cobrar($staff,'plin',30);
        DB::transaction(fn()=>$service->registrarCobro($pago,$staff));
        $this->assertSame(3,CajaMovimiento::count());$this->assertSame(6000,$service->resumen($turno)['esperado']);
        $service->cerrar($turno,['contado'=>60],$staff);
        $this->assertNull($turno->fresh()->diferencia_centavos);
        try{$service->cerrar($turno,['contado'=>100],$staff);$this->fail();}catch(ValidationException $e){}
        $this->assertNull($turno->fresh()->contado_centavos);$this->assertSame(6000,$turno->fresh()->esperado_centavos);
        try{$this->cobrar($staff,'efectivo',5);$this->fail();}catch(ValidationException $e){}
        $this->assertSame(3,Pago::count());
        $second=$service->abrir(['fondo'=>60],$staff);$this->assertNotEquals($turno->id,$second->id);
    }
    public function test_staff_can_close_own_shift_but_cannot_withdraw_or_close_another(): void {
        $staff=User::factory()->create(['role'=>'operador']);$other=User::factory()->create(['role'=>'operador']);
        $turno=app(CajaService::class)->abrir(['fondo'=>50],$staff);
        $this->actingAs($other)->post(route('admin.caja.cerrar',$turno),['confirmado'=>1])->assertForbidden();
        $this->actingAs($staff)->post(route('admin.caja.movimiento',$turno),['tipo'=>'egreso','monto'=>5,'motivo'=>'Retiro','solicitud_id'=>(string)Str::uuid()])->assertForbidden();
        $this->get(route('admin.caja.index'))->assertOk()->assertDontSee('Registrar ingreso o egreso de efectivo');
        $this->post(route('admin.caja.cerrar',$turno),['contado'=>49,'confirmado'=>1])->assertSessionHasErrors('contado');
        $this->post(route('admin.caja.cerrar',$turno),['confirmado'=>1,'observaciones'=>'Fin del turno'])->assertSessionHasNoErrors();
        $this->assertNull($turno->fresh()->diferencia_centavos);$this->assertSame(5000,$turno->fresh()->esperado_centavos);
        $this->get(route('admin.caja.index',['turno'=>$turno->id]))->assertOk()->assertSee('Fin del turno')->assertSee('sin conteo físico');
    }
    public function test_owner_movements_audited_duplicate_protected_and_no_overdraft(): void {
        $owner=User::factory()->create(['role'=>'super_admin']);$service=app(CajaService::class);$turno=$service->abrir(['fondo'=>10],$owner);
        $data=['tipo'=>'egreso','monto'=>5,'motivo'=>'Retiro autorizado','solicitud_id'=>(string)Str::uuid()];
        $service->movimiento($turno,$data,$owner);
        try{$service->movimiento($turno,$data,$owner);$this->fail();}catch(ValidationException $e){}
        try{$service->movimiento($turno,array_replace($data,['monto'=>6,'solicitud_id'=>(string)Str::uuid()]),$owner);$this->fail();}catch(ValidationException $e){}
        $this->assertSame(1,CajaMovimiento::count());$this->assertSame(500,$service->resumen($turno)['esperado']);
        $this->assertSame($owner->id,CajaMovimiento::first()->operador_id);
    }
    public function test_monthly_payment_rolls_back_without_open_shift_then_is_recorded_once(): void {
        $staff=User::factory()->create(['role'=>'operador']);$space=Espacio::create(['codigo'=>'T01','activo'=>true,'estado_actual'=>'libre','modo_monitoreo'=>'manual']);
        $data=['espacio_id'=>$space->id,'inicio'=>today()->format('Y-m-d'),'nombre'=>'Cliente mensual','placas'=>['ABC123'],'metodo_pago'=>'yape','pago_verificado'=>1];
        try{app(AbonoService::class)->registrar($data,$staff);$this->fail();}catch(ValidationException $e){$this->assertArrayHasKey('caja',$e->errors());}
        $this->assertSame(0,Abono::count());$this->assertSame(0,Abonado::count());$this->assertSame(0,Pago::count());
        $turno=app(CajaService::class)->abrir(['fondo'=>0],$staff);
        app(AbonoService::class)->registrar($data,$staff);
        $this->assertSame(27000,app(CajaService::class)->resumen($turno)['yape']);
        $this->assertSame(0,app(CajaService::class)->resumen($turno)['esperado']);
    }
    public function test_customer_cannot_access_cash_and_historical_payments_do_not_enter_new_shift(): void {
        Pago::create(['codigo_pago'=>'OLD','metodo_pago'=>'efectivo','monto'=>100,'estado'=>'aprobado']);
        $this->actingAs(User::factory()->create(['role'=>'user']))->get(route('admin.caja.index'))->assertForbidden();
        $this->post(route('admin.caja.abrir'),['fondo'=>0])->assertForbidden();
        $staff=User::factory()->create(['role'=>'operador']);$turno=app(CajaService::class)->abrir(['fondo'=>10],$staff);
        $this->assertSame(1000,app(CajaService::class)->resumen($turno)['esperado']);
        $this->actingAs($staff)->get(route('admin.estadias.index'))->assertOk()->assertDontSee('Registrar salida')->assertSee('Leer placa');
    }
}
