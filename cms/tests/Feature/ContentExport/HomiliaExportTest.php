<?php

declare(strict_types=1);

namespace Tests\Feature\ContentExport;

use App\Models\Homilia;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HomiliaExportTest extends TestCase
{
    private string $dataDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataDir = base_path('../data');
    }

    public function TestShouldExportHomiliaPublicada(): void
    {
        Homilia::factory()->publicado()->create([
            'titulo' => 'Homilia do Domingo',
            'slug'   => 'homilia-do-domingo-test',
        ]);

        $this->artisan('content:export', ['--no-build' => true])->assertSuccessful();

        $this->assertFileExists($this->dataDir.'/homilias.json');
        $content = json_decode(File::get($this->dataDir.'/homilias.json'), true);
        $slugs = array_column($content['homilias'] ?? [], 'slug');

        $this->assertContains('homilia-do-domingo-test', $slugs);
    }

    public function TestShouldNotExportHomiliaRascunho(): void
    {
        Homilia::factory()->rascunho()->create(['slug' => 'homilia-rascunho-xyz']);

        $this->artisan('content:export', ['--no-build' => true])->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/homilias.json'), true);
        $slugs = array_column($content['homilias'] ?? [], 'slug');

        $this->assertNotContains('homilia-rascunho-xyz', $slugs);
    }
}
