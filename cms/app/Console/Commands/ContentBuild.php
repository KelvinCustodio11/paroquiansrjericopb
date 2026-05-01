<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ContentBuild extends Command
{
    protected $signature = 'content:build';

    protected $description = 'Regenera as páginas HTML estáticas a partir de data/*.json (atalho: content:export --build).';

    public function handle(): int
    {
        $repoRoot = realpath(base_path('..'));
        $script   = $repoRoot.'/scripts/build-content.js';

        if (! file_exists($script)) {
            $this->error("Script não encontrado: {$script}");
            return self::FAILURE;
        }

        $this->info("Regenerando HTMLs estáticos via Node.js…");
        passthru('cd '.escapeshellarg($repoRoot).' && node scripts/build-content.js', $code);

        if ($code !== 0) {
            $this->error("build-content.js falhou com código {$code}.");
            return self::FAILURE;
        }

        $this->info('Build concluído com sucesso.');
        return self::SUCCESS;
    }
}
