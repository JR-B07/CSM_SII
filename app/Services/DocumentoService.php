<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Gestiona el almacenamiento privado de documentos (storage/app/private,
 * nunca en public/) y su versionamiento automático.
 */
class DocumentoService
{
    public function subir(UploadedFile $archivo, Usuario $usuario, ?string $cliente, ?string $proyecto): Documento
    {
        $carpeta = 'documentos/'.($cliente ?: 'sin-cliente').'/'.($proyecto ?: 'sin-proyecto');
        $nombreArchivo = time().'_'.$archivo->getClientOriginalName();

        $ruta = $archivo->storeAs($carpeta, $nombreArchivo, 'local');

        $version = Documento::where('nombre', $archivo->getClientOriginalName())
            ->where('cliente', $cliente)
            ->where('proyecto', $proyecto)
            ->max('version');

        return Documento::create([
            'nombre' => $archivo->getClientOriginalName(),
            'ruta_archivo' => $ruta,
            'tipo' => strtoupper($archivo->getClientOriginalExtension()),
            'tamano' => $archivo->getSize(),
            'cliente' => $cliente,
            'proyecto' => $proyecto,
            'usuario_id' => $usuario->id,
            'version' => ($version ?? 0) + 1,
        ]);
    }

    public function eliminar(Documento $documento): void
    {
        Storage::disk('local')->delete($documento->ruta_archivo);
        $documento->delete();
    }
}
