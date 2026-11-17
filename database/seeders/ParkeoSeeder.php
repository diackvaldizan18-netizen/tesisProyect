<?php

namespace Database\Seeders;

use App\Models\Espacio;
use App\Models\VehiculoTipo;
use App\Services\PlanoEspacios;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ParkeoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (['auto' => 'Auto', 'camioneta' => 'Miniván'] as $codigo => $nombre) {
                $tipo = VehiculoTipo::firstOrCreate(['codigo' => $codigo], ['nombre' => $nombre, 'activo' => true]);
                $tipo = VehiculoTipo::whereKey($tipo->id)->lockForUpdate()->firstOrFail();
                if ($tipo->activo && ! $tipo->tarifas()->exists()) {
                    $tipo->tarifas()->create(TarifaAvanzadaSeeder::tarifaPorHora() + ['activo' => true]);
                }
            }

            $tipos = VehiculoTipo::where('activo', true)->pluck('id');
            $existentes = Espacio::orderBy('id')->lockForUpdate()->get()
                ->groupBy(fn (Espacio $espacio) => PlanoEspacios::numero($espacio->codigo));

            foreach (range(1, PlanoEspacios::TOTAL) as $numero) {
                $codigo = sprintf('T%02d', $numero);
                $grupo = $existentes->get($numero);
                if ($grupo && $grupo->count() > 1) {
                    throw new RuntimeException("Hay varios espacios para $codigo. Revisa sus códigos antes de inicializar el plano.");
                }
                $espacio = $grupo?->first();
                if (! $espacio) {
                    $espacio = Espacio::create([
                        'codigo' => $codigo,
                        'descripcion' => ($numero <= 26 ? 'Techo ' : 'Patio ').$numero,
                        'estado_actual' => 'libre',
                        'activo' => true,
                        'incluido_estudio' => $numero <= 15,
                        'modo_monitoreo' => 'manual',
                    ]);
                } elseif ($espacio->codigo !== $codigo) {
                    // Keep the ID referenced by existing reservations, sensors and stays.
                    $espacio->update(['codigo' => $codigo]);
                }
                if (! $espacio->vehiculoTipos()->exists()) {
                    $espacio->vehiculoTipos()->syncWithoutDetaching($tipos);
                }
            }
        }, 3);
    }
}
