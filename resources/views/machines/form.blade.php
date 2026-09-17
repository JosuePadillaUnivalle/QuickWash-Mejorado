@extends('layouts.app')
@section('title', $machine->exists ? 'Editar lavadora' : 'Crear lavadora')
@section('content')
<div class="page-heading"><div><div class="eyebrow red">CATÁLOGO DE MÁQUINAS</div><h1>{{ $machine->exists ? 'Editar lavadora' : 'Crear lavadora' }}</h1><p>Configura el equipo y su estado de servicio.</p></div></div>
<section class="panel form-panel">
<form class="stack-form" method="POST" action="{{ $machine->exists ? route('machines.update', $machine) : route('machines.store') }}">
@csrf
@if($machine->exists) @method('PATCH') @endif
<label>Nombre<input name="name" value="{{ old('name', $machine->name) }}" maxlength="100" required placeholder="Ej. Lavadora 05"></label>
<div class="form-row">
<label>Capacidad (kg)<input type="number" name="capacity" value="{{ old('capacity', $machine->capacity) }}" min="1" max="100" step="1" required></label>
<label>Estado del equipo<select name="status" required><option value="disponible" @selected(old('status', $machine->status) === 'disponible')>Habilitada</option><option value="mantenimiento" @selected(old('status', $machine->status) === 'mantenimiento')>Mantenimiento</option></select></label>
</div>
<label>Ubicación<input name="location" value="{{ old('location', $machine->location) }}" maxlength="200" required placeholder="Ej. Lavandería · Planta baja"></label>
<p class="muted">Habilitada: admite reservas en turnos libres. Mantenimiento: fuera de servicio, aunque no tenga reservas. No se puede retirar de servicio una lavadora con reservas activas.</p>
<div class="form-actions"><button class="button">Guardar lavadora</button><a class="button secondary" href="{{ route('machines.index') }}">Volver al catálogo</a></div>
</form></section>
@endsection
