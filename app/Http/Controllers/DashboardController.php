<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Documento;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard.index');
    }

    public function getKpis(): JsonResponse
    {
        $hoy = Carbon::today()->toDateString();

        return response()->json([
            'asistencias_hoy' => Asistencia::whereDate('fecha', $hoy)->whereIn('estado', ['presente', 'tarde'])->count(),
            'ausencias_hoy' => Asistencia::whereDate('fecha', $hoy)->where('estado', 'ausente')->count(),
            'documentos_totales' => Documento::count(),
            'proyectos_activos' => Asistencia::whereDate('fecha', $hoy)->whereNotNull('proyecto')->distinct('proyecto')->count('proyecto'),
        ]);
    }

    public function getGraficos(): JsonResponse
    {
        $desde = Carbon::now()->subDays(6)->startOfDay();

        $asistenciaPorDia = Asistencia::selectRaw('fecha, count(*) as total')
            ->whereDate('fecha', '>=', $desde->toDateString())
            ->whereIn('estado', ['presente', 'tarde'])
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        $asistenciaPorDepartamento = Usuario::query()
            ->join('asistencias', 'asistencias.usuario_id', '=', 'usuarios.id')
            ->selectRaw('usuarios.departamento, count(*) as total')
            ->whereDate('asistencias.fecha', Carbon::today()->toDateString())
            ->groupBy('usuarios.departamento')
            ->get();

        return response()->json([
            'asistencia_por_dia' => $asistenciaPorDia,
            'asistencia_por_departamento' => $asistenciaPorDepartamento,
        ]);
    }
}
