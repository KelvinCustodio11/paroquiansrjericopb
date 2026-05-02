<?php

declare(strict_types=1);

namespace Tests\Feature\ContentExport;

use App\Models\Configuracao;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ConfiguracaoExportTest extends TestCase
{
    private string $dataDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataDir = base_path('../data');
    }

    /** @test */
    public function testShouldExportConfiguracaoToJson(): void
    {
        Configuracao::updateOrCreate(['id' => 1], [
            'cor_principal'     => '#acaa59',
            'hero_titulo'       => 'Bem-vindo',
            'footer_telefone'   => '(83) 99999-9999',
        ]);

        $this->artisan('content:export')->assertSuccessful();

        $this->assertFileExists($this->dataDir.'/configuracoes.json');
        $content = json_decode(File::get($this->dataDir.'/configuracoes.json'), true);

        $this->assertEquals('#acaa59', $content['cor_principal']);
        $this->assertEquals('Bem-vindo', $content['hero_titulo']);
    }

    /** @test */
    public function testShouldExportHabilitarFlagsInConfiguracaoJson(): void
    {
        Configuracao::updateOrCreate(['id' => 1], [
            'habilitar_santo_dia'     => true,
            'habilitar_evangelho_dia' => true,
            'habilitar_terco_dia'     => false,
            'habilitar_testemunhos'   => true,
        ]);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/configuracoes.json'), true);

        $this->assertTrue($content['habilitar_santo_dia']);
        $this->assertTrue($content['habilitar_evangelho_dia']);
        $this->assertFalse($content['habilitar_terco_dia']);
        $this->assertTrue($content['habilitar_testemunhos']);
    }

    /** @test */
    public function testShouldExportContatoCoordenasInConfiguracaoJson(): void
    {
        Configuracao::updateOrCreate(['id' => 1], [
            'contato_coordenadas_lat' => '-6.5321',
            'contato_coordenadas_lng' => '-37.8475',
            'contato_maps_url'        => 'https://www.google.com/maps/embed?pb=test',
        ]);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/configuracoes.json'), true);

        $this->assertEquals('-6.5321', $content['contato_coordenadas_lat']);
        $this->assertEquals('-37.8475', $content['contato_coordenadas_lng']);
    }

    /** @test */
    public function testShouldExportHeroTitulosAsArrayInConfiguracaoJson(): void
    {
        Configuracao::updateOrCreate(['id' => 1], [
            'hero_titulos' => [
                ['texto' => 'Fé e Amor', 'cor' => '', 'efeito' => 'fade', 'duracao' => 4000],
                ['texto' => 'Esperança', 'cor' => '', 'efeito' => 'fade', 'duracao' => 4000],
            ],
        ]);

        $this->artisan('content:export')->assertSuccessful();

        $content = json_decode(File::get($this->dataDir.'/configuracoes.json'), true);

        $this->assertIsArray($content['hero_titulos']);
        $this->assertCount(2, $content['hero_titulos']);
        $this->assertEquals('Fé e Amor', $content['hero_titulos'][0]['texto']);
    }
}
