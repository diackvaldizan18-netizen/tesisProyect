@extends('layouts.admin')
@section('title','Inicio - Parke’o')
@section('page-title',auth()->user()->esSuperAdmin() ? 'Control del estacionamiento' : 'Todo listo para recibirlos')
@section('page-subtitle',auth()->user()->esSuperAdmin() ? 'Operación, caja e incidencias bajo tu supervisión.' : 'Espacios, llegadas y cobros en un solo lugar.')
@section('content')
@include('admin.reception-home')
@endsection
