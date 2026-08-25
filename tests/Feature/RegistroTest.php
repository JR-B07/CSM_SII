<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistroTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_nuevo_puede_registrarse_y_queda_pendiente(): void
    {
        $response = $this->post('/registro', [
            'nombre' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('login'));

        $usuario = Usuario::where('email', 'juan@example.com')->first();

        $this->assertNotNull($usuario);
        $this->assertEquals('pendiente', $usuario->estado_cuenta);
        $this->assertFalse($usuario->activo);
    }

    public function test_registro_ignora_el_rol_enviado_por_el_usuario(): void
    {
        $this->post('/registro', [
            'nombre' => 'Atacante',
            'email' => 'atacante@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'rol' => 'admin',
            'salario_diario' => 99999,
        ]);

        $usuario = Usuario::where('email', 'atacante@example.com')->first();

        $this->assertEquals('rescatista', $usuario->rol);
        $this->assertEquals(0, (float) $usuario->salario_diario);
    }

    public function test_usuario_pendiente_no_puede_iniciar_sesion(): void
    {
        $usuario = Usuario::factory()->pendiente()->create(['email' => 'pendiente@example.com']);

        $response = $this->post('/login', [
            'email' => 'pendiente@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_puede_aprobar_solicitud_y_el_usuario_puede_iniciar_sesion(): void
    {
        $admin = Usuario::factory()->rol('admin')->create();
        $pendiente = Usuario::factory()->pendiente()->create(['email' => 'nuevo@example.com']);

        $response = $this->actingAs($admin)->postJson("/usuarios/{$pendiente->id}/aprobar", [
            'rol' => 'rescatista',
            'departamento' => 'Electromecánica',
            'salario_diario' => 350,
        ]);

        $response->assertOk();

        $pendiente->refresh();
        $this->assertEquals('aprobado', $pendiente->estado_cuenta);
        $this->assertTrue($pendiente->activo);
        $this->assertEquals(350, (float) $pendiente->salario_diario);

        $this->post('/logout');

        $login = $this->post('/login', ['email' => 'nuevo@example.com', 'password' => 'password']);
        $login->assertRedirect(route('dashboard'));
    }

    public function test_admin_puede_rechazar_solicitud(): void
    {
        $admin = Usuario::factory()->rol('admin')->create();
        $pendiente = Usuario::factory()->pendiente()->create();

        $this->actingAs($admin)
            ->postJson("/usuarios/{$pendiente->id}/rechazar", ['motivo' => 'No es empleado de CSM'])
            ->assertOk();

        $pendiente->refresh();
        $this->assertEquals('rechazado', $pendiente->estado_cuenta);
        $this->assertFalse($pendiente->activo);
    }

    public function test_rescatista_no_puede_aprobar_solicitudes(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();
        $pendiente = Usuario::factory()->pendiente()->create();

        $this->actingAs($rescatista)
            ->postJson("/usuarios/{$pendiente->id}/aprobar", ['rol' => 'rescatista', 'salario_diario' => 300])
            ->assertForbidden();
    }
}
