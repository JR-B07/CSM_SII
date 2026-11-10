<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\Encryptable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nomina extends Model
{
    use HasFactory, Auditable, Encryptable, SoftDeletes;

    protected $table = 'nominas';

    protected $fillable = [
        'usuario_id',
        'periodo_inicio',
        'periodo_fin',
        'tipo_periodo',
        'salario_diario',
        'dias_laborales',
        'dias_asistidos',
        'descuentos_faltas',
        'total_pago',
        'estado',
        'fecha_pago',
    ];

    /**
     * Atributos encriptados automáticamente (AES-256-CBC) por el Trait Encryptable.
     */
    protected array $encryptable = [
        'total_pago',
    ];

    protected function casts(): array
    {
        return [
            // Formato explícito para que el JSON serialice 'Y-m-d' (fecha
            // pura); sin esto Eloquent serializa como datetime ISO completo,
            // que MiNomina.vue no podía parsear ("Invalid Date" en Histórico).
            'periodo_inicio' => 'date:Y-m-d',
            'periodo_fin' => 'date:Y-m-d',
            'salario_diario' => 'decimal:2',
            'descuentos_faltas' => 'decimal:2',
            'fecha_pago' => 'date',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
