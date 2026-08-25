<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permisos_documentos', function (Blueprint $table) {
            $table->id()->comment('Identificador único del permiso');
            $table->foreignId('documento_id')->constrained('documentos')->cascadeOnDelete()
                ->comment('Documento al que aplica el permiso');
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete()
                ->comment('Usuario al que se le concede el permiso');
            $table->enum('nivel_permiso', ['lectura', 'lectura_descarga', 'edicion', 'admin'])
                ->default('lectura')
                ->comment('Nivel de acceso granular sobre el documento');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->unique(['documento_id', 'usuario_id'], 'permisos_documento_usuario_unique');
            $table->comment('Permisos granulares por usuario sobre cada documento (lectura, descarga, edición, admin)');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos_documentos');
    }
};
