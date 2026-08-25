<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Documento extends Model
{
    use HasFactory, Auditable, SoftDeletes;

    protected $table = 'documentos';

    protected $fillable = [
        'nombre',
        'ruta_archivo',
        'tipo',
        'tamano',
        'cliente',
        'proyecto',
        'usuario_id',
        'version',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function permisos(): HasMany
    {
        return $this->hasMany(PermisoDocumento::class);
    }
}
