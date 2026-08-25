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

    public function miNomina(Request $request): JsonResponse
    {
        $mes = (int) $request->input('mes', Carbon::now()->month);
        $anio = (int) $request->input('anio', Carbon::now()->year);

        $nomina = Nomina::where('usuario_id', $request->user()->id)
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->first();

        return response()->json(['nomina' => $nomina]);
    }

    public function historico(Request $request): JsonResponse
    {
        $nominas = Nomina::where('usuario_id', $request->user()->id)
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->limit(12)
            ->get();

        return response()->json(['nominas' => $nominas]);
    }

    public function index(Request $request): JsonResponse
    {
        $mes = (int) $request->input('mes', Carbon::now()->month);
        $anio = (int) $request->input('anio', Carbon::now()->year);

        $nominas = Nomina::with('usuario:id,nombre,departamento')
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->get();

        return response()->json(['nominas' => $nominas]);
    }

    public function calcular(Request $request): JsonResponse
    {
        $mes = (int) $request->input('mes', Carbon::now()->month);
        $anio = (int) $request->input('anio', Carbon::now()->year);

        $nominas = $this->nominaService->calcularParaTodos($mes, $anio);

        return response()->json([
            'message' => "Nómina de {$mes}/{$anio} calculada correctamente.",
            'total_procesadas' => $nominas->count(),
        ]);
    }

    /**
     * El técnico descarga el comprobante en PDF de su propia nómina.
     */
    public function comprobantePdf(Request $request): Response
    {
        $mes = (int) $request->input('mes', Carbon::now()->month);
        $anio = (int) $request->input('anio', Carbon::now()->year);

        $nomina = Nomina::with('usuario')
            ->where('usuario_id', $request->user()->id)
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->firstOrFail();

        $pdf = Pdf::loadView('nominas.comprobante-pdf', ['nomina' => $nomina]);

        return $pdf->download("comprobante-nomina-{$anio}-{$mes}.pdf");
    }

    public function reporteContador(Request $request): JsonResponse
    {
        $mes = (int) $request->input('mes', Carbon::now()->month);
        $anio = (int) $request->input('anio', Carbon::now()->year);

        $nominas = Nomina::with('usuario:id,nombre,departamento')
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->get()
            ->map(fn (Nomina $n) => [
                'usuario' => $n->usuario->nombre,
                'departamento' => $n->usuario->departamento,
                'dias_asistidos' => $n->dias_asistidos,
                'descuentos_faltas' => $n->descuentos_faltas,
                'descuentos_retardos' => $n->descuentos_retardos,
                'total_pago' => $n->total_pago,
                'estado' => $n->estado,
            ]);

        return response()->json(['mes' => $mes, 'anio' => $anio, 'nominas' => $nominas]);
    }

    /**
     * Exportación en CSV para el contador (Excel la abre nativamente).
     */
    public function reporteContadorCsv(Request $request): StreamedResponse
    {
        $mes = (int) $request->input('mes', Carbon::now()->month);
        $anio = (int) $request->input('anio', Carbon::now()->year);

        $nominas = Nomina::with('usuario:id,nombre,departamento')
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->get();

        return response()->streamDownload(function () use ($nominas) {
            $salida = fopen('php://output', 'w');
            fputcsv($salida, ['Empleado', 'Departamento', 'Días asistidos', 'Descuento faltas', 'Descuento retardos', 'Total a pagar', 'Estado']);

            foreach ($nominas as $nomina) {
                fputcsv($salida, [
                    $nomina->usuario->nombre,
                    $nomina->usuario->departamento,
                    $nomina->dias_asistidos,
                    $nomina->descuentos_faltas,
                    $nomina->descuentos_retardos,
                    $nomina->total_pago,
                    $nomina->estado,
                ]);
            }

            fclose($salida);
        }, "reporte-contador-{$anio}-{$mes}.csv", ['Content-Type' => 'text/csv']);
    }
}
