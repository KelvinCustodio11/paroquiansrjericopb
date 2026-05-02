<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('galeria_albuns', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('slug')->unique();
            $table->text('descricao')->nullable();
            $table->string('capa_imagem')->nullable();
            $table->string('categoria')->nullable(); // ex: festividades, obras, pastoral
            $table->integer('ordem')->default(0);
            $table->boolean('publico')->default(true);
            $table->timestamps();
        });

        Schema::create('galeria_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained('galeria_albuns')->cascadeOnDelete();
            $table->string('arquivo');
            $table->string('legenda')->nullable();
            $table->string('alt')->nullable();
            $table->integer('ordem')->default(0);
            $table->boolean('ativa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('galeria_fotos');
        Schema::dropIfExists('galeria_albuns');
    }
};
