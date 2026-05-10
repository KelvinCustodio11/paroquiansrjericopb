<?php

declare(strict_types=1);

namespace Tests\Feature\ContentExport;

use App\Models\Evento;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EventoExportTest extends TestCase
{
    private string $dataDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataDir = rtrim(config('site.root'), '/') . '/data';
    }

    #[Test]
    public function testShouldExportEventoToJson(): void
    {
        Evento::factory()->publicado()->create([
            'titulo' => 'Evento de Teste',
            'slug'   => 'evento-de-teste',
        ]);

        $this->artisan('content:export')->assertSuccessful();

        $this->assertFileExists($this->dataDir.'/eventos.json');
        $content = json_decode(File::get($this->dataDir.'/eventos.json'), true);
        $slugs = array_column($content['eventos'] ?? [], 'slug');

        $this->assertContains('evento-de-teste', $slugs);
    }

    #[Test]
    public function testShouldNotExportRascunhoEvento(): void
    {
        Evento::factory()->rascunho()->create(['slug' => 'evento-rascunho-xyz']);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/eventos.json'), true);
        $slugs = array_column($content['eventos'] ?? [], 'slug');

        $this->assertNotContains('evento-rascunho-xyz', $slugs);
    }

    #[Test]
    public function testShouldIncludeRequiredFieldsInEventoJson(): void
    {
        Evento::factory()->publicado()->create(['slug' => 'evento-campos-test']);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/eventos.json'), true);
        $evento = collect($content['eventos'] ?? [])->firstWhere('slug', 'evento-campos-test');

        $this->assertNotNull($evento);
        foreach (['slug', 'titulo', 'data_inicio', 'local', 'imagem_capa'] as $campo) {
            $this->assertArrayHasKey($campo, $evento, "Campo '{$campo}' ausente no export do evento.");
        }
    }
}
