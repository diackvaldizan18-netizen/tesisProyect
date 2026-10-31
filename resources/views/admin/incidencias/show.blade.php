@extends('layouts.admin')
@section('page-title','Incidencia #'.$incidencia->id)
@section('content')
@include('admin.estadias.messages')
<p><a href="{{ route('admin.incidencias.index') }}">← Volver a incidencias</a></p>
<div class="entry-grid"><section class="card"><h2>{{ \App\Models\Incidencia::TIPOS[$incidencia->tipo] }}</h2><p class="badge">{{ \App\Models\Incidencia::ESTADOS[$incidencia->estado] }} · {{ \App\Models\Incidencia::PRIORIDADES[$incidencia->prioridad] }}</p>
<p style="white-space:pre-wrap">{{ $incidencia->descripcion }}</p><p>{{ $incidencia->placa }} · {{ $incidencia->espacio?->codigo }}</p>
<p>Registró: {{ $incidencia->autor->name }} · {{ $incidencia->created_at->format('d/m/Y H:i') }}</p><p>Responsable: {{ $incidencia->responsable->name }}</p>
@if($incidencia->escalada)<p>Marcada para revisión del dueño.</p>
@endif
@if($incidencia->estadia)<a class="btn btn-secondary" href="{{ route('admin.estadias.show',$incidencia->estadia) }}">Abrir ticket {{ $incidencia->estadia->codigo_ticket }}</a>
@endif
<h3>Evidencias</h3>
@forelse($evidencias as $file)<p><a href="{{ route('admin.incidencias.evidencia',[$incidencia,$file->id]) }}">Descargar evidencia #{{ $file->id }}</a></p>
@empty<p>Sin archivos adjuntos.</p>
@endforelse
<h3>Historial</h3>
@foreach($eventos as $evento)<article class="card"><strong>{{ $evento->name }} · {{ $evento->created_at }}</strong><p>{{ $evento->nota }}</p>
@php($cambios=json_decode($evento->cambios,true))
@php($despues=$cambios['despues']??$cambios)<p>{{ \App\Models\Incidencia::ESTADOS[$despues['estado']] }} · {{ \App\Models\Incidencia::PRIORIDADES[$despues['prioridad']] }} · Responsable #{{ $despues['responsable_id'] }}</p></article>
@endforeach</section>
<section class="card"><h2>Agregar seguimiento</h2><form method="post" enctype="multipart/form-data" action="{{ route('admin.incidencias.update',$incidencia) }}">@csrf<input type="hidden" name="version" value="{{ $incidencia->version }}">
<div class="form-group"><label for="estado">Estado</label><select id="estado" name="estado">
@foreach(\App\Models\Incidencia::ESTADOS as $key=>$label)<option value="{{ $key }}" @selected(old('estado',$incidencia->estado)===$key)>{{ $label }}</option>
@endforeach</select><small>Para cerrar, primero marca como resuelta. Una cerrada puede volver a En atención.</small></div>
<div class="form-group"><label for="nota">Acciones realizadas o motivo del cambio</label><textarea id="nota" name="nota" minlength="5" maxlength="3000" required>{{ old('nota') }}</textarea></div>
@include('admin.incidencias.campos')<button class="btn btn-primary">Guardar seguimiento</button></form></section></div>
@endsection
