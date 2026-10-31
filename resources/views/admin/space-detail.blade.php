@php
 $ticket = $espacio->estadias->first();
 $reserva = $espacio->reservas->first();
 $asignacion = $espacio->abonoVigente;
@endphp
<p>{{ $n <= 26 ? 'Techo' : 'Patio' }} · {{ $espacio->modo_monitoreo === 'sensor' ? 'Control por sensor' : 'Control manual' }}</p>
@if($asignacion)
<section><h3>Espacio asignado</h3><p>{{ $asignacion->abonado->nombre }}</p><p>Vigente hasta {{ $asignacion->fin->format('d/m/Y H:i') }}</p>
<p>{{ $ticket && $ticket->abono_id !== $asignacion->id ? 'Vehículo temporal: requiere reubicación antes de recibir al titular.' : ($ticket ? 'Titular dentro' : 'Titular ausente') }}</p>
<a class="btn btn-secondary" href="{{ route('admin.abonados.show',$asignacion->abonado_id) }}">Ver abonado</a></section>
@endif
@if($ticket)
<section><h3>Vehículo dentro</h3><dl>
<dt>Placa / tipo</dt><dd><strong>{{ $ticket->placa }}</strong> · {{ $ticket->vehiculoTipo?->nombre }}</dd>
<dt>Ingreso</dt><dd>{{ $ticket->hora_ingreso->format('d/m/Y H:i') }}</dd>
<dt>Tiempo al consultar</dt><dd>{{ (int) $ticket->hora_ingreso->diffInMinutes(now()) }} minutos</dd>
<dt>Pago</dt><dd>{{ $ticket->pago_registrado_at ? 'Recibido · pendiente de salida física' : ($ticket->abono_id ? 'Consultar cobertura del abono en el ticket' : 'Consultar saldo actualizado en el ticket') }}</dd>
<dt>Llaves</dt><dd>{{ str_replace('_',' ',$ticket->custodia_estado ?? 'Sin registrar') }}@if($ticket->casillero_activo) · Casillero T{{ str_pad((string)$ticket->casillero_activo,2,'0',STR_PAD_LEFT) }}@endif</dd>
</dl><a class="btn btn-primary" href="{{ route('admin.estadias.show',$ticket) }}">Abrir ticket</a>
@if(!$ticket->abono_id && $asignacion)<a class="btn btn-secondary" href="{{ route('admin.dashboard',['reubicar'=>$ticket->id]) }}#map-relocation">Reubicar en el plano</a>
@endif
</section>
@elseif($reserva)
<section><h3>Reserva {{ $reserva->codigo_corto }}</h3><dl><dt>Cliente / placa</dt><dd>{{ $reserva->usuario?->name }} · {{ $reserva->placa ?? 'Sin registrar' }}</dd><dt>Llegada</dt><dd>{{ $reserva->fecha_reserva->format('d/m/Y') }} {{ substr($reserva->hora_inicio,0,5) }}</dd><dt>Estado</dt><dd>{{ str_replace('_',' ',$reserva->estado) }}</dd></dl>
@if(($reserva->pago_al_llegar && $reserva->estado === 'pendiente_pago') || ($reserva->estado === 'confirmada' && $reserva->pagos->contains('estado','aprobado')))
<a class="btn btn-primary" href="{{ route('admin.estadias.create',['reserva'=>$reserva->id]) }}">Registrar llegada</a>
@else
<a class="btn btn-secondary" href="{{ route('admin.reservas.index') }}">Consultar reserva y pago</a>
@endif
</section>
@elseif($espacio->estado_actual === 'libre' && $estado['configuracion_disponible'])
@if($asignacion)<p>El ingreso temporal requiere autorización y llaves para reubicar el vehículo.</p>
@endif
<a class="btn btn-primary" href="{{ route('admin.estadias.create',['espacio_id'=>$espacio->id]) }}">{{ $asignacion ? 'Preparar ingreso aquí' : 'Registrar entrada aquí' }}</a>
@else
<p>No se puede registrar una entrada aquí. Estado: {{ $estado['estado_texto'] }}.</p>
@endif
@if($espacio->modo_monitoreo === 'sensor')<p class="text-muted">{{ $espacio->sensor?->conexion_label ?? 'Sensor pendiente de instalar' }}</p>
@endif
