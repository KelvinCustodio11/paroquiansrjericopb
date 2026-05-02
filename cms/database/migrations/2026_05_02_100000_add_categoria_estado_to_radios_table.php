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
            $table->enum('categoria', [
                'catolica', 'gospel', 'religiosa', 'regional', 'outra',
            ])->default('catolica')->after('ordem');

            $table->string('estado', 2)->nullable()->after('categoria')
                ->comment('UF de origem da rádio (ex: PB, SP)');

            $table->string('cidade')->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('radios', function (Blueprint $table): void {
            $table->dropColumn(['categoria', 'estado', 'cidade']);
        });
    }
};
