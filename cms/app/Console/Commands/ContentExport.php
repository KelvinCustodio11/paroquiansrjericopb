<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Artigo;
use App\Models\Compromisso;
use App\Models\Configuracao;
use App\Models\Evento;
use App\Models\GaleriaAlbum;
use App\Models\Homilia;
use App\Models\HistoriaPagina;
use App\Models\Igreja;
use App\Models\MenuItem;
use App\Models\Ministerio;
use App\Models\Paroco;
use App\Models\Radio;
use App\Models\RadioBuscaExterna;
use App\Models\Testemunho;
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

    private function writeFileAtomically(string $path, string $contents): void
    {
        $directory = dirname($path);
        $temporary = tempnam($directory, '.publish-');
        if ($temporary === false || file_put_contents($temporary, $contents) === false || ! rename($temporary, $path)) {
            if ($temporary !== false) {
                @unlink($temporary);
            }
            throw new \RuntimeException("Não foi possível gravar {$path}. Verifique as permissões do diretório.");
        }
    }

    public function handle(): int
    {
        // config('site.root') lê SITE_ROOT do .env via config/site.php.
        // Funciona mesmo com config:cache ativo (ao contrário de env() direto).
        $repoRoot = config('site.root')
            ? rtrim(config('site.root'), '/')
            : realpath(base_path('..'));

        $dataDir = $repoRoot.'/data';

        if (! is_dir($dataDir)) {
            File::makeDirectory($dataDir, 0755, true);
            $this->info("Pasta criada: {$dataDir}");
        }

        $eventos = Evento::where('publicado', true)->orderBy('data_inicio', 'desc')->get()
            ->map(fn (Evento $e) => $e->toJsonExport())
            ->all();

        $payload = ['eventos' => $eventos];
        $this->writeFileAtomically(
            $dataDir.'/eventos.json',
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n"
        );
        $this->info('OK eventos.json ('.count($eventos).' registros)');

        // Artigos
        $artigos = Artigo::where('publicado', true)->orderBy('data_publicacao', 'desc')->get()
            ->map(fn (Artigo $a) => $a->toJsonExport())->all();
        $this->writeFileAtomically($dataDir.'/artigos.json',
            json_encode(['artigos' => $artigos], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info('OK artigos.json ('.count($artigos).' registros)');

        // Homilias
        $homilias = Homilia::where('publicado', true)->orderBy('data', 'desc')->get()
            ->map(fn (Homilia $h) => $h->toJsonExport())->all();
        $this->writeFileAtomically($dataDir.'/homilias.json',
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
        $this->writeFileAtomically($dataDir.'/horarios-missa.json',
            json_encode(['igrejas' => $igrejas], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info('OK horarios-missa.json ('.count($igrejas).' igreja(s))');

        // Agenda pastoral (compromissos)
        $compromissos = Compromisso::where('publico', true)->orderBy('data')->orderBy('hora')->get()
            ->map(fn (Compromisso $c) => $c->toJsonExport())->all();
        $this->writeFileAtomically($dataDir.'/agenda-pastoral.json',
            json_encode(['compromissos' => $compromissos], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info('OK agenda-pastoral.json ('.count($compromissos).' registros)');

        // Ministérios
        $ministerios = Ministerio::where('ativo', true)->orderBy('nome')->get()
            ->map(fn (Ministerio $m) => $m->toJsonExport())->all();
        $this->writeFileAtomically($dataDir.'/ministerios.json',
            json_encode(['ministerios' => $ministerios], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info('OK ministerios.json ('.count($ministerios).' registros)');

        // Ministerios por categoria (pastoral: catequese, estudo-biblico, grupo-oracao)
        $pastoral = array_filter($ministerios, fn ($m) => in_array($m['categoria'] ?? '', ['catequese', 'estudo-biblico', 'grupo-oracao', 'outro']));
        $this->writeFileAtomically($dataDir.'/pastoral.json',
            json_encode(['itens' => array_values($pastoral)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info('OK pastoral.json ('.count($pastoral).' registros)');

        // Pároco ativo
        $paroco = Paroco::where('ativo', true)->first();
        if ($paroco) {
            $this->writeFileAtomically($dataDir.'/paroco.json',
                json_encode($paroco->toJsonExport(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
            $this->info('OK paroco.json');
        } else {
            $this->warn('Nenhum pároco ativo encontrado — paroco.json não atualizado.');
        }

        // Configurações do site
        $config = Configuracao::current();
        $this->writeFileAtomically($dataDir.'/configuracoes.json',
            json_encode($config->toJsonExport(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info('OK configuracoes.json');

        // Rádios
        $radios = Radio::where('ativa', true)->orderBy('destaque', 'desc')->orderBy('ordem')->get()
            ->map(fn (Radio $r) => array_filter([
                'nome'            => $r->nome,
                'url'             => $r->url,
                'descricao'       => $r->descricao,
                'programacao'     => $r->programacao,
                'programacao_url' => $r->programacao_url,
                'hora_inicio'     => $r->hora_inicio ? substr((string) $r->hora_inicio, 0, 5) : null,
                'hora_fim'        => $r->hora_fim    ? substr((string) $r->hora_fim, 0, 5)    : null,
                'favicon'         => $r->favicon,
                'destaque'        => (bool) $r->destaque,
                'categoria'       => $r->categoria ?? 'catolica',
                'estado'          => $r->estado,
                'cidade'          => $r->cidade,
            ], fn ($v) => $v !== null && $v !== ''))->values()->all();

        $regrasBusca = RadioBuscaExterna::where('ativo', true)->orderBy('ordem')->get()
            ->map(fn (RadioBuscaExterna $regra) => array_filter([
                'label'  => $regra->label,
                'tag'    => $regra->tag,
                'pais'   => $regra->pais,
                'estado' => $regra->estado,
                'regiao' => $regra->regiao,
                'limite' => $regra->limite,
            ], fn ($v) => $v !== null && $v !== ''))->values()->all();

        $titulopainel = $config->radio_painel_titulo ?? 'Rádios Católicas ao Vivo';
        $playerAtivo  = $config->radio_player_ativo ?? true;

        $radioPayload = [
            'radios' => $radios,
            'config' => [
                'player_ativo'         => (bool) $playerAtivo,
                'titulo_painel'        => $titulopainel,
                'externas_habilitadas' => count($regrasBusca) > 0,
                'regras'               => $regrasBusca,
            ],
        ];

        $this->writeFileAtomically($dataDir.'/radios.json',
            json_encode($radioPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info('OK radios.json ('.count($radios).' rádio(s), '.count($regrasBusca).' regra(s) externa(s))');

        // Galeria
        $albuns = GaleriaAlbum::with('fotos')
            ->where('publico', true)
            ->orderBy('ordem')
            ->get()
            ->map(fn (GaleriaAlbum $a) => $a->toJsonExport())
            ->all();
        $this->writeFileAtomically($dataDir.'/galeria.json',
            json_encode(['albuns' => $albuns], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info('OK galeria.json ('.count($albuns).' álbum(ns))');

        // Menu
        $menuRaiz = MenuItem::with('filhos')
            ->whereNull('pai_id')
            ->where('visivel', true)
            ->orderBy('ordem')
            ->get()
            ->map(fn (MenuItem $m) => $m->toJsonExport())
            ->values()->all();
        $this->writeFileAtomically($dataDir.'/menu.json',
            json_encode(['items' => $menuRaiz], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info('OK menu.json ('.count($menuRaiz).' item(ns) raiz)');

        // Testemunhos aprovados
        $testemunhos = Testemunho::aprovados()->latest('aprovado_em')->get()
            ->map(fn (Testemunho $t) => $t->toJsonExport())->values()->all();
        $this->writeFileAtomically($dataDir.'/testemunhos.json',
            json_encode(['testemunhos' => $testemunhos], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info('OK testemunhos.json ('.count($testemunhos).' aprovado(s))');

        // História da paróquia (singleton HistoriaPagina)
        $historiaPagina = HistoriaPagina::current();
        $historiaData = $historiaPagina->toJsonExport();
        $this->writeFileAtomically($dataDir.'/historia.json',
            json_encode($historiaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
        $this->info('OK historia.json (HistoriaPagina id='.$historiaPagina->id.')');

        if ($this->option('validate')) {
            $this->info('Validando com schemas...');
            exec('cd '.escapeshellarg($repoRoot).' && node scripts/validate-data.js 2>&1', $out, $code);
            foreach ($out as $line) { $this->line($line); }
            if ($code !== 0) {
                $this->error('Validacao falhou — abortando build.');

                return self::FAILURE;
            }
        }

        if ($this->option('build')) {
            $this->info('Regenerando HTMLs estáticos (PHP puro)...');
            $result = $this->call('content:build-php');
            if ($result !== self::SUCCESS) {
                $this->error('content:build-php falhou.');

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
