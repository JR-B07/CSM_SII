<?php

namespace Tests\Feature;

use App\Models\Formato;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FormatoTest extends TestCase
{
    use RefreshDatabase;

    public function test_cualquier_usuario_autenticado_puede_subir_un_formato(): void
    {
        Storage::fake('local');

        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $response = $this->actingAs($rescatista)->postJson('/formatos/upload', [
            'nombre' => 'Checklist de rescate vertical',
            'tipo' => 'checklist',
            'archivo' => UploadedFile::fake()->create('checklist.pdf', 100, 'application/pdf'),
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('formatos', [
            'nombre' => 'Checklist de rescate vertical',
            'tipo' => 'checklist',
            'subido_por' => $rescatista->id,
        ]);
    }

    public function test_no_se_aceptan_archivos_fuera_de_pdf_o_imagen(): void
    {
        Storage::fake('local');

        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $response = $this->actingAs($rescatista)->postJson('/formatos/upload', [
            'nombre' => 'Formato inválido',
            'tipo' => 'checklist',
            'archivo' => UploadedFile::fake()->create('formato.docx', 100),
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('formatos', ['nombre' => 'Formato inválido']);
    }

    public function test_cualquier_usuario_autenticado_puede_listar_y_filtrar_formatos(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        Formato::create(['nombre' => 'Checklist A', 'tipo' => 'checklist', 'nombre_archivo' => 'a.pdf', 'ruta_archivo' => 'formatos/a.pdf', 'subido_por' => $rescatista->id]);
        Formato::create(['nombre' => 'Trigger B', 'tipo' => 'trigger', 'nombre_archivo' => 'b.pdf', 'ruta_archivo' => 'formatos/b.pdf', 'subido_por' => $rescatista->id]);

        $response = $this->actingAs($rescatista)->getJson('/formatos/data?tipo=checklist');

        $response->assertOk();
        $response->assertJsonCount(1, 'formatos');
        $response->assertJsonFragment(['nombre' => 'Checklist A']);
    }

    public function test_el_autor_puede_eliminar_su_propio_formato(): void
    {
        Storage::fake('local');

        $rescatista = Usuario::factory()->rol('rescatista')->create();
        $formato = Formato::create(['nombre' => 'Checklist A', 'tipo' => 'checklist', 'nombre_archivo' => 'a.pdf', 'ruta_archivo' => 'formatos/a.pdf', 'subido_por' => $rescatista->id]);

        $this->actingAs($rescatista)->deleteJson("/formatos/{$formato->id}")->assertOk();
        $this->assertSoftDeleted('formatos', ['id' => $formato->id]);
    }

    public function test_otro_rescatista_no_puede_eliminar_un_formato_ajeno(): void
    {
        $autor = Usuario::factory()->rol('rescatista')->create();
        $otro = Usuario::factory()->rol('rescatista')->create();
        $formato = Formato::create(['nombre' => 'Checklist A', 'tipo' => 'checklist', 'nombre_archivo' => 'a.pdf', 'ruta_archivo' => 'formatos/a.pdf', 'subido_por' => $autor->id]);

        $this->actingAs($otro)->deleteJson("/formatos/{$formato->id}")->assertForbidden();
    }

    public function test_rh_puede_eliminar_cualquier_formato(): void
    {
        $autor = Usuario::factory()->rol('rescatista')->create();
        $rh = Usuario::factory()->rol('RH')->create();
        $formato = Formato::create(['nombre' => 'Checklist A', 'tipo' => 'checklist', 'nombre_archivo' => 'a.pdf', 'ruta_archivo' => 'formatos/a.pdf', 'subido_por' => $autor->id]);

        $this->actingAs($rh)->deleteJson("/formatos/{$formato->id}")->assertOk();
    }

    public function test_subida_de_formato_queda_auditada(): void
    {
        Storage::fake('local');

        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $this->actingAs($rescatista)->postJson('/formatos/upload', [
            'nombre' => 'Trigger de emergencia',
            'tipo' => 'trigger',
            'archivo' => UploadedFile::fake()->create('trigger.pdf', 100),
        ]);

        $formato = Formato::where('nombre', 'Trigger de emergencia')->first();

        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $rescatista->id,
            'accion' => 'creado',
            'tabla_afectada' => 'formatos',
            'registro_id' => $formato->id,
        ]);
    }
}
