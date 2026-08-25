<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estado de aprobación de la cuenta. Los usuarios creados por un admin
     * (UsuarioController::store) nacen "aprobado"; los que se auto-registran
     * (RegistroController::store) nacen "pendiente" y no pueden iniciar
     * sesión hasta que un admin los apruebe.
     */
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->enum('estado_cuenta', ['pendiente', 'aprobado', 'rechazado'])
                ->default('aprobado')
                ->after('activo')
                ->comment('pendiente = auto-registrado sin revisar; bloquea el login hasta ser aprobado');

            $table->foreignId('aprobado_por')->nullable()->after('estado_cuenta')
                ->constrained('usuarios')->nullOnDelete()
                ->comment('Admin que aprobó/rechazó la cuenta');

            $table->timestamp('aprobado_en')->nullable()->after('aprobado_por');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('aprobado_por');
            $table->dropColumn(['estado_cuenta', 'aprobado_en']);
        });
    }
};
