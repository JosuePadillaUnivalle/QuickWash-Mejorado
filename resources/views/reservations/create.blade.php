@extends('layouts.app')
@section('title', 'Reservar máquina')
@section('content')
<div class="page-heading"><div><div class="eyebrow red">UN TURNO QUE SE ADAPTA A TI</div><h1>Reserva tu lavado</h1><p>Consulta el turno, elige una lavadora e indica cuántas prendas traerás.</p></div><span class="date-chip"><x-icon name="calendar"/> {{ $active }} de 3 reservas activas</span></div>
@if($active >= 3)<div class="alert error"><x-icon name="info"/>Ya tienes 3 reservas activas. Cancela una pendiente antes de su inicio o espera a que finalice un lavado.</div>@endif
<section class="panel"><div class="section-heading"><div><h2><span class="number-step">1</span> Consulta fecha y horario</h2><p>Turnos de una hora · Horario de Bolivia</p></div></div>
<form class="filter-bar booking-filter" method="GET">
<label>Fecha de tu reserva<input type="date" name="date" value="{{ $date }}" min="{{ today()->toDateString() }}" max="{{ today()->addDays(30)->toDateString() }}" required></label>
<label>Horario<select name="time">@foreach(range(8,19) as $hour)@php($value = sprintf('%02d:00', $hour))<option value="{{ $value }}" @selected($time === $value)>{{ $value }} – {{ sprintf('%02d:00', $hour+1) }}</option>@endforeach</select></label>
<button class="button"><x-icon name="search"/> Consultar disponibilidad</button></form></section>
<div class="section-heading standalone"><div><h2><span class="number-step">2</span> Selecciona una lavadora</h2><p>Resultado para {{ $start->format('d/m/Y') }}, de {{ $time }} a {{ $start->copy()->addHour()->format('H:i') }}. Si cambias la fecha o el horario, vuelve a consultar.</p></div></div>
<form method="POST" action="{{ route('reservations.store') }}" id="booking-form" data-confirm="¿Confirmar la reserva con los datos seleccionados?">
@csrf<input type="hidden" name="date" value="{{ $date }}"><input type="hidden" name="time" value="{{ $time }}">
<div class="machine-grid">
@forelse($machines as $machine)
@php($busy = in_array($machine->id, $occupied))
@php($maintenance = $machine->status !== 'disponible')
@php($disabled = $busy || $maintenance || !$start->isFuture() || $active >= 3)
<label class="machine-card machine-option {{ $disabled ? 'unavailable' : '' }}">
<div class="machine-visual {{ $maintenance ? 'maintenance' : '' }}"><span class="status {{ $maintenance ? 'pendiente' : ($busy ? 'en_proceso' : 'finalizada') }}">{{ $maintenance ? 'Mantenimiento' : ($busy ? 'Ocupada en este turno' : 'Disponible en este turno') }}</span><x-icon class="big-washer"/></div>
<div class="machine-details"><div class="machine-title"><h3>{{ $machine->name }}</h3><span>{{ $machine->capacity }} kg</span></div><p>Lavadora · Ciclo de 60 minutos</p><div class="location"><x-icon name="pin"/> {{ $machine->location }}</div>
<span class="machine-choice"><input type="radio" name="machine_id" value="{{ $machine->id }}" data-name="{{ $machine->name }}" @checked(old('machine_id') == $machine->id && !$disabled) @disabled($disabled) required>
{{ $disabled ? ($maintenance ? 'No disponible' : ($busy ? 'Turno reservado' : ($active >= 3 ? 'Límite alcanzado' : 'Horario iniciado'))) : 'Seleccionar '.$machine->name }}</span></div>
</label>
@empty<div class="panel empty-state"><h3>No hay lavadoras registradas</h3><p>Consulta nuevamente más tarde.</p></div>@endforelse
</div>
<section class="panel booking-summary"><div><h2><span class="number-step">3</span> Registra tus prendas</h2><p>La cantidad queda guardada en tu reserva y será visible para el personal.</p><label for="garment-count">Cantidad de prendas<input id="garment-count" type="number" name="garment_count" min="1" max="100" step="1" value="{{ old('garment_count') }}" placeholder="Ej. 8" required aria-describedby="garment-help"></label><small id="garment-help">De 1 a 100 prendas. Respeta la capacidad en kg de la lavadora.</small></div><div class="booking-recap"><strong>Tu turno seleccionado</strong><p>{{ $start->format('d/m/Y') }} · {{ $time }} – {{ $start->copy()->addHour()->format('H:i') }}</p><p id="selected-machine">Selecciona una lavadora disponible arriba.</p><button class="button full" @disabled($active >= 3 || !$start->isFuture() || $machines->where('status', 'disponible')->whereNotIn('id', $occupied)->isEmpty())>Confirmar reserva <x-icon name="arrow"/></button></div></section>
</form>
<div class="inline-note"><x-icon name="info"/><span>Pendiente y En proceso cuentan como activas. Solo puedes cancelar una reserva pendiente antes de que comience su horario.</span></div>
@endsection
