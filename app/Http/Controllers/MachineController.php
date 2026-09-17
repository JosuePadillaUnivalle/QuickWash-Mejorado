<?php
namespace App\Http\Controllers;
use App\Models\{Machine, Reservation};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
class MachineController extends Controller
{
    public function index()
    {
        return view('machines.index', ['machines' => Machine::where('type', 'lavadora')
            ->withCount(['reservations as active_reservations_count' => fn ($q) => $q->whereIn('status', Reservation::ACTIVE)])
            ->orderBy('name')->get()]);
    }

    public function create()
    {
        return view('machines.form', ['machine' => new Machine(['capacity' => 8, 'status' => 'disponible'])]);
    }

    public function store(Request $request)
    {
        Machine::create($this->validated($request) + ['type' => 'lavadora']);
        return redirect()->route('machines.index')->with('success', 'Lavadora creada. Ya puedes consultar su disponibilidad por turno.');
    }

    public function edit(Machine $machine)
    {
        abort_unless($machine->type === 'lavadora', 404);
        return view('machines.form', compact('machine'));
    }

    public function update(Request $request, Machine $machine)
    {
        $data = $this->validated($request, $machine);
        $this->changeMachine($machine, function (Machine $locked) use ($data) {
            if ($data['status'] === 'mantenimiento' && $locked->status !== 'mantenimiento') {
                $this->ensureNoActiveReservations($locked);
            }
            $locked->update($data);
        });
        return redirect()->route('machines.index')->with('success', 'Lavadora actualizada.');
    }

    public function destroy(Machine $machine)
    {
        $this->changeMachine($machine, function (Machine $locked) {
            $this->ensureNoActiveReservations($locked);
            $locked->delete();
        });
        return redirect()->route('machines.index')->with('success', 'Lavadora eliminada del catálogo. Sus reservas históricas se conservan.');
    }

    private function validated(Request $request, ?Machine $machine = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('machines', 'name')->ignore($machine?->id)],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
            'location' => ['required', 'string', 'max:200'],
            'status' => ['required', Rule::in(['disponible', 'mantenimiento'])],
            'type' => ['prohibited'],
        ]);
    }

    private function changeMachine(Machine $machine, \Closure $change): void
    {
        DB::transaction(function () use ($machine, $change) {
            // Serialize catalogue changes with bookings before reading active reservations.
            if (DB::getDriverName() === 'sqlite') {
                DB::table('machines')->where('id', $machine->id)->update(['id' => DB::raw('id')]);
            }
            $locked = Machine::whereKey($machine->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->type === 'lavadora', 404);
            $change($locked);
        }, 3);
    }

    private function ensureNoActiveReservations(Machine $machine): void
    {
        if ($machine->reservations()->whereIn('status', Reservation::ACTIVE)->exists()) {
            throw ValidationException::withMessages(['machine' => 'Esta lavadora tiene reservas pendientes o en proceso. Resuélvelas antes de eliminarla o ponerla en mantenimiento.']);
        }
    }
}
