<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->string('titulo_destaque')->nullable()->after('titulo');
            $table->string('local_maps')->nullable()->after('local');
            $table->text('descricao_curta')->nullable()->after('imagem_capa');
            $table->longText('descricao_completa')->nullable()->after('descricao_curta');
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropColumn(['titulo_destaque', 'local_maps', 'descricao_curta', 'descricao_completa']);
        });
    }
};
