<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureStudentEmail
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && !$user->isStaff() && !str_ends_with(mb_strtolower($user->email), '@est.univalle.edu')) {
            // Also protect sessions and remember-me cookies issued before this rule.
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'El acceso de estudiantes requiere un correo @est.univalle.edu.',
            ]);
        }

        return $next($request);
    }
}
