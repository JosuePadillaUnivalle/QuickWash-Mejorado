<?php
namespace App\Http\Controllers;
use App\Models\Machine;
class MachineController extends Controller
{
    public function index()
    {
        return view('machines.index', ['machines' => Machine::where('type', 'lavadora')->orderBy('name')->get()]);
    }
}
