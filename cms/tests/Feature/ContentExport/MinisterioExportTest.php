<?php

declare(strict_types=1);

namespace Tests\Feature\ContentExport;

use App\Models\Ministerio;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MinisterioExportTest extends TestCase
{
    private string $dataDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataDir = base_path('../data');
    }

    public function TestShouldExportMinisterioWithCategoria(): void
    {
        Ministerio::factory()->create([
            'nome'      => 'Ministério Teste',
            'ativo'     => true,
            'categoria' => 'ministerio',
        ]);

        $this->artisan('content:export', ['--no-build' => true])->assertSuccessful();

        $this->assertFileExists($this->dataDir.'/ministerios.json');
        $content = json_decode(File::get($this->dataDir.'/ministerios.json'), true);
        $ministerio = collect($content['ministerios'] ?? [])->firstWhere('nome', 'Ministério Teste');

        $this->assertNotNull($ministerio);
        $this->assertArrayHasKey('categoria', $ministerio);
        $this->assertEquals('ministerio', $ministerio['categoria']);
    }

    public function TestShouldNotExportMinisterioInativo(): void
    {
        Ministerio::factory()->create(['nome' => 'Ministerio Inativo', 'ativo' => false]);

        $this->artisan('content:export', ['--no-build' => true])->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/ministerios.json'), true);
        $nomes = array_column($content['ministerios'] ?? [], 'nome');

        $this->assertNotContains('Ministerio Inativo', $nomes);
    }

    public function TestShouldExportPastoralJsonSeparately(): void
    {
        Ministerio::factory()->create(['nome' => 'Catequese Teste', 'ativo' => true, 'categoria' => 'catequese']);
        Ministerio::factory()->create(['nome' => 'Ministerio de Louvor', 'ativo' => true, 'categoria' => 'ministerio']);

        $this->artisan('content:export', ['--no-build' => true])->assertSuccessful();

        $this->assertFileExists($this->dataDir.'/pastoral.json');
        $content = json_decode(File::get($this->dataDir.'/pastoral.json'), true);
        $nomes = array_column($content['itens'] ?? [], 'nome');

        $this->assertContains('Catequese Teste', $nomes, 'Catequese deve aparecer em pastoral.json');
        $this->assertNotContains('Ministerio de Louvor', $nomes, 'Ministério não deve aparecer em pastoral.json');
    }
}
