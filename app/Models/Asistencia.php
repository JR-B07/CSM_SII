<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    use HasFactory, Auditable;

    protected $table = 'asistencias';

    protected $fillable = [
        'usuario_id',
        'fecha',
        'hora_entrada',
        'hora_salida',
        'ubicacion_lat',
        'ubicacion_lng',
        'foto_verificacion',
        'proyecto',
        'compania',
        'puesto',
        'estado',
        'justificante',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'ubicacion_lat' => 'decimal:8',
            'ubicacion_lng' => 'decimal:8',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function esFalta(): bool
    {
        return $this->estado === 'ausente';
    }

    public function esJustificada(): bool
    {
        return $this->estado === 'justificado';
    }
}
