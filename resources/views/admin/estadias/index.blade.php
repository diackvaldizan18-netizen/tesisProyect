@extends('layouts.admin')
@section('page-title','Operación')
@section('page-subtitle','Encuentra un vehículo, consulta su tiempo y registra el cobro.')
@section('content')
@include('admin.estadias.messages')
@include('admin.estadias.scanner')
<div class="operation-links entry-actions"><a class="btn btn-primary" href="{{ route('admin.estadias.create') }}">+ Registrar entrada</a><button type="button" class="btn btn-primary" data-open-ticket-scanner><x-icon name="camera"/> Escanear ticket</button><a class="btn btn-secondary" href="{{ route('admin.placas.index') }}">Leer placa</a><a class="btn btn-secondary" href="{{ route('admin.monitoreo.index') }}">Ver espacios</a></div>
@if(auth()->user()->esSuperAdmin())<div class="management-stats"><x-admin.stat label="Vehículos dentro" :value="$activas" icon="car"/><x-admin.stat label="Pendientes de detección" :value="$esperando" icon="clock"/><x-admin.stat label="Salidas de hoy" :value="$salidasHoy" icon="exit"/></div>
@elseif($esperando)<p class="alert alert-info">{{ $esperando }} vehículos pendientes de detección. Consulta sus tickets para revisar el inicio del cobro.</p>
@endif

<nav class="operation-links" aria-label="Movimientos"><a class="btn {{ request('estado','activas')==='activas'?'btn-primary':'btn-secondary' }}" href="{{ route('admin.estadias.index') }}">Dentro ({{ $activas }})</a><a class="btn {{ request('estado')==='salidas'?'btn-primary':'btn-secondary' }}" href="{{ route('admin.estadias.index',['estado'=>'salidas']) }}">Salidas de hoy ({{ $salidasHoy }})</a><a class="btn {{ request('estado')==='historial'?'btn-primary':'btn-secondary' }}" href="{{ route('admin.estadias.index',['estado'=>'historial']) }}">Historial</a></nav><section class="card"><div class="panel-heading"><h2>{{ request('estado') === 'salidas' ? 'Salidas de hoy' : (request('estado') === 'historial' ? 'Historial de entradas y salidas' : 'Vehículos actualmente dentro') }}</h2></div>
<form class="filters" method="get">
@if(request('pendiente_salida'))<input type="hidden" name="pendiente_salida" value="1"><p>Pagados pendientes de salida. <a href="{{ route('admin.estadias.index') }}">Ver todos</a></p>
@endif
@if(request('llaves') === 'pendientes')<input type="hidden" name="llaves" value="pendientes"><p>Mostrando llaves pendientes. <a href="{{ route('admin.estadias.index') }}">Ver todos</a></p>
@endif<div class="form-group"><label for="buscar">Placa, cliente o ticket</label><input id="buscar" name="buscar" value="{{ request('buscar') }}" placeholder="Ej. ABC123"></div><input type="hidden" name="estado" value="{{ request('estado','activas') }}"><div class="form-group"><label for="fecha">Fecha de ingreso</label><input type="date" id="fecha" name="fecha" value="{{ request('fecha') }}"></div><button class="btn btn-secondary">Buscar</button></form>
<div class="table-responsive"><table class="data-table mobile-records" data-mobile-details="2"><thead><tr><th>Vehículo / ticket</th><th>Espacio</th><th>Ingreso</th><th>Estado</th><th></th></tr></thead><tbody>
@forelse($estadias as $e)<tr><td data-label="Vehículo / ticket"><strong class="plate-tag">{{ $e->placa }}</strong><small class="text-muted">{{ $e->codigo_ticket }}</small></td><td data-label="Espacio">{{ $e->espacio->codigo }}</td><td data-label="Ingreso">{{ $e->hora_ingreso->format('d/m/Y H:i') }}<small class="text-muted">{{ (int) $e->hora_ingreso->diffInMinutes($e->hora_salida ?? now()) }} min desde el ingreso</small></td><td data-label="Estado"><span class="badge">{{ $e->estado_label }}</span><small class="text-muted">{{ ['guardada'=>'Llave guardada','con_personal'=>'Llave con personal','devuelta'=>'Llave devuelta'][$e->custodia_estado] ?? ($e->llave_recibida ? 'Custodia por regularizar' : 'Llave pendiente') }}{{ $e->ubicacion_llave ? ' · '.$e->ubicacion_llave : '' }}</small></td><td data-label="Acción"><a class="btn btn-secondary" href="{{ route('admin.estadias.show',$e) }}">{{ $e->hora_salida?'Ver constancia':'Abrir ticket' }}</a></td></tr>
@empty<tr><td colspan="5" class="empty-compact">No hay tickets para estos filtros.</td></tr>
@endforelse</tbody></table></div>{{ $estadias->links() }}</section>
@endsection
