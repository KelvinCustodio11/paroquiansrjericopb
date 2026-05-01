<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ministerios', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('nome');
            $table->text('descricao');
            $table->string('coordenador_nome')->nullable();
            $table->string('coordenador_telefone')->nullable();
            $table->string('coordenador_email')->nullable();
            $table->string('encontros_dia_semana')->nullable();
            $table->string('encontros_horario')->nullable();
            $table->string('encontros_local')->nullable();
            $table->string('imagem')->nullable();
            $table->string('icone')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ministerios');
    }
};
