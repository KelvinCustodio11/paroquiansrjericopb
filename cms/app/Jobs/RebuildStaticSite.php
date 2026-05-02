<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class RebuildStaticSite implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Tentativas máximas em caso de falha. */
    public int $tries = 2;

    /** Sem delay — executa o quanto antes. */
    public function __construct() {}

    public function handle(): void
    {
        Artisan::call('content:export', ['--build' => true]);
        Log::info('[RebuildStaticSite] content:export --build concluído.');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[RebuildStaticSite] Falha ao exportar conteúdo: ' . $exception->getMessage());
    }
}
