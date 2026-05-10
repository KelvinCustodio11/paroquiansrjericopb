<?php

declare(strict_types=1);

namespace Tests\Feature\ContentExport;

use App\Models\Igreja;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testa a exportação de igrejas e horários de missa para horarios-missa.json.
 * A história da paróquia agora é gerenciada pelo HistoriaPagina (ver HistoriaPaginaExportTest).
 */
class IgrejaExportTest extends TestCase
{
    private string $dataDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataDir = rtrim(config('site.root'), '/') . '/data';
    }

    #[Test]
    public function exportaIgrejaAtivaParaHorariosMissaJson(): void
    {
        Igreja::factory()->create([
            'slug'  => 'matriz-teste',
            'nome'  => 'Igreja Matriz Teste',
            'ativa' => true,
        ]);

        $this->artisan('content:export')->assertSuccessful();

        $this->assertFileExists($this->dataDir.'/horarios-missa.json');
        $content = json_decode(File::get($this->dataDir.'/horarios-missa.json'), true);
        $slugs = array_column($content['igrejas'] ?? [], 'slug');

        $this->assertContains('matriz-teste', $slugs);
    }

    #[Test]
    public function naoExportaIgrejaInativa(): void
    {
        Igreja::factory()->create([
            'slug'  => 'igreja-inativa-xyz',
            'ativa' => false,
        ]);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/horarios-missa.json'), true);
        $slugs = array_column($content['igrejas'] ?? [], 'slug');

        $this->assertNotContains('igreja-inativa-xyz', $slugs);
    }

    #[Test]
    public function horariosMissaJsonContemCamposObrigatorios(): void
    {
        Igreja::factory()->create(['slug' => 'matriz-campos-test', 'ativa' => true]);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/horarios-missa.json'), true);
        $igreja = collect($content['igrejas'] ?? [])->firstWhere('slug', 'matriz-campos-test');

        $this->assertNotNull($igreja);
        foreach (['slug', 'nome', 'tipo', 'horarios'] as $campo) {
            $this->assertArrayHasKey($campo, $igreja, "Campo '{$campo}' ausente em horarios-missa.json.");
        }
    }

    #[Test]
    public function horariosMissaJsonTemArrayDeHorarios(): void
    {
        Igreja::factory()->create(['slug' => 'matriz-horarios-test', 'ativa' => true]);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/horarios-missa.json'), true);
        $igreja = collect($content['igrejas'] ?? [])->firstWhere('slug', 'matriz-horarios-test');

        $this->assertIsArray($igreja['horarios']);
    }

    #[Test]
    public function igrejaModelNaoPossuiCamposDeHistoria(): void
    {
        $igreja = Igreja::factory()->create();

        $this->assertArrayNotHasKey('historia_titulo',    $igreja->toArray());
        $this->assertArrayNotHasKey('historia_subtitulo', $igreja->toArray());
        $this->assertArrayNotHasKey('historia_secoes',    $igreja->toArray());
    }
}
