<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Delega para ContentBuildPhp — implementação PHP pura, sem Node.js.
 * Mantém a assinatura original `content:build` para não quebrar
 * ContentExport e outros chamadores.
 */
class ContentBuild extends Command
{
    protected $signature = 'content:build';

    protected $description = 'Regenera as páginas HTML estáticas a partir de data/*.json (PHP puro, sem Node.js).';

    public function handle(): int
    {
        return $this->call('content:build-php');
    }
}
