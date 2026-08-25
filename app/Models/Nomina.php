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
        'mes',
        'anio',
        'salario_diario',
        'dias_laborales',
        'dias_asistidos',
        'descuentos_faltas',
        'descuentos_retardos',
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
            'salario_diario' => 'decimal:2',
            'descuentos_faltas' => 'decimal:2',
            'descuentos_retardos' => 'decimal:2',
            'fecha_pago' => 'date',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
