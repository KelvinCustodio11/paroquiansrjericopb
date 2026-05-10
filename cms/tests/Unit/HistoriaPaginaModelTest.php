<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\HistoriaPagina;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Testes unitários do model HistoriaPagina.
 *
 * Cobre:
 *  - Padrão Singleton via current()
 *  - Persistência e leitura de todos os campos
 *  - Lógica de enriquecimento em toJsonExport():
 *      · vm_abas: ativo / tab_id / aba_id
 *      · topicos: atraso
 *      · servicos: atraso
 *      · membros: atraso
 *      · valores_faqs: aberto / heading_id / faq_id
 *      · valores_imagens: string normalizada para {imagem: ...}
 *  - Prefixo /storage/ em campos de imagem
 *  - Fallbacks quando campos são null
 */
class HistoriaPaginaModelTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Padrão Singleton
    // -------------------------------------------------------------------------

    #[Test]
    public function currentCriaRegistroQuandoNaoExiste(): void
    {
        $this->assertDatabaseEmpty('historia_pagina');

        $hp = HistoriaPagina::current();

        $this->assertInstanceOf(HistoriaPagina::class, $hp);
        $this->assertEquals(1, $hp->id);
        $this->assertDatabaseCount('historia_pagina', 1);
    }

    #[Test]
    public function currentRetornaOMesmoRegistroEmChamadasSubsequentes(): void
    {
        $primeiroId  = HistoriaPagina::current()->id;
        $segundoId   = HistoriaPagina::current()->id;
        $terceiroId  = HistoriaPagina::current()->id;

        $this->assertEquals(1, $primeiroId);
        $this->assertEquals($primeiroId, $segundoId);
        $this->assertEquals($primeiroId, $terceiroId);
        $this->assertDatabaseCount('historia_pagina', 1);
    }

    #[Test]
    public function currentNaoCriaSegundoRegistroSeJaExiste(): void
    {
        HistoriaPagina::create(['id' => 1]);
        HistoriaPagina::current();
        HistoriaPagina::current();

        $this->assertDatabaseCount('historia_pagina', 1);
    }

    // -------------------------------------------------------------------------
    // Campos escalares em toJsonExport()
    // -------------------------------------------------------------------------

    #[Test]
    public function toJsonExportRetornaChavesDeSeo(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'seo_titulo'    => 'Título SEO Test',
            'seo_descricao' => 'Descrição SEO Test',
        ]);

        $data = $hp->toJsonExport();

        $this->assertEquals('Título SEO Test',    $data['meta_titulo']);
        $this->assertEquals('Descrição SEO Test', $data['meta_descricao']);
    }

    #[Test]
    public function toJsonExportRetornaChavesDePageHeader(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'page_titulo'      => 'Minha Página',
            'breadcrumb_atual' => 'Breadcrumb Atual',
        ]);

        $data = $hp->toJsonExport();

        $this->assertEquals('Minha Página',    $data['page_titulo']);
        $this->assertEquals('Breadcrumb Atual', $data['breadcrumb_atual']);
    }

    #[Test]
    public function toJsonExportRetornaChavesDeAbout(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'about_subtitulo' => 'Sub About',
            'about_titulo'    => 'Título About',
            'about_intro1'    => 'Intro 1',
            'about_intro2'    => 'Intro 2',
        ]);

        $data = $hp->toJsonExport();

        $this->assertEquals('Sub About',    $data['about_subtitulo']);
        $this->assertEquals('Título About', $data['about_titulo']);
        $this->assertEquals('Intro 1',      $data['about_intro1']);
        $this->assertEquals('Intro 2',      $data['about_intro2']);
    }

    #[Test]
    public function toJsonExportRetornaChavesDeMissao(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'missao_subtitulo' => 'Sub Missão',
            'missao_titulo'    => 'Título Missão',
            'missao_subtexto'  => 'Subtexto',
            'missao_texto'     => 'Texto completo',
            'missao_cta_href'  => '/contato',
            'missao_cta_texto' => 'Fale Conosco',
        ]);

        $data = $hp->toJsonExport();

        $this->assertEquals('Sub Missão',    $data['missao_subtitulo']);
        $this->assertEquals('Título Missão', $data['missao_titulo']);
        $this->assertEquals('Subtexto',      $data['missao_subtexto']);
        $this->assertEquals('Texto completo', $data['missao_texto']);
        $this->assertEquals('/contato',      $data['missao_cta_href']);
        $this->assertEquals('Fale Conosco',  $data['missao_cta_texto']);
    }

    #[Test]
    public function toJsonExportRetornaChavesDeVmTitulos(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'vm_subtitulo' => 'Sub VM',
            'vm_titulo'    => 'Título VM',
        ]);

        $data = $hp->toJsonExport();

        $this->assertEquals('Sub VM',    $data['vm_subtitulo']);
        $this->assertEquals('Título VM', $data['vm_titulo']);
    }

    #[Test]
    public function toJsonExportRetornaChavesDeServicos(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'servicos_subtitulo' => 'Sub Serviços',
            'servicos_titulo'    => 'O Que Fazemos',
        ]);

        $data = $hp->toJsonExport();

        $this->assertEquals('Sub Serviços', $data['servicos_subtitulo']);
        $this->assertEquals('O Que Fazemos', $data['servicos_titulo']);
    }

    #[Test]
    public function toJsonExportRetornaChavesDeEquipe(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'equipe_subtitulo' => 'Sub Equipe',
            'equipe_titulo'    => 'Nossa Equipe',
        ]);

        $data = $hp->toJsonExport();

        $this->assertEquals('Sub Equipe',  $data['equipe_subtitulo']);
        $this->assertEquals('Nossa Equipe', $data['equipe_titulo']);
    }

    #[Test]
    public function toJsonExportRetornaChavesDeParoco(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'paroco_subtitulo' => 'Sub Pároco',
            'paroco_titulo'    => 'Palavra do Pároco',
            'paroco_subtexto'  => 'Subtexto Pároco',
            'paroco_texto'     => 'Texto longo do pároco',
            'paroco_cargo'     => 'Pároco',
        ]);

        $data = $hp->toJsonExport();

        $this->assertEquals('Sub Pároco',          $data['paroco_subtitulo']);
        $this->assertEquals('Palavra do Pároco',   $data['paroco_titulo']);
        $this->assertEquals('Subtexto Pároco',     $data['paroco_subtexto']);
        $this->assertEquals('Texto longo do pároco', $data['paroco_texto']);
        $this->assertEquals('Pároco',              $data['paroco_cargo']);
    }

    #[Test]
    public function toJsonExportRetornaChavesDeValoresTitulos(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'valores_subtitulo' => 'Sub Valores',
            'valores_titulo'    => 'Nossos Valores',
        ]);

        $data = $hp->toJsonExport();

        $this->assertEquals('Sub Valores',   $data['valores_subtitulo']);
        $this->assertEquals('Nossos Valores', $data['valores_titulo']);
    }

    // -------------------------------------------------------------------------
    // Prefixo /storage/ em imagens
    // -------------------------------------------------------------------------

    #[Test]
    public function toJsonExportPrefixa_storage_EmAboutImagem1(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'about_imagem1' => 'uploads/historia/foto.jpg',
        ]);

        $data = $hp->toJsonExport();

        $this->assertEquals('/storage/uploads/historia/foto.jpg', $data['about_imagem1']);
    }

    #[Test]
    public function toJsonExportPrefixa_storage_EmAboutImagem2(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'about_imagem2' => 'uploads/historia/foto2.jpg',
        ]);

        $this->assertStringStartsWith('/storage/', $hp->toJsonExport()['about_imagem2']);
    }

    #[Test]
    public function toJsonExportPrefixa_storage_EmMissaoImagem(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'missao_imagem' => 'uploads/historia/missao.jpg',
        ]);

        $this->assertStringStartsWith('/storage/', $hp->toJsonExport()['missao_imagem']);
    }

    #[Test]
    public function toJsonExportPrefixa_storage_EmParocoImagem(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'paroco_imagem' => 'uploads/historia/paroco.jpg',
        ]);

        $this->assertStringStartsWith('/storage/', $hp->toJsonExport()['paroco_imagem']);
    }

    #[Test]
    public function toJsonExportPrefixa_storage_EmParocoAssinatura(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'paroco_assinatura' => 'uploads/historia/assinatura.png',
        ]);

        $this->assertStringStartsWith('/storage/', $hp->toJsonExport()['paroco_assinatura']);
    }

    #[Test]
    public function toJsonExportNaoPrefixa_storage_QuandoImagemNula(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'about_imagem1'    => null,
            'about_imagem2'    => null,
            'missao_imagem'    => null,
            'paroco_imagem'    => null,
            'paroco_assinatura' => null,
        ]);

        $data = $hp->toJsonExport();

        $this->assertEquals('', $data['about_imagem1']);
        $this->assertEquals('', $data['about_imagem2']);
        $this->assertEquals('', $data['missao_imagem']);
        $this->assertEquals('', $data['paroco_imagem']);
        $this->assertEquals('', $data['paroco_assinatura']);
    }

    #[Test]
    public function toJsonExportNaoDuplicaSlashEm_storage(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'about_imagem1' => '/uploads/historia/foto.jpg', // barra inicial
        ]);

        $data = $hp->toJsonExport();

        $this->assertEquals('/storage/uploads/historia/foto.jpg', $data['about_imagem1']);
        $this->assertStringNotContainsString('//', $data['about_imagem1']);
    }

    // -------------------------------------------------------------------------
    // Enriquecimento: vm_abas
    // -------------------------------------------------------------------------

    #[Test]
    public function toJsonExportEnriquecePrimeiraAbaComAtivo(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'vm_abas' => [
                ['titulo' => 'Visão',   'texto' => 'Texto visão'],
                ['titulo' => 'Missão',  'texto' => 'Texto missão'],
                ['titulo' => 'Valores', 'texto' => 'Texto valores'],
            ],
        ]);

        $abas = $hp->toJsonExport()['vm_abas'];

        $this->assertTrue($abas[0]['ativo'],  'Primeira aba deve ser ativo=true');
        $this->assertFalse($abas[1]['ativo'], 'Segunda aba deve ser ativo=false');
        $this->assertFalse($abas[2]['ativo'], 'Terceira aba deve ser ativo=false');
    }

    #[Test]
    public function toJsonExportAdicionaTabIdEAbaIdEmVmAbas(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'vm_abas' => [
                ['titulo' => 'Aba A'],
                ['titulo' => 'Aba B'],
            ],
        ]);

        $abas = $hp->toJsonExport()['vm_abas'];

        $this->assertEquals('vm-tab-1',  $abas[0]['tab_id']);
        $this->assertEquals('vm-pane-1', $abas[0]['aba_id']);
        $this->assertEquals('vm-tab-2',  $abas[1]['tab_id']);
        $this->assertEquals('vm-pane-2', $abas[1]['aba_id']);
    }

    #[Test]
    public function toJsonExportRetornaNullParaVmAbasVazio(): void
    {
        $hp = HistoriaPagina::factory()->create(['vm_abas' => null]);

        $this->assertNull($hp->toJsonExport()['vm_abas']);
    }

    // -------------------------------------------------------------------------
    // Enriquecimento: topicos (atraso)
    // -------------------------------------------------------------------------

    #[Test]
    public function toJsonExportPrimeiroTopicoNaoTemAtraso(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'about_topicos' => [
                ['texto' => 'Primeiro'],
                ['texto' => 'Segundo'],
                ['texto' => 'Terceiro'],
            ],
        ]);

        $topicos = $hp->toJsonExport()['topicos'];

        $this->assertNull($topicos[0]['atraso']);
    }

    #[Test]
    public function toJsonExportTopicosSubsequentesTenAtraso(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'about_topicos' => [
                ['texto' => 'A'],
                ['texto' => 'B'],
                ['texto' => 'C'],
            ],
        ]);

        $topicos = $hp->toJsonExport()['topicos'];

        $this->assertNull($topicos[0]['atraso']);
        $this->assertEquals('0.25s', $topicos[1]['atraso']);
        $this->assertEquals('0.5s',  $topicos[2]['atraso']);
    }

    #[Test]
    public function toJsonExportRetornaNullParaTopicosVazio(): void
    {
        $hp = HistoriaPagina::factory()->create(['about_topicos' => null]);

        $this->assertNull($hp->toJsonExport()['topicos']);
    }

    // -------------------------------------------------------------------------
    // Enriquecimento: servicos (atraso)
    // -------------------------------------------------------------------------

    #[Test]
    public function toJsonExportAdicionaAtrasoEmServicos(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'servicos' => [
                ['titulo' => 'S1'],
                ['titulo' => 'S2'],
                ['titulo' => 'S3'],
            ],
        ]);

        $servicos = $hp->toJsonExport()['servicos'];

        $this->assertNull($servicos[0]['atraso']);
        $this->assertEquals('0.25s', $servicos[1]['atraso']);
        $this->assertEquals('0.5s',  $servicos[2]['atraso']);
    }

    #[Test]
    public function toJsonExportRetornaNullParaServicosVazio(): void
    {
        $hp = HistoriaPagina::factory()->create(['servicos' => null]);

        $this->assertNull($hp->toJsonExport()['servicos']);
    }

    // -------------------------------------------------------------------------
    // Enriquecimento: membros (atraso)
    // -------------------------------------------------------------------------

    #[Test]
    public function toJsonExportAdicionaAtrasoEmMembros(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'membros' => [
                ['nome' => 'M1'],
                ['nome' => 'M2'],
                ['nome' => 'M3'],
            ],
        ]);

        $membros = $hp->toJsonExport()['membros'];

        $this->assertNull($membros[0]['atraso']);
        $this->assertEquals('0.25s', $membros[1]['atraso']);
        $this->assertEquals('0.5s',  $membros[2]['atraso']);
    }

    #[Test]
    public function toJsonExportRetornaNullParaMembrosVazio(): void
    {
        $hp = HistoriaPagina::factory()->create(['membros' => null]);

        $this->assertNull($hp->toJsonExport()['membros']);
    }

    // -------------------------------------------------------------------------
    // Enriquecimento: valores_faqs
    // -------------------------------------------------------------------------

    #[Test]
    public function toJsonExportPrimeiroFaqEstaAberto(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'valores_faqs' => [
                ['pergunta' => 'Q1', 'resposta' => 'R1'],
                ['pergunta' => 'Q2', 'resposta' => 'R2'],
            ],
        ]);

        $faqs = $hp->toJsonExport()['valores_faqs'];

        $this->assertTrue($faqs[0]['aberto']);
        $this->assertFalse($faqs[1]['aberto']);
    }

    #[Test]
    public function toJsonExportAdicionaHeadingIdEFaqIdEmFaqs(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'valores_faqs' => [
                ['pergunta' => 'Q1'],
                ['pergunta' => 'Q2'],
            ],
        ]);

        $faqs = $hp->toJsonExport()['valores_faqs'];

        $this->assertEquals('heading1', $faqs[0]['heading_id']);
        $this->assertEquals('faq1',     $faqs[0]['faq_id']);
        $this->assertEquals('heading2', $faqs[1]['heading_id']);
        $this->assertEquals('faq2',     $faqs[1]['faq_id']);
    }

    #[Test]
    public function toJsonExportRetornaNullParaFaqsVazio(): void
    {
        $hp = HistoriaPagina::factory()->create(['valores_faqs' => null]);

        $this->assertNull($hp->toJsonExport()['valores_faqs']);
    }

    // -------------------------------------------------------------------------
    // Enriquecimento: valores_imagens (normalização string → {imagem: ...})
    // -------------------------------------------------------------------------

    #[Test]
    public function toJsonExportNormalizaStringParaObjetoEmValoresImagens(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'valores_imagens' => [
                'uploads/historia/v1.jpg',
                'uploads/historia/v2.jpg',
            ],
        ]);

        $imagens = $hp->toJsonExport()['valores_imagens'];

        $this->assertIsArray($imagens[0]);
        $this->assertArrayHasKey('imagem', $imagens[0]);
        $this->assertEquals('uploads/historia/v1.jpg', $imagens[0]['imagem']);
    }

    #[Test]
    public function toJsonExportMantemObjetoExistenteEmValoresImagens(): void
    {
        $hp = HistoriaPagina::factory()->create([
            'valores_imagens' => [
                ['imagem' => 'uploads/historia/v1.jpg'],
            ],
        ]);

        $imagens = $hp->toJsonExport()['valores_imagens'];

        $this->assertEquals('uploads/historia/v1.jpg', $imagens[0]['imagem']);
    }

    #[Test]
    public function toJsonExportRetornaNullParaValoresImagensVazio(): void
    {
        $hp = HistoriaPagina::factory()->create(['valores_imagens' => null]);

        $this->assertNull($hp->toJsonExport()['valores_imagens']);
    }

    // -------------------------------------------------------------------------
    // Fallbacks (campos null → valor padrão)
    // -------------------------------------------------------------------------

    #[Test]
    public function toJsonExportFallbackSeoTitulo(): void
    {
        $hp = HistoriaPagina::create(['id' => 1]);

        $data = $hp->toJsonExport();

        $this->assertEquals('História da Paróquia', $data['meta_titulo']);
    }

    #[Test]
    public function toJsonExportFallbackPageTitulo(): void
    {
        $hp = HistoriaPagina::create(['id' => 1]);

        $data = $hp->toJsonExport();

        $this->assertEquals('Nossa Trajetória', $data['page_titulo']);
    }

    #[Test]
    public function toJsonExportFallbackBreadcrumb(): void
    {
        $hp = HistoriaPagina::create(['id' => 1]);

        $data = $hp->toJsonExport();

        $this->assertEquals('Nossa História', $data['breadcrumb_atual']);
    }

    #[Test]
    public function toJsonExportFallbackMissaoCtaHref(): void
    {
        $hp = HistoriaPagina::create(['id' => 1]);

        $this->assertEquals('#', $hp->toJsonExport()['missao_cta_href']);
    }

    #[Test]
    public function toJsonExportFallbackMissaoCtaTexto(): void
    {
        $hp = HistoriaPagina::create(['id' => 1]);

        $this->assertEquals('Saiba mais', $hp->toJsonExport()['missao_cta_texto']);
    }

    // -------------------------------------------------------------------------
    // Completude do array retornado
    // -------------------------------------------------------------------------

    #[Test]
    public function toJsonExportContemTodasAsChavesEsperadas(): void
    {
        $hp = HistoriaPagina::factory()->create();

        $data = $hp->toJsonExport();

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
            $this->assertArrayHasKey($chave, $data, "Chave '{$chave}' ausente em toJsonExport().");
        }
    }

    #[Test]
    public function toJsonExportComCamposVaziosNaoGeraErro(): void
    {
        $hp = HistoriaPagina::factory()->vazio()->create();

        $data = $hp->toJsonExport();

        $this->assertNull($data['topicos']);
        $this->assertNull($data['vm_abas']);
        $this->assertNull($data['contador_items']);
        $this->assertNull($data['servicos']);
        $this->assertNull($data['membros']);
        $this->assertNull($data['valores_faqs']);
        $this->assertNull($data['valores_imagens']);
    }
}
