<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            $table->string('logo_cor',        20)->default('#acaa59')->after('cor_texto');
            $table->string('logo_header_img')->nullable()->after('logo_cor');
            $table->string('logo_footer_img')->nullable()->after('logo_header_img');
            $table->string('logo_loader_img')->nullable()->after('logo_footer_img');
        });
    }

    public function down(): void
    {
        Schema::table('configuracoes', function (Blueprint $table): void {
            $table->dropColumn(['logo_cor', 'logo_header_img', 'logo_footer_img', 'logo_loader_img']);
        });
    }
};
