<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Formato extends Model
{
    use HasFactory, Auditable, SoftDeletes;

    protected $table = 'formatos';

    protected $fillable = [
        'nombre',
        'tipo',
        'nombre_archivo',
        'ruta_archivo',
        'subido_por',
    ];

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'subido_por');
    }
}
