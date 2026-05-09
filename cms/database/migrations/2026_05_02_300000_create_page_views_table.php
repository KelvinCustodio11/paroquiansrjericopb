<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('pagina', 150)->index();   // ex: "home", "artigos", "artigo:boas-vindas"
            $table->string('titulo', 200)->nullable(); // título legível da página
            $table->char('ip_hash', 64);               // sha256(IP) — sem dado pessoal
            $table->timestamp('viewed_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
