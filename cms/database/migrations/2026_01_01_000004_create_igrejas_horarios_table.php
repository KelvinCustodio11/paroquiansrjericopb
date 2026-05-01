<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('igrejas', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('nome');
            $table->string('endereco')->nullable();
            $table->string('bairro')->nullable();
            $table->enum('tipo', ['matriz', 'capela', 'comunidade'])->default('capela');
            $table->boolean('ativa')->default(true);
            $table->timestamps();
        });

        Schema::create('horarios_missa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('igreja_id')->constrained('igrejas')->cascadeOnDelete();
            $table->enum('dia_semana', [
                'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado', 'domingo',
            ]);
            $table->time('hora');
            $table->enum('tipo_celebracao', [
                'missa', 'novena', 'adoracao', 'terco', 'outro',
            ])->default('missa');
            $table->string('observacao')->nullable();
            $table->timestamps();

            $table->index(['igreja_id', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_missa');
        Schema::dropIfExists('igrejas');
    }
};
