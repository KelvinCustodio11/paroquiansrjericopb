<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compromissos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->date('data');
            $table->time('hora')->nullable();
            $table->enum('tipo', [
                'reuniao', 'formacao', 'visita', 'celebracao', 'evento', 'outro',
            ])->default('outro');
            $table->string('local')->nullable();
            $table->string('responsavel')->nullable();
            $table->boolean('publico')->default(true);
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index(['data', 'publico']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compromissos');
    }
};
