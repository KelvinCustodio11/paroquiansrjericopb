<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            // ── Hero — múltiplos textos com rotação ──────────────────────────
            $table->json('hero_titulos')->nullable()
                ->after('hero_titulo')
                ->comment('[{"texto":"Fé...", "cor":"#acaa59", "efeito":"fade"}]');
            $table->json('hero_taglines')->nullable()
                ->after('hero_tagline');
            $table->json('hero_descricoes')->nullable()
                ->after('hero_descricao');

            // ── Identidade — nome e título exibidos no footer ─────────────────
            $table->string('paroquia_nome')->default('Paróquia Nossa Senhora dos Remédios')
                ->after('cor_texto');
            $table->string('paroquia_titulo')->nullable()->after('paroquia_nome');

            // ── Footer — links rápidos e sacramentos configuráveis ────────────
            $table->json('footer_links_rapidos')->nullable()
                ->after('footer_youtube')
                ->comment('[{"texto":"Eventos","link":"eventos.html"}]');
            $table->json('footer_sacramentos')->nullable()
                ->after('footer_links_rapidos')
                ->comment('[{"nome":"Batismo","link":"sacramento-detalhe.html"}]');

            // ── Contato ────────────────────────────────────────────────────────
            $table->string('contato_maps_url')->nullable()
                ->after('footer_sacramentos')
                ->comment('URL de embed do Google Maps');
            $table->string('contato_horario_secretaria')->nullable()
                ->after('contato_maps_url');
            $table->string('contato_coordenadas_lat')->nullable()
                ->after('contato_horario_secretaria');
            $table->string('contato_coordenadas_lng')->nullable()
                ->after('contato_coordenadas_lat');

            // ── Funcionalidades (devocoes, testemunhos etc.) ─────────────────
            $table->boolean('habilitar_santo_dia')->default(true)->after('contato_coordenadas_lng');
            $table->boolean('habilitar_evangelho_dia')->default(true)->after('habilitar_santo_dia');
            $table->boolean('habilitar_terco_dia')->default(true)->after('habilitar_evangelho_dia');
            $table->boolean('habilitar_testemunhos')->default(false)->after('habilitar_terco_dia');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            $table->dropColumn([
                'hero_titulos', 'hero_taglines', 'hero_descricoes',
                'paroquia_nome', 'paroquia_titulo',
                'footer_links_rapidos', 'footer_sacramentos',
                'contato_maps_url', 'contato_horario_secretaria',
                'contato_coordenadas_lat', 'contato_coordenadas_lng',
                'habilitar_santo_dia', 'habilitar_evangelho_dia',
                'habilitar_terco_dia', 'habilitar_testemunhos',
            ]);
        });
    }
};
