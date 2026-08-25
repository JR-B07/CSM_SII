<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nominas', function (Blueprint $table) {
            $table->id()->comment('Identificador único de la nómina mensual');
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete()
                ->comment('Empleado al que corresponde esta nómina');
            $table->unsignedTinyInteger('mes')->comment('Mes del periodo (1-12)');
            $table->unsignedSmallInteger('anio')->comment('Año del periodo');
            $table->decimal('salario_diario', 10, 2)->comment('Salario diario vigente al momento del cálculo');
            $table->unsignedInteger('dias_laborales')->comment('Total de registros de asistencia del periodo');
            $table->unsignedInteger('dias_asistidos')->comment('Días presente + tarde + justificado (días pagados)');
            $table->decimal('descuentos_faltas', 10, 2)->default(0)->comment('Descuento por faltas: 100% del salario diario por falta');
            $table->decimal('descuentos_retardos', 10, 2)->default(0)->comment('Descuento por retardos, % configurable en config/custom.php');
            $table->text('total_pago')->comment('Monto neto a pagar, ENCRIPTADO AES-256-CBC vía Trait Encryptable');
            $table->enum('estado', ['calculada', 'pagada', 'procesada'])->default('calculada')
                ->comment('Estado del ciclo de pago de la nómina');
            $table->date('fecha_pago')->nullable()->comment('Fecha en que RH marcó la nómina como pagada');
            $table->timestamps();
            $table->softDeletes()->comment('Retención legal de 7 años (LFPDPPP): nunca se borra físicamente');

            $table->unique(['usuario_id', 'mes', 'anio'], 'nominas_usuario_periodo_unique');
            $table->comment('Nómina mensual calculada automáticamente por NominaService/CalcularNominasJob');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nominas');
    }
};
