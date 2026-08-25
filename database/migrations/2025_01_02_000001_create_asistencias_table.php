<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id()->comment('Identificador único del registro de asistencia');
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete()
                ->comment('Empleado al que pertenece esta asistencia');
            $table->date('fecha')->comment('Día calendario del registro');
            $table->time('hora_entrada')->nullable()->comment('Hora de check-in');
            $table->time('hora_salida')->nullable()->comment('Hora de check-out');
            $table->decimal('ubicacion_lat', 10, 8)->nullable()->comment('Latitud GPS capturada en el check-in');
            $table->decimal('ubicacion_lng', 10, 8)->nullable()->comment('Longitud GPS capturada en el check-in');
            $table->string('foto_verificacion', 255)->nullable()->comment('Ruta en storage de la foto de verificación');
            $table->string('proyecto', 100)->nullable()->comment('Proyecto asignado al momento del check-in');
            $table->enum('estado', ['presente', 'ausente', 'tarde', 'justificado'])
                ->default('presente')
                ->comment('Determina el descuento aplicado en NominaService');
            $table->text('justificante')->nullable()->comment('Motivo de justificación capturado por RH');
            $table->timestamps();

            $table->unique(['usuario_id', 'fecha'], 'asistencias_usuario_fecha_unique');
            $table->comment('Registro diario de entrada/salida de cada técnico, base del cálculo de nómina');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
