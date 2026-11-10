<?php

namespace App\Http\Controllers;

use App\Models\Nomina;
use App\Services\NominaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NominaController extends Controller
{
    public function __construct(protected NominaService $nominaService)
    {
    }

    public function miNominaView(): View
    {
        return view('nominas.mi-nomina');
    }

    public function indexView(): View
    {
        return view('nominas.index');
    }

    /**
     * fecha (opcional) es cualquier día dentro de la quincena que se quiere
     * consultar; por defecto, hoy — así "Mi Nómina" siempre puede mostrar el
     * periodo en curso sin que el usuario tenga que calcular fechas.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function resolverPeriodo(Request $request): array
    {
        $fecha = Carbon::parse($request->input('fecha', Carbon::now()->toDateString()));

        return $this->nominaService->periodoQuincenalPara($fecha);
    }

    public function miNomina(Request $request): JsonResponse
    {
        [$inicio, $fin] = $this->resolverPeriodo($request);

        $nomina = Nomina::where('usuario_id', $request->user()->id)
            ->whereDate('periodo_inicio', $inicio->toDateString())
            ->first();

        return response()->json([
            'nomina' => $nomina,
            'periodo_inicio' => $inicio->toDateString(),
            'periodo_fin' => $fin->toDateString(),
        ]);
    }

    public function historico(Request $request): JsonResponse
    {
        $nominas = Nomina::where('usuario_id', $request->user()->id)
            ->orderByDesc('periodo_inicio')
            ->limit(12)
            ->get();

        return response()->json(['nominas' => $nominas]);
    }

    public function index(Request $request): JsonResponse
    {
        [$inicio, $fin] = $this->resolverPeriodo($request);

        $nominas = Nomina::with('usuario:id,nombre,departamento')
            ->whereDate('periodo_inicio', $inicio->toDateString())
            ->get();

        return response()->json([
            'nominas' => $nominas,
            'periodo_inicio' => $inicio->toDateString(),
            'periodo_fin' => $fin->toDateString(),
        ]);
    }

    public function calcular(Request $request): JsonResponse
    {
        [$inicio, $fin] = $this->resolverPeriodo($request);

        $nominas = $this->nominaService->calcularParaTodos($inicio, $fin);

        return response()->json([
            'message' => "Nómina del periodo {$inicio->toDateString()} a {$fin->toDateString()} calculada correctamente.",
            'total_procesadas' => $nominas->count(),
        ]);
    }

    /**
     * RH marca manualmente que ya se pagó una nómina. No se hace automático
     * porque el pago real ocurre fuera del sistema (transferencia/efectivo).
     */
    public function marcarPagada(int $id): JsonResponse
    {
        $nomina = Nomina::findOrFail($id);
        $nomina->update(['estado' => 'pagada', 'fecha_pago' => Carbon::now()]);

        return response()->json(['message' => 'Nómina marcada como pagada.', 'nomina' => $nomina]);
    }

    /**
     * El técnico descarga el comprobante en PDF de su propia nómina.
     */
    public function comprobantePdf(Request $request): Response
    {
        [$inicio] = $this->resolverPeriodo($request);

        $nomina = Nomina::with('usuario')
            ->where('usuario_id', $request->user()->id)
            ->whereDate('periodo_inicio', $inicio->toDateString())
            ->firstOrFail();

        $pdf = Pdf::loadView('nominas.comprobante-pdf', ['nomina' => $nomina]);

        return $pdf->download("comprobante-nomina-{$inicio->toDateString()}.pdf");
    }

    public function reporteContador(Request $request): JsonResponse
    {
        [$inicio, $fin] = $this->resolverPeriodo($request);

        $nominas = Nomina::with('usuario:id,nombre,departamento')
            ->whereDate('periodo_inicio', $inicio->toDateString())
            ->get()
            ->map(fn (Nomina $n) => [
                'usuario' => $n->usuario->nombre,
                'departamento' => $n->usuario->departamento,
                'dias_asistidos' => $n->dias_asistidos,
                'descuentos_faltas' => $n->descuentos_faltas,
                'total_pago' => $n->total_pago,
                'estado' => $n->estado,
            ]);

        return response()->json(['periodo_inicio' => $inicio->toDateString(), 'periodo_fin' => $fin->toDateString(), 'nominas' => $nominas]);
    }

    /**
     * Exportación en CSV para el contador (Excel la abre nativamente).
     */
    public function reporteContadorCsv(Request $request): StreamedResponse
    {
        [$inicio, $fin] = $this->resolverPeriodo($request);

        $nominas = Nomina::with('usuario:id,nombre,departamento')
            ->whereDate('periodo_inicio', $inicio->toDateString())
            ->get();

        return response()->streamDownload(function () use ($nominas) {
            $salida = fopen('php://output', 'w');
            fputcsv($salida, ['Empleado', 'Departamento', 'Días asistidos', 'Descuento faltas', 'Total a pagar', 'Estado']);

            foreach ($nominas as $nomina) {
                fputcsv($salida, [
                    $nomina->usuario->nombre,
                    $nomina->usuario->departamento,
                    $nomina->dias_asistidos,
                    $nomina->descuentos_faltas,
                    $nomina->total_pago,
                    $nomina->estado,
                ]);
            }

            fclose($salida);
        }, "reporte-contador-{$inicio->toDateString()}.csv", ['Content-Type' => 'text/csv']);
    }
}
