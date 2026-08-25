<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id()->comment('Identificador único del evento de auditoría');
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete()
                ->comment('Usuario que ejecutó la acción (null si el usuario fue eliminado)');
            $table->string('accion', 50)->comment('login, logout, creado, actualizado, eliminado, download, cambio_nomina…');
            $table->string('tabla_afectada', 50)->nullable()->comment('Tabla del modelo auditado');
            $table->unsignedBigInteger('registro_id')->nullable()->comment('ID del registro afectado en tabla_afectada');
            $table->json('detalles')->nullable()->comment('Cambios o metadatos adicionales en formato JSON');
            $table->string('ip_address', 45)->nullable()->comment('IP del cliente que originó la acción (IPv4/IPv6)');
            $table->timestamp('created_at')->nullable()->useCurrent();

            $table->index(['tabla_afectada', 'registro_id'], 'auditoria_tabla_registro_index');
            $table->comment('Bitácora inmutable generada por los Traits Auditable/AuditService');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};
