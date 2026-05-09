<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('radios', function (Blueprint $table): void {
            $table->string('programacao')->nullable()->after('descricao')
                ->comment('Linha de programação estática (ex: "Missas às 6h, 8h e 18h")');

            $table->string('programacao_url')->nullable()->after('programacao')
                ->comment('URL de API JSON que retorna {"programa":"..."} para programação ao vivo');
        });
    }

    public function down(): void
    {
        Schema::table('radios', function (Blueprint $table): void {
            $table->dropColumn(['programacao', 'programacao_url']);
        });
    }
};
