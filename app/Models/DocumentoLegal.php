<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentoLegal extends Model
{
    use HasFactory, Auditable, SoftDeletes;

    protected $table = 'documentos_legales';

    protected $fillable = [
        'usuario_id',
        'tipo',
        'numero_documento',
        'nombre_archivo',
        'ruta_archivo',
        'fecha_vencimiento',
        'subido_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_vencimiento' => 'date',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'subido_por');
    }
}
