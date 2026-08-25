<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAsistenciaRequest;
use App\Models\Asistencia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AsistenciaController extends Controller
{
    public function checkin(StoreAsistenciaRequest $request): JsonResponse
    {
        $usuario = $request->user();
        $ahora = Carbon::now();
        $horaLimite = config('custom.asistencia.hora_limite', '09:15:00');

        // La hora que queda registrada es la del dispositivo del empleado
        // (enviada por Checkin.vue), no la del servidor. Si por algún motivo
        // no llega (JS deshabilitado, reloj no disponible), se usa la del
        // servidor como respaldo para no perder el registro.
        $horaEntrada = $request->input('hora_dispositivo') ?? $ahora->toTimeString();

        $asistencia = Asistencia::where('usuario_id', $usuario->id)
            ->whereDate('fecha', $ahora->toDateString())
            ->first() ?? new Asistencia([
                'usuario_id' => $usuario->id,
                'fecha' => $ahora->toDateString(),
            ]);

        if ($asistencia->exists && $asistencia->hora_entrada) {
            return response()->json(['message' => 'Ya registraste tu entrada hoy.'], 422);
        }

        $asistencia->fill([
            'proyecto' => $request->input('proyecto'),
            'compania' => $request->input('compania'),
            'puesto' => $request->input('puesto'),
            'hora_entrada' => $horaEntrada,
            'ubicacion_lat' => $request->input('ubicacion_lat'),
            'ubicacion_lng' => $request->input('ubicacion_lng'),
            'estado' => $horaEntrada > $horaLimite ? 'tarde' : 'presente',
        ]);

        if ($foto = $request->input('foto')) {
            $asistencia->foto_verificacion = $this->guardarFoto($usuario->id, $ahora, $foto);
        }

        $asistencia->save();

        return response()->json([
            'message' => 'Check-in registrado correctamente.',
            'horaEntrada' => $asistencia->hora_entrada,
            'estado' => $asistencia->estado,
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $hoy = Carbon::today()->toDateString();

        $request->validate(['hora_dispositivo' => ['nullable', 'date_format:H:i:s']]);

        $asistencia = Asistencia::where('usuario_id', $usuario->id)
            ->whereDate('fecha', $hoy)
            ->first();

        if (! $asistencia || ! $asistencia->hora_entrada) {
            return response()->json(['message' => 'No hay un check-in registrado hoy.'], 422);
        }

        // Igual que en el check-in: se guarda la hora del dispositivo, con la
        // hora del servidor como respaldo si no llega.
        $horaSalida = $request->input('hora_dispositivo') ?? Carbon::now()->toTimeString();

        $asistencia->update(['hora_salida' => $horaSalida]);

        return response()->json([
            'message' => 'Check-out registrado correctamente.',
            'horaSalida' => $asistencia->hora_salida,
        ]);
    }

    public function miHistorial(Request $request): JsonResponse
    {
        $asistencias = Asistencia::where('usuario_id', $request->user()->id)
            ->orderByDesc('fecha')
            ->paginate(20);

        return response()->json($asistencias);
    }

    /**
     * Última asistencia (entrada/salida) del usuario autenticado, usada por
     * Checkin.vue para mostrar "Última entrada / Última salida".
     */
    public function ultima(Request $request): JsonResponse
    {
        $asistencia = Asistencia::where('usuario_id', $request->user()->id)
            ->orderByDesc('fecha')
            ->first();

        return response()->json(['asistencia' => $asistencia]);
    }

    /**
     * Proyectos usados recientemente, para poblar el selector en Checkin.vue.
     */
    public function proyectos(): JsonResponse
    {
        $proyectos = Asistencia::whereNotNull('proyecto')
            ->distinct()
            ->orderBy('proyecto')
            ->limit(50)
            ->pluck('proyecto');

        return response()->json(['proyectos' => $proyectos]);
    }

    public function indexView(): View
    {
        return view('asistencias.index');
    }

    public function index(Request $request): JsonResponse
    {
        $asistencias = Asistencia::with('usuario:id,nombre,departamento')
            ->when($request->filled('fecha'), fn ($q) => $q->whereDate('fecha', $request->input('fecha')))
            ->when($request->filled('usuario_id'), fn ($q) => $q->where('usuario_id', $request->input('usuario_id')))
            ->orderByDesc('fecha')
            ->paginate(30);

        return response()->json($asistencias);
    }

    public function justificar(Request $request, int $id): JsonResponse
    {
        $request->validate(['justificante' => ['required', 'string']]);

        $asistencia = Asistencia::findOrFail($id);
        $asistencia->update([
            'estado' => 'justificado',
            'justificante' => $request->input('justificante'),
        ]);

        return response()->json(['message' => 'Falta justificada correctamente.', 'asistencia' => $asistencia]);
    }

    /**
     * Disponible para cualquier usuario autenticado (así lo define el spec),
     * pero solo RH/admin reciben el resumen de toda la empresa: un técnico
     * que llame este endpoint solo ve el resumen de sus propias asistencias.
     */
    public function reportes(Request $request): JsonResponse
    {
        $desde = $request->input('desde', Carbon::now()->startOfMonth()->toDateString());
        $hasta = $request->input('hasta', Carbon::now()->toDateString());
        $usuario = $request->user();

        $query = Asistencia::whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta);

        if (! $usuario->esRH()) {
            $query->where('usuario_id', $usuario->id);
        }

        $resumen = $query
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        return response()->json(['desde' => $desde, 'hasta' => $hasta, 'resumen' => $resumen]);
    }

    public function checkinForm(): View
    {
        return view('asistencias.checkin');
    }

    protected function guardarFoto(int $usuarioId, Carbon $fecha, string $base64): string
    {
        $datos = preg_replace('#^data:image/\w+;base64,#', '', $base64);
        $contenido = base64_decode($datos, true) ?: '';

        $ruta = "asistencias/{$usuarioId}/{$fecha->format('Y-m-d_His')}.jpg";
        Storage::disk('local')->put($ruta, $contenido);

        return $ruta;
    }
}
