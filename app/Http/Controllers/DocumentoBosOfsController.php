<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentoBosOfsRequest;
use App\Models\DocumentoBosOfs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoBosOfsController extends Controller
{
    public function indexView(): View
    {
        return view('documentos-bos-ofs.index');
    }

    public function index(Request $request): JsonResponse
    {
        $documentos = DocumentoBosOfs::with('subidoPor:id,nombre')
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->input('tipo')))
            ->orderByDesc('mes')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['documentos' => $documentos]);
    }

    public function store(StoreDocumentoBosOfsRequest $request): JsonResponse
    {
        $archivo = $request->file('archivo');

        $ruta = $archivo->storeAs('documentos-bos-ofs', time().'_'.$archivo->getClientOriginalName(), 'local');

        $documento = DocumentoBosOfs::create([
            'nombre' => $request->validated('nombre'),
            'tipo' => $request->validated('tipo'),
            'mes' => $request->validated('mes'),
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'ruta_archivo' => $ruta,
            'subido_por' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Documento subido correctamente.', 'documento' => $documento], 201);
    }

    /**
     * Sirve el archivo en línea (no como descarga forzada) para que el
     * navegador lo muestre y el usuario lo pueda imprimir directamente.
     */
    public function ver(int $id): StreamedResponse
    {
        $documento = DocumentoBosOfs::findOrFail($id);

        return Storage::disk('local')->response($documento->ruta_archivo, $documento->nombre_archivo);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $documento = DocumentoBosOfs::findOrFail($id);

        abort_unless(
            $request->user()->esRH() || $documento->subido_por === $request->user()->id,
            403,
            'No tienes permiso para eliminar este documento.'
        );

        Storage::disk('local')->delete($documento->ruta_archivo);
        $documento->delete();

        return response()->json(['message' => 'Documento eliminado correctamente.']);
    }
}
