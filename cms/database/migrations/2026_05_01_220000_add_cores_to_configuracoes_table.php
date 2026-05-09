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
            $table->string('cor_fundo_escuro', 20)->default('#000000')->after('cor_principal');
            $table->string('cor_fundo_claro',  20)->default('#FFF4F1')->after('cor_fundo_escuro');
            $table->string('cor_texto',        20)->default('#525252')->after('cor_fundo_claro');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            $table->dropColumn(['cor_fundo_escuro', 'cor_fundo_claro', 'cor_texto']);
        });
    }
};
