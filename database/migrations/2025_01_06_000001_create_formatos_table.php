<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Formatos estándar de la empresa (checklists, formatos de trabajo,
     * triggers) que cualquier empleado puede subir, consultar e imprimir.
     * Distinto de `documentos` (archivos propios de un proyecto/cliente) y
     * de `documentos_legales` (papelería personal de cada empleado).
     */
    public function up(): void
    {
        Schema::create('formatos', function (Blueprint $table) {
            $table->id()->comment('Identificador único del formato');
            $table->string('nombre', 255)->comment('Nombre descriptivo del formato');
            $table->enum('tipo', ['checklist', 'formato_trabajo', 'trigger'])
                ->comment('Categoría del formato para filtrarlo en la librería');
            $table->string('nombre_archivo', 255)->comment('Nombre original del archivo subido');
            $table->string('ruta_archivo', 500)->comment('Ruta privada en storage/app (nunca en public/)');
            $table->foreignId('subido_por')->constrained('usuarios')->cascadeOnDelete()
                ->comment('Empleado que subió el formato');
            $table->timestamps();
            $table->softDeletes();

            $table->comment('Librería de formatos imprimibles (checklists, formatos de trabajo, triggers)');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formatos');
    }
};
