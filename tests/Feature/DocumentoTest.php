<?php

namespace Tests\Feature;

use App\Models\Documento;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentoTest extends TestCase
{
    use RefreshDatabase;

    public function test_rescatista_puede_subir_documentacion_de_su_proyecto(): void
    {
        Storage::fake('local');

        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $response = $this->actingAs($rescatista)->postJson('/documentos/upload', [
            'cliente' => 'CFE',
            'proyecto' => 'Planta Norte',
            'archivo' => UploadedFile::fake()->create('bitacora.pdf', 200, 'application/pdf'),
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('documentos', [
            'usuario_id' => $rescatista->id,
            'cliente' => 'CFE',
            'proyecto' => 'Planta Norte',
        ]);
    }

    public function test_rescatista_solo_ve_sus_propios_documentos(): void
    {
        Storage::fake('local');

        $rescatistaA = Usuario::factory()->rol('rescatista')->create();
        $rescatistaB = Usuario::factory()->rol('rescatista')->create();

        Documento::create([
            'nombre' => 'doc-a.pdf', 'ruta_archivo' => 'documentos/a/doc-a.pdf', 'tipo' => 'PDF',
            'tamano' => 100, 'usuario_id' => $rescatistaA->id, 'version' => 1,
        ]);
        Documento::create([
            'nombre' => 'doc-b.pdf', 'ruta_archivo' => 'documentos/b/doc-b.pdf', 'tipo' => 'PDF',
            'tamano' => 100, 'usuario_id' => $rescatistaB->id, 'version' => 1,
        ]);

        $response = $this->actingAs($rescatistaA)->getJson('/documentos/data');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['nombre' => 'doc-a.pdf']);
    }

    public function test_rh_ve_todos_los_documentos(): void
    {
        Storage::fake('local');

        $rh = Usuario::factory()->rol('RH')->create();
        $rescatistaA = Usuario::factory()->rol('rescatista')->create();
        $rescatistaB = Usuario::factory()->rol('rescatista')->create();

        Documento::create([
            'nombre' => 'doc-a.pdf', 'ruta_archivo' => 'documentos/a/doc-a.pdf', 'tipo' => 'PDF',
            'tamano' => 100, 'usuario_id' => $rescatistaA->id, 'version' => 1,
        ]);
        Documento::create([
            'nombre' => 'doc-b.pdf', 'ruta_archivo' => 'documentos/b/doc-b.pdf', 'tipo' => 'PDF',
            'tamano' => 100, 'usuario_id' => $rescatistaB->id, 'version' => 1,
        ]);

        $this->actingAs($rh)->getJson('/documentos/data')->assertJsonCount(2, 'data');
    }

    public function test_rescatista_no_puede_descargar_documento_de_otro_sin_permiso(): void
    {
        Storage::fake('local');

        $rescatistaA = Usuario::factory()->rol('rescatista')->create();
        $rescatistaB = Usuario::factory()->rol('rescatista')->create();

        $documento = Documento::create([
            'nombre' => 'doc-b.pdf', 'ruta_archivo' => 'documentos/b/doc-b.pdf', 'tipo' => 'PDF',
            'tamano' => 100, 'usuario_id' => $rescatistaB->id, 'version' => 1,
        ]);

        $this->actingAs($rescatistaA)
            ->get("/documentos/{$documento->id}/download")
            ->assertForbidden();
    }

    public function test_cualquier_usuario_autenticado_puede_ver_la_vista_de_documentos(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $this->actingAs($rescatista)->get('/documentos')->assertOk();
    }

    public function test_visitante_no_autenticado_no_puede_ver_documentos(): void
    {
        $this->get('/documentos')->assertRedirect(route('login'));
    }
}
