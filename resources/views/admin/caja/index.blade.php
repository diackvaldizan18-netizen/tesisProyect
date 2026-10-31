@extends('layouts.admin')
@section('page-title','Caja')
@section('page-subtitle','Apertura, cobros y cierre de turno.')
@section('content')
@include('admin.estadias.messages')
<section class="card"><div class="panel-heading"><div><h2>{{ $activa ? 'Caja abierta' : 'Caja cerrada' }}</h2>@if($activa)<p>{{ $activa->responsable->name }} · Desde {{ $activa->abierta_at->format('d/m/Y H:i') }}</p>@endif</div>@if($activa && $turno?->id !== $activa->id)<a class="btn btn-primary" href="{{ route('admin.caja.index') }}">Ver turno activo</a>@endif</div>
@if(!$activa)<form method="post" action="{{ route('admin.caja.abrir') }}" class="filters">@csrf<div class="form-group"><label for="fondo">Fondo inicial en efectivo (S/)</label><input id="fondo" name="fondo" type="number" min="0" max="999999" step="0.01" required value="{{ old('fondo') }}"></div><div class="form-group"><label for="obs-apertura">Observaciones (opcional)</label><input id="obs-apertura" name="observaciones" maxlength="1000" value="{{ old('observaciones') }}"></div><button class="btn btn-primary">Abrir mi turno</button></form>@endif</section>
@if($turno)
<section class="card"><div class="panel-heading"><h2>Turno #{{ $turno->id }} · {{ $turno->responsable->name }}</h2><span class="badge">{{ $turno->cerrada_at ? 'Cerrado' : 'En curso' }}</span></div><p>{{ $turno->abierta_at->format('d/m/Y H:i') }} → {{ $turno->cerrada_at?->format('d/m/Y H:i') ?? 'Abierto' }}</p>
<div class="management-stats"><x-admin.stat label="Cobros en efectivo" :value="'S/ '.number_format($resumen['efectivo']/100,2)" icon="wallet"/><x-admin.stat label="Yape" :value="'S/ '.number_format($resumen['yape']/100,2)" icon="wallet"/><x-admin.stat label="Plin" :value="'S/ '.number_format($resumen['plin']/100,2)" icon="wallet"/></div>
<dl class="subscriber-details"><div><dt>Fondo inicial</dt><dd>S/ {{ number_format($turno->fondo_centavos/100,2) }}</dd></div><div><dt>Ingresos / egresos del dueño</dt><dd>S/ {{ number_format($resumen['ingresos']/100,2) }} / S/ {{ number_format($resumen['egresos']/100,2) }}</dd></div><div><dt>Efectivo esperado en el cajón</dt><dd>S/ {{ number_format(($turno->esperado_centavos ?? $resumen['esperado'])/100,2) }}</dd></div>
@if($turno->cerrada_at)
@if($turno->contado_centavos !== null)<div><dt>Efectivo contado (cierre histórico)</dt><dd>S/ {{ number_format($turno->contado_centavos/100,2) }}</dd></div><div><dt>Diferencia</dt><dd>S/ {{ number_format($turno->diferencia_centavos/100,2) }}</dd></div>
@else<div><dt>Tipo de cierre</dt><dd>Calculado por el sistema · sin conteo físico</dd></div>
@endif
@endif</dl>
<p class="text-muted">Yape y Plin se muestran por separado y no forman parte del efectivo del cajón.</p>
@if($turno->observaciones_apertura)<p>Apertura: {{ $turno->observaciones_apertura }}</p>@endif
@if($turno->observaciones_cierre)<p>Cierre: {{ $turno->observaciones_cierre }}</p>@endif
@if($turno->cerrada_at)<p>Cerrado por: {{ $turno->cierreResponsable?->name }}</p>@endif
@if(!$turno->cerrada_at && ($turno->responsable_id === auth()->id() || auth()->user()->esSuperAdmin()))
<form method="post" action="{{ route('admin.caja.cerrar',$turno) }}">@csrf
<h3>Cierre calculado</h3><p>El sistema guardará el efectivo esperado según los movimientos registrados. No se registra un conteo físico.</p>
<label>Observaciones (opcional)<textarea name="observaciones" maxlength="1000">{{ old('observaciones') }}</textarea></label>
<label class="checkbox-row"><input type="checkbox" name="confirmado" value="1" required> Confirmo que terminé mi turno y deseo cerrarlo.</label><button class="btn btn-primary">Confirmar cierre de turno</button></form>
@elseif(!$turno->cerrada_at)<p>El cierre corresponde a {{ $turno->responsable->name }} o al dueño.</p>@endif
@if(!$turno->cerrada_at && auth()->user()->esSuperAdmin())<details><summary>Registrar ingreso o egreso de efectivo</summary><form method="post" action="{{ route('admin.caja.movimiento',$turno) }}">@csrf<input type="hidden" name="solicitud_id" value="{{ old('solicitud_id',(string)\Illuminate\Support\Str::uuid()) }}"><div class="form-group"><label for="tipo">Movimiento</label><select id="tipo" name="tipo"><option value="ingreso">Ingreso</option><option value="egreso">Egreso / retiro</option></select></div><div class="form-group"><label for="monto">Importe (S/)</label><input id="monto" name="monto" type="number" min="0.01" step="0.01" max="999999" required></div><div class="form-group"><label for="motivo">Motivo</label><textarea id="motivo" name="motivo" minlength="5" maxlength="1000" required></textarea></div><button class="btn btn-secondary">Registrar movimiento</button></form></details>@endif
</section>
<section class="card"><h2>Movimientos del turno</h2><div class="table-responsive"><table class="data-table mobile-records"><thead><tr><th>Fecha</th><th>Concepto</th><th>Medio</th><th>Importe</th><th>Registrado por</th></tr></thead><tbody>@forelse($movimientos as $m)<tr><td data-label="Fecha">{{ $m->created_at->format('d/m H:i') }}</td><td data-label="Concepto">{{ $m->motivo }} @if($m->pago)<small>{{ $m->pago->codigo_pago }}</small>@endif</td><td data-label="Medio">@if($m->desglose)
@foreach($m->desglose as $medio=>$centavos)<div>{{ ucfirst($medio) }}: S/ {{ number_format($centavos/100,2) }}</div>@endforeach
@else
{{ ucfirst($m->metodo) }}
@endif</td><td data-label="Importe">{{ $m->tipo==='egreso' ? '−' : '+' }} S/ {{ number_format($m->monto_centavos/100,2) }}</td><td data-label="Responsable">{{ $m->operador->name }}</td></tr>@empty<tr><td colspan="5">Todavía no hay movimientos.</td></tr>@endforelse</tbody></table></div>{{ $movimientos->links() }}</section>
@endif
<section class="card" id="historial"><h2>Historial de turnos</h2><p class="text-muted">Los pagos anteriores a Caja conservan su historial original; no se suman a los turnos nuevos.</p>@forelse($turnos as $t)<a class="reservation-choice" href="{{ route('admin.caja.index',['turno'=>$t->id]) }}"><strong>Turno #{{ $t->id }} · {{ $t->responsable->name }}</strong><span>{{ $t->abierta_at->format('d/m/Y H:i') }} · {{ $t->cerrada_at ? 'Cerrado' : 'Abierto' }}</span></a>@empty<p>Aún no hay turnos registrados.</p>@endforelse{{ $turnos->links() }}</section>
@endsection
