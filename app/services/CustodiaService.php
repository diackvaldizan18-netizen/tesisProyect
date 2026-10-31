<?php
namespace App\Services;

use App\Models\{Estadia, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\UniqueConstraintViolationException;

class CustodiaService
{
    public function asignar(Estadia $stay, int $locker, User $staff): void
    {
        if ($locker < 1 || $locker > 41 || $this->ocupado($locker, $stay->id)) {
            throw ValidationException::withMessages(['casillero'=>'El casillero del espacio no está disponible. Selecciona otro casillero libre antes de registrar la entrada.']);
        }
        $stay->update(['casillero_activo'=>$locker,'custodia_estado'=>'pendiente',
            'ubicacion_llave'=>'Casillero T'.str_pad((string)$locker,2,'0',STR_PAD_LEFT)]);
        DB::table('custodia_eventos')->insert(['estadia_id'=>$stay->id,'operador_id'=>$staff->id,'accion'=>'asignar','casillero'=>$locker,'created_at'=>now()]);
    }

    private function ocupado(int $locker, int $except): bool
    {
        return Estadia::where('id','!=',$except)->where(function ($q) use ($locker) {
            $q->where('casillero_activo',$locker)->orWhere(function ($q) use ($locker) {
                $q->activas()->whereNull('custodia_estado')->where('llave_recibida',true)
                    ->whereIn('ubicacion_llave',[(string)$locker,'Casillero '.$locker,'Casillero T'.str_pad((string)$locker,2,'0',STR_PAD_LEFT)]);
            });
        })->exists();
    }

    public function registrar(Estadia $ticket, array $data, User $staff): void
    {
        try {
            DB::transaction(function () use ($ticket, $data, $staff) {
                $stay = Estadia::lockForUpdate()->findOrFail($ticket->id);
                $action = $data['accion'];
                $state = $stay->custodia_estado;
                $locker = $stay->casillero_activo;
                if ($stay->hora_salida) $this->invalid('El ticket ya está cerrado.');
                if ($action === 'recibir') {
                    if ($state && !in_array($state,['devuelta','pendiente'],true)) $this->invalid('La llave ya tiene una custodia activa.');
                    $locker = (int) ($data['casillero'] ?? 0);
                    if ($locker < 1 || $locker > 41) $this->invalid('Selecciona un casillero del 1 al 41.');
                    // Existing records keep their location until staff physically regularizes custody.
                    if ($this->ocupado($locker,$stay->id)) $this->invalid('Ese casillero ya está ocupado.');
                    $state = 'guardada';
                } elseif ($action === 'retirar') {
                    if ($state !== 'guardada' || !$stay->autoriza_movimiento) $this->invalid('Requiere llave guardada y autorización de movimiento.');
                    $state = 'con_personal';
                } elseif ($action === 'guardar') {
                    if ($state !== 'con_personal' || $stay->custodia_responsable_id !== $staff->id) $this->invalid('Debe guardar la llave el trabajador que la retiró.');
                    $state = 'guardada';
                } elseif ($action === 'devolver') {
                    if ($state !== 'guardada') $this->invalid('Primero devuelve la llave a su casillero.');
                    $state = 'devuelta';
                } else $this->invalid('Acción de custodia no válida.');
                DB::table('custodia_eventos')->insert(['estadia_id'=>$stay->id,'operador_id'=>$staff->id,'accion'=>$action,'casillero'=>$locker,'created_at'=>now()]);
                $stay->update(['casillero_activo'=>$state === 'devuelta' ? null : $locker,
                    'custodia_estado'=>$state,'custodia_responsable_id'=>$staff->id,
                    'llave_recibida'=>$state !== 'devuelta','ubicacion_llave'=>'Casillero T'.str_pad((string)$locker,2,'0',STR_PAD_LEFT)]);
            });
        } catch (UniqueConstraintViolationException $e) {
            $this->invalid('Otro trabajador acaba de ocupar ese casillero. Actualiza y elige otro.');
        }
    }
    private function invalid(string $message): never { throw ValidationException::withMessages(['custodia'=>$message]); }
}
