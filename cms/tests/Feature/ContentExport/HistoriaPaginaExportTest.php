<?php

declare(strict_types=1);

namespace Tests\Feature\ContentExport;

use App\Models\HistoriaPagina;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testes de integração: exportação de historia.json a partir de HistoriaPagina.
 *
 * Cobre:
 *  - Geração de historia.json pelo comando content:export
 *  - JSON válido e com todas as chaves esperadas
 *  - Valores escalares exportados corretamente
 *  - Arrays enriquecidos (vm_abas, topicos, servicos, membros, faqs, imagens)
 *  - Fallbacks com modelo vazio
 *  - Idempotência (re-exportação não corrompe arquivo)
 */
class HistoriaPaginaExportTest extends TestCase
{
    private string $dataDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dataDir = rtrim(config('site.root'), '/') . '/data';
    }

    // -------------------------------------------------------------------------
    // Geração do arquivo
    // -------------------------------------------------------------------------

    #[Test]
    public function exportGeraHistoriaJson(): void
    {
        HistoriaPagina::factory()->create();

        $this->artisan('content:export')->assertSuccessful();

        $this->assertFileExists($this->dataDir.'/historia.json');
    }

    #[Test]
    public function exportGeraJsonValido(): void
    {
        HistoriaPagina::factory()->create();

        $this->artisan('content:export')->assertSuccessful();

        $json = json_decode(File::get($this->dataDir.'/historia.json'), true);
        $this->assertNotNull($json, 'historia.json contém JSON inválido: '.json_last_error_msg());
    }

    #[Test]
    public function exportGeraHistoriaJsonMesmoComModelVazio(): void
    {
        // current() cria registro em branco se não existir
        HistoriaPagina::create(['id' => 1]);

        $this->artisan('content:export')->assertSuccessful();

        $this->assertFileExists($this->dataDir.'/historia.json');
    }

    // -------------------------------------------------------------------------
    // Campos escalares
    // -------------------------------------------------------------------------

    #[Test]
    public function exportEscreveSeoTituloEmHistoriaJson(): void
    {
        HistoriaPagina::factory()->create(['seo_titulo' => 'Título SEO Exportado']);

        $this->artisan('content:export')->assertSuccessful();

        $data = json_decode(File::get($this->dataDir.'/historia.json'), true);
        $this->assertEquals('Título SEO Exportado', $data['meta_titulo']);
    }

    #[Test]
    public function exportEscrevePageTituloEmHistoriaJson(): void
    {
        HistoriaPagina::factory()->create(['page_titulo' => 'Página Exportada']);

        $this->artisan('content:export')->assertSuccessful();

        $data = json_decode(File::get($this->dataDir.'/historia.json'), true);
        $this->assertEquals('Página Exportada', $data['page_titulo']);
    }

    #[Test]
    public function exportEscreveMissaoTextoEmHistoriaJson(): void
    {
        HistoriaPagina::factory()->create(['missao_texto' => 'Texto da missão paroquial.']);

        $this->artisan('content:export')->assertSuccessful();

        $data = json_decode(File::get($this->dataDir.'/historia.json'), true);
        $this->assertEquals('Texto da missão paroquial.', $data['missao_texto']);
    }

    // -------------------------------------------------------------------------
    // Enriquecimento de arrays
    // -------------------------------------------------------------------------

    #[Test]
    public function exportEnriquecePrimeiraAbaDeVmAbas(): void
    {
        HistoriaPagina::factory()->create([
            'vm_abas' => [
                ['titulo' => 'Aba 1'],
                ['titulo' => 'Aba 2'],
            ],
        ]);

        $this->artisan('content:export')->assertSuccessful();

        $data = json_decode(File::get($this->dataDir.'/historia.json'), true);
        $abas = $data['vm_abas'];

        $this->assertTrue($abas[0]['ativo']);
        $this->assertFalse($abas[1]['ativo']);
        $this->assertEquals('vm-tab-1', $abas[0]['tab_id']);
        $this->assertEquals('vm-pane-1', $abas[0]['aba_id']);
    }

    #[Test]
    public function exportAdicionaAtrasoEmTopicos(): void
    {
        HistoriaPagina::factory()->create([
            'about_topicos' => [
                ['texto' => 'T1'],
                ['texto' => 'T2'],
            ],
        ]);

        $this->artisan('content:export')->assertSuccessful();

        $data    = json_decode(File::get($this->dataDir.'/historia.json'), true);
        $topicos = $data['topicos'];

        $this->assertNull($topicos[0]['atraso']);
        $this->assertEquals('0.25s', $topicos[1]['atraso']);
    }

    #[Test]
    public function exportNormalizaValoresImagensParaObjeto(): void
    {
        HistoriaPagina::factory()->create([
            'valores_imagens' => [
                'uploads/historia/img1.jpg',
                ['imagem' => 'uploads/historia/img2.jpg'],
            ],
        ]);

        $this->artisan('content:export')->assertSuccessful();

        $data    = json_decode(File::get($this->dataDir.'/historia.json'), true);
        $imagens = $data['valores_imagens'];

        $this->assertArrayHasKey('imagem', $imagens[0]);
        $this->assertEquals('uploads/historia/img1.jpg', $imagens[0]['imagem']);
        $this->assertEquals('uploads/historia/img2.jpg', $imagens[1]['imagem']);
    }

    #[Test]
    public function exportAdicionaAbertoPrimeiroFaq(): void
    {
        HistoriaPagina::factory()->create([
            'valores_faqs' => [
                ['pergunta' => 'Q1', 'resposta' => 'R1'],
                ['pergunta' => 'Q2', 'resposta' => 'R2'],
            ],
        ]);

        $this->artisan('content:export')->assertSuccessful();

        $data = json_decode(File::get($this->dataDir.'/historia.json'), true);
        $faqs = $data['valores_faqs'];

        $this->assertTrue($faqs[0]['aberto']);
        $this->assertFalse($faqs[1]['aberto']);
        $this->assertEquals('heading1', $faqs[0]['heading_id']);
        $this->assertEquals('faq1',     $faqs[0]['faq_id']);
    }

    // -------------------------------------------------------------------------
    // Todas as chaves presentes
    // -------------------------------------------------------------------------

    #[Test]
    public function historiaJsonContemTodasAsChavesEsperadas(): void
    {
        HistoriaPagina::factory()->create();

        $this->artisan('content:export')->assertSuccessful();

        $data = json_decode(File::get($this->dataDir.'/historia.json'), true);

        $chaves = [
            'meta_titulo', 'meta_descricao',
            'page_titulo', 'breadcrumb_atual',
            'about_subtitulo', 'about_titulo', 'about_intro1', 'about_intro2',
            'about_imagem1', 'about_imagem2', 'topicos',
            'missao_subtitulo', 'missao_titulo', 'missao_subtexto', 'missao_texto',
            'missao_cta_href', 'missao_cta_texto', 'missao_imagem',
            'vm_subtitulo', 'vm_titulo', 'vm_abas',
            'contador_items',
            'servicos_subtitulo', 'servicos_titulo', 'servicos',
            'equipe_subtitulo', 'equipe_titulo', 'membros',
            'paroco_subtitulo', 'paroco_titulo', 'paroco_subtexto', 'paroco_texto',
            'paroco_imagem', 'paroco_assinatura', 'paroco_cargo',
            'valores_subtitulo', 'valores_titulo', 'valores_faqs', 'valores_imagens',
        ];

        foreach ($chaves as $chave) {
            $this->assertArrayHasKey($chave, $data, "Chave '{$chave}' ausente em historia.json.");
        }
    }

    // -------------------------------------------------------------------------
    // Fallbacks
    // -------------------------------------------------------------------------

    #[Test]
    public function historiaJsonComModelVazioUsaFallbacks(): void
    {
        HistoriaPagina::create(['id' => 1]);

        $this->artisan('content:export')->assertSuccessful();

        $data = json_decode(File::get($this->dataDir.'/historia.json'), true);

        $this->assertEquals('História da Paróquia', $data['meta_titulo']);
        $this->assertEquals('Nossa Trajetória',     $data['page_titulo']);
        $this->assertEquals('Nossa História',       $data['breadcrumb_atual']);
        $this->assertEquals('#',                    $data['missao_cta_href']);
        $this->assertEquals('Saiba mais',           $data['missao_cta_texto']);
    }

    // -------------------------------------------------------------------------
    // Idempotência
    // -------------------------------------------------------------------------

    #[Test]
    public function exportIdempotente(): void
    {
        HistoriaPagina::factory()->create(['seo_titulo' => 'Título Idempotente']);

        $this->artisan('content:export')->assertSuccessful();
        $conteudo1 = File::get($this->dataDir.'/historia.json');

        $this->artisan('content:export')->assertSuccessful();
        $conteudo2 = File::get($this->dataDir.'/historia.json');

        $this->assertEquals($conteudo1, $conteudo2);
    }
}
