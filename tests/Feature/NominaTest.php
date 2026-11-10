<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Nomina;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NominaTest extends TestCase
{
    use RefreshDatabase;

    private function crearNomina(int $usuarioId, array $overrides = []): Nomina
    {
        return Nomina::create(array_merge([
            'usuario_id' => $usuarioId,
            'periodo_inicio' => '2025-01-01',
            'periodo_fin' => '2025-01-15',
            'tipo_periodo' => 'quincenal',
            'salario_diario' => 300,
            'dias_laborales' => 10,
            'dias_asistidos' => 10,
            'descuentos_faltas' => 0,
            'total_pago' => '3000',
            'estado' => 'calculada',
        ], $overrides));
    }

    public function test_rh_puede_calcular_la_nomina_del_periodo_via_endpoint(): void
    {
        $rh = Usuario::factory()->rol('RH')->create();
        $rescatista = Usuario::factory()->rol('rescatista')->create(['salario_diario' => 300]);

        for ($dia = 1; $dia <= 10; $dia++) {
            Asistencia::create([
                'usuario_id' => $rescatista->id,
                'fecha' => sprintf('2025-01-%02d', $dia),
                'hora_entrada' => '09:00:00',
                'hora_salida' => '17:00:00',
                'estado' => 'presente',
            ]);
        }

        $response = $this->actingAs($rh)->postJson('/nominas/calcular', ['fecha' => '2025-01-08']);

        $response->assertOk();
        $response->assertJson(['total_procesadas' => 2]);

        $nomina = Nomina::where('usuario_id', $rescatista->id)->first();

        $this->assertNotNull($nomina);
        $this->assertEquals('2025-01-01', $nomina->periodo_inicio->toDateString());
        $this->assertEquals('2025-01-15', $nomina->periodo_fin->toDateString());
        $this->assertEquals(3000.0, (float) $nomina->total_pago);
    }

    public function test_rescatista_no_puede_calcular_nomina(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $this->actingAs($rescatista)
            ->postJson('/nominas/calcular', ['fecha' => '2025-01-08'])
            ->assertForbidden();
    }

    public function test_rescatista_solo_ve_su_propia_nomina(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create(['salario_diario' => 300]);
        $this->crearNomina($rescatista->id);

        $response = $this->actingAs($rescatista)->getJson('/nominas/mi-nomina/data?fecha=2025-01-08');

        $response->assertOk();
        $this->assertEquals(3000.0, (float) $response->json('nomina.total_pago'));
        $response->assertJson(['periodo_inicio' => '2025-01-01', 'periodo_fin' => '2025-01-15']);
    }

    public function test_visitante_no_autenticado_no_accede_a_su_nomina(): void
    {
        $this->getJson('/nominas/mi-nomina/data')->assertUnauthorized();
    }

    public function test_rescatista_puede_descargar_su_comprobante_en_pdf(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create(['salario_diario' => 300]);
        $this->crearNomina($rescatista->id);

        $response = $this->actingAs($rescatista)->get('/nominas/mi-nomina/pdf?fecha=2025-01-08');

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_rescatista_no_puede_descargar_comprobante_de_otro_rescatista(): void
    {
        $rescatistaA = Usuario::factory()->rol('rescatista')->create();
        $rescatistaB = Usuario::factory()->rol('rescatista')->create();
        $this->crearNomina($rescatistaA->id);

        $this->actingAs($rescatistaB)
            ->get('/nominas/mi-nomina/pdf?fecha=2025-01-08')
            ->assertNotFound();
    }

    public function test_solo_rh_puede_ver_la_vista_de_administracion_de_nominas(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();
        $rh = Usuario::factory()->rol('RH')->create();

        $this->actingAs($rescatista)->get('/nominas')->assertForbidden();
        $this->actingAs($rh)->get('/nominas')->assertOk();
    }

    public function test_rh_puede_marcar_una_nomina_como_pagada(): void
    {
        $rh = Usuario::factory()->rol('RH')->create();
        $rescatista = Usuario::factory()->rol('rescatista')->create();
        $nomina = $this->crearNomina($rescatista->id);

        $response = $this->actingAs($rh)->postJson("/nominas/{$nomina->id}/pagar");

        $response->assertOk();
        $this->assertEquals('pagada', $nomina->fresh()->estado);
        $this->assertNotNull($nomina->fresh()->fecha_pago);
    }

    public function test_rescatista_no_puede_marcar_nominas_como_pagadas(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();
        $nomina = $this->crearNomina($rescatista->id);

        $this->actingAs($rescatista)
            ->postJson("/nominas/{$nomina->id}/pagar")
            ->assertForbidden();
    }

    public function test_registrar_asistencia_genera_la_nomina_del_periodo_en_vivo(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create(['salario_diario' => 300]);

        $this->actingAs($rescatista)->postJson('/asistencias/checkin', [
            'hora_dispositivo' => '08:00:00',
        ])->assertOk();

        $nomina = Nomina::where('usuario_id', $rescatista->id)->first();

        $this->assertNotNull($nomina, 'El check-in debe generar/actualizar la nómina del periodo sin esperar al job nocturno.');
        $this->assertEquals(1, $nomina->dias_asistidos);
        $this->assertEquals(300.0, (float) $nomina->total_pago);
    }
}
