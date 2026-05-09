<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('igrejas', function (Blueprint $table): void {
            $table->string('historia_titulo')->nullable()->after('ativa');
            $table->string('historia_subtitulo')->nullable()->after('historia_titulo');
            $table->json('historia_secoes')->nullable()->after('historia_subtitulo')
                ->comment('[{"titulo":"Fundação","texto":"...","imagem":"images/..."}]');
        });
    }

    public function down(): void
    {
        Schema::table('igrejas', function (Blueprint $table): void {
            $table->dropColumn(['historia_titulo', 'historia_subtitulo', 'historia_secoes']);
        });
    }
};
