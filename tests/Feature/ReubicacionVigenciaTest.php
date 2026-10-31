<?php
namespace Tests\Feature;
use App\Models\{User,Espacio,VehiculoTipo,Tarifa,Abonado,Abono,Pago};
use App\Services\{EstadiaService,CustodiaService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReubicacionVigenciaTest extends TestCase {
 use RefreshDatabase;
 public function test_only_active_paid_assignment_allows_button_and_post():void {
  $staff=User::factory()->create(['role'=>'operador']);
  $type=VehiculoTipo::create(['codigo'=>'auto','nombre'=>'Auto','activo'=>true]);
  Tarifa::create(['vehiculo_tipo_id'=>$type->id,'nombre'=>'Hora','tipo_tarifa'=>'por_hora','monto_por_hora'=>5,'activo'=>true]);
  foreach(['T24','T27'] as $code){$space=Espacio::create(['codigo'=>$code,'activo'=>true,'modo_monitoreo'=>'manual','estado_actual'=>'libre']);$space->vehiculoTipos()->attach($type);$spaces[]=$space;}
  [$source,$target]=$spaces;
  $stay=app(EstadiaService::class)->ingresar(['espacio_id'=>$source->id,'vehiculo_tipo_id'=>$type->id,'placa'=>'ABC123','autoriza_movimiento'=>1],$staff);
  app(CustodiaService::class)->registrar($stay,['accion'=>'recibir','casillero'=>24],$staff);
  $this->actingAs($staff);
  $post=['origen_id'=>$source->id,'destino_id'=>$target->id,'desde_plano'=>1,'movimiento_confirmado'=>1];
  $checkBlocked=function()use($stay,$post){
   $this->get(route('admin.dashboard'))->assertOk()->assertDontSee('Reubicar en el plano');
   $this->get(route('admin.dashboard',['reubicar'=>$stay->id]))->assertNotFound();
   $this->post(route('admin.estadias.reubicar',$stay),$post)->assertSessionHasErrors('reubicacion');
  };
  $checkBlocked();
  $subscriber=Abonado::create(['nombre'=>'Titular','placas'=>['ABO123']]);
  $abono=Abono::create(['abonado_id'=>$subscriber->id,'espacio_id'=>$source->id,'inicio'=>now()->addDay(),'fin'=>now()->addMonth(),'zona'=>'techo','monto'=>270,'registrado_por'=>$staff->id]);
  $payment=Pago::create(['abono_id'=>$abono->id,'codigo_pago'=>'ABO-TEST','metodo_pago'=>'efectivo','monto'=>270,'estado'=>'aprobado']);
  $checkBlocked();
  $abono->update(['inicio'=>now()->subDay(),'fin'=>now()->subMinute()]);$checkBlocked();
  $abono->update(['fin'=>now()->addMonth()]);$payment->update(['estado'=>'pendiente']);$checkBlocked();
  $payment->update(['estado'=>'aprobado']);
  $this->get(route('admin.dashboard'))->assertOk()->assertSee('Reubicar en el plano');
  $this->get(route('admin.dashboard',['reubicar'=>$stay->id]))->assertOk()->assertSee('data-move-form',false)->assertDontSee('name="motivo"',false)->assertDontSee('Confirmar reubicación');
  $target->update(['estado_actual'=>'ocupado']);
  $this->post(route('admin.estadias.reubicar',$stay),$post)->assertSessionHasErrors('reubicacion');
  $this->assertSame($source->id,$stay->fresh()->espacio_id);
  $target->update(['estado_actual'=>'libre']);
  $this->post(route('admin.estadias.reubicar',$stay),$post)->assertSessionHasNoErrors()->assertRedirect(route('admin.dashboard'));
  $this->assertSame($target->id,$stay->fresh()->espacio_id);$this->assertSame(1,$stay->reubicaciones()->count());
  $this->assertSame('guardada',$stay->fresh()->custodia_estado);
 }
}
