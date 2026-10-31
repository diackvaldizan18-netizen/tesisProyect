@if($payment?->desglose)
<dl class="checkout-breakdown">
@foreach($payment->desglose as $medio=>$centavos)<dt>{{ ucfirst($medio) }}</dt><dd>S/ {{ number_format($centavos/100,2) }}</dd>@endforeach
@if($payment->efectivo_entregado_centavos !== null)<dt>Efectivo entregado</dt><dd>S/ {{ number_format($payment->efectivo_entregado_centavos/100,2) }}</dd><dt>Vuelto</dt><dd>S/ {{ number_format($payment->vuelto_centavos/100,2) }}</dd>@endif
</dl>
@endif
