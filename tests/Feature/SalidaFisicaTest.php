<?php
namespace Tests\Feature;
use App\Models\{User,Espacio,Estadia,VehiculoTipo,Tarifa,Pago,CajaMovimiento,CajaTurno};
use App\Services\{EstadiaService,CajaService,LiquidacionService,CustodiaService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;
class SalidaFisicaTest extends TestCase {
    use RefreshDatabase;
    public function test_payment_keeps_occupancy_and_late_physical_exit_does_not_charge_again():void {
        $this->travelTo(now()->startOfDay()->addHours(10));
        $staff=User::factory()->create(['role'=>'operador']);$turn=app(CajaService::class)->abrir(['fondo'=>0],$staff);
        $type=VehiculoTipo::create(['codigo'=>'auto','nombre'=>'Auto','activo'=>true]);
        Tarifa::create(['vehiculo_tipo_id'=>$type->id,'nombre'=>'Hora','tipo_tarifa'=>'por_hora','monto_por_hora'=>5,'activo'=>true]);
        $space=Espacio::create(['codigo'=>'T01','activo'=>true,'estado_actual'=>'libre','modo_monitoreo'=>'manual']);$space->vehiculoTipos()->attach($type);
        $stay=app(EstadiaService::class)->ingresar(['espacio_id'=>$space->id,'vehiculo_tipo_id'=>$type->id,'placa'=>'ABC123'],$staff);
        app(CustodiaService::class)->registrar($stay,['accion'=>'recibir','casillero'=>1],$staff);
        $this->actingAs($staff)->post(route('admin.estadias.salida',$stay),['salida_confirmada'=>1])->assertSessionHasErrors();
        $quote=Crypt::encryptString(json_encode(['id'=>$stay->id,'inicio'=>$stay->inicio_cobro->toIso8601String(),'corte'=>now()->toIso8601String()]));
        $payment=['cotizacion'=>$quote,'metodo_pago'=>'efectivo','efectivo_recibido'=>10,'pago_verificado'=>1];
        $this->post(route('admin.estadias.cobrar',$stay),$payment)->assertSessionHasNoErrors();
        $this->assertNull($stay->fresh()->hora_salida);$this->assertSame('ocupado',$space->fresh()->estado_actual);
        $this->assertSame('ABC123',$stay->fresh()->placa_activa);$this->assertSame(1,CajaMovimiento::count());
        $this->post(route('admin.estadias.cobrar',$stay),$payment)->assertSessionHasErrors('salida');
        $this->post(route('admin.estadias.salida',$stay),[])->assertSessionHasErrors('salida_confirmada');
        $this->post(route('admin.estadias.salida',$stay),['salida_confirmada'=>1])->assertSessionHasErrors('custodia');
        app(CustodiaService::class)->registrar($stay,['accion'=>'devolver'],$staff);
        app(CajaService::class)->cerrar($turn,['contado'=>5],$staff);
        $this->travel(6)->minutes();
        $this->get(route('admin.estadias.show',$stay))->assertOk()->assertSee('Pago recibido')->assertDontSee('data-cortesia',false);
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('salida pendiente')->assertDontSee('cortesía vencida');
        $this->travel(2)->hours();$this->assertSame(0,app(LiquidacionService::class)->calcular($stay->fresh(),now())['saldo_centavos']);
        $this->post(route('admin.estadias.salida',$stay),['salida_confirmada'=>1])->assertSessionHasNoErrors();
        $this->assertSame('libre',$space->fresh()->estado_actual);$this->assertSame('5.00',$stay->fresh()->saldo_cobrado);
        $this->assertNotNull($stay->fresh()->hora_salida);$this->assertNull($stay->fresh()->espacio_activo_id);
        $this->assertSame(1,Pago::count());$this->assertSame(1,CajaMovimiento::count());
        $this->post(route('admin.estadias.salida',$stay),['salida_confirmada'=>1])->assertSessionHasErrors('salida');
        $this->actingAs(User::factory()->create(['role'=>'user']))->post(route('admin.estadias.cobrar',$stay),$payment)->assertForbidden();
    }
}
