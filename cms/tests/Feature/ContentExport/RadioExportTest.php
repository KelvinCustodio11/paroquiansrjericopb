<?php

declare(strict_types=1);

namespace Tests\Feature\ContentExport;

use App\Models\Radio;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RadioExportTest extends TestCase
{
    private string $dataDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataDir = base_path('../data');
    }

    /** @test */
    public function testShouldExportRadioWithCategoriaEstadoCidade(): void
    {
        Radio::factory()->create([
            'nome'      => 'Rádio Teste',
            'ativa'     => true,
            'categoria' => 'catolica',
            'estado'    => 'PB',
            'cidade'    => 'João Pessoa',
        ]);

        $this->artisan('content:export')->assertSuccessful();

        $this->assertFileExists($this->dataDir.'/radios.json');
        $content = json_decode(File::get($this->dataDir.'/radios.json'), true);
        $radio = collect($content['radios'] ?? [])->firstWhere('nome', 'Rádio Teste');

        $this->assertNotNull($radio);
        $this->assertEquals('catolica', $radio['categoria']);
        $this->assertEquals('PB', $radio['estado']);
        $this->assertEquals('João Pessoa', $radio['cidade']);
    }

    /** @test */
    public function testShouldNotExportRadioInativa(): void
    {
        Radio::factory()->create(['nome' => 'Rádio Inativa', 'ativa' => false]);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/radios.json'), true);
        $nomes = array_column($content['radios'] ?? [], 'nome');

        $this->assertNotContains('Rádio Inativa', $nomes);
    }

    /** @test */
    public function testShouldExportRadioOrderedByDestaqueDesc(): void
    {
        Radio::factory()->create(['nome' => 'Normal', 'ativa' => true, 'destaque' => false, 'ordem' => 2]);
        Radio::factory()->create(['nome' => 'Destaque', 'ativa' => true, 'destaque' => true, 'ordem' => 1]);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/radios.json'), true);
        $nomes = array_column($content['radios'] ?? [], 'nome');
        $posDestaque = array_search('Destaque', $nomes);
        $posNormal = array_search('Normal', $nomes);

        $this->assertLessThan($posNormal, $posDestaque, 'Destaque deve aparecer antes de Normal.');
    }
}
