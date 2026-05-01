<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('parocos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('saudacao')->default('Padre');
            $table->date('data_ordenacao')->nullable();
            $table->date('data_inicio_paroquia')->nullable();
            $table->text('biografia');
            $table->string('foto')->nullable();
            $table->string('contato_email')->nullable();
            $table->string('contato_telefone')->nullable();
            $table->string('redes_facebook')->nullable();
            $table->string('redes_instagram')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parocos');
    }
};
