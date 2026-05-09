<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Espelha schemas/evento.schema.json (raiz do repo).
 * Valores enum mantidos identicos ao schema para consistencia
 * com o gerador estatico (scripts/build-content.js).
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('titulo');
            $table->string('subtitulo')->nullable();
            $table->date('data_inicio');
            $table->date('data_fim')->nullable();
            $table->time('hora_inicio')->nullable();
            $table->string('local')->nullable();
            $table->enum('categoria', [
                'liturgico', 'pastoral', 'social', 'formativo', 'festivo', 'outro',
            ]);
            $table->enum('status', [
                'agendado', 'em-andamento', 'encerrado', 'cancelado',
            ])->default('agendado');
            $table->text('resumo')->nullable();
            $table->longText('conteudo')->nullable();
            $table->string('imagem_capa')->nullable();
            $table->json('programacao')->nullable();
            $table->json('inscricao')->nullable();
            $table->boolean('publicado')->default(false);
            $table->boolean('destaque')->default(false);
            $table->timestamps();

            $table->index('data_inicio');
            $table->index(['publicado', 'data_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
