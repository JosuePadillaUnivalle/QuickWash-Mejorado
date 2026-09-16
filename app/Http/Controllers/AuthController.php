<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate(['email' => 'required|email|max:200', 'password' => 'required|string']);
        // Apply the restriction before authentication, including remembered logins.
        // Staff accounts may use their assigned address outside the student domain.
        if (!str_ends_with($data['email'], '@est.univalle.edu')) {
            $data[] = fn (Builder $query) => $query->where('role', 'personal');
        }
        if (!Auth::attempt($data, $request->boolean('remember'))) throw ValidationException::withMessages(['email' => 'El correo o la contraseña son incorrectos. Los estudiantes deben usar su correo @est.univalle.edu.']);
        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }
    public function register(Request $request)
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:200|ends_with:@est.univalle.edu|unique:users', 'password' => ['required', 'confirmed', Password::min(8)]], [
            'email.ends_with' => 'Debes usar tu correo de estudiante con dominio @est.univalle.edu.',
        ]);
        // Public registration always creates students; role is never mass assigned.
        $user = User::create($data);
        Auth::login($user);
        $request->session()->regenerate();
        return redirect()->route('dashboard')->with('success', '¡Bienvenido a Quick Wash! Tu cuenta está lista.');
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
