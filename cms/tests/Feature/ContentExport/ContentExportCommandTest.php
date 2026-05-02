<?php

declare(strict_types=1);

namespace Tests\Feature\ContentExport;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ContentExportCommandTest extends TestCase
{
    private string $dataDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataDir = base_path('../data');
    }

    /** @test */
    public function testShouldRunContentExportCommandWithoutError(): void
    {
        $exitCode = Artisan::call('content:export');

        $this->assertEquals(0, $exitCode);
    }

    /** @test */
    public function testShouldCreateDataDirectoryIfNotExists(): void
    {
        // Garantir que o diretório existe (o próprio command cria)
        $this->artisan('content:export')->assertSuccessful();

        $this->assertDirectoryExists($this->dataDir);
    }

    /** @test */
    public function testShouldExportAllRequiredJsonFiles(): void
    {
        $this->artisan('content:export')->assertSuccessful();

        $expectedFiles = [
            'artigos.json',
            'eventos.json',
            'homilias.json',
            'ministerios.json',
            'radios.json',
            'configuracoes.json',
            'galeria.json',
            'paroco.json',
            'horarios-missa.json',
            'menu.json',
            'testemunhos.json',
        ];

        foreach ($expectedFiles as $file) {
            $this->assertFileExists(
                $this->dataDir.'/'.$file,
                "Arquivo '{$file}' não foi gerado pelo content:export."
            );
        }
    }

    /** @test */
    public function testShouldGenerateValidJsonInAllExportedFiles(): void
    {
        $this->artisan('content:export')->assertSuccessful();

        foreach (File::files($this->dataDir) as $file) {
            if ($file->getExtension() !== 'json') {
                continue;
            }
            $json = json_decode(File::get($file->getPathname()));
            $this->assertNotNull(
                $json,
                "Arquivo '{$file->getFilename()}' contém JSON inválido: ".json_last_error_msg()
            );
        }
    }
}
