<?php

namespace App\Jobs;

use App\Services\AuditService;
use App\Services\EmailService;
use App\Services\NominaService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Recalcula la nómina del mes en curso para todos los usuarios activos.
 * Se ejecuta cada noche vía Laravel Scheduler (ver routes/console.php).
 */
class CalcularNominasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        protected ?int $mes = null,
        protected ?int $anio = null,
    ) {
    }

    public function handle(NominaService $nominaService, AuditService $auditService, EmailService $emailService): void
    {
        $hoy = Carbon::now();
        $mes = $this->mes ?? $hoy->month;
        $anio = $this->anio ?? $hoy->year;

        $nominas = $nominaService->calcularParaTodos($mes, $anio);

        $nominas->each(function ($nomina) use ($emailService) {
            $emailService->enviarNominaLista($nomina->usuario, $nomina);
        });

        $auditService->registrar(
            accion: 'cambio_nomina',
            tablaAfectada: 'nominas',
            detalles: [
                'origen' => 'CalcularNominasJob',
                'mes' => $mes,
                'anio' => $anio,
                'usuarios_procesados' => $nominas->count(),
            ],
        );

        Log::info("CalcularNominasJob: {$nominas->count()} nóminas recalculadas para {$mes}/{$anio}.");
    }
}
