@extends('layouts.admin')
@section('title','Inicio - Parke’o')
@section('page-title','Mapa de estacionamientos')
@section('page-subtitle','Selecciona un espacio para registrar una salida, consultar su ticket o registrar un ingreso.')
@push('styles') @vite('resources/css/pages/vehiculos-activos.css') @endpush
@section('content')
<div class="map-intro"><div><span class="eyebrow">OPERACIÓN EN TIEMPO REAL</span><h2>Elige un espacio</h2><p>Los espacios ocupados muestran su ticket y la salida correspondiente. Los libres permiten registrar un ingreso.</p></div><a class="btn btn-secondary" href="{{ route('admin.dashboard') }}"><x-icon name="arrow"/>Actualizar mapa</a></div>
@include('partials.plano',['administrativo'=>true])
<section class="card active-vehicles" data-active-vehicles aria-labelledby="active-vehicles-title">
    <div class="panel-heading"><div><span class="eyebrow">OPERACIÓN ACTUAL</span><h2 id="active-vehicles-title">Vehículos estacionados <small data-active-count>{{ $vehiculosActivos->count() }}</small></h2></div></div>
    <div class="active-vehicles-filters">
        <label>Buscar por placa<input type="search" data-active-search placeholder="Ej. ABC123" autocomplete="off"></label>
        <label>Tipo de vehículo<select data-active-type><option value="">Todos los tipos</option>@foreach($vehiculosActivos->pluck('tipo')->unique()->sort() as $tipo)<option value="{{ $tipo }}">{{ $tipo }}</option>@endforeach</select></label>
        <label>Tarifa<select data-active-rate><option value="">Todas las tarifas</option>@foreach($vehiculosActivos->pluck('tarifa')->unique()->sort() as $tarifa)<option value="{{ $tarifa }}">{{ $tarifa }}</option>@endforeach</select></label>
    </div>
    <div class="table-wrapper"><table class="data-table active-vehicles-table"><thead><tr><th>Placa</th><th>Vehículo</th><th>Espacio</th><th>Tarifa</th><th>Entrada</th><th>Tiempo</th><th>Importe acumulado</th><th>Estado</th><th></th></tr></thead><tbody data-active-rows>
        @forelse($vehiculosActivos as $vehiculo)
        <tr data-plate="{{ $vehiculo['placa'] }}" data-type="{{ $vehiculo['tipo'] }}" data-rate="{{ $vehiculo['tarifa'] }}"><td data-label="Placa"><strong>{{ $vehiculo['placa'] }}</strong></td><td data-label="Vehículo">{{ $vehiculo['tipo'] }}</td><td data-label="Espacio">{{ $vehiculo['espacio'] }}</td><td data-label="Tarifa">{{ $vehiculo['tarifa'] }}</td><td data-label="Entrada">{{ $vehiculo['entrada'] }}</td><td data-label="Tiempo" data-entry-time="{{ $vehiculo['entrada_iso'] }}">{{ (int) floor(\Carbon\Carbon::parse($vehiculo['entrada_iso'])->diffInMinutes(now())) }} min</td><td data-label="Importe acumulado">S/ {{ $vehiculo['importe'] }}</td><td data-label="Estado"><span class="badge">{{ $vehiculo['estado'] }}</span></td><td data-label="Acción"><a class="btn btn-secondary" href="{{ $vehiculo['url'] }}">Ver</a></td></tr>
        @empty<tr data-active-empty><td colspan="9">No hay vehículos por hora o bloque estacionados.</td></tr>@endforelse
    </tbody></table></div>
</section>
<nav class="dashboard-shortcuts" aria-label="Accesos de operación"><a href="{{ route('admin.estadias.index') }}#buscar"><x-icon name="search"/>Buscar vehículo o ticket</a><a href="{{ route('admin.reservas.index',['vista'=>'hoy']) }}"><x-icon name="clock"/>Reservas de hoy</a><a href="{{ route('admin.caja.index') }}"><x-icon name="wallet"/>Caja</a><a href="{{ route('admin.resumen.index') }}"><x-icon name="chart"/>Resumen de actividad</a></nav>
@if($llegadas->isNotEmpty())<section class="card upcoming-arrivals"><div class="panel-heading"><h2>Próximas llegadas</h2><a href="{{ route('admin.reservas.index',['vista'=>'hoy']) }}">Ver todas</a></div><div class="upcoming-list">@foreach($llegadas as $r)<a href="{{ route('admin.estadias.create',['reserva'=>$r->id]) }}"><strong>{{ $r->placa ?? 'Placa pendiente' }} · {{ $r->espacio?->codigo }}</strong><span>{{ $r->usuario?->name }}</span><small>{{ $r->fecha_reserva->format('d/m') }} · {{ substr($r->hora_inicio,0,5) }}</small></a>@endforeach</div></section>@endif
@endsection
@push('scripts') @vite('resources/js/mapa-admin.js') @endpush
