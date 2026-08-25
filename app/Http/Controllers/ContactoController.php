<?php

namespace App\Http\Controllers;

use App\Services\EmailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContactoController extends Controller
{
    public function __construct(protected EmailService $emailService)
    {
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'mensaje' => ['required', 'string', 'max:2000'],
        ]);

        $this->emailService->enviarContacto($datos['nombre'], $datos['email'], $datos['mensaje']);

        return back()->with('status', 'Tu mensaje fue enviado correctamente. Te contactaremos pronto.');
    }
}
