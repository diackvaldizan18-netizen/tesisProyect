<div class="reception-home {{ auth()->user()->esSuperAdmin() ? 'owner-home' : '' }}">
<section class="reception-metrics" aria-label="Estado de recepción">
@foreach([['Vehículos dentro',$activas],['Libres generales',$libresGenerales],['Entradas hoy',$entradasHoy],['Salidas hoy',$salidasHoy],['Pagos por revisar',$pendientes]] as [$label,$value])
<div><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
@endforeach
</section>
<div class="reception-toolbar">
<nav class="reception-shortcuts" aria-label="Acciones rápidas">
<a class="btn btn-primary" href="{{ route('admin.estadias.create') }}">+ Registrar entrada</a>
<button class="btn btn-secondary" type="button" data-open-ticket-scanner>Escanear ticket</button>
<a class="btn btn-secondary" href="{{ route('admin.estadias.index') }}#buscar">Buscar vehículo / ticket</a>
<a class="btn btn-secondary" href="{{ route('admin.reservas.index',['vista'=>'hoy']) }}">Reservas de hoy</a>
</nav>
<a class="reception-cash" href="{{ route('admin.caja.index') }}">Caja {{ $cajaActiva ? 'abierta · '.$cajaActiva->responsable->name : 'cerrada · Abrir turno' }} →</a>
</div>
@if(auth()->user()->esSuperAdmin())
<nav class="owner-links" aria-label="Administración del negocio">
@foreach(['admin.caja.index'=>'Caja y movimientos','admin.incidencias.index'=>'Incidencias','admin.abonados.index'=>'Abonados','admin.reportes.index'=>'Reportes','superadmin.dashboard'=>'Personal'] as $route=>$label)
<a class="btn btn-secondary" href="{{ route($route) }}">{{ $label }}</a>
@endforeach
</nav>
@include('admin.owner-overview')
@endif
@include('admin.estadias.messages')
@include('admin.map-relocation')
<div class="reception-workspace">
<div class="reception-map">
<div class="reception-map-heading"><span>Consultado: {{ now()->format('H:i:s') }}</span><a class="btn btn-secondary" href="{{ route('admin.dashboard',request()->only(['periodo','fecha'])) }}">Actualizar estados</a></div>
@include('partials.plano',['administrativo'=>true,'recepcion'=>true])
</div>
<aside class="reception-context">
<section class="card"><h2>Atención pendiente</h2>
@if($salidasPendientes)<p><a href="{{ route('admin.estadias.index',['pendiente_salida'=>1]) }}">{{ $salidasPendientes }} con pago recibido y salida pendiente</a></p>
@endif
@if($llavesPendientes)<p><a href="{{ route('admin.estadias.index',['llaves'=>'pendientes']) }}">{{ $llavesPendientes }} con llaves pendientes de recepción</a></p>
@endif
@if($incidenciasPendientes)<p><a href="{{ route('admin.incidencias.index') }}">{{ $incidenciasPendientes }} incidencias pendientes</a></p>
@endif
@if($incidenciasEscaladas)<p><a href="{{ route('admin.incidencias.index',['escaladas'=>1]) }}">{{ $incidenciasEscaladas }} para revisión del dueño</a></p>
@endif
@if($pendientes)<p><a href="{{ route('admin.pagos.index') }}">Revisar comprobantes de pago</a></p>
@endif
@if(!$salidasPendientes && !$llavesPendientes && !$incidenciasPendientes && !$pendientes)<p class="text-muted">No hay alertas pendientes.</p>
@endif
</section>
<section class="card"><h2>Espacios de abonados</h2><p><strong>{{ $libresAbonados }}</strong> sin vehículo dentro. Su uso temporal requiere autorización y conservar el espacio del titular.</p></section>
<section class="card"><h2>Próximas llegadas</h2>
@forelse($llegadas as $r)
<a class="reservation-choice" href="{{ route('admin.estadias.create',['reserva'=>$r->id]) }}"><strong>{{ $r->placa ?? 'Sin placa registrada' }} · {{ $r->espacio?->codigo }}</strong><span>{{ $r->usuario?->name }}</span><small>{{ $r->fecha_reserva->format('d/m') }} · {{ substr($r->hora_inicio,0,5) }}</small></a>
@empty
<p class="text-muted">No hay llegadas pendientes.</p>
@endforelse
</section>
</aside>
</div>
<details class="reception-statistics"><summary>Actividad y cobros del día</summary>
@include('admin.dashboard-summary',['compactReception'=>!auth()->user()->esSuperAdmin()])
</details>
@include('admin.estadias.scanner')
</div>
