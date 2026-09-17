<?php
namespace App\Services;
use App\Models\{Machine, Reservation, User};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class BookingService
{
    public function book(User $user, int $machineId, Carbon $start, int $garmentCount): Reservation
    {
        return DB::transaction(function () use ($user, $machineId, $start, $garmentCount) {
            // PHP 8.3 ignores SQLite transaction_mode. Acquire the write lock
            // before any transactional read, so concurrent requests wait safely.
            $this->lockSqliteWriter($user);
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedUser->role === 'estudiante', 403);
            $machine = Machine::whereKey($machineId)->lockForUpdate()->firstOrFail();
            if (!$start->isFuture() || $start->gt(now()->addDays(30)->endOfDay()) || $start->hour < 8 || $start->hour > 19 || $start->minute !== 0 || $start->second !== 0) {
                throw ValidationException::withMessages(['slot' => 'Elige un turno futuro, entre las 08:00 y las 19:00, dentro de los próximos 30 días.']);
            }
            if ($garmentCount < 1 || $garmentCount > 100) {
                throw ValidationException::withMessages(['garment_count' => 'Indica una cantidad entera de 1 a 100 prendas.']);
            }
            if ($machine->type !== 'lavadora') {
                throw ValidationException::withMessages(['machine_id' => 'Selecciona una máquina de lavado del catálogo.']);
            }
            if ($machine->status !== 'disponible') {
                throw ValidationException::withMessages(['machine_id' => 'Esta máquina está en mantenimiento. Elige otra.']);
            }
            app(ReservationLifecycle::class)->syncMachine($machineId);
            if (Reservation::where('machine_id', $machineId)->where('status', 'esperando_recogida')->exists()) {
                throw ValidationException::withMessages(['machine_id' => 'Esta máquina tiene ropa pendiente de recogida. Elige otra o espera a que se libere.']);
            }
            if ($lockedUser->reservations()->whereIn('status', Reservation::ACTIVE)->count() >= 3) {
                throw ValidationException::withMessages(['slot' => 'Ya tienes 3 reservas activas. Cancela una pendiente antes de su inicio o espera a que un lavado finalice.']);
            }
            if (DB::table('reservation_slots')->where('machine_id', $machine->id)->where('starts_at', $start)->exists()) {
                throw ValidationException::withMessages(['slot' => 'Este turno acaba de ocuparse. Selecciona otra máquina u horario.']);
            }
            $reservation = Reservation::create(['user_id' => $user->id, 'machine_id' => $machine->id, 'starts_at' => $start, 'ends_at' => $start->copy()->addHour(), 'garment_count' => $garmentCount, 'status' => 'pendiente']);
            DB::table('reservation_slots')->insert(['reservation_id' => $reservation->id, 'machine_id' => $machine->id, 'starts_at' => $start]);
            return $reservation;
        }, 3);
    }
    public function transition(Reservation $reservation, User $actor, string $status): void
    {
        DB::transaction(function () use ($reservation, $actor, $status) {
            $this->lockSqliteWriter($actor);
            Machine::withTrashed()->whereKey($reservation->machine_id)->lockForUpdate()->firstOrFail();
            $item = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            if (!$actor->isStaff()) {
                abort_unless($actor->role === 'estudiante' && $item->user_id === $actor->id && $status === 'cancelada', 403);
                if (!$item->canBeCancelledByStudent()) {
                    throw ValidationException::withMessages(['status' => 'Solo puedes cancelar reservas pendientes antes de que comience el horario.']);
                }
            }
            if ($status !== 'cancelada' || !$item->canBeCancelledByStudent()) {
                throw ValidationException::withMessages(['status' => 'El inicio es automático y solo el estudiante confirma la recogida. Solo se permite cancelar una reserva pendiente antes de su horario.']);
            }
            $item->update(['status' => $status]);
            if ($status === 'cancelada') DB::table('reservation_slots')->where('reservation_id', $item->id)->delete();
        }, 3);
    }

    private function lockSqliteWriter(User $user): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::table('users')->where('id', $user->id)->update(['id' => DB::raw('id')]);
        }
    }
}
