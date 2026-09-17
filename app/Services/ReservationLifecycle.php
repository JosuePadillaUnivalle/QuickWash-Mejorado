<?php
namespace App\Services;

use App\Models\{Machine, Reservation, User};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationLifecycle
{
    public function sync(): void
    {
        Reservation::whereIn('status', Reservation::ACTIVE)->distinct()->pluck('machine_id')
            ->each(fn ($id) => $this->syncMachine((int) $id));
    }

    public function syncMachine(int $machineId): void
    {
        DB::transaction(function () use ($machineId) {
            $machine = $this->lockMachine($machineId);
            $this->advance($machine);
        }, 3);
    }

    public function collect(Reservation $reservation, User $actor): void
    {
        abort_unless($actor->role === 'estudiante' && $actor->id === $reservation->user_id, 403);
        DB::transaction(function () use ($reservation) {
            $machine = $this->lockMachine($reservation->machine_id);
            $this->advance($machine);
            $item = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            // Retried submissions must not move the release time or restart the next wash.
            if ($item->status === 'finalizada' && $item->collected_at) return;
            if ($item->status !== 'esperando_recogida') {
                throw ValidationException::withMessages(['status' => 'Solo puedes confirmar la recogida cuando el lavado haya terminado.']);
            }
            $item->update(['status' => 'finalizada', 'collected_at' => now()]);
            $this->advance($machine);
        }, 3);
    }

    private function lockMachine(int $id): Machine
    {
        // SQLite needs its write lock before the first read; PostgreSQL uses the row lock.
        if (DB::getDriverName() === 'sqlite') {
            DB::table('machines')->where('id', $id)->update(['id' => DB::raw('id')]);
        }
        return Machine::withTrashed()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    private function advance(Machine $machine): void
    {
        $now = now();
        $occupied = Reservation::where('machine_id', $machine->id)
            ->whereIn('status', ['en_proceso', 'esperando_recogida'])->orderBy('starts_at')->get();
        if ($occupied->isNotEmpty()) {
            foreach ($occupied as $item) {
                $start = $item->processing_started_at ?? $item->starts_at;
                $end = $item->processing_ends_at ?? $item->ends_at;
                $item->fill(['processing_started_at' => $start, 'processing_ends_at' => $end]);
                if ($end->lte($now)) $item->status = 'esperando_recogida';
                if ($item->isDirty()) $item->save();
            }
            return; // Clothing keeps the machine occupied, even after the wash ends.
        }
        if ($machine->trashed() || $machine->status !== 'disponible') return;
        $next = Reservation::where('machine_id', $machine->id)->where('status', 'pendiente')
            ->where('starts_at', '<=', $now)->orderBy('starts_at')->orderBy('id')->first();
        if (!$next) return;
        $released = Reservation::where('machine_id', $machine->id)->max('collected_at');
        $start = $next->starts_at->copy();
        if ($released && Carbon::parse($released)->gt($start)) $start = Carbon::parse($released);
        $end = $start->copy()->addSeconds((int) $next->starts_at->diffInSeconds($next->ends_at));
        $next->update([
            'processing_started_at' => $start, 'processing_ends_at' => $end,
            'status' => $end->lte($now) ? 'esperando_recogida' : 'en_proceso',
        ]);
    }
}
