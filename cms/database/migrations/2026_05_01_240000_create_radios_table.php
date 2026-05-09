<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radios', function (Blueprint $table): void {
            $table->id();
            $table->string('nome');
            $table->string('url');
            $table->string('descricao')->nullable();
            $table->string('favicon')->nullable();
            $table->boolean('destaque')->default(false);
            $table->boolean('ativa')->default(true);
            $table->integer('ordem')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radios');
    }
};
