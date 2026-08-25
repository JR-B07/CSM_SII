<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Usuario>
 */
class UsuarioFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'rol' => 'rescatista',
            'departamento' => fake()->randomElement(['EHS', 'Sistemas contra incendios', 'Electromecánica', 'Proyectos']),
            'salario_diario' => fake()->randomFloat(2, 250, 600),
            'email_verified_at' => now(),
            'password' => static::$password ??= 'password',
            'activo' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function rol(string $rol): static
    {
        return $this->state(fn() => ['rol' => $rol]);
    }

    public function inactivo(): static
    {
        return $this->state(fn() => ['activo' => false]);
    }

    public function pendiente(): static
    {
        return $this->state(fn() => ['activo' => false, 'estado_cuenta' => 'pendiente']);
    }
}
