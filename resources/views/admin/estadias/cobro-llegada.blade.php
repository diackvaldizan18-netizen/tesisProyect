@if($reserva?->pago_al_llegar && $reserva->estado === 'pendiente_pago')
<fieldset><legend>Cobro al llegar · S/ {{ number_format($reserva->monto_total,2) }}</legend>
<p>Verifica placa y reserva. Escanear el QR no confirma un pago.</p>
@include('admin.payment-fields',['totalCentavos'=>\App\Services\LiquidacionService::centavos($reserva->monto_total)])
</fieldset>
@endif
