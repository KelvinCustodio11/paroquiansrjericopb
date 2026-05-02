<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ministerios', function (Blueprint $table): void {
            $table->enum('categoria', [
                'ministerio', 'catequese', 'estudo-biblico', 'grupo-oracao', 'outro',
            ])->default('ministerio')->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('ministerios', function (Blueprint $table): void {
            $table->dropColumn('categoria');
        });
    }
};
