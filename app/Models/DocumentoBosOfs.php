<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DocumentoBosOfs extends Model
{
    use HasFactory, Auditable, SoftDeletes;

    protected $table = 'documentos_bos_ofs';

    protected $fillable = [
        'nombre',
        'tipo',
        'mes',
        'nombre_archivo',
        'ruta_archivo',
        'subido_por',
    ];

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'subido_por');
    }
}
