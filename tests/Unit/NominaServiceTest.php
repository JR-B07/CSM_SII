<?php

namespace Tests\Unit;

use App\Models\Asistencia;
use App\Models\Usuario;
use App\Services\NominaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NominaServiceTest extends TestCase
{
    use RefreshDatabase;

    private const INICIO = '2025-01-01';
    private const FIN = '2025-01-15';

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periodo(): array
    {
        return [Carbon::parse(self::INICIO), Carbon::parse(self::FIN)];
    }

    private function crearAsistencias(Usuario $usuario, string $estado, int $cantidad, int $diaInicial = 1): void
    {
        for ($i = 0; $i < $cantidad; $i++) {
            Asistencia::create([
                'usuario_id' => $usuario->id,
                'fecha' => sprintf('2025-01-%02d', $diaInicial + $i),
                'hora_entrada' => '09:00:00',
                'hora_salida' => '17:00:00',
                'estado' => $estado,
            ]);
        }
    }

    public function test_calcula_nomina_correctamente(): void
    {
        $usuario = Usuario::factory()->create(['salario_diario' => 300]);

        $this->crearAsistencias($usuario, 'presente', 10);

        [$inicio, $fin] = $this->periodo();
        $nomina = (new NominaService())->calcularNomina($usuario, $inicio, $fin);

        // 10 días × $300 = $3,000
        $this->assertEquals(3000.0, (float) $nomina->total_pago);
        $this->assertEquals(10, $nomina->dias_asistidos);
        $this->assertEquals(0.0, (float) $nomina->descuentos_faltas);
        $this->assertEquals(self::INICIO, $nomina->periodo_inicio->toDateString());
        $this->assertEquals(self::FIN, $nomina->periodo_fin->toDateString());
    }

    public function test_descuentos_por_faltas(): void
    {
        $usuario = Usuario::factory()->create(['salario_diario' => 300]);

        $this->crearAsistencias($usuario, 'presente', 10, diaInicial: 1);
        $this->crearAsistencias($usuario, 'ausente', 2, diaInicial: 11);

        [$inicio, $fin] = $this->periodo();
        $nomina = (new NominaService())->calcularNomina($usuario, $inicio, $fin);

        // 10 × $300 - (2 × $300) = $2,400
        $this->assertEquals(2400.0, (float) $nomina->total_pago);
        $this->assertEquals(10, $nomina->dias_asistidos);
        $this->assertEquals(600.0, (float) $nomina->descuentos_faltas);
    }

    public function test_los_retardos_se_pagan_igual_que_un_dia_presente(): void
    {
        $usuario = Usuario::factory()->create(['salario_diario' => 300]);

        $this->crearAsistencias($usuario, 'presente', 8, diaInicial: 1);
        $this->crearAsistencias($usuario, 'tarde', 2, diaInicial: 9);

        [$inicio, $fin] = $this->periodo();
        $nomina = (new NominaService())->calcularNomina($usuario, $inicio, $fin);

        // 10 días asistidos (incluye retardos) × $300, sin descuento.
        $this->assertEquals(3000.0, (float) $nomina->total_pago);
        $this->assertEquals(10, $nomina->dias_asistidos);
    }

    public function test_permisos_justificados_no_generan_descuento(): void
    {
        $usuario = Usuario::factory()->create(['salario_diario' => 300]);

        $this->crearAsistencias($usuario, 'presente', 8, diaInicial: 1);
        $this->crearAsistencias($usuario, 'justificado', 2, diaInicial: 9);

        [$inicio, $fin] = $this->periodo();
        $nomina = (new NominaService())->calcularNomina($usuario, $inicio, $fin);

        // Los justificados se pagan como día asistido, sin descuento.
        $this->assertEquals(3000.0, (float) $nomina->total_pago);
        $this->assertEquals(10, $nomina->dias_asistidos);
    }

    public function test_total_pago_se_almacena_encriptado_en_bd(): void
    {
        $usuario = Usuario::factory()->create(['salario_diario' => 300]);
        $this->crearAsistencias($usuario, 'presente', 10);

        [$inicio, $fin] = $this->periodo();
        $nomina = (new NominaService())->calcularNomina($usuario, $inicio, $fin);

        $valorCrudo = DB::table('nominas')->where('id', $nomina->id)->value('total_pago');

        $this->assertNotEquals('3000', $valorCrudo);
        $this->assertStringStartsNotWith('3000', $valorCrudo);
        $this->assertEquals(3000.0, (float) $nomina->fresh()->total_pago);

        // toArray()/toJson() (usado por las respuestas de la API) debe devolver
        // el monto desencriptado, no el texto cifrado.
        $this->assertEquals(3000.0, (float) $nomina->fresh()->toArray()['total_pago']);
    }

    public function test_calcular_para_todos_procesa_solo_usuarios_activos(): void
    {
        $activo = Usuario::factory()->create(['salario_diario' => 300, 'activo' => true]);
        $inactivo = Usuario::factory()->inactivo()->create(['salario_diario' => 300]);

        $this->crearAsistencias($activo, 'presente', 5);
        $this->crearAsistencias($inactivo, 'presente', 5);

        [$inicio, $fin] = $this->periodo();
        $nominas = (new NominaService())->calcularParaTodos($inicio, $fin);

        $this->assertCount(1, $nominas);
        $this->assertEquals($activo->id, $nominas->first()->usuario_id);
    }

    public function test_recalcular_no_pisa_un_estado_pagada_existente(): void
    {
        $usuario = Usuario::factory()->create(['salario_diario' => 300]);
        $this->crearAsistencias($usuario, 'presente', 5);

        $service = new NominaService();
        [$inicio, $fin] = $this->periodo();
        $nomina = $service->calcularNomina($usuario, $inicio, $fin);
        $nomina->update(['estado' => 'pagada']);

        $this->crearAsistencias($usuario, 'presente', 1, diaInicial: 6);
        $service->calcularNomina($usuario, $inicio, $fin);

        $this->assertEquals('pagada', $nomina->fresh()->estado);
        $this->assertEquals(6, $nomina->fresh()->dias_asistidos);
    }

    public function test_recalcular_restaura_una_nomina_borrada_suavemente(): void
    {
        $usuario = Usuario::factory()->create(['salario_diario' => 300]);
        $this->crearAsistencias($usuario, 'presente', 5);

        $service = new NominaService();
        [$inicio, $fin] = $this->periodo();
        $nomina = $service->calcularNomina($usuario, $inicio, $fin);
        $nomina->delete();

        $this->assertTrue($nomina->fresh()->trashed());

        $this->crearAsistencias($usuario, 'presente', 1, diaInicial: 6);
        $recalculada = $service->calcularNomina($usuario, $inicio, $fin);

        $this->assertEquals($nomina->id, $recalculada->id);
        $this->assertFalse($recalculada->fresh()->trashed());
        $this->assertEquals(6, $recalculada->dias_asistidos);
    }

    public function test_periodo_quincenal_para_primera_mitad_del_mes(): void
    {
        [$inicio, $fin] = (new NominaService())->periodoQuincenalPara(Carbon::parse('2025-01-08'));

        $this->assertEquals('2025-01-01', $inicio->toDateString());
        $this->assertEquals('2025-01-15', $fin->toDateString());
    }

    public function test_periodo_quincenal_para_segunda_mitad_del_mes(): void
    {
        [$inicio, $fin] = (new NominaService())->periodoQuincenalPara(Carbon::parse('2025-01-20'));

        $this->assertEquals('2025-01-16', $inicio->toDateString());
        $this->assertEquals('2025-01-31', $fin->toDateString());
    }

    public function test_periodo_quincenal_respeta_meses_de_28_dias(): void
    {
        [$inicio, $fin] = (new NominaService())->periodoQuincenalPara(Carbon::parse('2025-02-20'));

        $this->assertEquals('2025-02-16', $inicio->toDateString());
        $this->assertEquals('2025-02-28', $fin->toDateString());
    }
}
