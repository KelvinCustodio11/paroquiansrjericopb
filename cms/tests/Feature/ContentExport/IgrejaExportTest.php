<?php

declare(strict_types=1);

namespace Tests\Feature\ContentExport;

use App\Models\Igreja;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class IgrejaExportTest extends TestCase
{
    private string $dataDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataDir = base_path('../data');
    }

    public function TestShouldExportHistoriaJson(): void
    {
        Igreja::factory()->create([
            'ativa'              => true,
            'historia_titulo'    => 'Nossa Trajetória',
            'historia_subtitulo' => '200 anos de fé',
            'historia_secoes'    => [
                ['titulo' => 'Fundação', 'texto' => 'Fundada em 1800...', 'imagem' => ''],
            ],
        ]);

        $this->artisan('content:export', ['--no-build' => true])->assertSuccessful();

        $this->assertFileExists($this->dataDir.'/historia.json');
        $content = json_decode(File::get($this->dataDir.'/historia.json'), true);

        $this->assertEquals('Nossa Trajetória', $content['titulo']);
        $this->assertEquals('200 anos de fé', $content['subtitulo']);
        $this->assertCount(1, $content['secoes']);
        $this->assertEquals('Fundação', $content['secoes'][0]['titulo']);
    }

    public function TestShouldNotExportHistoriaWhenHistoriaSecoesIsEmpty(): void
    {
        Igreja::factory()->create([
            'ativa'           => true,
            'historia_secoes' => null,
        ]);

        // Remover arquivo previamente existente para garantir o teste
        if (File::exists($this->dataDir.'/historia.json')) {
            File::delete($this->dataDir.'/historia.json');
        }

        $this->artisan('content:export', ['--no-build' => true])->assertSuccessful();

        $this->assertFileDoesNotExist($this->dataDir.'/historia.json');
    }

    public function TestShouldIncludeAllSecoesFieldsInHistoriaJson(): void
    {
        Igreja::factory()->create([
            'ativa'           => true,
            'historia_secoes' => [
                ['titulo' => 'Século XIX', 'texto' => 'Texto longo...', 'imagem' => 'images/historia1.jpg'],
            ],
        ]);

        $this->artisan('content:export', ['--no-build' => true])->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/historia.json'), true);
        $secao = $content['secoes'][0] ?? [];

        foreach (['titulo', 'texto', 'imagem'] as $campo) {
            $this->assertArrayHasKey($campo, $secao, "Campo '{$campo}' ausente em historia.json[secoes].");
        }
    }
}
