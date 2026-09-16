<?php
namespace App\Http\Controllers;
use App\Models\{Machine, Reservation};
use Illuminate\Http\Request;
class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $query = Reservation::query();
        if (!$request->user()->isStaff()) $query->where('user_id', $request->user()->id);
        $active = (clone $query)->whereIn('status', Reservation::ACTIVE)->count();
        $inProgress = (clone $query)->where('status', 'en_proceso')->count();
        $completed = (clone $query)->where('status', 'finalizada')->count();
        $recent = (clone $query)->with(['machine', 'user'])->latest('starts_at')->limit(5)->get();
        $machines = Machine::where('type', 'lavadora')->orderBy('name')->get();
        $available = $machines->where('status', 'disponible')->count();
        return view('dashboard', compact('active', 'inProgress', 'completed', 'recent', 'machines', 'available'));
    }
}
