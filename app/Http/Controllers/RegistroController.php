<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistroRequest;
use App\Models\Usuario;
use App\Services\EmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegistroController extends Controller
{
    public function __construct(protected EmailService $emailService) {}

    public function mostrarFormulario(): View
    {
        return view('auth.registro');
    }

    /**
     * Auto-registro público. El rol, departamento y salario diario NUNCA se
     * toman del request: nacen fijos/vacíos y solo un admin los define al
     * aprobar la cuenta (UsuarioController::aprobar), para que nadie pueda
     * auto-asignarse un rol privilegiado o un salario.
     */
    public function store(StoreRegistroRequest $request): RedirectResponse
    {
        $usuario = Usuario::create([
            'nombre' => $request->validated('nombre'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'rol' => 'rescatista',
            'salario_diario' => 0,
            'activo' => false,
            'estado_cuenta' => 'pendiente',
        ]);

        $this->emailService->enviarNuevoRegistro($usuario);

        return redirect()->route('login')->with(
            'status',
            'Tu solicitud fue enviada. Un administrador debe aprobar tu cuenta antes de que puedas iniciar sesión.'
        );
    }
}
