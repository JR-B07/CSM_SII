<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class PerfilController extends Controller
{
    public function mostrar(Request $request): View
    {
        $usuario = $request->user();
        $documentos = $usuario->documentosLegales()->orderByDesc('created_at')->get();

        return view('perfil.mostrar', ['usuario' => $usuario, 'documentos' => $documentos]);
    }
}
