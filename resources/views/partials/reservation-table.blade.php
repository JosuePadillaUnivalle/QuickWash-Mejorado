<div class="table-wrap"><table><thead><tr><th>Reserva / máquina</th>@if(auth()->user()->isStaff())<th>Estudiante</th>@endif<th>Fecha y horario</th><th>Prendas</th><th>Estado</th>@unless($compact ?? false)<th>Acción</th>@endunless</tr></thead><tbody>
@forelse($reservations as $reservation)<tr><td><div class="table-machine"><span class="table-icon"><x-icon/></span><div><strong>{{ $reservation->machine->name }}</strong><small>{{ $reservation->code }}</small></div></div></td>
@if(auth()->user()->isStaff())<td><strong>{{ $reservation->user->name }}</strong><small>{{ $reservation->user->email }}</small></td>@endif
<td><strong>{{ $reservation->starts_at->format('d/m/Y') }}</strong><small>{{ $reservation->starts_at->format('H:i') }} – {{ $reservation->ends_at->format('H:i') }}</small></td>
<td><span class="garment-badge">{{ $reservation->garment_count ?? 'No registrada' }}</span>@if(is_null($reservation->garment_count))<small>Reserva de versión inicial</small>@endif</td>
<td><span class="status {{ $reservation->status }}">{{ \App\Models\Reservation::LABELS[$reservation->status] }}</span>
@if($reservation->status === 'pendiente' && $reservation->starts_at->lte(now()))<small>En espera de que se libere la máquina</small>@endif
@if($reservation->status === 'en_proceso')<small>Lavado hasta {{ $reservation->processing_ends_at?->format('d/m H:i') }}</small>@endif
@if($reservation->status === 'esperando_recogida')<small>Lavado terminado · La máquina sigue ocupada</small>@endif
@if($reservation->processing_started_at && !$reservation->processing_started_at->equalTo($reservation->starts_at))<small>Inicio real: {{ $reservation->processing_started_at->format('d/m H:i') }}</small>@endif
@if($reservation->collected_at)<small>Recogido: {{ $reservation->collected_at->format('d/m H:i') }}</small>@endif</td>
@unless($compact ?? false)<td>
@if(!auth()->user()->isStaff() && $reservation->status === 'esperando_recogida')
<form method="POST" action="{{ route('reservations.collect', $reservation) }}" data-confirm="¿Confirmas que ya retiraste toda tu ropa de la máquina? La reserva {{ $reservation->code }} finalizará y el siguiente lavado podrá comenzar.">@csrf @method('PATCH')<button class="button small">Recogido</button></form>
@elseif($reservation->canBeCancelledByStudent())
<form method="POST" action="{{ route(auth()->user()->isStaff() ? 'reservations.status' : 'reservations.cancel', $reservation) }}" data-confirm="¿Cancelar la reserva {{ $reservation->code }}? El turno quedará libre para otro estudiante.">@csrf @method('PATCH')<input type="hidden" name="status" value="cancelada"><button class="button small danger-outline">Cancelar</button></form>
@else<span class="muted">{{ $reservation->status === 'esperando_recogida' ? 'El estudiante confirma la recogida' : ($reservation->status === 'en_proceso' ? 'Lavado automático' : 'Sin acciones') }}</span>@endif
</td>@endunless</tr>
@empty<tr><td colspan="7"><div class="empty-state"><span class="empty-icon"><x-icon name="calendar"/></span><h3>No hay reservas para mostrar</h3><p>{{ request()->hasAny(['status', 'search', 'date']) ? 'Prueba con otros filtros para encontrar tus reservas.' : 'Cuando hagas una reserva, podrás seguir su estado aquí.' }}</p>@unless(auth()->user()->isStaff())<a class="button small" href="{{ route('reservations.create') }}">Reservar una máquina <x-icon name="arrow"/></a>@endunless</div></td></tr>@endforelse
</tbody></table></div>
