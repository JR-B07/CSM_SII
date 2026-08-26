<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_dar_de_baja_a_un_usuario(): void
    {
        $admin = Usuario::factory()->rol('admin')->create();
        $rescatista = Usuario::factory()->rol('rescatista')->create(['activo' => true]);

        $this->actingAs($admin)
            ->deleteJson("/usuarios/{$rescatista->id}")
            ->assertOk();

        $this->assertFalse($rescatista->fresh()->activo);
        $this->assertDatabaseHas('usuarios', ['id' => $rescatista->id]);
    }

    public function test_usuario_dado_de_baja_no_puede_iniciar_sesion(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create(['activo' => false]);

        $response = $this->post('/login', [
            'email' => $rescatista->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_puede_reactivar_a_un_usuario(): void
    {
        $admin = Usuario::factory()->rol('admin')->create();
        $rescatista = Usuario::factory()->rol('rescatista')->create(['activo' => false]);

        $this->actingAs($admin)
            ->postJson("/usuarios/{$rescatista->id}/activar")
            ->assertOk();

        $this->assertTrue($rescatista->fresh()->activo);
    }

    public function test_rescatista_no_puede_dar_de_baja_a_otro_usuario(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();
        $otro = Usuario::factory()->rol('rescatista')->create();

        $this->actingAs($rescatista)
            ->deleteJson("/usuarios/{$otro->id}")
            ->assertForbidden();

        $this->assertTrue($otro->fresh()->activo);
    }

    public function test_dar_de_baja_queda_registrado_en_auditoria(): void
    {
        $admin = Usuario::factory()->rol('admin')->create();
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $this->actingAs($admin)->deleteJson("/usuarios/{$rescatista->id}");

        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $admin->id,
            'accion' => 'actualizado',
            'tabla_afectada' => 'usuarios',
            'registro_id' => $rescatista->id,
        ]);
    }

    public function test_admin_puede_crear_un_usuario_desde_el_formulario_y_es_redirigido_al_listado(): void
    {
        $admin = Usuario::factory()->rol('admin')->create();

        $response = $this->actingAs($admin)->post('/usuarios', [
            'nombre' => 'Juan Vargas',
            'email' => 'juan.vargas@example.com',
            'rol' => 'rescatista',
            'departamento' => 'supervisor HSE',
            'salario_diario' => 1000,
            'password' => '',
        ]);

        $response->assertRedirect(route('usuarios.index'));
        $response->assertSessionHas('status', 'Usuario creado exitosamente.');

        $this->assertDatabaseHas('usuarios', [
            'nombre' => 'Juan Vargas',
            'email' => 'juan.vargas@example.com',
            'rol' => 'rescatista',
        ]);

        $this->followingRedirects()
            ->actingAs($admin)
            ->getJson('/usuarios/data')
            ->assertJsonFragment(['email' => 'juan.vargas@example.com']);
    }

    public function test_crear_usuario_con_datos_invalidos_muestra_errores_en_el_formulario(): void
    {
        $admin = Usuario::factory()->rol('admin')->create();

        $response = $this->actingAs($admin)->post('/usuarios', [
            'nombre' => '',
            'email' => 'no-es-un-correo',
            'rol' => 'rescatista',
            'salario_diario' => 1000,
        ]);

        $response->assertSessionHasErrors(['nombre', 'email']);
        $this->assertDatabaseMissing('usuarios', ['email' => 'no-es-un-correo']);
    }

    public function test_cuenta_autoregistrada_pendiente_no_aparece_en_el_listado_general(): void
    {
        $admin = Usuario::factory()->rol('admin')->create();
        $pendiente = Usuario::factory()->pendiente()->create(['email' => 'nuevo@example.com']);

        $response = $this->actingAs($admin)->getJson('/usuarios/data');

        $response->assertOk();
        $response->assertJsonMissing(['email' => 'nuevo@example.com']);
    }

    public function test_cuenta_autoregistrada_pendiente_si_aparece_en_la_cola_de_aprobacion(): void
    {
        $admin = Usuario::factory()->rol('admin')->create();
        $pendiente = Usuario::factory()->pendiente()->create(['email' => 'nuevo@example.com']);

        $this->actingAs($admin)
            ->getJson('/usuarios/pendientes/data')
            ->assertOk()
            ->assertJsonFragment(['email' => 'nuevo@example.com']);
    }

    public function test_el_listado_no_esconde_usuarios_al_pasar_de_20(): void
    {
        $admin = Usuario::factory()->rol('admin')->create();
        Usuario::factory()->count(25)->rol('rescatista')->create();

        $response = $this->actingAs($admin)->getJson('/usuarios/data');

        $response->assertOk();
        // 25 rescatistas + el admin de este test.
        $this->assertCount(26, $response->json('data'));
    }
}
