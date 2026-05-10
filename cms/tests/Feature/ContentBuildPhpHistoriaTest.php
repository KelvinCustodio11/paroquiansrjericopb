<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\HistoriaPagina;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testes de integração: command content:build-php gerando historia.html
 * a partir do template + JSON.
 *
 * Cada teste configura um diretório temporário isolado como SITE_ROOT para não
 * tocar nos arquivos reais do repositório durante a suite de testes.
 *
 * Cobre:
 *  - Geração de historia.html a partir de templates/historia.html + data/historia.json
 *  - Substituição de variáveis Mustache simples {{var}}
 *  - Substituição de seções {{#var}}...{{/var}} (arrays e truthy)
 *  - Seções invertidas {{^var}}...{{/var}}
 *  - Iteração de arrays indexados
 *  - HTML bruto {{{var}}}
 *  - Idempotência do build (sem escrita quando conteúdo inalterado)
 *  - Skip gracioso quando data/historia.json ou template ausente
 *  - Pipeline completo content:export --build produz historia.html válido
 */
class ContentBuildPhpHistoriaTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        parent::setUp();

        // Cria estrutura temporária isolada
        $this->tmpRoot = sys_get_temp_dir() . '/historia-build-test-' . uniqid();
        mkdir($this->tmpRoot . '/data',      0755, true);
        mkdir($this->tmpRoot . '/templates', 0755, true);
        mkdir($this->tmpRoot . '/partials',  0755, true);

        // Aponta SITE_ROOT para o diretório temporário
        config(['site.root' => $this->tmpRoot]);
    }

    protected function tearDown(): void
    {
        // IMPORTANTE: limpar filesystem ANTES de parent::tearDown()
        // pois parent destrói o container Laravel, tornando facades inacessíveis
        if (is_dir($this->tmpRoot)) {
            $this->deleteRecursive($this->tmpRoot);
        }

        parent::tearDown();
    }

    /**
     * Remove diretório recursivamente usando PHP puro (sem depender do container Laravel).
     */
    private function deleteRecursive(string $dir): void
    {
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->deleteRecursive($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers de fixture
    // ──────────────────────────────────────────────────────────────────────────

    private function escreveTemplate(string $conteudo): void
    {
        file_put_contents($this->tmpRoot . '/templates/historia.html', $conteudo);
    }

    /**
     * Copia o template real para o tmpRoot, permitindo testes end-to-end isolados.
     */
    private function copiaTemplateReal(): void
    {
        $realRoot = realpath(base_path('..'));
        $src = $realRoot . '/templates/historia.html';
        if (file_exists($src)) {
            copy($src, $this->tmpRoot . '/templates/historia.html');
        }
    }

    private function escreveJson(array $dados): void
    {
        file_put_contents(
            $this->tmpRoot . '/data/historia.json',
            json_encode($dados, JSON_UNESCAPED_UNICODE)
        );
    }

    private function lerSaida(): string
    {
        $path = $this->tmpRoot . '/historia.html';
        $this->assertFileExists($path, 'historia.html não foi gerado pelo build.');
        return file_get_contents($path);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Geração do arquivo
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function buildGeraHistoriaHtml(): void
    {
        $this->escreveTemplate('<h1>{{page_titulo}}</h1>');
        $this->escreveJson(['page_titulo' => 'Nossa História']);

        $this->artisan('content:build-php')->assertSuccessful();

        $this->assertFileExists($this->tmpRoot . '/historia.html');
    }

    #[Test]
    public function buildPulaSemErroQuandoDataFileAusente(): void
    {
        $this->escreveTemplate('<h1>{{page_titulo}}</h1>');
        // NÃO cria data/historia.json

        $exitCode = Artisan::call('content:build-php');

        $this->assertEquals(0, $exitCode);
        $this->assertFileDoesNotExist($this->tmpRoot . '/historia.html');
    }

    #[Test]
    public function buildPulaSemErroQuandoTemplateAusente(): void
    {
        $this->escreveJson(['page_titulo' => 'Título']);
        // NÃO cria templates/historia.html

        $exitCode = Artisan::call('content:build-php');

        $this->assertEquals(0, $exitCode);
        $this->assertFileDoesNotExist($this->tmpRoot . '/historia.html');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Substituição de variáveis simples {{var}}
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function buildSubstitiuiVariavelSimples(): void
    {
        $this->escreveTemplate('<h1>{{page_titulo}}</h1>');
        $this->escreveJson(['page_titulo' => 'Nossa Trajetória']);

        $this->artisan('content:build-php')->assertSuccessful();

        $this->assertStringContainsString('Nossa Trajetória', $this->lerSaida());
    }

    #[Test]
    public function buildSubstitiuiMultiplasVariaveis(): void
    {
        $this->escreveTemplate('<h1>{{page_titulo}}</h1><p>{{breadcrumb_atual}}</p>');
        $this->escreveJson([
            'page_titulo'      => 'Título da Página',
            'breadcrumb_atual' => 'Breadcrumb',
        ]);

        $this->artisan('content:build-php')->assertSuccessful();

        $saida = $this->lerSaida();
        $this->assertStringContainsString('Título da Página', $saida);
        $this->assertStringContainsString('Breadcrumb', $saida);
    }

    #[Test]
    public function buildEscapeHtmlEmVariavelSimples(): void
    {
        $this->escreveTemplate('<p>{{about_titulo}}</p>');
        $this->escreveJson(['about_titulo' => '<script>alert("xss")</script>']);

        $this->artisan('content:build-php')->assertSuccessful();

        $saida = $this->lerSaida();
        $this->assertStringNotContainsString('<script>', $saida);
        $this->assertStringContainsString('&lt;script&gt;', $saida);
    }

    #[Test]
    public function buildVariavelAusenteSubstitiuiVazio(): void
    {
        $this->escreveTemplate('<p>{{variavel_inexistente}}</p>');
        $this->escreveJson([]);

        $this->artisan('content:build-php')->assertSuccessful();

        $this->assertStringContainsString('<p></p>', $this->lerSaida());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HTML bruto {{{var}}}
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function buildNaoEscapeHtmlBruto(): void
    {
        $this->escreveTemplate('<div>{{{missao_texto}}}</div>');
        $this->escreveJson(['missao_texto' => '<p>Texto <strong>em negrito</strong></p>']);

        $this->artisan('content:build-php')->assertSuccessful();

        $saida = $this->lerSaida();
        $this->assertStringContainsString('<p>Texto <strong>em negrito</strong></p>', $saida);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Seções {{#var}}...{{/var}}
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function buildRenderizaSecaoQuandoVarTruthy(): void
    {
        $this->escreveTemplate('{{#vm_titulo}}<h2>{{vm_titulo}}</h2>{{/vm_titulo}}');
        $this->escreveJson(['vm_titulo' => 'Visão e Missão']);

        $this->artisan('content:build-php')->assertSuccessful();

        $this->assertStringContainsString('<h2>Visão e Missão</h2>', $this->lerSaida());
    }

    #[Test]
    public function buildOmiteSecaoQuandoVarFalsy(): void
    {
        $this->escreveTemplate('{{#vm_titulo}}<h2>{{vm_titulo}}</h2>{{/vm_titulo}}');
        $this->escreveJson(['vm_titulo' => '']);

        $this->artisan('content:build-php')->assertSuccessful();

        $this->assertStringNotContainsString('<h2>', $this->lerSaida());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Seções invertidas {{^var}}...{{/var}}
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function buildRenderizaSecaoInvertidaQuandoVarFalsy(): void
    {
        $this->escreveTemplate('{{^topicos}}<p>Sem tópicos</p>{{/topicos}}');
        $this->escreveJson(['topicos' => null]);

        $this->artisan('content:build-php')->assertSuccessful();

        $this->assertStringContainsString('<p>Sem tópicos</p>', $this->lerSaida());
    }

    #[Test]
    public function buildOmiteSecaoInvertidaQuandoVarTruthy(): void
    {
        $this->escreveTemplate('{{^topicos}}<p>Sem tópicos</p>{{/topicos}}');
        $this->escreveJson(['topicos' => [['texto' => 'Item']]]);

        $this->artisan('content:build-php')->assertSuccessful();

        $this->assertStringNotContainsString('<p>Sem tópicos</p>', $this->lerSaida());
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Iteração de arrays indexados
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function buildIteraArrayDeTopicos(): void
    {
        $this->escreveTemplate(
            '{{#topicos}}<li>{{texto}}</li>{{/topicos}}'
        );
        $this->escreveJson([
            'topicos' => [
                ['texto' => 'Item 1'],
                ['texto' => 'Item 2'],
                ['texto' => 'Item 3'],
            ],
        ]);

        $this->artisan('content:build-php')->assertSuccessful();

        $saida = $this->lerSaida();
        $this->assertStringContainsString('<li>Item 1</li>', $saida);
        $this->assertStringContainsString('<li>Item 2</li>', $saida);
        $this->assertStringContainsString('<li>Item 3</li>', $saida);
    }

    #[Test]
    public function buildIteraArrayDeVmAbas(): void
    {
        $this->escreveTemplate(
            '{{#vm_abas}}<div id="{{aba_id}}">{{titulo}}</div>{{/vm_abas}}'
        );
        $this->escreveJson([
            'vm_abas' => [
                ['aba_id' => 'vm-pane-1', 'titulo' => 'Visão',   'ativo' => true],
                ['aba_id' => 'vm-pane-2', 'titulo' => 'Missão',  'ativo' => false],
            ],
        ]);

        $this->artisan('content:build-php')->assertSuccessful();

        $saida = $this->lerSaida();
        $this->assertStringContainsString('id="vm-pane-1"', $saida);
        $this->assertStringContainsString('Visão', $saida);
        $this->assertStringContainsString('id="vm-pane-2"', $saida);
        $this->assertStringContainsString('Missão', $saida);
    }

    #[Test]
    public function buildIteraArrayDeContadorItems(): void
    {
        $this->escreveTemplate(
            '{{#contador_items}}<span>{{numero}}</span>{{/contador_items}}'
        );
        $this->escreveJson([
            'contador_items' => [
                ['numero' => '200+', 'legenda' => 'Anos'],
                ['numero' => '5000', 'legenda' => 'Fiéis'],
            ],
        ]);

        $this->artisan('content:build-php')->assertSuccessful();

        $saida = $this->lerSaida();
        $this->assertStringContainsString('200+', $saida);
        $this->assertStringContainsString('5000', $saida);
    }

    #[Test]
    public function buildIteraArrayDeServicos(): void
    {
        $this->escreveTemplate(
            '{{#servicos}}<h3>{{titulo}}</h3>{{/servicos}}'
        );
        $this->escreveJson([
            'servicos' => [
                ['titulo' => 'Liturgia',  'atraso' => null],
                ['titulo' => 'Catequese', 'atraso' => '0.25s'],
            ],
        ]);

        $this->artisan('content:build-php')->assertSuccessful();

        $saida = $this->lerSaida();
        $this->assertStringContainsString('Liturgia', $saida);
        $this->assertStringContainsString('Catequese', $saida);
    }

    #[Test]
    public function buildIteraArrayDeMembros(): void
    {
        $this->escreveTemplate(
            '{{#membros}}<p>{{nome}}</p>{{/membros}}'
        );
        $this->escreveJson([
            'membros' => [
                ['nome' => 'Pe. João', 'atraso' => null],
                ['nome' => 'Dn. Paulo', 'atraso' => '0.25s'],
            ],
        ]);

        $this->artisan('content:build-php')->assertSuccessful();

        $saida = $this->lerSaida();
        $this->assertStringContainsString('Pe. João', $saida);
        $this->assertStringContainsString('Dn. Paulo', $saida);
    }

    #[Test]
    public function buildIteraArrayDeValoresFaqs(): void
    {
        $this->escreveTemplate(
            '{{#valores_faqs}}<button id="{{heading_id}}">{{pergunta}}</button>{{/valores_faqs}}'
        );
        $this->escreveJson([
            'valores_faqs' => [
                ['heading_id' => 'heading1', 'pergunta' => 'O que nos move?', 'aberto' => true],
                ['heading_id' => 'heading2', 'pergunta' => 'Qual nossa visão?', 'aberto' => false],
            ],
        ]);

        $this->artisan('content:build-php')->assertSuccessful();

        $saida = $this->lerSaida();
        $this->assertStringContainsString('id="heading1"', $saida);
        $this->assertStringContainsString('O que nos move?', $saida);
        $this->assertStringContainsString('id="heading2"', $saida);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Idempotência
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function buildEIdempotente(): void
    {
        $this->escreveTemplate('<h1>{{page_titulo}}</h1>');
        $this->escreveJson(['page_titulo' => 'Idempotência']);

        $this->artisan('content:build-php')->assertSuccessful();
        $conteudo1 = file_get_contents($this->tmpRoot . '/historia.html');
        $mtime1    = filemtime($this->tmpRoot . '/historia.html');

        // Aguarda 1 segundo para distinguir mtimes em sistemas com resolução de 1s
        sleep(1);

        $this->artisan('content:build-php')->assertSuccessful();
        $conteudo2 = file_get_contents($this->tmpRoot . '/historia.html');
        $mtime2    = filemtime($this->tmpRoot . '/historia.html');

        $this->assertEquals($conteudo1, $conteudo2, 'Conteúdo mudou sem alteração nos dados.');
        $this->assertEquals($mtime1, $mtime2, 'Arquivo foi reescrito sem necessidade (não idempotente).');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Banner de geração automática
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function buildIncluiBannerDeGeracaoAutomatica(): void
    {
        $this->escreveTemplate('<p>conteudo</p>');
        $this->escreveJson([]);

        $this->artisan('content:build-php')->assertSuccessful();

        $saida = $this->lerSaida();
        $this->assertStringContainsString('GENERATED FROM data/historia.json', $saida);
        $this->assertStringContainsString('DO NOT EDIT MANUALLY', $saida);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Pipeline completo: content:export --build
    // ──────────────────────────────────────────────────────────────────────────

    #[Test]
    public function pipelineCompletoExportBuildGeraHistoriaHtml(): void
    {
        // Usa tmpRoot isolado com o template real para não contaminar data/ do site
        $this->copiaTemplateReal();

        HistoriaPagina::factory()->create([
            'seo_titulo'  => 'Teste Pipeline Completo',
            'page_titulo' => 'Página Gerada pelo Pipeline',
        ]);

        $this->artisan('content:export', ['--build' => true])->assertSuccessful();

        $this->assertFileExists($this->tmpRoot . '/historia.html');
    }

    #[Test]
    public function pipelineCompletoSubstitiuiVariaveisNoHtmlFinal(): void
    {
        // Usa tmpRoot isolado com o template real para não contaminar data/ do site
        $this->copiaTemplateReal();

        HistoriaPagina::factory()->create([
            'page_titulo'      => 'TITULO_UNICO_DE_TESTE_ABC123',
            'breadcrumb_atual' => 'BREADCRUMB_UNICO_TESTE_ABC123',
        ]);

        $this->artisan('content:export', ['--build' => true])->assertSuccessful();

        $saida = file_get_contents($this->tmpRoot . '/historia.html');
        $this->assertStringContainsString('TITULO_UNICO_DE_TESTE_ABC123', $saida);
        $this->assertStringContainsString('BREADCRUMB_UNICO_TESTE_ABC123', $saida);
    }
}
