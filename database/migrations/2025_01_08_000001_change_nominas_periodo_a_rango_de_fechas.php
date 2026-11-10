<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La nómina pasa de calcularse por mes calendario a calcularse por
     * quincena (1-15 / 16-fin de mes), para reflejar el ciclo de pago real
     * de la empresa. mes/anio no pueden representar eso, así que se
     * reemplazan por un rango de fechas explícito.
     */
    public function up(): void
    {
        // MySQL no deja soltar el índice único porque es el que respalda la
        // llave foránea de usuario_id; se agrega un índice temporal para
        // que la FK quede cubierta mientras se reconstruye el esquema.
        Schema::table('nominas', function (Blueprint $table) {
            $table->index('usuario_id', 'nominas_usuario_id_temp_index');
        });

        Schema::table('nominas', function (Blueprint $table) {
            $table->dropUnique('nominas_usuario_periodo_unique');
            $table->dropColumn(['mes', 'anio']);

            $table->date('periodo_inicio')->after('usuario_id')->comment('Primer día del periodo de pago');
            $table->date('periodo_fin')->after('periodo_inicio')->comment('Último día del periodo de pago');
            $table->string('tipo_periodo', 20)->default('quincenal')->after('periodo_fin')
                ->comment('semanal | quincenal, según config/custom.php');

            $table->unique(['usuario_id', 'periodo_inicio'], 'nominas_usuario_periodo_unique');
        });

        // La unique nueva ya cubre usuario_id como primera columna.
        Schema::table('nominas', function (Blueprint $table) {
            $table->dropIndex('nominas_usuario_id_temp_index');
        });
    }

    public function down(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            $table->index('usuario_id', 'nominas_usuario_id_temp_index');
        });

        Schema::table('nominas', function (Blueprint $table) {
            $table->dropUnique('nominas_usuario_periodo_unique');
            $table->dropColumn(['periodo_inicio', 'periodo_fin', 'tipo_periodo']);

            $table->unsignedTinyInteger('mes')->after('usuario_id')->comment('Mes del periodo (1-12)');
            $table->unsignedSmallInteger('anio')->after('mes')->comment('Año del periodo');

            $table->unique(['usuario_id', 'mes', 'anio'], 'nominas_usuario_periodo_unique');
        });

        Schema::table('nominas', function (Blueprint $table) {
            $table->dropIndex('nominas_usuario_id_temp_index');
        });
    }
};
