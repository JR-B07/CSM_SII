<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->string('compania', 150)->nullable()->after('proyecto')
                ->comment('Compañía/cliente con la que asiste el empleado ese día');
            $table->string('puesto', 100)->nullable()->after('compania')
                ->comment('Puesto que desempeña el empleado ese día');
        });
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropColumn(['compania', 'puesto']);
        });
    }
};
