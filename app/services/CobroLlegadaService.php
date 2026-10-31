<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CobroLlegadaService
{
    // Called only inside the entry transaction, with the reservation locked.
    public function registrar(Reserva $reserva, array $data, User $operador): void
    {
        if (empty($data['pago_verificado'])) {
            throw ValidationException::withMessages(['pago_verificado' => 'Verifica que recibiste el pago antes de confirmar la entrada.']);
        }
        $parts=app(PagoPresencialService::class)->preparar(LiquidacionService::centavos($reserva->monto_total),$data);
        if ($reserva->pagos()->whereNotNull('enviado_at')->where('estado', 'pendiente')->exists()) {
            throw ValidationException::withMessages(['reserva_id' => 'Esta reserva tiene un pago en revisión. Resuélvelo antes de cobrar.']);
        }
        $pago = $reserva->pagos()->where('estado', 'pendiente')->whereNull('enviado_at')->first();
        $values = $parts + ['monto' => $reserva->monto_total, 'estado'=>'aprobado',
            'pagado_at' => now(), 'revisado_at' => now(), 'revisado_por' => $operador->id,
            'motivo_revision' => 'Cobro al llegar verificado por el encargado.'];
        if ($pago) {
            $pago->update($values);
        } else {
            $pago = $reserva->pagos()->create($values + ['user_id' => $reserva->user_id, 'codigo_pago' => 'CAJA-'.Str::uuid()]);
        }
        app(CajaService::class)->registrarCobro($pago,$operador);
        $reserva->update(['estado' => 'confirmada', 'pagado_at' => now(), 'expires_at' => null]);
    }
}
