<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();
            $table->string('titulo');
            $table->string('link')->default('#');
            $table->string('icone')->nullable()->comment('Classe heroicon ou fa (ex: heroicon-o-home)');
            $table->string('page_key')->nullable()->comment('Valor para data-page (ex: home, historia, eventos)');
            $table->unsignedBigInteger('pai_id')->nullable()->index()->comment('ID do item pai para subitems de dropdown');
            $table->integer('ordem')->default(0);
            $table->boolean('visivel')->default(true);
            $table->boolean('externo')->default(false)->comment('Abre em nova aba');
            $table->timestamps();

            $table->foreign('pai_id')->references('id')->on('menu_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
