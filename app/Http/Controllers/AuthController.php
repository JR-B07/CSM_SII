<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const COOKIE_EMAIL_RECORDADO = 'email_recordado';

    public function __construct(protected AuditService $auditService)
    {
    }

    public function showLogin(): View
    {
        return view('auth.login', [
            'emailRecordado' => request()->cookie(self::COOKIE_EMAIL_RECORDADO),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Las credenciales no coinciden con nuestros registros.',
            ])->onlyInput('email');
        }

        $usuario = Auth::user();

        if (! $usuario->estaAprobado()) {
            Auth::logout();

            $mensaje = $usuario->estaPendiente()
                ? 'Tu cuenta todavía no ha sido aprobada por un administrador.'
                : 'Tu solicitud de cuenta fue rechazada. Contacta a Recursos Humanos.';

            return back()->withErrors(['email' => $mensaje])->onlyInput('email');
        }

        if (! $usuario->activo) {
            Auth::logout();

            return back()->withErrors([
                'email' => 'Tu cuenta ha sido dada de baja. Contacta a Recursos Humanos.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $this->auditService->login(Auth::id());

        // "Recordarme": además de mantener la sesión activa (vía Auth::attempt
        // arriba), recuerda el correo en el formulario de login por un año.
        if ($request->boolean('remember')) {
            Cookie::queue(self::COOKIE_EMAIL_RECORDADO, $usuario->email, 60 * 24 * 365);
        } else {
            Cookie::queue(Cookie::forget(self::COOKIE_EMAIL_RECORDADO));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function forgotPassword(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        return back()->with('status', __($status));
    }

    public function logout(Request $request): RedirectResponse
    {
        $usuarioId = Auth::id();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($usuarioId) {
            $this->auditService->logout($usuarioId);
        }

        return redirect()->route('login');
    }
}
