<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentoLegalRequest;
use App\Models\DocumentoLegal;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoLegalController extends Controller
{
    public function gestionView(int $usuarioId): View
    {
        $usuario = Usuario::findOrFail($usuarioId);

        return view('usuarios.documentos-legales', ['usuario' => $usuario]);
    }

    /**
     * RH/admin ven los documentos de cualquier empleado; un empleado normal
     * solo puede consultar los suyos.
     */
    public function index(Request $request, int $usuarioId): JsonResponse
    {
        $solicitante = $request->user();
        abort_unless($solicitante->esRH() || $solicitante->id === $usuarioId, 403);

        $documentos = DocumentoLegal::where('usuario_id', $usuarioId)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['documentos' => $documentos]);
    }

    public function store(StoreDocumentoLegalRequest $request, int $usuarioId): JsonResponse
    {
        $usuario = Usuario::findOrFail($usuarioId);
        $archivo = $request->file('archivo');

        $ruta = $archivo->storeAs(
            "documentos-legales/{$usuario->id}",
            time().'_'.$archivo->getClientOriginalName(),
            'local'
        );

        $documento = DocumentoLegal::create([
            'usuario_id' => $usuario->id,
            'tipo' => $request->validated('tipo'),
            'numero_documento' => $request->validated('numero_documento'),
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'ruta_archivo' => $ruta,
            'fecha_vencimiento' => $request->validated('fecha_vencimiento'),
            'subido_por' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Documento subido correctamente.', 'documento' => $documento], 201);
    }

    public function download(Request $request, int $id): StreamedResponse
    {
        $documento = DocumentoLegal::findOrFail($id);
        $solicitante = $request->user();

        abort_unless($solicitante->esRH() || $solicitante->id === $documento->usuario_id, 403);

        return Storage::disk('local')->download($documento->ruta_archivo, $documento->nombre_archivo);
    }

    public function destroy(int $id): JsonResponse
    {
        $documento = DocumentoLegal::findOrFail($id);
        $documento->delete();

        return response()->json(['message' => 'Documento eliminado correctamente.']);
    }
}
