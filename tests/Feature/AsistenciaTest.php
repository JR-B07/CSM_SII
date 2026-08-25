<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsistenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_rescatista_solo_ve_su_propio_resumen_de_reportes(): void
    {
        $rescatistaA = Usuario::factory()->rol('rescatista')->create();
        $rescatistaB = Usuario::factory()->rol('rescatista')->create();

        Asistencia::create([
            'usuario_id' => $rescatistaA->id,
            'fecha' => now()->toDateString(),
            'estado' => 'presente',
        ]);

        Asistencia::create([
            'usuario_id' => $rescatistaB->id,
            'fecha' => now()->toDateString(),
            'estado' => 'presente',
        ]);
        Asistencia::create([
            'usuario_id' => $rescatistaB->id,
            'fecha' => now()->subDay()->toDateString(),
            'estado' => 'ausente',
        ]);

        $response = $this->actingAs($rescatistaB)->getJson('/asistencias/reportes');

        $response->assertOk();
        $this->assertEquals(1, $response->json('resumen.presente'));
        $this->assertEquals(1, $response->json('resumen.ausente'));
    }

    public function test_rh_ve_el_resumen_de_toda_la_empresa_en_reportes(): void
    {
        $rh = Usuario::factory()->rol('RH')->create();
        $rescatistaA = Usuario::factory()->rol('rescatista')->create();
        $rescatistaB = Usuario::factory()->rol('rescatista')->create();

        Asistencia::create(['usuario_id' => $rescatistaA->id, 'fecha' => now()->toDateString(), 'estado' => 'presente']);
        Asistencia::create(['usuario_id' => $rescatistaB->id, 'fecha' => now()->toDateString(), 'estado' => 'presente']);

        $response = $this->actingAs($rh)->getJson('/asistencias/reportes');

        $response->assertOk();
        $this->assertEquals(2, $response->json('resumen.presente'));
    }

    public function test_rescatista_puede_registrar_checkin(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $response = $this->actingAs($rescatista)->postJson('/asistencias/checkin', [
            'proyecto' => 'Planta Norte',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('asistencias', [
            'usuario_id' => $rescatista->id,
            'proyecto' => 'Planta Norte',
        ]);
    }

    public function test_solo_rh_puede_listar_todas_las_asistencias(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $this->actingAs($rescatista)->getJson('/asistencias')->assertForbidden();
    }

    public function test_segundo_checkin_del_mismo_dia_no_duplica_registro(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $this->actingAs($rescatista)->postJson('/asistencias/checkin', ['proyecto' => 'Planta Norte'])->assertOk();
        $response = $this->actingAs($rescatista)->postJson('/asistencias/checkin', ['proyecto' => 'Planta Norte']);

        $response->assertStatus(422);
        $this->assertEquals(1, Asistencia::where('usuario_id', $rescatista->id)->count());
    }

    public function test_checkout_encuentra_el_checkin_del_mismo_dia(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $this->actingAs($rescatista)->postJson('/asistencias/checkin', ['proyecto' => 'Planta Norte'])->assertOk();
        $response = $this->actingAs($rescatista)->postJson('/asistencias/checkout');

        $response->assertOk();
        $this->assertNotNull(Asistencia::where('usuario_id', $rescatista->id)->first()->hora_salida);
    }

    public function test_checkin_registra_evento_en_auditoria(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $this->actingAs($rescatista)->postJson('/asistencias/checkin', ['proyecto' => 'Planta Norte'])->assertOk();

        $asistencia = Asistencia::where('usuario_id', $rescatista->id)->first();

        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $rescatista->id,
            'accion' => 'creado',
            'tabla_afectada' => 'asistencias',
            'registro_id' => $asistencia->id,
        ]);
    }

    public function test_endpoint_ultima_devuelve_el_registro_mas_reciente(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        Asistencia::create(['usuario_id' => $rescatista->id, 'fecha' => now()->subDay()->toDateString(), 'estado' => 'presente']);
        $reciente = Asistencia::create(['usuario_id' => $rescatista->id, 'fecha' => now()->toDateString(), 'estado' => 'presente']);

        $response = $this->actingAs($rescatista)->getJson('/asistencias/ultima');

        $response->assertOk();
        $this->assertEquals($reciente->id, $response->json('asistencia.id'));
    }

    public function test_endpoint_proyectos_devuelve_lista_sin_duplicados(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        Asistencia::create(['usuario_id' => $rescatista->id, 'fecha' => now()->subDay()->toDateString(), 'estado' => 'presente', 'proyecto' => 'Planta Norte']);
        Asistencia::create(['usuario_id' => $rescatista->id, 'fecha' => now()->toDateString(), 'estado' => 'presente', 'proyecto' => 'Planta Norte']);

        $response = $this->actingAs($rescatista)->getJson('/asistencias/proyectos');

        $response->assertOk();
        $this->assertEquals(['Planta Norte'], $response->json('proyectos'));
    }

    public function test_checkin_guarda_compania_y_puesto(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $this->actingAs($rescatista)->postJson('/asistencias/checkin', [
            'proyecto' => 'Planta Norte',
            'compania' => 'CSM Operaciones',
            'puesto' => 'Rescatista',
        ])->assertOk();

        $this->assertDatabaseHas('asistencias', [
            'usuario_id' => $rescatista->id,
            'compania' => 'CSM Operaciones',
            'puesto' => 'Rescatista',
        ]);
    }

    public function test_checkin_usa_la_hora_enviada_por_el_dispositivo(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $this->actingAs($rescatista)->postJson('/asistencias/checkin', [
            'proyecto' => 'Planta Norte',
            'hora_dispositivo' => '07:15:00',
        ])->assertOk();

        $this->assertDatabaseHas('asistencias', [
            'usuario_id' => $rescatista->id,
            'hora_entrada' => '07:15:00',
        ]);
    }

    public function test_checkout_usa_la_hora_enviada_por_el_dispositivo(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $this->actingAs($rescatista)->postJson('/asistencias/checkin', ['proyecto' => 'Planta Norte'])->assertOk();

        $this->actingAs($rescatista)->postJson('/asistencias/checkout', [
            'hora_dispositivo' => '17:45:00',
        ])->assertOk();

        $this->assertDatabaseHas('asistencias', [
            'usuario_id' => $rescatista->id,
            'hora_salida' => '17:45:00',
        ]);
    }

    public function test_rh_ve_compania_y_puesto_en_el_listado_de_gestion(): void
    {
        $rh = Usuario::factory()->rol('RH')->create();
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        Asistencia::create([
            'usuario_id' => $rescatista->id,
            'fecha' => now()->toDateString(),
            'estado' => 'presente',
            'proyecto' => 'Planta Norte',
            'compania' => 'CSM Operaciones',
            'puesto' => 'Rescatista',
        ]);

        $response = $this->actingAs($rh)->getJson('/asistencias/data');

        $response->assertOk();
        $response->assertJsonFragment(['compania' => 'CSM Operaciones', 'puesto' => 'Rescatista']);
    }

    public function test_solo_rh_puede_ver_la_vista_de_gestion_de_asistencias(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();
        $rh = Usuario::factory()->rol('RH')->create();

        $this->actingAs($rescatista)->get('/asistencias')->assertForbidden();
        $this->actingAs($rh)->get('/asistencias')->assertOk();
    }
}
