<?php

namespace App\Traits;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Registra automáticamente en la tabla `auditoria` cada creación,
 * actualización o eliminación del modelo que use este trait.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($model) => $model->registrarAuditoria('creado'));
        static::updated(fn ($model) => $model->registrarAuditoria('actualizado'));
        static::deleted(fn ($model) => $model->registrarAuditoria('eliminado'));
    }

    public function registrarAuditoria(string $accion): void
    {
        Auditoria::create([
            'usuario_id' => Auth::id(),
            'accion' => $accion,
            'tabla_afectada' => $this->getTable(),
            'registro_id' => $this->getKey(),
            'detalles' => $accion === 'eliminado' ? null : $this->getChanges(),
            'ip_address' => Request::ip(),
        ]);
    }
}
