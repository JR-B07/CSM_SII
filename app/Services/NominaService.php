<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Nomina;
use App\Models\Usuario;
use Illuminate\Support\Collection;

/**
 * Calcula la nómina mensual de un técnico a partir de sus asistencias.
 *
 * Fórmula: (Días asistidos × Salario diario) - Descuentos = Total pago
 *   - Faltas (estado=ausente):      -100% del día
 *   - Retardos (estado=tarde):      -N% del día (config('custom.nomina.porcentaje_retardo'))
 *   - Permisos justificados:        0% de descuento (se pagan como día asistido)
 */
class NominaService
{
    public function __construct(
        protected ?float $porcentajeRetardo = null,
    ) {
        $this->porcentajeRetardo ??= (float) config('custom.nomina.porcentaje_retardo', 0.5);
    }

    public function calcularNomina(Usuario $usuario, int $mes, int $anio): Nomina
    {
        $asistencias = Asistencia::query()
            ->where('usuario_id', $usuario->id)
            ->whereYear('fecha', $anio)
            ->whereMonth('fecha', $mes)
            ->get();

        $salarioDiario = (float) $usuario->salario_diario;

        $diasAsistidos = $asistencias->whereIn('estado', ['presente', 'tarde', 'justificado'])->count();
        $faltas = $asistencias->where('estado', 'ausente')->count();
        $retardos = $asistencias->where('estado', 'tarde')->count();
        $diasLaborales = $asistencias->count();

        $descuentoFaltas = round($faltas * $salarioDiario, 2);
        $descuentoRetardos = round($retardos * $salarioDiario * $this->porcentajeRetardo, 2);

        $totalPago = max(round(($diasAsistidos * $salarioDiario) - $descuentoFaltas - $descuentoRetardos, 2), 0);

        return Nomina::updateOrCreate(
            ['usuario_id' => $usuario->id, 'mes' => $mes, 'anio' => $anio],
            [
                'salario_diario' => $salarioDiario,
                'dias_laborales' => $diasLaborales,
                'dias_asistidos' => $diasAsistidos,
                'descuentos_faltas' => $descuentoFaltas,
                'descuentos_retardos' => $descuentoRetardos,
                'total_pago' => (string) $totalPago,
                'estado' => 'calculada',
            ]
        );
    }

    /**
     * Calcula la nómina de todos los usuarios activos para el periodo indicado.
     *
     * @return Collection<int, Nomina>
     */
    public function calcularParaTodos(int $mes, int $anio): Collection
    {
        return Usuario::where('activo', true)
            ->get()
            ->map(fn (Usuario $usuario) => $this->calcularNomina($usuario, $mes, $anio));
    }
}
