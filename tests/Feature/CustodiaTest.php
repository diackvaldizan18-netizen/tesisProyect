<?php
namespace Tests\Feature;
use App\Models\{User, Estadia, Espacio, VehiculoTipo, Tarifa};
use App\Services\{CustodiaService, EstadiaService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustodiaTest extends TestCase {
    use RefreshDatabase;
    private function ticket(string $code, string $plate): Estadia {
        $tipo=VehiculoTipo::firstOrCreate(['codigo'=>'auto'],['nombre'=>'Auto','activo'=>true]);
        if (!Tarifa::exists()) Tarifa::create(['vehiculo_tipo_id'=>$tipo->id,'nombre'=>'Hora','tipo_tarifa'=>'por_hora','monto_por_hora'=>5,'activo'=>true]);
        $space=Espacio::create(['codigo'=>$code,'activo'=>true,'estado_actual'=>'libre','modo_monitoreo'=>'manual']);
        $space->vehiculoTipos()->attach($tipo);
        return app(EstadiaService::class)->ingresar(['espacio_id'=>$space->id,'vehiculo_tipo_id'=>$tipo->id,'placa'=>$plate,'autoriza_movimiento'=>1],User::first());
    }
    public function test_custody_tracks_staff_preserves_locker_during_movement_and_releases_on_return(): void {
        $staff=User::factory()->create(['role'=>'operador']); $other=User::factory()->create(['role'=>'operador']);
        $a=$this->ticket('T01','ABC123'); $b=$this->ticket('T02','DEF456'); $service=app(CustodiaService::class);
        $service->registrar($a,['accion'=>'recibir','casillero'=>12],$staff);
        $service->registrar($a,['accion'=>'retirar'],$staff);
        try {$service->registrar($b,['accion'=>'recibir','casillero'=>12],$other); $this->fail();} catch(ValidationException $e) {}
        try {$service->registrar($a,['accion'=>'guardar'],$other); $this->fail();} catch(ValidationException $e) {}
        $this->assertSame(12,$a->fresh()->casillero_activo);
        $service->registrar($a,['accion'=>'guardar'],$staff);
        $service->registrar($a,['accion'=>'devolver'],$staff);
        $this->assertNull($a->fresh()->casillero_activo);
        $service->registrar($b,['accion'=>'recibir','casillero'=>12],$other);
        $this->assertSame(7,DB::table('custodia_eventos')->count());
        $this->assertSame('devuelta',$a->fresh()->custodia_estado);
        $this->assertSame($a->hora_ingreso->toIso8601String(),$a->fresh()->hora_ingreso->toIso8601String());
    }
    public function test_routes_validate_permissions_confirmation_and_render_modalities(): void {
        $staff=User::factory()->create(['role'=>'operador']); $a=$this->ticket('T01','ABC123');
        $this->actingAs($staff)->post(route('admin.estadias.custodia',$a),['accion'=>'recibir','casillero'=>42,'confirmado'=>1])->assertSessionHasErrors('casillero');
        $this->post(route('admin.estadias.custodia',$a),['accion'=>'recibir','casillero'=>1])->assertSessionHasErrors('confirmado');
        foreach(['hora','abonado','reserva'] as $mode) $this->get(route('admin.estadias.create',['modalidad'=>$mode]))->assertOk();
        $this->get(route('admin.estadias.show',$a))->assertOk()->assertSee('Custodia de llaves');
        $this->get(route('admin.dashboard'))->assertOk()
            ->assertSee('reception-drawer',false)->assertSee('data-open-ticket-scanner',false)
            ->assertSee('Abrir ticket')->assertDontSee('Ver ticket / reubicar / salida')
            ->assertSeeInOrder(['Estado de recepción','Acciones rápidas','data-parking','Actividad y cobros del día'],false);
        $this->actingAs(User::factory()->create(['role'=>'user']))->post(route('admin.estadias.custodia',$a),['accion'=>'recibir','casillero'=>1,'confirmado'=>1])->assertForbidden();
        $this->assertSame(1,DB::table('custodia_eventos')->count());
    }
    public function test_checkout_is_blocked_until_keys_are_returned(): void {
        $staff=User::factory()->create(['role'=>'operador']); $a=$this->ticket('T01','ABC123');
        app(CustodiaService::class)->registrar($a,['accion'=>'recibir','casillero'=>1],$staff);
        try { app(EstadiaService::class)->salir($a,['salida_confirmada'=>1],$staff); $this->fail(); }
        catch(ValidationException $e) { $this->assertArrayHasKey('custodia',$e->errors()); }
        $this->assertNull($a->fresh()->hora_salida);
    }

    public function test_space_locker_is_assigned_pending_and_conflict_requires_explicit_alternative(): void {
        $staff=User::factory()->create(['role'=>'operador']);
        $a=$this->ticket('T04','ABC123');
        $this->assertSame(4,$a->fresh()->casillero_activo);
        $this->assertSame('pendiente',$a->fresh()->custodia_estado);
        $this->assertFalse($a->fresh()->llave_recibida);
        $destination=Espacio::create(['codigo'=>'T05','activo'=>true,'estado_actual'=>'ocupado','modo_monitoreo'=>'manual']);
        // A relocated vehicle retains its original locker.
        $original=$a->espacio;
        $a->update(['espacio_id'=>$destination->id,'espacio_activo_id'=>$destination->id]);
        $original->update(['estado_actual'=>'libre']);
        $data=['espacio_id'=>$original->id,'vehiculo_tipo_id'=>$a->vehiculo_tipo_id,'placa'=>'DEF456'];
        try { app(EstadiaService::class)->ingresar($data,$staff); $this->fail(); }
        catch(ValidationException $e) { $this->assertArrayHasKey('casillero',$e->errors()); }
        $this->assertSame(1,Estadia::count());
        $b=app(EstadiaService::class)->ingresar($data+['casillero'=>6],$staff);
        $this->assertSame(6,$b->fresh()->casillero_activo);
        $this->assertSame(4,$a->fresh()->casillero_activo);
    }

    public function test_ticket_qr_is_unique_stable_signed_staff_only_and_read_only(): void {
        $staff=User::factory()->create(['role'=>'operador']);
        $a=$this->ticket('T01','ABC123'); $b=$this->ticket('T02','DEF456');
        $url=\Illuminate\Support\Facades\URL::signedRoute('admin.estadias.escanear',$a);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role'=>'user']))->get($url)->assertForbidden();
        $this->actingAs($staff)->get($url)->assertRedirect(route('admin.estadias.show',$a));
        $this->get(route('admin.estadias.index'))->assertOk()->assertSee('Escanear ticket')->assertSee('id="ticket-scanner"',false);
        $this->get($url.'&tamper=1')->assertForbidden();
        $first=$this->get(route('admin.estadias.ticket',$a))->assertOk()->assertSee('data:image/svg+xml;base64,',false)->viewData('qr');
        $this->assertSame($first,$this->get(route('admin.estadias.ticket',$a))->viewData('qr'));
        $this->assertNotSame($first,$this->get(route('admin.estadias.ticket',$b))->viewData('qr'));
        $this->assertNull($a->fresh()->hora_salida);
        $this->assertSame(0,\App\Models\Pago::count());
    }
}
