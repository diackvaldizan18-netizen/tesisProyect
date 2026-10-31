<section class="owner-overview" aria-label="Supervisión del dueño">
<article class="card"><div class="owner-heading"><h2>Caja del turno actual</h2><a href="{{ route('admin.caja.index') }}">Ver caja →</a></div>
@if($cajaActiva)
<p>{{ $cajaActiva->responsable->name }} · Abierta {{ $cajaActiva->abierta_at->format('d/m H:i') }}</p>
<dl class="owner-cash-values">
<div><dt>Efectivo esperado</dt><dd>S/ {{ number_format($ownerOverview['caja']['esperado']/100,2) }}</dd></div>
<div><dt>Cobros en Yape</dt><dd>S/ {{ number_format($ownerOverview['caja']['yape']/100,2) }}</dd></div>
<div><dt>Cobros en Plin</dt><dd>S/ {{ number_format($ownerOverview['caja']['plin']/100,2) }}</dd></div>
</dl><small>El efectivo esperado incluye fondo, cobros en efectivo e ingresos, menos egresos. Los pagos digitales se muestran por separado.</small>
@else
<p>No hay turno abierto. Abre Caja antes de registrar nuevos cobros.</p>
@endif
<p><a class="btn btn-secondary" href="{{ route('admin.caja.index') }}">Administrar caja y movimientos</a></p>
</article>
<article class="card"><div class="owner-heading"><h2>Últimos cierres</h2><a href="{{ route('admin.caja.index') }}#historial">Ver historial →</a></div>
@forelse($ownerOverview['cierres'] as $turno)
<a class="owner-close" href="{{ route('admin.caja.index',['turno'=>$turno->id]) }}"><span>{{ $turno->responsable->name }}<small>{{ $turno->cerrada_at->format('d/m/Y H:i') }}</small></span><strong class="{{ $turno->diferencia_centavos ? 'owner-difference' : '' }}">{{ $turno->contado_centavos === null ? 'Cierre calculado' : ($turno->diferencia_centavos ? 'Diferencia S/ '.number_format($turno->diferencia_centavos/100,2) : 'Sin diferencia') }}</strong></a>
@empty
<p>No hay turnos cerrados registrados.</p>
@endforelse
<small>Los cierres calculados conservan el total del sistema sin conteo físico. Las diferencias corresponden únicamente a cierres históricos con conteo.</small>
</article>
<article class="card"><div class="owner-heading"><h2>Incidencias escaladas</h2><a href="{{ route('admin.incidencias.index',['escaladas'=>1]) }}">Revisar →</a></div>
@forelse($ownerOverview['incidencias'] as $incidencia)
<a class="owner-close" href="{{ route('admin.incidencias.show',$incidencia) }}"><span>#{{ $incidencia->id }} · {{ \App\Models\Incidencia::TIPOS[$incidencia->tipo] ?? $incidencia->tipo }}<small>{{ $incidencia->placa ?: 'Sin vehículo asociado' }} · {{ str_replace('_',' ',$incidencia->estado) }}</small></span><strong>{{ \App\Models\Incidencia::PRIORIDADES[$incidencia->prioridad] ?? $incidencia->prioridad }}</strong></a>
@empty
<p>No hay incidencias escaladas pendientes.</p>
@endforelse
</article>
</section>
