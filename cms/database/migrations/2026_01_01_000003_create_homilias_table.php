<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('homilias', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('titulo');
            $table->date('data');
            $table->string('celebrante');
            $table->string('ocasiao')->nullable();
            $table->string('leitura_referencia')->nullable();
            $table->text('leitura_texto')->nullable();
            $table->string('resumo', 320);
            $table->longText('transcricao')->nullable();
            $table->string('audio_url')->nullable();
            $table->string('video_url')->nullable();
            $table->string('imagem_capa_url')->nullable();
            $table->string('imagem_capa_alt')->nullable();
            $table->boolean('publicado')->default(false);
            $table->timestamps();

            $table->index(['publicado', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('homilias');
    }
};
