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
            $table->boolean('radio_player_ativo')
                ->default(true)
                ->after('radio_painel_titulo');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            $table->dropColumn('radio_player_ativo');
        });
    }
};
