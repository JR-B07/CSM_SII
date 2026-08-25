<?php

namespace Tests\Feature;

use App\Models\DocumentoLegal;
use App\Models\Usuario;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentoLegalTest extends TestCase
{
    use RefreshDatabase;

    public function test_rh_puede_subir_documento_legal_de_un_empleado(): void
    {
        Storage::fake('local');

        $rh = Usuario::factory()->rol('RH')->create();
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $response = $this->actingAs($rh)->postJson("/usuarios/{$rescatista->id}/documentos-legales", [
            'tipo' => 'IMSS',
            'numero_documento' => '12345678901',
            'archivo' => UploadedFile::fake()->create('imss.pdf', 100, 'application/pdf'),
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('documentos_legales', [
            'usuario_id' => $rescatista->id,
            'tipo' => 'IMSS',
            'numero_documento' => '12345678901',
            'subido_por' => $rh->id,
        ]);
    }

    public function test_rescatista_no_puede_subir_documentos_legales(): void
    {
        Storage::fake('local');

        $rescatistaA = Usuario::factory()->rol('rescatista')->create();
        $rescatistaB = Usuario::factory()->rol('rescatista')->create();

        $this->actingAs($rescatistaA)->postJson("/usuarios/{$rescatistaB->id}/documentos-legales", [
            'tipo' => 'INE',
            'archivo' => UploadedFile::fake()->create('ine.pdf', 100),
        ])->assertForbidden();
    }

    public function test_empleado_puede_ver_y_descargar_sus_propios_documentos(): void
    {
        Storage::fake('local');

        $rescatista = Usuario::factory()->rol('rescatista')->create();
        $rh = Usuario::factory()->rol('RH')->create();

        $documento = DocumentoLegal::create([
            'usuario_id' => $rescatista->id,
            'tipo' => 'RFC',
            'nombre_archivo' => 'rfc.pdf',
            'ruta_archivo' => 'documentos-legales/' . $rescatista->id . '/rfc.pdf',
            'subido_por' => $rh->id,
        ]);
        Storage::disk('local')->put($documento->ruta_archivo, 'contenido');

        $this->actingAs($rescatista)
            ->getJson("/usuarios/{$rescatista->id}/documentos-legales/data")
            ->assertOk()
            ->assertJsonCount(1, 'documentos');

        $this->actingAs($rescatista)
            ->get("/documentos-legales/{$documento->id}/download")
            ->assertOk();
    }

    public function test_empleado_no_puede_ver_documentos_legales_de_otro(): void
    {
        $rescatistaA = Usuario::factory()->rol('rescatista')->create();
        $rescatistaB = Usuario::factory()->rol('rescatista')->create();

        $this->actingAs($rescatistaA)
            ->getJson("/usuarios/{$rescatistaB->id}/documentos-legales/data")
            ->assertForbidden();
    }

    public function test_empleado_no_puede_descargar_documento_legal_de_otro(): void
    {
        Storage::fake('local');

        $rescatistaA = Usuario::factory()->rol('rescatista')->create();
        $rescatistaB = Usuario::factory()->rol('rescatista')->create();
        $rh = Usuario::factory()->rol('RH')->create();

        $documento = DocumentoLegal::create([
            'usuario_id' => $rescatistaB->id,
            'tipo' => 'INE',
            'nombre_archivo' => 'ine.pdf',
            'ruta_archivo' => 'documentos-legales/' . $rescatistaB->id . '/ine.pdf',
            'subido_por' => $rh->id,
        ]);
        Storage::disk('local')->put($documento->ruta_archivo, 'contenido');

        $this->actingAs($rescatistaA)
            ->get("/documentos-legales/{$documento->id}/download")
            ->assertForbidden();
    }

    public function test_subida_de_documento_legal_queda_auditada(): void
    {
        Storage::fake('local');

        $rh = Usuario::factory()->rol('RH')->create();
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $this->actingAs($rh)->postJson("/usuarios/{$rescatista->id}/documentos-legales", [
            'tipo' => 'CURP',
            'archivo' => UploadedFile::fake()->create('curp.pdf', 50),
        ]);

        $documento = DocumentoLegal::where('usuario_id', $rescatista->id)->first();

        $this->assertDatabaseHas('auditoria', [
            'usuario_id' => $rh->id,
            'accion' => 'creado',
            'tabla_afectada' => 'documentos_legales',
            'registro_id' => $documento->id,
        ]);
    }

    public function test_solo_rh_puede_eliminar_documento_legal(): void
    {
        Storage::fake('local');

        $rh = Usuario::factory()->rol('RH')->create();
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $documento = DocumentoLegal::create([
            'usuario_id' => $rescatista->id,
            'tipo' => 'otro',
            'nombre_archivo' => 'doc.pdf',
            'ruta_archivo' => 'documentos-legales/' . $rescatista->id . '/doc.pdf',
            'subido_por' => $rh->id,
        ]);

        $this->actingAs($rescatista)->deleteJson("/documentos-legales/{$documento->id}")->assertForbidden();

        $this->actingAs($rh)->deleteJson("/documentos-legales/{$documento->id}")->assertOk();
        $this->assertSoftDeleted('documentos_legales', ['id' => $documento->id]);
    }
}
