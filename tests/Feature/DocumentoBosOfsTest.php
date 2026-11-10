<?php

namespace Tests\Feature;

use App\Models\DocumentoBosOfs;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentoBosOfsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cualquier_usuario_autenticado_puede_subir_un_documento(): void
    {
        Storage::fake('local');

        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $response = $this->actingAs($rescatista)->postJson('/documentos-bos-ofs/upload', [
            'nombre' => 'Reporte BOS septiembre',
            'tipo' => 'BOS',
            'mes' => '2026-09',
            'archivo' => UploadedFile::fake()->create('reporte.pdf', 100, 'application/pdf'),
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('documentos_bos_ofs', [
            'nombre' => 'Reporte BOS septiembre',
            'tipo' => 'BOS',
            'mes' => '2026-09',
            'subido_por' => $rescatista->id,
        ]);
    }

    public function test_se_aceptan_archivos_de_word_y_excel(): void
    {
        Storage::fake('local');

        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $response = $this->actingAs($rescatista)->postJson('/documentos-bos-ofs/upload', [
            'nombre' => 'Reporte OFS en Excel',
            'tipo' => 'OFS',
            'mes' => '2026-09',
            'archivo' => UploadedFile::fake()->create('reporte.xlsx', 100),
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('documentos_bos_ofs', ['nombre' => 'Reporte OFS en Excel']);
    }

    public function test_no_se_acepta_un_tipo_fuera_de_bos_u_ofs(): void
    {
        Storage::fake('local');

        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $response = $this->actingAs($rescatista)->postJson('/documentos-bos-ofs/upload', [
            'nombre' => 'Reporte inválido',
            'tipo' => 'OTRO',
            'mes' => '2026-09',
            'archivo' => UploadedFile::fake()->create('reporte.pdf', 100),
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('documentos_bos_ofs', ['nombre' => 'Reporte inválido']);
    }

    public function test_no_se_acepta_un_mes_con_formato_invalido(): void
    {
        Storage::fake('local');

        $rescatista = Usuario::factory()->rol('rescatista')->create();

        $response = $this->actingAs($rescatista)->postJson('/documentos-bos-ofs/upload', [
            'nombre' => 'Reporte inválido',
            'tipo' => 'BOS',
            'mes' => 'septiembre 2026',
            'archivo' => UploadedFile::fake()->create('reporte.pdf', 100),
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('documentos_bos_ofs', ['nombre' => 'Reporte inválido']);
    }

    public function test_cualquier_usuario_autenticado_puede_listar_y_filtrar_por_tipo(): void
    {
        $rescatista = Usuario::factory()->rol('rescatista')->create();

        DocumentoBosOfs::create(['nombre' => 'BOS A', 'tipo' => 'BOS', 'mes' => '2026-09', 'nombre_archivo' => 'a.pdf', 'ruta_archivo' => 'documentos-bos-ofs/a.pdf', 'subido_por' => $rescatista->id]);
        DocumentoBosOfs::create(['nombre' => 'OFS B', 'tipo' => 'OFS', 'mes' => '2026-09', 'nombre_archivo' => 'b.pdf', 'ruta_archivo' => 'documentos-bos-ofs/b.pdf', 'subido_por' => $rescatista->id]);

        $response = $this->actingAs($rescatista)->getJson('/documentos-bos-ofs/data?tipo=BOS');

        $response->assertOk();
        $response->assertJsonCount(1, 'documentos');
        $response->assertJsonFragment(['nombre' => 'BOS A']);
    }

    public function test_el_autor_puede_eliminar_su_propio_documento(): void
    {
        Storage::fake('local');

        $rescatista = Usuario::factory()->rol('rescatista')->create();
        $documento = DocumentoBosOfs::create(['nombre' => 'BOS A', 'tipo' => 'BOS', 'mes' => '2026-09', 'nombre_archivo' => 'a.pdf', 'ruta_archivo' => 'documentos-bos-ofs/a.pdf', 'subido_por' => $rescatista->id]);

        $this->actingAs($rescatista)->deleteJson("/documentos-bos-ofs/{$documento->id}")->assertOk();
        $this->assertSoftDeleted('documentos_bos_ofs', ['id' => $documento->id]);
    }

    public function test_otro_rescatista_no_puede_eliminar_un_documento_ajeno(): void
    {
        $autor = Usuario::factory()->rol('rescatista')->create();
        $otro = Usuario::factory()->rol('rescatista')->create();
        $documento = DocumentoBosOfs::create(['nombre' => 'BOS A', 'tipo' => 'BOS', 'mes' => '2026-09', 'nombre_archivo' => 'a.pdf', 'ruta_archivo' => 'documentos-bos-ofs/a.pdf', 'subido_por' => $autor->id]);

        $this->actingAs($otro)->deleteJson("/documentos-bos-ofs/{$documento->id}")->assertForbidden();
    }

    public function test_rh_puede_eliminar_cualquier_documento(): void
    {
        $autor = Usuario::factory()->rol('rescatista')->create();
        $rh = Usuario::factory()->rol('RH')->create();
        $documento = DocumentoBosOfs::create(['nombre' => 'BOS A', 'tipo' => 'BOS', 'mes' => '2026-09', 'nombre_archivo' => 'a.pdf', 'ruta_archivo' => 'documentos-bos-ofs/a.pdf', 'subido_por' => $autor->id]);

        $this->actingAs($rh)->deleteJson("/documentos-bos-ofs/{$documento->id}")->assertOk();
    }
}
