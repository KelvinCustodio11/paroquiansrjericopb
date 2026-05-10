<?php

declare(strict_types=1);

namespace Tests\Feature\ContentExport;

use App\Models\MenuItem;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MenuItemExportTest extends TestCase
{
    private string $dataDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataDir = rtrim(config('site.root'), '/') . '/data';
    }

    #[Test]
    public function testShouldExportMenuItemsToJson(): void
    {
        MenuItem::factory()->create(['titulo' => 'Início', 'link' => 'index.html', 'visivel' => true, 'ordem' => 1]);

        $this->artisan('content:export')->assertSuccessful();

        $this->assertFileExists($this->dataDir.'/menu.json');
        $content = json_decode(File::get($this->dataDir.'/menu.json'), true);

        $this->assertArrayHasKey('items', $content);
        $titulos = array_column($content['items'], 'titulo');
        $this->assertContains('Início', $titulos);
    }

    #[Test]
    public function testShouldNotExportMenuItemInvisivel(): void
    {
        MenuItem::factory()->create(['titulo' => 'Oculto', 'visivel' => false]);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/menu.json'), true);
        $titulos = array_column($content['items'] ?? [], 'titulo');

        $this->assertNotContains('Oculto', $titulos);
    }

    #[Test]
    public function testShouldExportMenuItemWithFilhosNested(): void
    {
        $pai = MenuItem::factory()->create(['titulo' => 'Sobre', 'visivel' => true, 'pai_id' => null]);
        MenuItem::factory()->create(['titulo' => 'Nossa História', 'visivel' => true, 'pai_id' => $pai->id]);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/menu.json'), true);
        $paiItem = collect($content['items'] ?? [])->firstWhere('titulo', 'Sobre');

        $this->assertNotNull($paiItem);
        $this->assertNotEmpty($paiItem['filhos'] ?? []);
        $titulos = array_column($paiItem['filhos'], 'titulo');
        $this->assertContains('Nossa História', $titulos);
    }

    #[Test]
    public function testShouldExportMenuItemsOrderedByOrdem(): void
    {
        MenuItem::factory()->create(['titulo' => 'Último', 'visivel' => true, 'ordem' => 99]);
        MenuItem::factory()->create(['titulo' => 'Primeiro', 'visivel' => true, 'ordem' => 1]);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/menu.json'), true);
        $titulos = array_column($content['items'] ?? [], 'titulo');
        $posPrimeiro = array_search('Primeiro', $titulos);
        $posUltimo = array_search('Último', $titulos);

        $this->assertLessThan($posUltimo, $posPrimeiro, 'Item com ordem menor deve aparecer primeiro.');
    }
}
