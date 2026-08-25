<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordarmeTest extends TestCase
{
    use RefreshDatabase;

    public function test_al_iniciar_sesion_con_recordarme_se_guarda_un_remember_token(): void
    {
        $usuario = Usuario::factory()->create();

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'password',
            'remember' => 'on',
        ])->assertRedirect(route('dashboard'));

        $this->assertNotNull($usuario->fresh()->remember_token);
    }

    public function test_sin_recordarme_no_se_emite_cookie_persistente(): void
    {
        $usuario = Usuario::factory()->create(['remember_token' => null]);

        $response = $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertNull($usuario->fresh()->remember_token);

        $tieneCookieRecordarme = collect($response->headers->getCookies())
            ->contains(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));

        $this->assertFalse($tieneCookieRecordarme);
    }

    public function test_la_cookie_de_recordarme_revive_la_sesion_sin_cookie_de_sesion_activa(): void
    {
        $usuario = Usuario::factory()->create();

        $loginResponse = $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'password',
            'remember' => 'on',
        ]);

        $rememberCookie = collect($loginResponse->headers->getCookies())
            ->first(fn ($cookie) => str_starts_with($cookie->getName(), 'remember_web_'));

        $this->assertNotNull($rememberCookie, 'La cookie remember_web_* no se generó al iniciar sesión.');

        // Simula una visita nueva del navegador: sin cookie de sesión activa,
        // solo con la cookie persistente de "Recordarme".
        $this->app['auth']->forgetGuards();

        $this->withUnencryptedCookie($rememberCookie->getName(), $rememberCookie->getValue())
            ->get('/dashboard')
            ->assertOk();

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_al_iniciar_sesion_con_recordarme_se_guarda_el_correo_para_el_formulario(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this->post('/login', [
            'email' => $usuario->email,
            'password' => 'password',
            'remember' => 'on',
        ]);

        $response->assertCookie('email_recordado', $usuario->email);
    }

    public function test_el_login_prellena_el_correo_recordado_y_marca_la_casilla(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this->withCookie('email_recordado', $usuario->email)->get('/login');

        $response->assertOk();
        $response->assertSee($usuario->email);
        $response->assertSee('checked', false);
    }

    public function test_sin_recordarme_se_borra_el_correo_recordado_previo(): void
    {
        $usuario = Usuario::factory()->create();

        $response = $this->withCookie('email_recordado', $usuario->email)->post('/login', [
            'email' => $usuario->email,
            'password' => 'password',
        ]);

        $response->assertCookieExpired('email_recordado');
    }
}
