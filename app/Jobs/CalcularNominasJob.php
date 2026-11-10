<?php

namespace App\Jobs;

use App\Services\AuditService;
use App\Services\EmailService;
use App\Services\NominaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Recalcula la nómina de la quincena en curso para todos los usuarios
 * activos. Se ejecuta cada noche vía Laravel Scheduler (ver
 * routes/console.php) como respaldo — AsistenciaController ya recalcula en
 * vivo con cada check-in/check-out, esto solo corrige inconsistencias.
 */
class CalcularNominasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        protected ?Carbon $periodoInicio = null,
        protected ?Carbon $periodoFin = null,
    ) {
    }

    public function handle(NominaService $nominaService, AuditService $auditService, EmailService $emailService): void
    {
        [$inicio, $fin] = $this->periodoInicio && $this->periodoFin
            ? [$this->periodoInicio, $this->periodoFin]
            : $nominaService->periodoQuincenalPara(Carbon::now());

        $nominas = $nominaService->calcularParaTodos($inicio, $fin);

        $nominas->each(function ($nomina) use ($emailService) {
            $emailService->enviarNominaLista($nomina->usuario, $nomina);
        });

        $auditService->registrar(
            accion: 'cambio_nomina',
            tablaAfectada: 'nominas',
            detalles: [
                'origen' => 'CalcularNominasJob',
                'periodo_inicio' => $inicio->toDateString(),
                'periodo_fin' => $fin->toDateString(),
                'usuarios_procesados' => $nominas->count(),
            ],
        );

        Log::info("CalcularNominasJob: {$nominas->count()} nóminas recalculadas para {$inicio->toDateString()} - {$fin->toDateString()}.");
    }
}
