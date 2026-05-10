<?php

declare(strict_types=1);

namespace Tests\Feature\ContentExport;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContentExportCommandTest extends TestCase
{
    private string $dataDir;

    protected function setUp(): void
    {
        parent::setUp();
        // SITE_ROOT é /tmp/paroquia-test-site em teste (phpunit.xml)
        $this->dataDir = rtrim(config('site.root'), '/') . '/data';
    }

    #[Test]
    public function testShouldRunContentExportCommandWithoutError(): void
    {
        $exitCode = Artisan::call('content:export');

        $this->assertEquals(0, $exitCode);
    }

    #[Test]
    public function testShouldCreateDataDirectoryIfNotExists(): void
    {
        // Garantir que o diretório existe (o próprio command cria)
        $this->artisan('content:export')->assertSuccessful();

        $this->assertDirectoryExists($this->dataDir);
    }

    #[Test]
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
            'horarios-missa.json',
            'menu.json',
            'testemunhos.json',
            'historia.json',
        ];

        foreach ($expectedFiles as $file) {
            $this->assertFileExists(
                $this->dataDir.'/'.$file,
                "Arquivo '{$file}' não foi gerado pelo content:export."
            );
        }
    }

    #[Test]
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
