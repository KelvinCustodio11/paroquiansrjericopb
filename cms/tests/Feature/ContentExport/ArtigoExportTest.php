<?php

declare(strict_types=1);

namespace Tests\Feature\ContentExport;

use App\Models\Artigo;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ArtigoExportTest extends TestCase
{
    private string $dataDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataDir = base_path('../data');
    }

    public function TestShouldExportArtigoToJson(): void
    {
        $artigo = Artigo::factory()->publicado()->create([
            'titulo' => 'Artigo de Teste',
            'slug'   => 'artigo-de-teste',
        ]);

        $this->artisan('content:export', ['--no-build' => true])->assertSuccessful();

        $this->assertFileExists($this->dataDir.'/artigos.json');
        $content = json_decode(File::get($this->dataDir.'/artigos.json'), true);
        $slugs = array_column($content['artigos'] ?? [], 'slug');

        $this->assertContains('artigo-de-teste', $slugs);
    }

    public function TestShouldNotExportUnpublishedArtigo(): void
    {
        $artigo = Artigo::factory()->rascunho()->create([
            'titulo' => 'Artigo Rascunho',
            'slug'   => 'artigo-rascunho-xyz',
        ]);

        $this->artisan('content:export', ['--no-build' => true])->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/artigos.json'), true);
        $slugs = array_column($content['artigos'] ?? [], 'slug');

        $this->assertNotContains('artigo-rascunho-xyz', $slugs);
    }

    public function TestShouldIncludeAllRequiredFieldsInArtigoJson(): void
    {
        Artigo::factory()->publicado()->create(['slug' => 'artigo-campos-test']);

        $this->artisan('content:export', ['--no-build' => true])->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/artigos.json'), true);
        $artigo = collect($content['artigos'] ?? [])->firstWhere('slug', 'artigo-campos-test');

        $this->assertNotNull($artigo);
        foreach (['slug', 'titulo', 'resumo', 'conteudo', 'data', 'imagem_capa'] as $campo) {
            $this->assertArrayHasKey($campo, $artigo, "Campo '{$campo}' ausente no export do artigo.");
        }
    }

    public function TestShouldExportArtigosOrderedByDateDesc(): void
    {
        Artigo::factory()->publicado()->create(['slug' => 'artigo-antigo', 'publicado_em' => now()->subDays(10)]);
        Artigo::factory()->publicado()->create(['slug' => 'artigo-recente', 'publicado_em' => now()]);

        $this->artisan('content:export', ['--no-build' => true])->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/artigos.json'), true);
        $slugs = array_column($content['artigos'] ?? [], 'slug');
        $posicoesRecente = array_search('artigo-recente', $slugs);
        $posicoesAntigo = array_search('artigo-antigo', $slugs);

        $this->assertLessThan($posicoesAntigo, $posicoesRecente, 'Artigo recente deve aparecer antes do antigo.');
    }
}
