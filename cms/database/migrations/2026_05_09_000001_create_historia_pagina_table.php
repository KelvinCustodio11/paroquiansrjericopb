<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('historia_pagina', function (Blueprint $table) {
            $table->id();

            // ── SEO ──────────────────────────────────────────────────────────
            $table->string('seo_titulo')->nullable()->default('História da Paróquia');
            $table->string('seo_descricao', 320)->nullable();

            // ── Page Header ──────────────────────────────────────────────────
            $table->string('page_titulo')->nullable()->default('Nossa Trajetória');
            $table->string('breadcrumb_atual')->nullable()->default('Nossa História');

            // ── About Us (Apresentação) ───────────────────────────────────────
            $table->string('about_subtitulo')->nullable();
            $table->string('about_titulo')->nullable();
            $table->text('about_intro1')->nullable();
            $table->text('about_intro2')->nullable();
            $table->string('about_imagem1')->nullable()->comment('Caminho storage ou URL — imagem principal (540×400)');
            $table->string('about_imagem2')->nullable()->comment('Caminho storage ou URL — imagem secundária (300×300)');
            $table->json('about_topicos')->nullable()->comment('[{icone, titulo}]');

            // ── Missão ────────────────────────────────────────────────────────
            $table->string('missao_subtitulo')->nullable();
            $table->string('missao_titulo')->nullable();
            $table->string('missao_subtexto')->nullable();
            $table->text('missao_texto')->nullable();
            $table->string('missao_cta_href')->nullable();
            $table->string('missao_cta_texto')->nullable();
            $table->string('missao_imagem')->nullable()->comment('Caminho storage ou URL (600×480)');

            // ── Visão / Missão (abas) ─────────────────────────────────────────
            $table->string('vm_subtitulo')->nullable();
            $table->string('vm_titulo')->nullable();
            $table->json('vm_abas')->nullable()->comment('[{label, subtitulo, titulo, subtexto, texto, imagem}]');

            // ── Contadores ────────────────────────────────────────────────────
            $table->json('contador_items')->nullable()->comment('[{valor, sufixo, label, descricao}]');

            // ── What We Do (Serviços) ─────────────────────────────────────────
            $table->string('servicos_subtitulo')->nullable();
            $table->string('servicos_titulo')->nullable();
            $table->json('servicos')->nullable()->comment('[{icone, titulo, descricao}]');

            // ── Equipe ────────────────────────────────────────────────────────
            $table->string('equipe_subtitulo')->nullable();
            $table->string('equipe_titulo')->nullable();
            $table->json('membros')->nullable()->comment('[{imagem, nome, cargo, facebook, instagram, whatsapp}]');

            // ── Mensagem do Pároco ────────────────────────────────────────────
            $table->string('paroco_subtitulo')->nullable();
            $table->string('paroco_titulo')->nullable();
            $table->string('paroco_subtexto')->nullable();
            $table->text('paroco_texto')->nullable();
            $table->string('paroco_imagem')->nullable()->comment('Caminho storage ou URL (600×500)');
            $table->string('paroco_assinatura')->nullable()->comment('Caminho storage — imagem da assinatura (120×60)');
            $table->string('paroco_cargo')->nullable();

            // ── Valores (FAQ + Slider) ────────────────────────────────────────
            $table->string('valores_subtitulo')->nullable();
            $table->string('valores_titulo')->nullable();
            $table->json('valores_faqs')->nullable()->comment('[{pergunta, resposta}]');
            $table->json('valores_imagens')->nullable()->comment('[{imagem}]');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historia_pagina');
    }
};
