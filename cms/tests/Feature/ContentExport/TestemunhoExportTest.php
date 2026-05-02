<?php

declare(strict_types=1);

namespace Tests\Feature\ContentExport;

use App\Models\Testemunho;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TestemunhoExportTest extends TestCase
{
    private string $dataDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataDir = base_path('../data');
    }

    /** @test */
    public function testShouldExportAprovadoTestemunho(): void
    {
        Testemunho::factory()->aprovado()->create([
            'nome'  => 'Maria Silva',
            'texto' => 'Testemunho de aprovação.',
        ]);

        $this->artisan('content:export')->assertSuccessful();

        $this->assertFileExists($this->dataDir.'/testemunhos.json');
        $content = json_decode(File::get($this->dataDir.'/testemunhos.json'), true);
        $nomes = array_column($content['testemunhos'] ?? [], 'nome');

        $this->assertContains('Maria Silva', $nomes);
    }

    /** @test */
    public function testShouldNotExportPendenteTestemunho(): void
    {
        Testemunho::factory()->pendente()->create(['nome' => 'João Pendente']);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/testemunhos.json'), true);
        $nomes = array_column($content['testemunhos'] ?? [], 'nome');

        $this->assertNotContains('João Pendente', $nomes);
    }

    /** @test */
    public function testShouldNotExportRejeitadoTestemunho(): void
    {
        Testemunho::factory()->rejeitado()->create(['nome' => 'Pedro Rejeitado']);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/testemunhos.json'), true);
        $nomes = array_column($content['testemunhos'] ?? [], 'nome');

        $this->assertNotContains('Pedro Rejeitado', $nomes);
    }

    /** @test */
    public function testShouldNotExportEmailInTestemunhoJson(): void
    {
        Testemunho::factory()->aprovado()->create([
            'nome'  => 'Ana LGPD',
            'email' => 'ana.lgpd@example.com',
        ]);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/testemunhos.json'), true);
        $testemunho = collect($content['testemunhos'] ?? [])->firstWhere('nome', 'Ana LGPD');

        $this->assertNotNull($testemunho);
        $this->assertArrayNotHasKey('email', $testemunho, 'E-mail NÃO deve ser exportado por LGPD.');
    }

    /** @test */
    public function testShouldExportTestemunhosOrderedByAprovadoEmDesc(): void
    {
        Testemunho::factory()->aprovado()->create(['nome' => 'Antigo', 'aprovado_em' => now()->subDays(10)]);
        Testemunho::factory()->aprovado()->create(['nome' => 'Recente', 'aprovado_em' => now()]);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/testemunhos.json'), true);
        $nomes = array_column($content['testemunhos'] ?? [], 'nome');
        $posRecente = array_search('Recente', $nomes);
        $posAntigo = array_search('Antigo', $nomes);

        $this->assertLessThan($posAntigo, $posRecente, 'Aprovação mais recente deve aparecer primeiro.');
    }
}
