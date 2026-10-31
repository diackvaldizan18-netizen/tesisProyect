<?php
namespace Tests\Feature;
use App\Models\{User,Espacio,VehiculoTipo,Tarifa,Pago,CajaMovimiento};
use App\Services\{CajaService,EstadiaService,CustodiaService,PagoPresencialService,AbonoService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PagoCombinadoTest extends TestCase {
 use RefreshDatabase;
 public function test_mixed_checkout_net_cash_change_duplicate_and_automatic_close(): void {
  $staff=User::factory()->create(['role'=>'operador']);$service=app(CajaService::class);$turn=$service->abrir(['fondo'=>50],$staff);
  $type=VehiculoTipo::create(['codigo'=>'auto','nombre'=>'Auto','activo'=>true]);
  Tarifa::create(['vehiculo_tipo_id'=>$type->id,'nombre'=>'Hora','tipo_tarifa'=>'por_hora','monto_por_hora'=>5,'activo'=>true]);
  $space=Espacio::create(['codigo'=>'T11','activo'=>true,'estado_actual'=>'libre','modo_monitoreo'=>'manual']);$space->vehiculoTipos()->attach($type);
  $stay=app(EstadiaService::class)->ingresar(['espacio_id'=>$space->id,'vehiculo_tipo_id'=>$type->id,'placa'=>'ABC123'],$staff);
  $this->travel(130)->minutes();
  $quote=Crypt::encryptString(json_encode(['id'=>$stay->id,'inicio'=>$stay->inicio_cobro->toIso8601String(),'corte'=>now()->toIso8601String()]));
  $data=['cotizacion'=>$quote,'metodo_pago'=>'yape','pago_combinado'=>1,'monto_digital'=>5,'efectivo_recibido'=>20,'referencia_pago'=>'MIX-123','pago_verificado'=>1];
  $this->actingAs($staff)->post(route('admin.estadias.cobrar',$stay),$data)->assertSessionHasNoErrors();
  $payment=Pago::firstOrFail();$this->assertSame(['efectivo'=>1000,'yape'=>500],$payment->desglose);
  $this->assertEquals(1000,$payment->vuelto_centavos);$this->assertSame('10.00',$stay->fresh()->vuelto);
  $this->assertSame(6000,$service->resumen($turn)['esperado']);$this->assertSame(500,$service->resumen($turn)['yape']);
  $this->post(route('admin.estadias.cobrar',$stay),$data)->assertSessionHasErrors();$this->assertSame(1,CajaMovimiento::count());
  $this->post(route('admin.caja.cerrar',$turn),[])->assertSessionHasErrors('confirmado');
  $this->post(route('admin.caja.cerrar',$turn),['confirmado'=>1])->assertSessionHasNoErrors();
  $this->assertSame(6000,$turn->fresh()->esperado_centavos);$this->assertNull($turn->fresh()->contado_centavos);
  $this->assertNull($stay->fresh()->hora_salida);
 }
 public function test_invalid_splits_are_rejected_and_monthly_split_is_recorded():void {
  foreach([['monto_digital'=>0,'efectivo_recibido'=>20],['monto_digital'=>15,'efectivo_recibido'=>20],['monto_digital'=>5,'efectivo_recibido'=>9]] as $data){
   try{app(PagoPresencialService::class)->preparar(1500,$data+['metodo_pago'=>'plin','pago_combinado'=>1,'referencia_pago'=>'TEST']);$this->fail('Invalid split accepted');}catch(ValidationException $e){$this->assertNotEmpty($e->errors());}
  }
  $staff=User::factory()->create(['role'=>'operador']);$turn=app(CajaService::class)->abrir(['fondo'=>0],$staff);
  $space=Espacio::create(['codigo'=>'T01','activo'=>true,'estado_actual'=>'libre','modo_monitoreo'=>'manual']);
  $a=app(AbonoService::class)->registrar(['nombre'=>'Abonado','placas'=>['ABC123'],'espacio_id'=>$space->id,'inicio'=>today()->format('Y-m-d'),'metodo_pago'=>'plin','pago_combinado'=>1,'monto_digital'=>200,'efectivo_recibido'=>100,'pago_verificado'=>1],$staff);
  $this->assertSame(['efectivo'=>7000,'plin'=>20000],$a->pago->desglose);$this->assertEquals(3000,$a->pago->vuelto_centavos);
  $this->assertSame(7000,app(CajaService::class)->resumen($turn)['esperado']);
 }
 public function test_map_destination_preserves_stay_and_rejects_stale_source():void {
  $staff=User::factory()->create(['role'=>'operador']);$type=VehiculoTipo::create(['codigo'=>'auto','nombre'=>'Auto','activo'=>true]);
  Tarifa::create(['vehiculo_tipo_id'=>$type->id,'nombre'=>'Hora','tipo_tarifa'=>'por_hora','monto_por_hora'=>5,'activo'=>true]);
  foreach(['T11','T27'] as $code){$s=Espacio::create(['codigo'=>$code,'activo'=>true,'estado_actual'=>'libre','modo_monitoreo'=>'manual']);$s->vehiculoTipos()->attach($type);$spaces[]=$s;}
  [$source,$target]=$spaces;
  app(CajaService::class)->abrir(['fondo'=>0],$staff);
  app(AbonoService::class)->registrar(['nombre'=>'Titular','placas'=>['ABO123'],'espacio_id'=>$source->id,'inicio'=>today()->format('Y-m-d'),'metodo_pago'=>'efectivo','efectivo_recibido'=>270,'pago_verificado'=>1],$staff);
  $stay=app(EstadiaService::class)->ingresar(['espacio_id'=>$source->id,'vehiculo_tipo_id'=>$type->id,'placa'=>'ABC123','autoriza_movimiento'=>1],$staff);
  app(CustodiaService::class)->registrar($stay,['accion'=>'recibir','casillero'=>11],$staff);
  $start=$stay->inicio_cobro->toIso8601String();
  $this->actingAs($staff)->get(route('admin.dashboard',['reubicar'=>$stay->id]))->assertOk()->assertSee('data-move-target',false)->assertViewHas('moverDestinos',fn($items)=>$items->count()===1&&$items->first()->id===$target->id);
  $data=['origen_id'=>$source->id,'destino_id'=>$target->id,'movimiento_confirmado'=>1,'desde_plano'=>1];
  $this->post(route('admin.estadias.reubicar',$stay),$data)->assertSessionHasNoErrors();
  $this->assertSame($start,$stay->fresh()->inicio_cobro->toIso8601String());$this->assertSame($target->id,$stay->fresh()->espacio_id);$this->assertSame(11,$stay->fresh()->casillero_activo);$this->assertSame('guardada',$stay->fresh()->custodia_estado);
  $this->post(route('admin.estadias.reubicar',$stay),$data)->assertSessionHasErrors('reubicacion');$this->assertSame(1,$stay->reubicaciones()->count());
 }
}
