<?php

namespace Tests\Unit;

use App\Models\Asistencia;
use App\Models\Usuario;
use App\Services\NominaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NominaServiceTest extends TestCase
{
    use RefreshDatabase;

    private const MES = 1;
    private const ANIO = 2025;

    private function crearAsistencias(Usuario $usuario, string $estado, int $cantidad, int $diaInicial = 1): void
    {
        for ($i = 0; $i < $cantidad; $i++) {
            Asistencia::create([
                'usuario_id' => $usuario->id,
                'fecha' => sprintf('%04d-%02d-%02d', self::ANIO, self::MES, $diaInicial + $i),
                'hora_entrada' => '09:00:00',
                'hora_salida' => '17:00:00',
                'estado' => $estado,
            ]);
        }
    }

    public function test_calcula_nomina_correctamente(): void
    {
        $usuario = Usuario::factory()->create(['salario_diario' => 300]);

        $this->crearAsistencias($usuario, 'presente', 20);

        $nomina = (new NominaService())->calcularNomina($usuario, self::MES, self::ANIO);

        // 20 días × $300 = $6,000
        $this->assertEquals(6000.0, (float) $nomina->total_pago);
        $this->assertEquals(20, $nomina->dias_asistidos);
        $this->assertEquals(0.0, (float) $nomina->descuentos_faltas);
    }

    public function test_descuentos_por_faltas(): void
    {
        $usuario = Usuario::factory()->create(['salario_diario' => 300]);

        $this->crearAsistencias($usuario, 'presente', 20, diaInicial: 1);
        $this->crearAsistencias($usuario, 'ausente', 2, diaInicial: 21);

        $nomina = (new NominaService())->calcularNomina($usuario, self::MES, self::ANIO);

        // 20 × $300 - (2 × $300) = $5,400
        $this->assertEquals(5400.0, (float) $nomina->total_pago);
        $this->assertEquals(20, $nomina->dias_asistidos);
        $this->assertEquals(600.0, (float) $nomina->descuentos_faltas);
    }

    public function test_descuentos_por_retardos(): void
    {
        $usuario = Usuario::factory()->create(['salario_diario' => 300]);

        $this->crearAsistencias($usuario, 'presente', 18, diaInicial: 1);
        $this->crearAsistencias($usuario, 'tarde', 2, diaInicial: 19);

        $nomina = (new NominaService())->calcularNomina($usuario, self::MES, self::ANIO);

        // 20 días asistidos (incluye retardos) × $300 - (2 × $300 × 50%) = $5,700
        $this->assertEquals(5700.0, (float) $nomina->total_pago);
        $this->assertEquals(20, $nomina->dias_asistidos);
        $this->assertEquals(300.0, (float) $nomina->descuentos_retardos);
    }

    public function test_permisos_justificados_no_generan_descuento(): void
    {
        $usuario = Usuario::factory()->create(['salario_diario' => 300]);

        $this->crearAsistencias($usuario, 'presente', 18, diaInicial: 1);
        $this->crearAsistencias($usuario, 'justificado', 2, diaInicial: 19);

        $nomina = (new NominaService())->calcularNomina($usuario, self::MES, self::ANIO);

        // Los justificados se pagan como día asistido, sin descuento.
        $this->assertEquals(6000.0, (float) $nomina->total_pago);
        $this->assertEquals(20, $nomina->dias_asistidos);
    }

    public function test_total_pago_se_almacena_encriptado_en_bd(): void
    {
        $usuario = Usuario::factory()->create(['salario_diario' => 300]);
        $this->crearAsistencias($usuario, 'presente', 20);

        $nomina = (new NominaService())->calcularNomina($usuario, self::MES, self::ANIO);

        $valorCrudo = DB::table('nominas')->where('id', $nomina->id)->value('total_pago');

        $this->assertNotEquals('6000', $valorCrudo);
        $this->assertStringStartsNotWith('6000', $valorCrudo);
        $this->assertEquals(6000.0, (float) $nomina->fresh()->total_pago);

        // toArray()/toJson() (usado por las respuestas de la API) debe devolver
        // el monto desencriptado, no el texto cifrado.
        $this->assertEquals(6000.0, (float) $nomina->fresh()->toArray()['total_pago']);
    }

    public function test_calcular_para_todos_procesa_solo_usuarios_activos(): void
    {
        $activo = Usuario::factory()->create(['salario_diario' => 300, 'activo' => true]);
        $inactivo = Usuario::factory()->inactivo()->create(['salario_diario' => 300]);

        $this->crearAsistencias($activo, 'presente', 5);
        $this->crearAsistencias($inactivo, 'presente', 5);

        $nominas = (new NominaService())->calcularParaTodos(self::MES, self::ANIO);

        $this->assertCount(1, $nominas);
        $this->assertEquals($activo->id, $nominas->first()->usuario_id);
    }
}
