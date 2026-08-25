<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documentos', function (Blueprint $table) {
            $table->id()->comment('Identificador único del documento');
            $table->string('nombre', 255)->comment('Nombre original del archivo');
            $table->string('ruta_archivo', 500)->comment('Ruta privada en storage/app (nunca en public/)');
            $table->string('tipo', 20)->comment('Extensión/tipo: PDF, DOCX, XLSX, JPG, PNG…');
            $table->unsignedBigInteger('tamano')->comment('Tamaño del archivo en bytes (máx. 50MB por spec)');
            $table->string('cliente', 100)->nullable()->comment('Cliente al que pertenece el documento');
            $table->string('proyecto', 100)->nullable()->comment('Proyecto al que pertenece el documento');
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete()
                ->comment('Usuario que subió el documento');
            $table->unsignedInteger('version')->default(1)->comment('Número de versión, incrementa en cada reemplazo');
            $table->timestamps();
            $table->softDeletes()->comment('Retención legal: no se borra físicamente al eliminar');

            $table->comment('Documentos de proyectos organizados por cliente/proyecto, con control de versiones');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos');
    }
};
