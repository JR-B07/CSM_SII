<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id()->comment('Identificador único del usuario');
            $table->string('nombre', 100)->comment('Nombre completo del empleado');
            $table->string('email', 100)->unique()->comment('Correo electrónico, usado para iniciar sesión');
            $table->enum('rol', ['rescatista', 'supervisor', 'RH', 'admin', 'contador'])
                ->default('rescatista')
                ->comment('Rol de autorización: rescatista, supervisor, RH, admin o contador');
            $table->string('departamento', 50)->nullable()->comment('Departamento o área asignada');
            $table->decimal('salario_diario', 10, 2)->default(0)->comment('Salario diario base usado por NominaService');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->comment('Hash bcrypt (cast "hashed" en el modelo Usuario)');
            $table->boolean('activo')->default(true)->comment('Empleados inactivos no se procesan en la nómina');
            $table->rememberToken();
            $table->timestamps();

            $table->comment('Empleados de CSM Operaciones y su información para autenticación y nómina');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
