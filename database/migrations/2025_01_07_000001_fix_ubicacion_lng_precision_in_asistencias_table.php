<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * decimal(10,8) solo admite 2 dígitos enteros (hasta ±99.99999999),
     * pero la longitud puede llegar a ±180 (3 dígitos enteros, p. ej. México
     * ronda -100.xxxxxxx). Eso causaba "Numeric value out of range" en MySQL
     * al guardar el check-in; SQLite (usado en tests) no aplica esta
     * precisión, por eso no se detectó antes.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE asistencias MODIFY ubicacion_lng DECIMAL(11, 8) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE asistencias MODIFY ubicacion_lng DECIMAL(10, 8) NULL');
        }
    }
};
