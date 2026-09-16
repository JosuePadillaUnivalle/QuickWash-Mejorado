@extends('layouts.app')
@section('title', 'Catálogo de máquinas')
@section('content')
<div class="page-heading"><div><div class="eyebrow red">QUICKWASH CAMPUS</div><h1>Catálogo de lavadoras</h1><p>Consulta los equipos habilitados. La ocupación de cada turno se registra en las reservas.</p></div></div>
<div class="machine-grid">@forelse($machines as $machine)<article class="machine-card"><div class="machine-visual {{ $machine->status === 'mantenimiento' ? 'maintenance' : '' }}"><span class="status {{ $machine->status === 'disponible' ? 'finalizada' : 'pendiente' }}">{{ $machine->status === 'disponible' ? 'Habilitada' : 'Mantenimiento' }}</span><x-icon class="big-washer"/></div><div class="machine-details"><div class="machine-title"><h3>{{ $machine->name }}</h3><span>{{ $machine->capacity }} kg</span></div><p>Lavadora · Ciclo de 60 minutos</p><div class="location"><x-icon name="pin"/>{{ $machine->location }}</div></div></article>@empty<div class="empty-state"><h3>No hay lavadoras registradas</h3></div>@endforelse</div>
<div class="inline-note"><x-icon name="info"/><span>El personal tiene acceso de consulta al catálogo. Su operación principal es actualizar el estado de las reservas.</span></div>
@endsection
