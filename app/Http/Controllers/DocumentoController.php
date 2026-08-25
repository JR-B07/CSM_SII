<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentoRequest;
use App\Models\Documento;
use App\Models\PermisoDocumento;
use App\Services\AuditService;
use App\Services\DocumentoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoController extends Controller
{
    public function __construct(
        protected DocumentoService $documentoService,
        protected AuditService $auditService,
    ) {
    }

    public function indexView(): View
    {
        return view('documentos.index');
    }

    public function upload(StoreDocumentoRequest $request): JsonResponse
    {
        $documento = $this->documentoService->subir(
            archivo: $request->file('archivo'),
            usuario: $request->user(),
            cliente: $request->input('cliente'),
            proyecto: $request->input('proyecto'),
        );

        return response()->json(['message' => 'Documento subido correctamente.', 'documento' => $documento], 201);
    }

    public function lista(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $query = Documento::with('usuario:id,nombre')->orderByDesc('created_at');

        if (! $usuario->esRH()) {
            $query->where(function ($q) use ($usuario) {
                $q->where('usuario_id', $usuario->id)
                    ->orWhereHas('permisos', fn ($p) => $p->where('usuario_id', $usuario->id));
            });
        }

        return response()->json($query->paginate(20));
    }

    public function download(Request $request, int $id): StreamedResponse
    {
        $documento = Documento::findOrFail($id);
        $usuario = $request->user();

        $tienePermiso = $usuario->esRH()
            || $documento->usuario_id === $usuario->id
            || $documento->permisos()
                ->where('usuario_id', $usuario->id)
                ->whereIn('nivel_permiso', ['lectura_descarga', 'edicion', 'admin'])
                ->exists();

        abort_unless($tienePermiso, 403, 'No tienes permiso para descargar este documento.');

        $this->auditService->descargaDocumento($usuario->id, $documento->id);

        return Storage::disk('local')->download($documento->ruta_archivo, $documento->nombre);
    }

    public function updatePermisos(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
            'nivel_permiso' => ['required', 'in:lectura,lectura_descarga,edicion,admin'],
        ]);

        $documento = Documento::findOrFail($id);

        $permiso = PermisoDocumento::updateOrCreate(
            ['documento_id' => $documento->id, 'usuario_id' => $request->input('usuario_id')],
            ['nivel_permiso' => $request->input('nivel_permiso'), 'created_at' => now()],
        );

        return response()->json(['message' => 'Permisos actualizados correctamente.', 'permiso' => $permiso]);
    }
}
