<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('artigos', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('titulo');
            $table->date('data_publicacao');
            $table->date('data_atualizacao')->nullable();
            $table->string('autor_nome');
            $table->string('autor_papel')->nullable();
            $table->string('autor_foto')->nullable();
            $table->enum('categoria', [
                'noticias', 'espiritualidade', 'pastoral', 'comunidade',
                'formacao', 'evangelho', 'outro',
            ])->default('outro');
            $table->json('tags')->nullable();
            $table->string('resumo', 320);
            $table->string('imagem_capa_url')->nullable();
            $table->string('imagem_capa_alt')->nullable();
            $table->integer('imagem_capa_largura')->nullable();
            $table->integer('imagem_capa_altura')->nullable();
            $table->longText('conteudo');
            $table->boolean('destaque')->default(false);
            $table->boolean('publicado')->default(false);
            $table->timestamps();

            $table->index(['publicado', 'data_publicacao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artigos');
    }
};
