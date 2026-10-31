@extends('layouts.admin')
@section('page-title','Abonados')
@section('page-subtitle','Espacio fijo mensual: techo S/ 270 · patio S/ 240.')
@section('content')
@include('admin.estadias.messages')
<div class="management-stats"><x-admin.stat label="Abonados registrados" :value="$totalAbonados" icon="users"/><x-admin.stat label="Mensualidades vigentes" :value="$vigentes" icon="check"/><x-admin.stat label="Vencen en 7 días" :value="$porVencer" icon="clock"/></div>
<section class="card"><div class="panel-heading"><div><h2>Clientes abonados</h2><p class="text-muted">Consulta su espacio, placas y vigencia mensual.</p></div><a class="btn btn-primary" href="#nuevo-abonado">+ Nuevo abonado</a></div>
<form method="get" class="search-form"><input name="buscar" aria-label="Buscar por nombre o placa" placeholder="Nombre o placa" value="{{ request('buscar') }}"><button class="btn btn-secondary">Buscar</button></form>
<div class="subscriber-grid">@forelse($clientes as $cliente)
@php
 $actual=$cliente->abonos->first(fn($a)=>$a->inicio->lte(now()) && $a->fin->gt(now()) && $a->pago?->estado === 'aprobado');
 $proximo=$cliente->abonos->filter(fn($a)=>$a->inicio->gt(now()) && $a->pago?->estado === 'aprobado')->sortBy('inicio')->first();
 $periodo=$actual ?? $proximo ?? $cliente->abonos->first();
 $estado=$actual ? 'Vigente' : ($proximo ? 'Próximo periodo' : 'Sin mensualidad vigente');
@endphp
<article class="subscriber-card"><div class="subscriber-heading"><span class="subscriber-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($cliente->nombre,0,1)) }}</span><div><h3>{{ $cliente->nombre }}</h3><span class="badge">{{ $estado }}</span></div></div>
<div class="subscriber-plates">@foreach($cliente->placas as $placa)<span class="plate-tag">{{ $placa }}</span>@endforeach</div>
<dl class="subscriber-details"><div><dt>Espacio {{ $actual ? 'actual' : ($proximo ? 'próximo' : 'del último periodo') }}</dt><dd>{{ $periodo?->espacio?->codigo ?? 'Sin asignación' }} · {{ ucfirst($periodo?->zona ?? '') }}</dd></div><div><dt>Mensualidad</dt><dd>{{ $periodo ? 'S/ '.number_format($periodo->monto,2) : '—' }}</dd></div><div><dt>{{ $proximo && !$actual ? 'Comienza' : 'Vencimiento' }}</dt><dd>{{ ($proximo && !$actual ? $periodo?->inicio : $periodo?->fin)?->format('d/m/Y H:i') ?? '—' }}</dd></div></dl>
<a class="btn btn-secondary" href="{{ route('admin.abonados.show',$cliente) }}">Ver detalle y mensualidades <span aria-hidden="true">→</span></a></article>
@empty<div class="empty-compact"><h3>{{ request('buscar') ? 'No encontramos coincidencias' : 'Todavía no hay abonados' }}</h3><p>{{ request('buscar') ? 'Prueba con otro nombre o placa.' : 'Registra al primer cliente y asigna su espacio mensual.' }}</p></div>@endforelse</div>
{{ $clientes->links() }}</section>
<section class="card subscriber-new" id="nuevo-abonado"><h2>Nuevo abonado</h2><p class="text-muted">Datos del cliente, espacio fijo y primera mensualidad.</p><form method="post" action="{{ route('admin.abonados.store') }}">@csrf
<div class="form-group"><label for="nombre">Nombre del cliente</label><input id="nombre" name="nombre" maxlength="150" required value="{{ old('nombre') }}"></div>
<div class="form-group"><label for="telefono">Teléfono</label><input id="telefono" name="telefono" maxlength="40" value="{{ old('telefono') }}"></div>
<div class="form-group"><label for="placas_texto">Placas autorizadas (separadas por coma, máximo 5)</label><input id="placas_texto" name="placas_texto" required value="{{ old('placas_texto') }}"><small>Un solo vehículo puede ocupar el espacio a la vez.</small></div>
@include('admin.abonados.periodo')
<button class="btn btn-primary">Registrar pago y asignar espacio</button></form></section>
@endsection
