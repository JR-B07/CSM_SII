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

    public function test_rh_puede_calcular_la_nomina_del_mes_via_endpoint(): void
    {
        $rh = Usuario::factory()->rol('RH')->create();
        $rescatista = Usuario::factory()->rol('rescatista')->create(['salario_diario' => 300]);

        for ($dia = 1; $dia <= 20; $dia++) {
            Asistencia::create([
                'usuario_id' => $rescatista->id,
                'fecha' => sprintf('2025-01-%02d', $dia),
                'hora_entrada' => '09:00:00',
                'hora_salida' => '17:00:00',
                'estado' => 'presente',
            ]);
        }

        $response = $this->actingAs($rh)->postJson('/nominas/calcular', [
            'mes' => 1,
            'anio' => 2025,
        ]);

        $response->assertOk();
        $response->assertJson(['total_procesadas' => 2]);

        $nomina = Nomina::where('usuario_id', $rescatista->id)->first();

        $this->assertNotNull($nomina);
        $this->assertEquals(6000.0, (float) $nomina->total_pago);
    }

    public function test_rescatista_no_puede_calcular_nomina(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $this->actingAs($rescatista)
            ->postJson('/nominas/calcular', ['mes' => 1, 'anio' => 2025])
            ->assertForbidden();
    }

    public function test_rescatista_solo_ve_su_propia_nomina(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create(['salario_diario' => 300]);

        Nomina::create([
            'usuario_id' => $rescatista->id,
            'mes' => 1,
            'anio' => 2025,
            'salario_diario' => 300,
            'dias_laborales' => 20,
            'dias_asistidos' => 20,
            'descuentos_faltas' => 0,
            'descuentos_retardos' => 0,
            'total_pago' => '6000',
            'estado' => 'calculada',
        ]);

        $response = $this->actingAs($rescatista)->getJson('/nominas/mi-nomina/data?mes=1&anio=2025');

        $response->assertOk();
        $this->assertEquals(6000.0, (float) $response->json('nomina.total_pago'));
    }

    public function test_visitante_no_autenticado_no_accede_a_su_nomina(): void
    {
        $this->getJson('/nominas/mi-nomina/data')->assertUnauthorized();
    }

    public function test_rescatista_puede_descargar_su_comprobante_en_pdf(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create(['salario_diario' => 300]);

        Nomina::create([
            'usuario_id' => $rescatista->id,
            'mes' => 1,
            'anio' => 2025,
            'salario_diario' => 300,
            'dias_laborales' => 20,
            'dias_asistidos' => 20,
            'descuentos_faltas' => 0,
            'descuentos_retardos' => 0,
            'total_pago' => '6000',
            'estado' => 'calculada',
        ]);

        $response = $this->actingAs($rescatista)->get('/nominas/mi-nomina/pdf?mes=1&anio=2025');

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_rescatista_no_puede_descargar_comprobante_de_otro_rescatista(): void
    {
        $rescatistaA = Usuario::factory()->rol('rescatista')->create();
        $rescatistaB = Usuario::factory()->rol('rescatista')->create();

        Nomina::create([
            'usuario_id' => $rescatistaA->id,
            'mes' => 1,
            'anio' => 2025,
            'salario_diario' => 300,
            'dias_laborales' => 20,
            'dias_asistidos' => 20,
            'descuentos_faltas' => 0,
            'descuentos_retardos' => 0,
            'total_pago' => '6000',
            'estado' => 'calculada',
        ]);

        $this->actingAs($rescatistaB)
            ->get('/nominas/mi-nomina/pdf?mes=1&anio=2025')
            ->assertNotFound();
    }

    public function test_solo_rh_puede_ver_la_vista_de_administracion_de_nominas(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();
        $rh = Usuario::factory()->rol('RH')->create();

        $this->actingAs($rescatista)->get('/nominas')->assertForbidden();
        $this->actingAs($rh)->get('/nominas')->assertOk();
    }
}
