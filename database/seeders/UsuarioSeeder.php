<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    /**
     * Crea el personal inicial de CSM Operaciones (14 empleados).
     */
    public function run(): void
    {
        Usuario::updateOrCreate(
            ['email' => 'director@csm-operaciones.com.mx'],
            [
                'nombre' => 'Juan Héctor Morín Torres',
                'rol' => 'admin',
                'departamento' => 'Dirección',
                'salario_diario' => 1200,
                'password' => 'password',
                'activo' => true,
            ]
        );

        Usuario::updateOrCreate(
            ['email' => 'rh@csm-operaciones.com.mx'],
            [
                'nombre' => 'Recursos Humanos CSM',
                'rol' => 'RH',
                'departamento' => 'Recursos Humanos',
                'salario_diario' => 700,
                'password' => 'password',
                'activo' => true,
            ]
        );

        Usuario::updateOrCreate(
            ['email' => 'contador@csm-operaciones.com.mx'],
            [
                'nombre' => 'Contador CSM',
                'rol' => 'contador',
                'departamento' => 'Contabilidad',
                'salario_diario' => 650,
                'password' => 'password',
                'activo' => true,
            ]
        );

        Usuario::updateOrCreate(
            ['email' => 'supervisor@csm-operaciones.com.mx'],
            [
                'nombre' => 'Supervisor de Campo',
                'rol' => 'supervisor',
                'departamento' => 'Electromecánica',
                'salario_diario' => 550,
                'password' => 'password',
                'activo' => true,
            ]
        );

        // 10 técnicos de campo
        Usuario::factory()
            ->count(10)
            ->rol('rescatista')
            ->create();
    }
}
