@if($moverTicket)
<section class="card" id="map-relocation" data-map-relocation>
<h2>Reubicar {{ $moverTicket->placa }} · {{ $moverTicket->espacio->codigo }}</h2>
@if(!$moverTicket->autoriza_movimiento || !$moverTicket->llave_recibida || !$moverTicket->ubicacion_llave)
<p>Falta registrar la autorización del cliente o la recepción de su llave.</p><a class="btn btn-secondary" href="{{ route('admin.estadias.show',$moverTicket) }}#custodia">Completar datos del ticket</a>
@elseif($moverTicket->custodia_estado === 'con_personal' && $moverTicket->custodia_responsable_id !== auth()->id())
<p>Otro trabajador tiene la llave. Debe devolverla a su casillero antes de reubicar.</p>
@else
<p><strong>Toca el espacio libre de destino para registrar el traslado.</strong> Hazlo después de mover el vehículo. Se conserva su ticket, tiempo, pago y casillero.</p>
<form method="post" action="{{ route('admin.estadias.reubicar',$moverTicket) }}" data-move-form>@csrf
<input type="hidden" name="origen_id" value="{{ $moverTicket->espacio_id }}">
<input type="hidden" name="desde_plano" value="1">
<input type="hidden" name="movimiento_confirmado" value="1">
<input type="hidden" name="destino_id" data-move-target>
<script type="application/json" data-move-destinations>@json($moverDestinos->pluck('id')->values())</script>
<noscript><p>Activa JavaScript para seleccionar el destino en el plano, o abre el ticket para usar la selección manual.</p><a href="{{ route('admin.estadias.show',$moverTicket) }}#reubicacion">Selección manual</a></noscript>
<p data-move-message role="status">{{ $moverDestinos->isEmpty() ? 'No hay destinos compatibles libres. La ubicación se mantiene.' : 'Los destinos disponibles están resaltados.' }}</p>
</form>
@endif
<a class="btn btn-secondary" href="{{ route('admin.dashboard') }}">Cancelar</a>
</section>
@push('scripts')<script src="{{ asset('js/map-relocation.js') }}?v={{ filemtime(public_path('js/map-relocation.js')) }}" defer></script>@endpush
@endif
