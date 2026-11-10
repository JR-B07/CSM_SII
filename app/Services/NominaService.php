<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Nomina;
use App\Models\Usuario;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Calcula la nómina de un empleado para un periodo de pago a partir de sus
 * asistencias.
 *
 * Fórmula: (Días asistidos × Salario diario) - Descuentos = Total pago
 *   - Faltas (estado=ausente):      -100% del día
 *   - Retardos (estado=tarde):      sin descuento, se pagan como día asistido
 *   - Permisos justificados:        sin descuento, se pagan como día asistido
 */
class NominaService
{
    public function calcularNomina(Usuario $usuario, Carbon $periodoInicio, Carbon $periodoFin): Nomina
    {
        $asistencias = Asistencia::query()
            ->where('usuario_id', $usuario->id)
            ->whereDate('fecha', '>=', $periodoInicio->toDateString())
            ->whereDate('fecha', '<=', $periodoFin->toDateString())
            ->get();

        $salarioDiario = (float) $usuario->salario_diario;

        $diasAsistidos = $asistencias->whereIn('estado', ['presente', 'tarde', 'justificado'])->count();
        $faltas = $asistencias->where('estado', 'ausente')->count();
        $diasLaborales = $asistencias->count();

        $descuentoFaltas = round($faltas * $salarioDiario, 2);

        $totalPago = max(round(($diasAsistidos * $salarioDiario) - $descuentoFaltas, 2), 0);

        $datos = [
            'periodo_fin' => $periodoFin->toDateString(),
            'tipo_periodo' => 'quincenal',
            'salario_diario' => $salarioDiario,
            'dias_laborales' => $diasLaborales,
            'dias_asistidos' => $diasAsistidos,
            'descuentos_faltas' => $descuentoFaltas,
            'total_pago' => (string) $totalPago,
        ];

        $nomina = $this->buscarNomina($usuario, $periodoInicio);

        if ($nomina) {
            return $this->actualizar($nomina, $datos);
        }

        try {
            return Nomina::create($datos + [
                'usuario_id' => $usuario->id,
                'periodo_inicio' => $periodoInicio->toDateString(),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // Puede chocar por dos motivos: (1) dos peticiones casi
            // simultáneas (p. ej. check-in seguido de check-out) que ambas
            // vieron que la fila no existía y ambas intentaron crearla, o
            // (2) ya existía una fila borrada-suavemente para este periodo
            // (el índice único no distingue soft-deletes). En ambos casos,
            // la buscamos (incluyendo borradas) y la actualizamos/restauramos
            // en vez de fallar.
            $nomina = $this->buscarNomina($usuario, $periodoInicio, conTrashed: true);

            if (! $nomina) {
                throw $e;
            }

            return $this->actualizar($nomina, $datos);
        }
    }

    /**
     * Eloquent guarda 'periodo_inicio' (cast 'date') como datetime completo
     * internamente, así que un where() exacto con solo la fecha nunca
     * encuentra la fila ya creada (mismo caso que 'fecha' en Asistencia).
     * Por eso se busca con whereDate() en vez de usar updateOrCreate().
     */
    private function buscarNomina(Usuario $usuario, Carbon $periodoInicio, bool $conTrashed = false): ?Nomina
    {
        $query = $conTrashed ? Nomina::withTrashed() : Nomina::query();

        return $query
            ->where('usuario_id', $usuario->id)
            ->whereDate('periodo_inicio', $periodoInicio->toDateString())
            ->first();
    }

    private function actualizar(Nomina $nomina, array $datos): Nomina
    {
        if ($nomina->trashed()) {
            $nomina->restore();
        }

        // 'estado' no se toca al actualizar: este método se recalcula en
        // vivo con cada check-in/check-out, y no debe pisar un 'pagada' que
        // RH ya haya marcado manualmente.
        $nomina->update($datos);

        return $nomina;
    }

    /**
     * Calcula la nómina de todos los usuarios activos para el periodo indicado.
     *
     * @return Collection<int, Nomina>
     */
    public function calcularParaTodos(Carbon $periodoInicio, Carbon $periodoFin): Collection
    {
        return Usuario::where('activo', true)
            ->get()
            ->map(fn (Usuario $usuario) => $this->calcularNomina($usuario, $periodoInicio, $periodoFin));
    }

    /**
     * Límites [inicio, fin] de la quincena (1-15 / 16-fin de mes) que
     * contiene la fecha dada.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function periodoQuincenalPara(Carbon $fecha): array
    {
        if ($fecha->day <= 15) {
            return [$fecha->copy()->startOfMonth(), $fecha->copy()->setDay(15)->startOfDay()];
        }

        return [$fecha->copy()->setDay(16)->startOfDay(), $fecha->copy()->endOfMonth()->startOfDay()];
    }
}
