<?php

namespace Tests\Feature;

use App\Models\Espacio;
use App\Models\VehiculoTipo;
use Database\Seeders\ParkeoSeeder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InicializarPlanoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->artisan('migrate')->assertSuccessful();
    }

    public function test_plano_nuevo_se_puede_cotizar_y_reinicializar(): void
    {
        $this->seed(ParkeoSeeder::class);
        $this->seed(ParkeoSeeder::class);
        $this->assertEquals(range(1, 41), Espacio::orderBy('codigo')->pluck('codigo')->map(fn ($c) => (int) substr($c, 1))->all());
        $this->assertSame(41, Espacio::where('activo', true)->where('modo_monitoreo', 'manual')->where('estado_actual', 'libre')->count());
        $this->assertSame(15, Espacio::where('incluido_estudio', true)->count());
        $this->assertDatabaseCount('espacio_vehiculo_tipo', 82);
        $this->assertDatabaseCount('tarifas', 2);
        $this->assertDatabaseCount('sensores', 0);
        $this->get(route('public.disponibilidad'))->assertOk()->assertDontSee('No habilitado');
        foreach (['T01' => 'auto', 'T41' => 'camioneta'] as $codigo => $tipo) {
            $espacio = Espacio::where('codigo', $codigo)->firstOrFail();
            $this->getJson(route('public.cotizar', [$espacio, 'vehiculo_tipo_id' => VehiculoTipo::where('codigo', $tipo)->value('id'), 'duracion_minutos' => 60]))
                ->assertOk()->assertJsonPath('total', '5.00')->assertJsonPath('puede_reservar', true);
        }
    }

    public function test_respeta_espacios_y_tarifas_existentes(): void
    {
        $this->seed(ParkeoSeeder::class);
        $espacio = Espacio::where('codigo', 'T01')->firstOrFail();
        $espacio->update(['codigo'=>'E01', 'descripcion'=>'En reparación','estado_actual'=>'mantenimiento','activo'=>false,'incluido_estudio'=>false,'modo_monitoreo'=>'sensor']);
        $tipo = VehiculoTipo::where('codigo', 'auto')->firstOrFail();
        $tipo->tarifas()->update(['activo'=>false, 'monto_por_hora'=>12]);
        $this->seed(ParkeoSeeder::class);
        $this->assertDatabaseHas('espacios', ['id'=>$espacio->id, 'codigo'=>'T01','descripcion'=>'En reparación','estado_actual'=>'mantenimiento','activo'=>false,'incluido_estudio'=>false,'modo_monitoreo'=>'sensor']);
        $this->assertDatabaseHas('tarifas', ['vehiculo_tipo_id'=>$tipo->id,'activo'=>false,'monto_por_hora'=>12]);
        $this->assertDatabaseCount('tarifas', 2);
        $this->assertDatabaseCount('espacios', 41);
    }
}
