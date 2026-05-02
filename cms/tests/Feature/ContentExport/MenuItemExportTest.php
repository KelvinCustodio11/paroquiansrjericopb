<?php

declare(strict_types=1);

namespace Tests\Feature\ContentExport;

use App\Models\MenuItem;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MenuItemExportTest extends TestCase
{
    private string $dataDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataDir = base_path('../data');
    }

    /** @test */
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

    /** @test */
    public function testShouldNotExportMenuItemInvisivel(): void
    {
        MenuItem::factory()->create(['titulo' => 'Oculto', 'visivel' => false]);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/menu.json'), true);
        $titulos = array_column($content['items'] ?? [], 'titulo');

        $this->assertNotContains('Oculto', $titulos);
    }

    /** @test */
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

    /** @test */
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
