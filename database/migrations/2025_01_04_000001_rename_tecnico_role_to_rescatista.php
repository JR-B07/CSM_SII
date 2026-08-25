<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La migración original de `usuarios` se editó después de haber sido
     * aplicada (renombrando el rol "tecnico" a "rescatista"), lo cual no
     * tiene efecto sobre bases de datos que ya la habían corrido. Esta
     * migración alinea el ENUM real con el código actual.
     */
    public function up(): void
    {
        // SQLite (usado en tests, vía RefreshDatabase) ya crea la tabla con el
        // ENUM correcto desde la migración original; este ALTER es solo para
        // bases de datos reales (MySQL) que ya existían antes del rename.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE usuarios MODIFY rol ENUM('rescatista', 'supervisor', 'RH', 'admin', 'contador') NOT NULL DEFAULT 'rescatista'");
        }

        DB::table('usuarios')->where('rol', 'tecnico')->update(['rol' => 'rescatista']);
    }

    public function down(): void
    {
        DB::table('usuarios')->where('rol', 'rescatista')->update(['rol' => 'tecnico']);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE usuarios MODIFY rol ENUM('tecnico', 'supervisor', 'RH', 'admin', 'contador') NOT NULL DEFAULT 'tecnico'");
        }
    }
};
