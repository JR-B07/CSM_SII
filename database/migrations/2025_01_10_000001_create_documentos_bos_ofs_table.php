<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reportes mensuales BOS/OFS. Distinto de `documentos` (archivos de
     * proyecto/cliente en general) y de `formatos` (checklists/triggers):
     * aquí cada archivo se clasifica por tipo (BOS u OFS) y por el mes al
     * que corresponde, no por cliente/proyecto.
     */
    public function up(): void
    {
        Schema::create('documentos_bos_ofs', function (Blueprint $table) {
            $table->id()->comment('Identificador único del documento');
            $table->string('nombre', 255)->comment('Nombre descriptivo del documento');
            $table->enum('tipo', ['BOS', 'OFS'])->comment('Categoría del reporte mensual');
            $table->string('mes', 7)->comment('Mes al que pertenece, formato YYYY-MM');
            $table->string('nombre_archivo', 255)->comment('Nombre original del archivo subido');
            $table->string('ruta_archivo', 500)->comment('Ruta privada en storage/app (nunca en public/)');
            $table->foreignId('subido_por')->constrained('usuarios')->cascadeOnDelete()
                ->comment('Empleado que subió el documento');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo', 'mes']);
            $table->comment('Reportes mensuales BOS/OFS');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_bos_ofs');
    }
};
