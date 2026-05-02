<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testemunhos', function (Blueprint $table): void {
            $table->id();
            $table->string('nome');
            $table->string('email')->nullable();
            $table->string('cidade')->nullable();
            $table->text('texto');
            $table->enum('status', ['pendente', 'aprovado', 'rejeitado'])->default('pendente');
            $table->boolean('consentimento_lgpd')->default(false)
                ->comment('O usuário concordou com o uso dos dados');
            $table->text('motivo_rejeicao')->nullable();
            $table->timestamp('aprovado_em')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testemunhos');
    }
};
