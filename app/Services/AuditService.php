<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Http\Request;

/**
 * Registro manual de acciones que no derivan de eventos de modelo Eloquent
 * (login, logout, descargas, cambios de nómina disparados desde un Job, etc).
 * Los cambios sobre modelos que usan App\Traits\Auditable ya se auditan solos.
 */
class AuditService
{
    public function registrar(string $accion, ?int $usuarioId = null, ?string $tablaAfectada = null, ?int $registroId = null, ?array $detalles = null): Auditoria
    {
        return Auditoria::create([
            'usuario_id' => $usuarioId ?? auth()->id(),
            'accion' => $accion,
            'tabla_afectada' => $tablaAfectada,
            'registro_id' => $registroId,
            'detalles' => $detalles,
            'ip_address' => request()?->ip(),
        ]);
    }

    public function login(int $usuarioId): Auditoria
    {
        return $this->registrar('login', $usuarioId, 'usuarios', $usuarioId);
    }

    public function logout(int $usuarioId): Auditoria
    {
        return $this->registrar('logout', $usuarioId, 'usuarios', $usuarioId);
    }

    public function descargaDocumento(int $usuarioId, int $documentoId): Auditoria
    {
        return $this->registrar('download', $usuarioId, 'documentos', $documentoId);
    }
}
