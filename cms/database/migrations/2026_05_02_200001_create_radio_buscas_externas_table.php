<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radio_buscas_externas', function (Blueprint $table): void {
            $table->id();

            $table->string('label')
                ->comment('Nome legível da regra (ex: "Católicas da Paraíba")');

            $table->string('tag')->nullable()
                ->comment('Tag da Radio Browser API: catholic, gospel, christian, religious...');

            $table->string('pais', 2)->default('BR')
                ->comment('Código ISO do país (ex: BR, PT)');

            $table->string('estado')->nullable()
                ->comment('Nome completo do estado na Radio Browser API (ex: Paraíba, São Paulo)');

            $table->string('regiao')->nullable()
                ->comment('Região do Brasil, se quiser filtrar por região');

            $table->integer('limite')->default(10)
                ->comment('Quantidade máxima de rádios a buscar nesta regra');

            $table->boolean('ativo')->default(true);

            $table->integer('ordem')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radio_buscas_externas');
    }
};
