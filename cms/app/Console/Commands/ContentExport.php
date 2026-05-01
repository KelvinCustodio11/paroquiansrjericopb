<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Evento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Exporta o banco de dados para data/*.json (na raiz do repo principal).
 * Após exportar, opcionalmente roda o gerador estatico (Node.js).
 *
 * Uso:
 *   php artisan content:export                # so exporta JSON
 *   php artisan content:export --build        # exporta + roda build-content.js
 *   php artisan content:export --build --validate  # + valida com Node
 */
class ContentExport extends Command
{
    protected $signature = 'content:export {--build} {--validate}';

    protected $description = 'Exporta dados do CMS para data/*.json (e opcionalmente regera HTMLs estaticos).';

    public function handle(): int
    {
        $repoRoot = realpath(base_path('..')); // assumindo cms/ dentro do repo
        $dataDir = $repoRoot.'/data';

        if (! is_dir($dataDir)) {
            $this->error("Diretorio de saida nao encontrado: {$dataDir}");

            return self::FAILURE;
        }

        $eventos = Evento::orderBy('data_inicio', 'desc')->get()
            ->map(fn (Evento $e) => $e->toJsonExport())
            ->all();

        $payload = ['eventos' => $eventos];
        File::put(
            $dataDir.'/eventos.json',
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n"
        );
        $this->info('OK eventos.json ('.count($eventos).' registros)');

        // TODO: replicar para Artigo, Homilia, HorarioMissa, Compromisso, Ministerio, Paroco

        if ($this->option('validate')) {
            $this->info('Validando com schemas...');
            passthru('cd '.escapeshellarg($repoRoot).' && node scripts/validate-data.js', $code);
            if ($code !== 0) {
                $this->error('Validacao falhou — abortando build.');

                return self::FAILURE;
            }
        }

        if ($this->option('build')) {
            $this->info('Regenerando HTMLs estaticos...');
            passthru('cd '.escapeshellarg($repoRoot).' && node scripts/build-content.js', $code);
            if ($code !== 0) {
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
