<?php

namespace App\Models;

use App\Traits\Auditable;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    /** @use HasFactory<UsuarioFactory> */
    use HasFactory, Notifiable, Auditable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombre',
        'email',
        'rol',
        'departamento',
        'salario_diario',
        'password',
        'activo',
        'estado_cuenta',
        'aprobado_por',
        'aprobado_en',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'salario_diario' => 'decimal:2',
            'aprobado_en' => 'datetime',
        ];
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }

    public function nominas(): HasMany
    {
        return $this->hasMany(Nomina::class);
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(Auditoria::class);
    }

    public function documentosLegales(): HasMany
    {
        return $this->hasMany(DocumentoLegal::class);
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'aprobado_por');
    }

    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->rol, $roles, true);
    }

    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function esRH(): bool
    {
        return $this->tieneRol('RH', 'admin');
    }

    public function estaPendiente(): bool
    {
        return $this->estado_cuenta === 'pendiente';
    }

    public function estaAprobado(): bool
    {
        return $this->estado_cuenta === 'aprobado';
    }

    public function estaRechazado(): bool
    {
        return $this->estado_cuenta === 'rechazado';
    }
}
