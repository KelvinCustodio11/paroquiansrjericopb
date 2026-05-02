<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona janela de transmissão por rádio
        Schema::table('radios', function (Blueprint $table): void {
            $table->time('hora_inicio')->nullable()->after('programacao_url')
                ->comment('Horário de início da transmissão (ex: 08:00:00)');

            $table->time('hora_fim')->nullable()->after('hora_inicio')
                ->comment('Horário de fim da transmissão (ex: 09:30:00)');
        });

        // Adiciona título do painel de rádio nas configurações globais
        Schema::table('configuracoes', function (Blueprint $table): void {
            $table->string('radio_painel_titulo')
                ->default('Rádios Católicas ao Vivo')
                ->after('habilitar_testemunhos')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('radios', function (Blueprint $table): void {
            $table->dropColumn(['hora_inicio', 'hora_fim']);
        });

        Schema::table('configuracoes', function (Blueprint $table): void {
            $table->dropColumn('radio_painel_titulo');
        });
    }
};
