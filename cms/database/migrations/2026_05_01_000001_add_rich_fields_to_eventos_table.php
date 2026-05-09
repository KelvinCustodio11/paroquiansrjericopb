<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona campos opcionais para o layout rico de evento-detalhe:
 *   - stats_bar:              barra de 3 estatísticas (valor + legenda)
 *   - topicos_destaque:       lista de tópicos com ícone de check
 *   - texto_pos_topicos:      parágrafo de conclusão após os tópicos
 *   - galeria_titulo / galeria_titulo_destaque / galeria_subtitulo / galeria_imagens
 *   - programacao_titulo / programacao_titulo_destaque / programacao_subtitulo
 *   - sidebar_descricao:      texto de intro da sidebar
 *   - sidebar_items:          itens de info customizados (ícone + título + valor)
 *   - sidebar_milestones:     marcos de timeline (título + complemento + valor + progresso)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            // Barra de estatísticas
            $table->json('stats_bar')->nullable()->after('imagem_capa');

            // Tópicos em destaque (checkmarks)
            $table->json('topicos_destaque')->nullable()->after('stats_bar');
            $table->text('texto_pos_topicos')->nullable()->after('topicos_destaque');

            // Galeria de fotos
            $table->string('galeria_titulo')->nullable()->after('texto_pos_topicos');
            $table->string('galeria_titulo_destaque')->nullable()->after('galeria_titulo');
            $table->string('galeria_subtitulo')->nullable()->after('galeria_titulo_destaque');
            $table->json('galeria_imagens')->nullable()->after('galeria_subtitulo');

            // Títulos da seção de programação
            $table->string('programacao_titulo')->nullable()->after('galeria_imagens');
            $table->string('programacao_titulo_destaque')->nullable()->after('programacao_titulo');
            $table->string('programacao_subtitulo')->nullable()->after('programacao_titulo_destaque');

            // Sidebar
            $table->text('sidebar_descricao')->nullable()->after('programacao_subtitulo');
            $table->json('sidebar_items')->nullable()->after('sidebar_descricao');
            $table->json('sidebar_milestones')->nullable()->after('sidebar_items');
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropColumn([
                'stats_bar',
                'topicos_destaque',
                'texto_pos_topicos',
                'galeria_titulo',
                'galeria_titulo_destaque',
                'galeria_subtitulo',
                'galeria_imagens',
                'programacao_titulo',
                'programacao_titulo_destaque',
                'programacao_subtitulo',
                'sidebar_descricao',
                'sidebar_items',
                'sidebar_milestones',
            ]);
        });
    }
};
