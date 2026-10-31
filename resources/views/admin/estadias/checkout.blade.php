
@if($estadia->pago_registrado_at)
@include('admin.payment-breakdown',['payment'=>$estadia->pagoSalida])
<h2>Pago recibido</h2><div class="checkout-total"><small>Cobrado</small><strong>S/ {{ number_format($estadia->saldo_cobrado,2) }}</strong></div>
<p>Registrado el {{ $estadia->pago_registrado_at->format('d/m/Y H:i:s') }}.</p>
@if($estadia->vuelto !== null)<p>Vuelto: S/ {{ number_format($estadia->vuelto,2) }}</p>
@endif
<p>El vehÃ­culo sigue dentro. El espacio se libera al confirmar la salida fÃ­sica.</p>
@elseif($liquidacion['saldo_centavos']>0)
@include('admin.estadias.pago-form')
@else
<h2>Sin saldo pendiente</h2><p>{{ $liquidacion['detalle'] }}</p>
@endif
@if($estadia->pago_registrado_at || $liquidacion['saldo_centavos']===0)
<form method="post" action="{{ route('admin.estadias.salida',$estadia) }}">@csrf
<label class="checkbox-row"><input type="checkbox" name="salida_confirmada" value="1" required> Confirmo que el vehÃ­culo saliÃ³ fÃ­sicamente del estacionamiento.</label>
<button class="btn btn-primary">Confirmar salida</button></form>
@if($estadia->llave_recibida)<p>Primero registra la devoluciÃ³n de las llaves en Custodia.</p>
@endif
@endif
