@extends('layouts.admin')
@section('page-title','Incidencias')
@section('page-subtitle','Registra novedades y consulta su seguimiento.')
@section('content')
@include('admin.estadias.messages')
<section class="card"><div class="panel-heading"><h2>Seguimiento</h2><a class="btn btn-primary" href="#nueva-incidencia">+ Registrar incidencia</a></div>
<form class="filters" method="get"><div class="form-group"><label for="buscar">Placa, ticket o descripción</label><input id="buscar" name="buscar" value="{{ request('buscar') }}"></div><div class="form-group"><label for="estado-filtro">Estado</label><select id="estado-filtro" name="estado"><option value="pendientes">Pendientes</option>
@foreach(\App\Models\Incidencia::ESTADOS as $key=>$label)<option value="{{ $key }}" @selected(request('estado')===$key)>{{ $label }}</option>
@endforeach</select></div><label><input type="checkbox" name="escaladas" value="1" @checked(request('escaladas'))> Revisión del dueño</label><button class="btn btn-secondary">Buscar</button></form>
<div class="table-responsive"><table class="data-table mobile-records"><thead><tr><th>Incidencia</th><th>Referencia</th><th>Prioridad / estado</th><th>Responsable</th><th></th></tr></thead><tbody>
@forelse($incidencias as $item)<tr><td data-label="Incidencia">#{{ $item->id }} · {{ \App\Models\Incidencia::TIPOS[$item->tipo] }}<small>{{ \Illuminate\Support\Str::limit($item->descripcion,90) }}</small></td><td data-label="Referencia">{{ $item->placa ?? 'Sin placa' }} · {{ $item->espacio?->codigo ?? 'Sin espacio' }}</td><td data-label="Estado">{{ \App\Models\Incidencia::PRIORIDADES[$item->prioridad] }} · {{ \App\Models\Incidencia::ESTADOS[$item->estado] }}
@if($item->escalada)<small>Revisión del dueño</small>
@endif</td><td data-label="Responsable">{{ $item->responsable->name }}</td><td><a class="btn btn-secondary" href="{{ route('admin.incidencias.show',$item) }}">Ver seguimiento</a></td></tr>
@empty<tr><td colspan="5">No hay incidencias para este filtro.</td></tr>
@endforelse</tbody></table></div>{{ $incidencias->links() }}</section>
<section class="card subscriber-new" id="nueva-incidencia"><h2>Registrar incidencia</h2><form method="post" enctype="multipart/form-data" action="{{ route('admin.incidencias.store') }}">@csrf
<div class="form-group"><label for="tipo">Tipo</label><select id="tipo" name="tipo">
@foreach(\App\Models\Incidencia::TIPOS as $key=>$label)<option value="{{ $key }}" @selected(old('tipo')===$key)>{{ $label }}</option>
@endforeach</select></div>
<div class="form-group"><label for="descripcion">¿Qué ocurrió?</label><textarea id="descripcion" name="descripcion" minlength="10" maxlength="5000" required>{{ old('descripcion') }}</textarea></div>
<div class="form-group"><label for="ticket">Código del ticket (opcional)</label><input id="ticket" name="ticket" maxlength="50" value="{{ old('ticket',request('ticket')) }}"><small>Si indicas el ticket, se toman su placa y espacio actual.</small></div>
<div class="form-group"><label for="placa">Placa (si no tienes ticket)</label><input id="placa" name="placa" maxlength="11" value="{{ old('placa') }}"></div>
<div class="form-group"><label for="espacio_id">Espacio (opcional)</label><select id="espacio_id" name="espacio_id"><option value="">Sin indicar / usar el ticket</option>
@foreach($espacios as $espacio)<option value="{{ $espacio->id }}" @selected(old('espacio_id')==$espacio->id)>{{ $espacio->codigo }}</option>
@endforeach</select></div>
@include('admin.incidencias.campos')<button class="btn btn-primary">Guardar incidencia</button></form></section>
@endsection
