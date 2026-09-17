<?php
namespace App\Http\Middleware;

use App\Services\ReservationLifecycle;
use Closure;
use Illuminate\Http\Request;

class SyncReservations
{
    public function handle(Request $request, Closure $next)
    {
        app(ReservationLifecycle::class)->sync();
        return $next($request);
    }
}
