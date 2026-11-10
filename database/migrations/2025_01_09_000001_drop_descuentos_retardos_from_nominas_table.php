<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Se quita el descuento automático por retardos: un "tarde" ahora se
     * paga igual que un "presente" (el estado sigue quedando registrado
     * para referencia de RH, solo deja de restar del pago).
     */
    public function up(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            $table->dropColumn('descuentos_retardos');
        });
    }

    public function down(): void
    {
        Schema::table('nominas', function (Blueprint $table) {
            $table->decimal('descuentos_retardos', 10, 2)->default(0)->after('descuentos_faltas')
                ->comment('Descuento por retardos, % configurable en config/custom.php');
        });
    }
};
