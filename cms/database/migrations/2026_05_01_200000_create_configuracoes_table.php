<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracoes', function (Blueprint $table) {
            $table->id();

            // ── Identidade Visual ────────────────────────────────────────────
            $table->string('cor_principal')->default('#000000');

            // ── Cabeçalho (Header) ───────────────────────────────────────────
            $table->string('header_cta_texto')->default('Ouça agora');
            $table->string('header_cta_link')->default('#');

            // ── Seção Hero (página inicial) ──────────────────────────────────
            $table->string('hero_imagem')->nullable();
            $table->string('hero_tagline')->default('Paróquia Nossa Senhora dos Remédios — Jericó/PB');
            $table->string('hero_titulo')->default('Fé, Esperança e Amor no coração do Sertão Paraibano!');
            $table->text('hero_descricao')->nullable();
            $table->string('hero_btn1_texto')->default('Horários');
            $table->string('hero_btn1_link')->default('agenda-liturgica.html');
            $table->string('hero_btn2_texto')->default('Calendário Litúrgico');
            $table->string('hero_btn2_link')->default('agenda-liturgica.html');

            // ── Rodapé (Footer) ──────────────────────────────────────────────
            $table->text('footer_descricao')->default('A Paróquia Nossa Senhora dos Remédios é uma comunidade de fé comprometida com o amor a Deus e ao próximo.');
            $table->string('footer_telefone')->default('(83) 3435-1020');
            $table->string('footer_email')->default('paroquiaremediosjerico@gmail.com');
            $table->text('footer_endereco')->default('Rua da Matriz, s/n - Centro, Jericó/PB, CEP 58830-000');
            $table->string('footer_facebook')->nullable();
            $table->string('footer_instagram')->nullable();
            $table->string('footer_whatsapp')->nullable();
            $table->string('footer_youtube')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracoes');
    }
};
