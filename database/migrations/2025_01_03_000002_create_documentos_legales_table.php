<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos_legales', function (Blueprint $table) {
            $table->id()->comment('Identificador único del documento legal');
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete()
                ->comment('Empleado dueño del documento (IMSS, INE, RFC, etc.)');
            $table->enum('tipo', ['IMSS', 'INE', 'RFC', 'CURP', 'comprobante_domicilio', 'contrato', 'acta_nacimiento', 'otro'])
                ->comment('Tipo de documento legal');
            $table->string('numero_documento', 100)->nullable()->comment('NSS, folio de INE, clave de RFC, etc.');
            $table->string('nombre_archivo', 255)->comment('Nombre original del archivo subido');
            $table->string('ruta_archivo', 500)->comment('Ruta privada en storage/app (nunca en public/)');
            $table->date('fecha_vencimiento')->nullable()->comment('Para documentos con vigencia (IMSS, contrato)');
            $table->foreignId('subido_por')->constrained('usuarios')->restrictOnDelete()
                ->comment('RH/admin que subió el documento');
            $table->timestamps();
            $table->softDeletes()->comment('Retención legal: no se borra físicamente');

            $table->comment('Documentos legales/administrativos de cada empleado, gestionados por RH/admin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_legales');
    }
};
