<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Artigo;
use App\Models\Compromisso;
use App\Models\Evento;
use App\Models\Homilia;
use App\Models\Igreja;
use App\Models\Ministerio;
use App\Models\Paroco;
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

        // Artigos
        $artigos = Artigo::where('publicado', true)->orderBy('data_publicacao', 'desc')->get()
            ->map(fn (Artigo $a) => $a->toJsonExport())->all();
        File::put($dataDir.'/artigos.json',
            json_encode(['artigos' => $artigos], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info('OK artigos.json ('.count($artigos).' registros)');

        // Homilias
        $homilias = Homilia::where('publicado', true)->orderBy('data', 'desc')->get()
            ->map(fn (Homilia $h) => $h->toJsonExport())->all();
        File::put($dataDir.'/homilias.json',
            json_encode(['homilias' => $homilias], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info('OK homilias.json ('.count($homilias).' registros)');

        // Horários de Missa (agrupados por igreja)
        $igrejas = Igreja::with('horarios')->where('ativa', true)->get()->map(fn (Igreja $ig) => [
            'slug'     => $ig->slug,
            'nome'     => $ig->nome,
            'endereco' => $ig->endereco,
            'bairro'   => $ig->bairro,
            'tipo'     => $ig->tipo,
            'horarios' => $ig->horarios->map(fn ($h) => [
                'dia_semana'      => $h->dia_semana,
                'hora'            => $h->hora,
                'tipo_celebracao' => $h->tipo_celebracao,
                'observacao'      => $h->observacao,
            ])->sortBy('dia_semana')->values()->all(),
        ])->all();
        File::put($dataDir.'/horarios-missa.json',
            json_encode(['igrejas' => $igrejas], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info('OK horarios-missa.json ('.count($igrejas).' igreja(s))');

        // Agenda pastoral (compromissos)
        $compromissos = Compromisso::where('publico', true)->orderBy('data')->orderBy('hora')->get()
            ->map(fn (Compromisso $c) => $c->toJsonExport())->all();
        File::put($dataDir.'/agenda-pastoral.json',
            json_encode(['compromissos' => $compromissos], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info('OK agenda-pastoral.json ('.count($compromissos).' registros)');

        // Ministérios
        $ministerios = Ministerio::where('ativo', true)->orderBy('nome')->get()
            ->map(fn (Ministerio $m) => $m->toJsonExport())->all();
        File::put($dataDir.'/ministerios.json',
            json_encode(['ministerios' => $ministerios], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info('OK ministerios.json ('.count($ministerios).' registros)');

        // Pároco ativo
        $paroco = Paroco::where('ativo', true)->first();
        if ($paroco) {
            File::put($dataDir.'/paroco.json',
                json_encode($paroco->toJsonExport(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
            $this->info('OK paroco.json');
        } else {
            $this->warn('Nenhum pároco ativo encontrado — paroco.json não atualizado.');
        }

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
