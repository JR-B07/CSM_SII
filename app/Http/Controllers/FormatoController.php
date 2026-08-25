<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormatoRequest;
use App\Models\Formato;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormatoController extends Controller
{
    public function indexView(): View
    {
        return view('formatos.index');
    }

    public function index(Request $request): JsonResponse
    {
        $formatos = Formato::with('subidoPor:id,nombre')
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->input('tipo')))
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['formatos' => $formatos]);
    }

    public function store(StoreFormatoRequest $request): JsonResponse
    {
        $archivo = $request->file('archivo');

        $ruta = $archivo->storeAs('formatos', time().'_'.$archivo->getClientOriginalName(), 'local');

        $formato = Formato::create([
            'nombre' => $request->validated('nombre'),
            'tipo' => $request->validated('tipo'),
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'ruta_archivo' => $ruta,
            'subido_por' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Formato subido correctamente.', 'formato' => $formato], 201);
    }

    /**
     * Sirve el archivo en línea (no como descarga forzada) para que el
     * navegador lo muestre y el usuario lo pueda imprimir directamente.
     */
    public function ver(int $id): StreamedResponse
    {
        $formato = Formato::findOrFail($id);

        return Storage::disk('local')->response($formato->ruta_archivo, $formato->nombre_archivo);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $formato = Formato::findOrFail($id);

        abort_unless(
            $request->user()->esRH() || $formato->subido_por === $request->user()->id,
            403,
            'No tienes permiso para eliminar este formato.'
        );

        Storage::disk('local')->delete($formato->ruta_archivo);
        $formato->delete();

        return response()->json(['message' => 'Formato eliminado correctamente.']);
    }
}
