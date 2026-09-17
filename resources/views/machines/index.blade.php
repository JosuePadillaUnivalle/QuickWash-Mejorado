@extends('layouts.app')
@section('title', 'Catálogo de máquinas')
@section('content')
<div class="page-heading"><div><div class="eyebrow red">QUICKWASH CAMPUS</div><h1>Catálogo de lavadoras</h1><p>Administra los equipos. La ocupación de cada turno se consulta en las reservas.</p></div><a class="button" href="{{ route('machines.create') }}"><x-icon name="plus"/> Crear lavadora</a></div>
<div class="machine-grid">@forelse($machines as $machine)<article class="machine-card"><div class="machine-visual {{ $machine->status === 'mantenimiento' ? 'maintenance' : '' }}"><span class="status {{ $machine->status === 'disponible' ? 'finalizada' : 'pendiente' }}">{{ $machine->status === 'disponible' ? ($machine->occupied_count ? 'Ocupada hasta la recogida' : 'Habilitada') : 'Mantenimiento' }}</span><x-icon class="big-washer"/></div><div class="machine-details"><div class="machine-title"><h3>{{ $machine->name }}</h3><span>{{ $machine->capacity }} kg</span></div><p>Lavadora · Ciclo de 60 minutos</p><div class="location"><x-icon name="pin"/>{{ $machine->location }}</div>
<p>{{ $machine->active_reservations_count }} reserva(s) activa(s)</p>
<div class="form-actions"><a class="button small secondary" href="{{ route('machines.edit', $machine) }}">Editar</a><form method="POST" action="{{ route('machines.destroy', $machine) }}" data-confirm="¿Eliminar {{ $machine->name }} del catálogo? Se conservarán sus reservas históricas.">@csrf @method('DELETE')<button class="button small danger-outline" @disabled($machine->active_reservations_count > 0)>Eliminar</button></form></div>
@if($machine->active_reservations_count > 0)<small class="muted">Resuelve las reservas activas antes de eliminar o pasar a mantenimiento.</small>@endif
</div></article>@empty<div class="empty-state"><h3>No hay lavadoras registradas</h3><p>Crea una lavadora para comenzar.</p></div>@endforelse</div>
<div class="inline-note"><x-icon name="info"/><span>Mantenimiento es una condición del equipo, no una reserva. Usa Editar para habilitarlo cuando esté listo para operar. Eliminar retira la lavadora del catálogo y conserva su historial.</span></div>
@endsection
