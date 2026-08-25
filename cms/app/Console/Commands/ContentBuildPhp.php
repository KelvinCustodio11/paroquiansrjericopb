<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Porta PHP pura do scripts/build-content.js.
 *
 * Faz exatamente o mesmo trabalho sem depender de Node.js — compatível com
 * hospedagem compartilhada Plesk onde node não está disponível.
 *
 * Uso:
 *   php artisan content:build-php
 */
class ContentBuildPhp extends Command
{
    protected $signature = 'content:build-php';

    protected $description = 'Regenera HTMLs estáticos a partir de data/*.json (PHP puro, sem Node.js).';

    // ─── Caminhos ────────────────────────────────────────────────────────────

    private string $root;
    private string $data;
    private string $tpl;
    private string $partials;

    // Campos que devem ser emitidos como HTML bruto (sem escape)
    private const RAW_FIELDS = [
        'conteudo', 'descricao_completa', 'transcricao',
        'transcricao_or_resumo', 'texto_pos_topicos',
    ];

    private const MESES = [
        1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
        'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro',
    ];

    // ─── Entry point ─────────────────────────────────────────────────────────

    public function handle(): int
    {
        $siteRoot = config('site.root')
            ? rtrim((string) config('site.root'), '/')
            : realpath(base_path('..'));

        if (! $siteRoot || ! is_dir($siteRoot)) {
            $this->error("SITE_ROOT inválido ou não encontrado: {$siteRoot}");
            return self::FAILURE;
        }

        $this->root     = $siteRoot;
        $this->data     = $siteRoot . '/data';
        $this->tpl      = $siteRoot . '/templates';
        $this->partials = $siteRoot . '/partials';

        $plan = [
            ['dataFile' => 'eventos.json',  'collection' => 'eventos',  'template' => 'evento.html',  'outDir' => 'eventos'],
            ['dataFile' => 'artigos.json',  'collection' => 'artigos',  'template' => 'artigo.html',  'outDir' => 'artigos'],
            ['dataFile' => 'homilias.json', 'collection' => 'homilias', 'template' => 'homilia.html', 'outDir' => 'homilias'],
        ];

        $total = 0;

        foreach ($plan as $p) {
            $dataPath = "{$this->data}/{$p['dataFile']}";
            $tplPath  = "{$this->tpl}/{$p['template']}";

            if (! file_exists($dataPath) || ! file_exists($tplPath)) {
                $this->line("  - pulando {$p['dataFile']} (data ou template ausente)");
                continue;
            }

            $data  = json_decode(file_get_contents($dataPath), true) ?? [];
            $tpl   = file_get_contents($tplPath);
            $items = $data[$p['collection']] ?? [];

            $outDirPath = "{$this->root}/{$p['outDir']}";
            if (! is_dir($outDirPath)) {
                mkdir($outDirPath, 0755, true);
            }

            $count      = 0;
            $activeSlugs = [];

            foreach ($items as $item) {
                if (($item['publicado'] ?? true) === false) {
                    $this->line("  · {$p['outDir']}/{$item['slug']}.html — rascunho, pulando");
                    continue;
                }

                $enriched = match ($p['collection']) {
                    'eventos'  => $this->enrichEvento($item),
                    'artigos'  => $this->enrichArtigo($item),
                    'homilias' => $this->enrichHomilia($item),
                    default    => $item,
                };

                $rendered = $this->render($tpl, $enriched);
                $rendered = $this->expandPartials($rendered);
                $rendered = $this->rewritePaths($rendered);

                $banner  = "<!-- GENERATED FROM data/{$p['dataFile']}#{$item['slug']} — DO NOT EDIT MANUALLY. Run: php artisan content:build-php -->\n";
                $out     = $banner . $rendered;
                $outFile = "{$outDirPath}/{$item['slug']}.html";

                $before = file_exists($outFile) ? file_get_contents($outFile) : null;
                if ($before !== $out) {
                    file_put_contents($outFile, $out);
                    $this->line("  ✓ {$p['outDir']}/{$item['slug']}.html");
                } else {
                    $this->line("  · {$p['outDir']}/{$item['slug']}.html (sem alterações)");
                }

                $activeSlugs[] = "{$item['slug']}.html";
                $count++;
            }

            // Remove órfãos
            foreach (glob("{$outDirPath}/*.html") as $file) {
                $base = basename($file);
                if (! in_array($base, $activeSlugs, true)) {
                    unlink($file);
                    $this->line("  🗑 {$p['outDir']}/{$base} (órfão removido)");
                }
            }

            $this->line("  - {$p['dataFile']}: {$count} item(ns) processado(s)");
            $total += $count;
        }

        $this->info("Total: {$total} página(s) gerada(s).");

        // ── Seções dinâmicas ────────────────────────────────────────────────
        $this->injectDynamicSections();

        // ── Single-page templates (historia.html, etc.) ──────────────────────
        $this->buildSinglePageTemplates();

        // ── Configurações ───────────────────────────────────────────────────
        $this->applyConfiguracoes();

        // ── Rebuild de partials (header/footer) ─────────────────────────────
        $this->rebuildPartials();

        return self::SUCCESS;
    }

    // ─── Template engine ─────────────────────────────────────────────────────

    private function render(string $tpl, array $ctx): string
    {
        return $this->renderSection($tpl, $ctx);
    }

    private function renderSection(string $tpl, array $ctx): string
    {
        // {{#key}}...{{/key}} e {{^key}}...{{/key}}
        $tpl = preg_replace_callback(
            '/\{\{([#^])([\w.]+)\}\}([\s\S]*?)\{\{\/\2\}\}/s',
            function (array $m) use ($ctx) {
                [$full, $sigil, $key, $inner] = $m;
                $value = $this->getPath($ctx, $key);

                if ($sigil === '^') {
                    $empty = ! $value || (is_array($value) && count($value) === 0);
                    return $empty ? $this->renderSection($inner, $ctx) : '';
                }

                // sigil === '#'
                if (! $value) {
                    return '';
                }
                if (is_array($value) && isset($value[0])) {
                    // array indexado → itera
                    return implode('', array_map(function ($item) use ($inner, $ctx) {
                        $localCtx = is_array($item)
                            ? array_merge($ctx, $item, ['.' => $item])
                            : array_merge($ctx, ['.' => $item]);
                        return $this->renderSection($inner, $localCtx);
                    }, $value));
                }
                if (is_array($value)) {
                    // array associativo → merge
                    return $this->renderSection($inner, array_merge($ctx, $value));
                }
                // truthy primitivo
                return $this->renderSection($inner, $ctx);
            },
            $tpl
        );

        // {{{var}}} sem escape
        $tpl = preg_replace_callback('/\{\{\{([\w.]+)\}\}\}/', function (array $m) use ($ctx) {
            $v = $this->getPath($ctx, $m[1]);
            return $v === null ? '' : (string) $v;
        }, $tpl);

        // {{var}} com escape
        $rawFields = array_flip(self::RAW_FIELDS);
        $tpl = preg_replace_callback('/\{\{([\w.]+)\}\}/', function (array $m) use ($ctx, $rawFields) {
            $key = $m[1];
            if ($key === '.') {
                return htmlspecialchars((string) ($ctx['.'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
            $v = $this->getPath($ctx, $key);
            if ($v === null) {
                return '';
            }
            $leafKey = explode('.', $key);
            $leafKey = end($leafKey);
            if (isset($rawFields[$key]) || isset($rawFields[$leafKey])) {
                return (string) $v;
            }
            if (is_array($v)) {
                return '';
            }
            return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }, $tpl);

        return $tpl;
    }

    private function getPath(array $obj, string $dotted): mixed
    {
        $parts = explode('.', $dotted);
        $cur   = $obj;
        foreach ($parts as $k) {
            if (! is_array($cur) || ! array_key_exists($k, $cur)) {
                return null;
            }
            $cur = $cur[$k];
        }
        return $cur;
    }

    // ─── Partial expansion ────────────────────────────────────────────────────

    private function expandPartials(string $html): string
    {
        return preg_replace_callback(
            '/<!--\s*@include\s+(partials\/[^\s]+\.html)\s*-->/',
            function (array $m) {
                $p = "{$this->root}/{$m[1]}";
                if (! file_exists($p)) {
                    return $m[0];
                }
                $content = file_get_contents($p);
                return "<!-- @include-start {$m[1]} -->\n{$content}\n<!-- @include-end {$m[1]} -->";
            },
            $html
        );
    }

    // ─── Path rewriting (raiz → ../) ─────────────────────────────────────────

    private function rewritePaths(string $html): string
    {
        $roots    = ['images/', 'css/', 'js/', 'webfonts/', 'partials/'];
        $fileExt  = '/\.(html|php|xml|ico|png|jpg|jpeg|webp|svg|gif|css|js|woff2?|ttf)$/i';

        $shouldRewrite = function (string $val) use ($roots, $fileExt): bool {
            if (! $val) {
                return false;
            }
            foreach (['../', '/', 'http://', 'https://', '#', 'mailto:', 'tel:', 'data:', 'javascript:'] as $prefix) {
                if (str_starts_with($val, $prefix)) {
                    return false;
                }
            }
            if (str_starts_with($val, './')) {
                $val = substr($val, 2);
            }
            foreach ($roots as $r) {
                if (str_starts_with($val, $r)) {
                    return true;
                }
            }
            if (! str_contains($val, '/') && preg_match($fileExt, $val)) {
                return true;
            }
            return false;
        };

        // attr="value"
        $html = preg_replace_callback(
            '/\b(href|src|action|data-src|poster)\s*=\s*"([^"#?]+)([?#][^"]*)?"/i',
            function (array $m) use ($shouldRewrite) {
                if (! $shouldRewrite($m[2])) {
                    return $m[0];
                }
                $val = ltrim($m[2], './');
                return "{$m[1]}=\"../{$val}" . ($m[3] ?? '') . '"';
            },
            $html
        );

        // attr='value'
        $html = preg_replace_callback(
            "/\\b(href|src|action|data-src|poster)\\s*=\\s*'([^'#?]+)([?#][^']*)?'/i",
            function (array $m) use ($shouldRewrite) {
                if (! $shouldRewrite($m[2])) {
                    return $m[0];
                }
                $val = ltrim($m[2], './');
                return "{$m[1]}='../{$val}" . ($m[3] ?? '') . "'";
            },
            $html
        );

        // url("...") e url('...')
        $html = preg_replace_callback(
            "/url\\(\\s*(['\"]?)([^'\"\\)]+)\\1\\s*\\)/",
            function (array $m) use ($shouldRewrite) {
                if (! $shouldRewrite($m[2])) {
                    return $m[0];
                }
                $val = ltrim($m[2], './');
                return "url({$m[1]}../{$val}{$m[1]})";
            },
            $html
        );

        return $html;
    }

    // ─── Enrichers ────────────────────────────────────────────────────────────

    private function enrichEvento(array $item): array
    {
        $imgCapa = is_string($item['imagem_capa'] ?? null)
            ? ['url' => $item['imagem_capa'] ? '/' . $this->resolveStorageAsset($item['imagem_capa'], 'events') : '', 'alt' => $item['titulo'] ?? '']
            : ($item['imagem_capa'] ?? ['url' => '', 'alt' => '']);

        $local = is_string($item['local'] ?? null)
            ? ['nome' => $item['local'], 'endereco' => '', 'bairro' => '', 'cidade' => 'Jericó', 'estado' => 'PB', 'pais' => 'BR']
            : ($item['local'] ?? []);

        $horarioInicio = $item['horario_inicio'] ?? $item['hora_inicio'] ?? '';
        $horarioFim    = $item['horario_fim']    ?? $item['hora_fim']    ?? '';

        $descricaoCompleta = $item['descricao_completa'] ?? $item['conteudo'] ?? '';
        $descricaoCurta    = $item['descricao_curta']    ?? $item['resumo']   ?? '';

        $statsBar       = ! empty($item['stats_bar'])        ? $item['stats_bar']        : null;
        $topicosDestaque = ! empty($item['topicos_destaque']) ? ['items' => $item['topicos_destaque']] : null;

        $galeria = null;
        if (! empty($item['galeria']['imagens'])) {
            $galeria = array_merge($item['galeria'], [
                'imagens' => array_map(fn ($img) => [
                    'url' => $img['url'] ? '/' . $this->resolveStorageAsset($img['url'], 'galeria') : '',
                    'alt' => $img['alt'] ?? '',
                ], $item['galeria']['imagens']),
            ]);
        }

        $programacao = ! empty($item['programacao']) ? ['items' => $item['programacao']] : null;

        $statusJsonLd = [
            'agendado'     => 'EventScheduled',
            'em-andamento' => 'EventScheduled',
            'encerrado'    => 'EventScheduled',
            'cancelado'    => 'EventCancelled',
        ][$item['status'] ?? ''] ?? 'EventScheduled';

        $sidebarItemsList      = ! empty($item['sidebar_items'])      ? ['items' => $item['sidebar_items']]      : null;
        $sidebarMilestonesList = ! empty($item['sidebar_milestones']) ? ['items' => $item['sidebar_milestones']] : null;

        return array_merge($item, [
            'imagem_capa'                 => $imgCapa,
            'local'                       => $local,
            'horario_inicio'              => $horarioInicio,
            'horario_fim'                 => $horarioFim,
            'descricao_completa'          => $descricaoCompleta,
            'descricao_curta'             => $descricaoCurta,
            'data_inicio_formatada'       => $this->formatDateBR($item['data_inicio'] ?? ''),
            'data_fim_formatada'          => $this->formatDateBR($item['data_fim']    ?? ''),
            'inscricao_obrigatoria'       => ($item['inscricao']['obrigatoria'] ?? false),
            'local_mapa_url'              => $local['mapa']['google_maps_url'] ?? null,
            'programacao'                 => $programacao,
            'stats_bar'                   => $statsBar,
            'topicos_destaque_list'       => $topicosDestaque,
            'galeria'                     => $galeria,
            'programacao_titulo'          => $item['programacao_titulo']          ?? 'Cronograma',
            'programacao_titulo_destaque' => $item['programacao_titulo_destaque'] ?? 'do Evento',
            'programacao_subtitulo'       => $item['programacao_subtitulo']       ?? 'programação',
            'sidebar_descricao'           => $item['sidebar_descricao'] ?? $descricaoCurta,
            'sidebar_items_list'          => $sidebarItemsList,
            'sidebar_milestones_list'     => $sidebarMilestonesList,
            'jsonld_event_status'         => $statusJsonLd,
        ]);
    }

    private function enrichArtigo(array $item): array
    {
        $imgCapa = is_string($item['imagem_capa'] ?? null)
            ? ['url' => $item['imagem_capa'] ? '/' . $this->resolveStorageAsset($item['imagem_capa'], 'artigos') : '', 'alt' => $item['titulo'] ?? '']
            : ($item['imagem_capa'] ?? ['url' => '', 'alt' => '']);

        return array_merge($item, [
            'imagem_capa'                    => $imgCapa,
            'data_publicacao_formatada'      => $this->formatDateBR($item['data_publicacao'] ?? ''),
            'data_atualizacao_or_publicacao' => $item['data_atualizacao'] ?? $item['data_publicacao'] ?? '',
            'tags_list'                      => ! empty($item['tags']) ? ['items' => $item['tags']] : null,
        ]);
    }

    private function enrichHomilia(array $item): array
    {
        if (is_string($item['imagem_capa'] ?? null)) {
            $imgCapaUrl = $item['imagem_capa'] ? '/' . $this->resolveStorageAsset($item['imagem_capa'], 'homilias') : '';
        } else {
            $imgCapaUrl = $item['imagem_capa']['url'] ?? '';
        }

        $resumoEscapado = htmlspecialchars($item['resumo'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return array_merge($item, [
            'imagem_capa_url'             => $imgCapaUrl,
            'data_formatada'              => $this->formatDateBR($item['data'] ?? ''),
            'leitura_evangelho_referencia' => $item['leitura_evangelho']['referencia'] ?? null,
            'transcricao_or_resumo'       => $item['transcricao'] ?? "<p>{$resumoEscapado}</p>",
        ]);
    }

    // ─── Seções dinâmicas (index.html + eventos.html) ────────────────────────

    private function injectDynamicSections(): void
    {
        $eventosDataPath = "{$this->data}/eventos.json";
        if (! file_exists($eventosDataPath)) {
            $this->line('  - eventos.json ausente, pulando seções dinâmicas');
            return;
        }

        $todos    = (json_decode(file_get_contents($eventosDataPath), true) ?? [])['eventos'] ?? [];
        $visiveis = array_values(array_filter($todos, fn ($e) => ($e['publicado'] ?? true) !== false));

        $this->line("\n  - Injetando seções dinâmicas (" . count($visiveis) . " evento(s))...");

        $indexPath   = "{$this->root}/index.html";
        $eventosPath = "{$this->root}/eventos.html";

        // 1. index.html: our-event destaque
        $destaque = $this->selecionarDestaque($visiveis);
        $html = $destaque
            ? $this->ourEventHtml($destaque, false)
            : $this->ourEventHtmlVazio('próximo evento', 'Nenhum evento <span>agendado</span>', 'eventos.html', 'ver todos os eventos');
        $this->injectSection($indexPath, 'index:evento-destaque', $html);

        // 2. index.html: grade de eventos (.ministries-item)
        $this->injectSection($indexPath, 'index:eventos-grade', $this->buildIndexGrade($visiveis));

        // 3. eventos.html: our-event destaques
        if ($visiveis) {
            $top2 = array_slice($visiveis, 0, 2);
            $html = implode("\n", array_map(
                fn ($ev, $i) => $this->ourEventHtml($ev, $i % 2 !== 0),
                $top2,
                array_keys($top2)  // garante que os dois arrays têm o mesmo tamanho
            ));
        } else {
            $html = $this->ourEventHtmlVazio('próximos eventos', 'Nenhum evento <span>agendado</span>', 'contato.html', 'fale conosco');
        }
        $this->injectSection($eventosPath, 'eventos:destaques', $html);

        // 4. eventos.html: ticker
        $this->injectSection($eventosPath, 'eventos:ticker-content', $this->buildTicker($visiveis));

        // 5. eventos.html: grade de eventos (.campaign-item)
        $this->injectSection($eventosPath, 'eventos:grade', $this->buildEventosGrade($visiveis));

        $this->line('  - Seções dinâmicas concluídas.');
    }

    private function selecionarDestaque(array $lista): ?array
    {
        if (! $lista) {
            return null;
        }
        $hoje    = new \DateTime('today');
        $futuros = array_values(array_filter($lista, function (array $e) use ($hoje) {
            $fim = $e['data_fim'] ?? $e['data_inicio'] ?? null;
            return $fim ? new \DateTime($fim) >= $hoje : true;
        }));
        foreach ($futuros as $e) {
            if ($e['destaque'] ?? false) {
                return $e;
            }
        }
        return $futuros[0] ?? $lista[array_key_last($lista)];
    }

    private function localStr(mixed $local): string
    {
        if (! $local) {
            return '';
        }
        if (is_string($local)) {
            return $local;
        }
        $nome = $local['nome'] ?? '';
        $uf   = implode('/', array_filter([$local['cidade'] ?? '', $local['estado'] ?? '']));
        return implode(' — ', array_filter([$nome, $uf]));
    }

    private function imgPath(mixed $img): string
    {
        if (! $img) {
            return 'images/event-image.jpg';
        }
        if (is_array($img)) {
            $img = $img['url'] ?? '';
        }
        return $img ? $this->resolveStorageAsset((string) $img, 'events') : 'images/event-image.jpg';
    }

    private function ourEventHtml(array $ev, bool $reverseLayout): string
    {
        $img   = htmlspecialchars($this->imgPath($ev['imagem_capa'] ?? null), ENT_QUOTES, 'UTF-8');
        $tit   = htmlspecialchars($ev['titulo'] ?? '', ENT_QUOTES, 'UTF-8');
        $label = htmlspecialchars($ev['categoria'] ?? 'próximo evento', ENT_QUOTES, 'UTF-8');

        $dtParts = [$this->formatDateBR($ev['data_inicio'] ?? '')];
        if (! empty($ev['data_fim']) && $ev['data_fim'] !== $ev['data_inicio']) {
            $dtParts[] = $this->formatDateBR($ev['data_fim']);
        }
        $dt  = htmlspecialchars(implode(' – ', $dtParts) . (! empty($ev['hora_inicio']) ? " — {$ev['hora_inicio']}" : ''), ENT_QUOTES, 'UTF-8');
        $loc = htmlspecialchars($this->localStr($ev['local'] ?? null), ENT_QUOTES, 'UTF-8');
        $res = htmlspecialchars($ev['resumo'] ?? $ev['subtitulo'] ?? '', ENT_QUOTES, 'UTF-8');

        $link   = 'eventos/' . htmlspecialchars($ev['slug'] ?? '', ENT_QUOTES, 'UTF-8') . '.html';
        $colImg = $reverseLayout ? 'col-lg-6 order-lg-2' : 'col-lg-6';
        $colTxt = $reverseLayout ? 'col-lg-6 order-lg-1' : 'col-lg-6';

        $statusBadge = match ($ev['status'] ?? '') {
            'cancelado' => "\n                        <span class=\"badge bg-danger mb-2\">Cancelado</span>",
            'encerrado' => "\n                        <span class=\"badge bg-secondary mb-2\">Encerrado</span>",
            default     => '',
        };

        $resHtml = $res
            ? "\n                    <div class=\"event-footer\">\n                        <p class=\"wow fadeInUp\" data-wow-delay=\"0.5s\">{$res}</p>\n                    </div>"
            : '';

        return <<<HTML
    <div class="our-event">
        <div class="container">
            <div class="row align-items-center">
                <div class="{$colImg}">
                    <div class="event-image">
                        <figure class="image-anime reveal">
                            <img loading="lazy" decoding="async" src="{$img}" onerror="this.src='images/event-image.jpg'" alt="{$tit}">
                        </figure>
                    </div>
                </div>
                <div class="{$colTxt}">
                    <div class="event-content">
                        <div class="section-title">
                            <h3 class="wow fadeInUp">{$label}</h3>
                            <h2 class="text-anime-style-2" data-cursor="-opaque">{$tit}</h2>{$statusBadge}
                        </div>
                        <div class="event-body">
                            <div class="event-item wow fadeInUp">
                                <div class="icon-box"><i class="fa-solid fa-calendar-days"></i></div>
                                <div class="event-item-content"><p>{$dt}</p></div>
                            </div>
                            <div class="event-item wow fadeInUp" data-wow-delay="0.25s">
                                <div class="icon-box"><i class="fa-solid fa-location-dot"></i></div>
                                <div class="event-item-content"><p>{$loc}</p></div>
                            </div>
                        </div>{$resHtml}
                        <div class="event-btn wow fadeInUp" data-wow-delay="0.75s">
                            <a href="{$link}" class="btn-default">ver programação completa</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
HTML;
    }

    private function ourEventHtmlVazio(string $label, string $titulo, string $link, string $btnTxt): string
    {
        return <<<HTML
    <div class="our-event">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="event-image">
                        <figure class="image-anime reveal">
                            <img loading="lazy" decoding="async" src="images/event-image.jpg" alt="">
                        </figure>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="event-content">
                        <div class="section-title">
                            <h3 class="wow fadeInUp">{$label}</h3>
                            <h2 class="text-anime-style-2" data-cursor="-opaque">{$titulo}</h2>
                        </div>
                        <div class="event-btn wow fadeInUp" data-wow-delay="0.5s">
                            <a href="{$link}" class="btn-default">{$btnTxt}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
HTML;
    }

    private function buildIndexGrade(array $visiveis): string
    {
        $fallbacks = ['images/campaign-img-1.jpg', 'images/campaign-img-3.jpg', 'images/campaign-img-2.jpg'];
        $delays    = ['0.1s', '0.25s', '0.5s'];

        if ($visiveis) {
            $slice = array_values(array_slice($visiveis, 0, 3));
            $cards = implode("\n", array_map(function (array $ev, int $i) use ($fallbacks, $delays) {
                $img  = htmlspecialchars($this->imgPath($ev['imagem_capa'] ?? null), ENT_QUOTES, 'UTF-8');
                $tit  = htmlspecialchars($ev['titulo'] ?? '', ENT_QUOTES, 'UTF-8');
                $link = 'eventos/' . htmlspecialchars($ev['slug'] ?? '', ENT_QUOTES, 'UTF-8') . '.html';
                $sub  = htmlspecialchars(
                    implode(' — ', array_filter([
                        $this->formatDateBR($ev['data_inicio'] ?? ''),
                        $ev['hora_inicio'] ?? '',
                        $this->localStr($ev['local'] ?? null),
                    ])),
                    ENT_QUOTES, 'UTF-8'
                );
                $fb    = $fallbacks[$i] ?? $fallbacks[0];
                $delay = $delays[$i] ?? '0.5s';
                $statusBadge = match ($ev['status'] ?? '') {
                    'cancelado' => ' <span class="badge bg-danger" style="font-size:0.65em;">Cancelado</span>',
                    'encerrado' => ' <span class="badge bg-secondary" style="font-size:0.65em;">Encerrado</span>',
                    default     => '',
                };
                return <<<HTML
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="{$delay}">
                    <div class="ministries-item">
                        <div class="ministries-image" data-cursor-text="Ver">
                            <a href="{$link}">
                                <figure>
                                    <img loading="lazy" decoding="async" src="{$img}" onerror="this.src='{$fb}'" alt="{$tit}">
                                </figure>
                            </a>
                        </div>
                        <div class="ministries-content">
                            <h3>{$tit}{$statusBadge}</h3>
                            <p>{$sub}</p>
                        </div>
                        <div class="ministries-btn">
                            <a href="{$link}" class="readmore-btn"><img loading="lazy" decoding="async" src="images/arrow-white.svg" alt=""></a>
                        </div>
                    </div>
                </div>
HTML;
            }, $slice, array_keys($slice)));
        } else {
            $cards = '                <div class="col-lg-12"><p class="text-center">Nenhum evento agendado no momento.</p></div>';
        }

        $footer = <<<HTML
                <div class="col-lg-12">
                    <div class="our-ministries-footer wow fadeInUp" data-wow-delay="0.75s">
                        <p>Confira todos os eventos e novidades da nossa paróquia e participe das celebrações. <a href="eventos.html">Ver Todos os Eventos</a></p>
                    </div>
                </div>
HTML;

        return "            <div class=\"row\">\n{$cards}\n{$footer}\n            </div>";
    }

    private function buildTicker(array $visiveis): string
    {
        $aster = '<img loading="lazy" decoding="async" src="images/icon-asterisk.svg" alt="">';
        $spans = $visiveis
            ? implode("\n", array_map(fn ($ev) => "                    <span>{$aster}" . htmlspecialchars($ev['titulo'] ?? '', ENT_QUOTES, 'UTF-8') . '</span>', $visiveis))
            : "                    <span>{$aster}Confira os próximos eventos da paróquia</span>";

        $block = "                <div class=\"scrolling-content\">\n{$spans}\n                </div>";
        return "{$block}\n{$block}";
    }

    private function buildEventosGrade(array $visiveis): string
    {
        if (! $visiveis) {
            return '                <div class="col-lg-12"><p class="text-center py-5">Nenhum evento agendado no momento. Volte em breve!</p></div>';
        }

        $fallbacks = ['images/campaign-img-1.jpg', 'images/campaign-img-3.jpg', 'images/campaign-img-2.jpg'];
        $catMap    = ['liturgico' => 'Litúrgico', 'pastoral' => 'Pastoral', 'social' => 'Social', 'formativo' => 'Formativo', 'festivo' => 'Festivo', 'outro' => 'Outro'];

        return implode("\n", array_map(function (array $ev, int $i) use ($fallbacks, $catMap) {
            $img   = htmlspecialchars($this->imgPath($ev['imagem_capa'] ?? null), ENT_QUOTES, 'UTF-8');
            $tit   = htmlspecialchars($ev['titulo'] ?? '', ENT_QUOTES, 'UTF-8');
            $link  = 'eventos/' . htmlspecialchars($ev['slug'] ?? '', ENT_QUOTES, 'UTF-8') . '.html';
            $res   = htmlspecialchars($ev['resumo'] ?? $ev['subtitulo'] ?? '', ENT_QUOTES, 'UTF-8');
            $dtParts = [$this->formatDateBR($ev['data_inicio'] ?? '')];
            if (! empty($ev['data_fim']) && $ev['data_fim'] !== $ev['data_inicio']) {
                $dtParts[] = $this->formatDateBR($ev['data_fim']);
            }
            $dt  = htmlspecialchars(implode(' – ', $dtParts), ENT_QUOTES, 'UTF-8');
            $hr  = htmlspecialchars($ev['hora_inicio'] ?? '', ENT_QUOTES, 'UTF-8');
            $loc = htmlspecialchars($this->localStr($ev['local'] ?? null), ENT_QUOTES, 'UTF-8');
            $fb  = $fallbacks[$i % count($fallbacks)];
            $delay = $i > 0 ? ' data-wow-delay="' . number_format($i * 0.25, 2) . 's"' : '';

            $cat    = $ev['categoria'] ?? '';
            $catLabel = $catMap[$cat] ?? htmlspecialchars($cat, ENT_QUOTES, 'UTF-8');
            $catBadge = $cat ? "\n                            <span class=\"badge bg-secondary mb-1\" style=\"font-size:0.7em;font-weight:500;\">{$catLabel}</span>" : '';

            $statusBadge = match ($ev['status'] ?? '') {
                'cancelado' => ' <span class="badge bg-danger" style="font-size:0.65em;">Cancelado</span>',
                'encerrado' => ' <span class="badge bg-secondary" style="font-size:0.65em;">Encerrado</span>',
                default     => '',
            };

            $resRow  = $res  ? "\n                                <p>{$res}</p>"                : '';
            $hrCell  = $hr   ? "\n                                    <div class=\"skill-no\">{$hr}</div>" : '';
            $locRow  = $loc  ? "\n                                <div class=\"skill-data\" style=\"margin-top:8px;margin-bottom:0;\"><div class=\"skill-title\"><i class=\"fa-solid fa-location-dot\"></i> &nbsp;{$loc}</div></div>" : '';

            return <<<HTML
                <div class="col-lg-4 col-md-6">
                    <div class="campaign-item wow fadeInUp"{$delay}>
                        <div class="campaign-image">
                            <figure>
                                <a href="{$link}" class="image-anime" data-cursor-text="Ver">
                                    <img loading="lazy" decoding="async" src="{$img}" onerror="this.src='{$fb}'" alt="{$tit}">
                                </a>
                            </figure>
                        </div>
                        <div class="campaign-body">
                            <div class="campaign-content">{$catBadge}
                                <h2>{$tit}{$statusBadge}</h2>{$resRow}
                            </div>
                            <div class="campaign-btn">
                                <a href="{$link}" class="read-more-btn">ver detalhes</a>
                            </div>
                            <div class="skillbar" style="pointer-events:none;">
                                <div class="skill-data">
                                    <div class="skill-title"><i class="fa-regular fa-calendar-days"></i> &nbsp;{$dt}</div>{$hrCell}
                                </div>{$locRow}
                            </div>
                        </div>
                    </div>
                </div>
HTML;
        }, $visiveis, array_keys($visiveis)));
    }

    // ─── Configurações do site ────────────────────────────────────────────────

    private function applyConfiguracoes(): void
    {
        $configPath = "{$this->data}/configuracoes.json";
        if (! file_exists($configPath)) {
            $this->line('  . configuracoes.json ausente — pulando.');
            return;
        }

        $cfg = json_decode(file_get_contents($configPath), true) ?? [];
        $this->line("\n  - Aplicando configurações do site...");

        // 1. css/theme-cms.css
        $hexRe = '/^#[0-9a-fA-F]{3,8}$/';
        $corAcento = preg_match($hexRe, $cfg['cor_principal']    ?? '') ? $cfg['cor_principal']    : '#acaa59';
        $corEscuro = preg_match($hexRe, $cfg['cor_fundo_escuro'] ?? '') ? $cfg['cor_fundo_escuro'] : '#000000';
        $corClaro  = preg_match($hexRe, $cfg['cor_fundo_claro']  ?? '') ? $cfg['cor_fundo_claro']  : '#FFF4F1';
        $corTexto  = preg_match($hexRe, $cfg['cor_texto']        ?? '') ? $cfg['cor_texto']        : '#525252';

        $cssContent = ":root {\n    --accent-color:    {$corAcento};\n    --primary-color:   {$corEscuro};\n    --secondary-color: {$corClaro};\n    --text-color:      {$corTexto};\n}\n";
        $themeCssPath = "{$this->root}/css/theme-cms.css";
        $themeCssDir  = dirname($themeCssPath);
        if (! is_dir($themeCssDir)) {
            mkdir($themeCssDir, 0755, true);
        }
        if (! file_exists($themeCssPath) || file_get_contents($themeCssPath) !== $cssContent) {
            file_put_contents($themeCssPath, $cssContent);
            $this->line("  ✓ css/theme-cms.css");
        }

        // 2. Hero (index.html)
        $esc = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $heroContent = <<<HTML
		<!-- Section Title Start -->
                        <div class="section-title">
                            <h3 class="wow fadeInUp">{$esc($cfg['hero_tagline'] ?? 'Paróquia Nossa Senhora dos Remédios — Jericó/PB')}</h3>
                            <h1 class="text-anime-style-2" data-cursor="-opaque">{$esc($cfg['hero_titulo'] ?? 'Fé, Esperança e Amor no coração do Sertão Paraibano!')}</h1>
                            <p class="wow fadeInUp" data-wow-delay="0.25s">{$esc($cfg['hero_descricao'] ?? '')}</p>
                        </div>
                        <!-- Section Title End -->

                        <!-- Hero Content Body Start -->
                        <div class="hero-content-body wow fadeInUp" data-wow-delay="0.5s">
                            <a href="{$esc($cfg['hero_btn1_link'] ?? 'agenda-liturgica.html')}" class="btn-default btn-highlighted"><span>{$esc($cfg['hero_btn1_texto'] ?? 'Horários')}</span></a>
                            <a href="{$esc($cfg['hero_btn2_link'] ?? 'agenda-liturgica.html')}" class="btn-default"><span>{$esc($cfg['hero_btn2_texto'] ?? 'Calendário Litúrgico')}</span></a>
                        </div>
                        <!-- Hero Content Body End -->
HTML;
        $this->injectSection("{$this->root}/index.html", 'site:hero-content', $heroContent);

        // 3. Header CTA
        $ctaLink  = $esc($cfg['header_cta_link']  ?? '#');
        $ctaTxt   = $esc($cfg['header_cta_texto'] ?? 'Ouça agora');
        $ctaAttr  = $ctaLink === '#' ? ' data-radio-trigger' : '';
        $headerCta = <<<HTML
					<div class="header-btn d-inline-flex">
                        <a href="{$ctaLink}" class="btn-default"{$ctaAttr}><span>{$ctaTxt}</span></a>
                    </div>
HTML;
        $this->injectSection("{$this->root}/partials/header.html", 'site:header-cta', $headerCta);

        // 4. Footer — descrição
        $footerDesc = $esc($cfg['footer_descricao'] ?? '');
        $this->injectSection("{$this->root}/partials/footer.html", 'site:footer-descricao', "\t\t\t\t\t\t<p>{$footerDesc}</p>");

        // 5. Footer — contato
        $tel      = $esc($cfg['footer_telefone'] ?? '');
        $email    = $esc($cfg['footer_email']    ?? '');
        $endereco = $esc($cfg['footer_endereco'] ?? '');
        $telLink   = 'tel:+55' . preg_replace('/\D/', '', $cfg['footer_telefone'] ?? '');
        $emailLink = 'mailto:' . ($cfg['footer_email'] ?? '');
        $footerContato = <<<HTML
					<div class="footer-info-box">
                            <div class="icon-box"><img src="images/icon-phone.svg" alt="" width="24" height="24"></div>
                            <div class="footer-info-box-content"><p><a href="{$esc($telLink)}">{$tel}</a></p></div>
                        </div>
                        <div class="footer-info-box">
                            <div class="icon-box"><img src="images/icon-mail.svg" alt="" width="24" height="24"></div>
                            <div class="footer-info-box-content"><p><a href="{$esc($emailLink)}">{$email}</a></p></div>
                        </div>
                        <div class="footer-info-box">
                            <div class="icon-box"><img src="images/icon-location.svg" alt="" width="24" height="24"></div>
                            <div class="footer-info-box-content"><p>{$endereco}</p></div>
                        </div>
HTML;
        $this->injectSection("{$this->root}/partials/footer.html", 'site:footer-contato', $footerContato);

        // 6. Footer — redes sociais
        $socialLinks = implode("\n", array_filter([
            ! empty($cfg['footer_facebook'])  ? "\t\t\t\t\t\t\t<li><a href=\"{$esc($cfg['footer_facebook'])}\" target=\"_blank\" rel=\"noopener noreferrer\" aria-label=\"Facebook\"><i class=\"fa-brands fa-facebook-f\" aria-hidden=\"true\"></i></a></li>" : '',
            ! empty($cfg['footer_instagram']) ? "\t\t\t\t\t\t\t<li><a href=\"{$esc($cfg['footer_instagram'])}\" target=\"_blank\" rel=\"noopener noreferrer\" aria-label=\"Instagram\"><i class=\"fa-brands fa-instagram\" aria-hidden=\"true\"></i></a></li>" : '',
            ! empty($cfg['footer_whatsapp'])  ? "\t\t\t\t\t\t\t<li><a href=\"{$esc($cfg['footer_whatsapp'])}\" target=\"_blank\" rel=\"noopener noreferrer\" aria-label=\"WhatsApp\"><i class=\"fa-brands fa-whatsapp\" aria-hidden=\"true\"></i></a></li>" : '',
            ! empty($cfg['footer_youtube'])   ? "\t\t\t\t\t\t\t<li><a href=\"{$esc($cfg['footer_youtube'])}\" target=\"_blank\" rel=\"noopener noreferrer\" aria-label=\"YouTube\"><i class=\"fa-brands fa-youtube\" aria-hidden=\"true\"></i></a></li>" : '',
        ]));
        $this->injectSection("{$this->root}/partials/footer.html", 'site:footer-redes', $socialLinks);

        // 7. Logos
        $logoCor = preg_match('/^#[0-9a-fA-F]{3,8}$/', $cfg['logo_cor'] ?? '') ? $cfg['logo_cor'] : '#acaa59';

        $this->injectSection("{$this->root}/partials/header.html", 'site:header-logo', $this->buildLogo($cfg, 'header', $logoCor));
        $this->injectSection("{$this->root}/partials/footer.html", 'site:footer-logo',  $this->buildLogo($cfg, 'footer', $logoCor));
        $this->injectSection("{$this->root}/partials/header.html", 'site:loader-logo',  $this->buildLogo($cfg, 'loader', $logoCor));

        $this->line("  ✓ configurações aplicadas (cor: {$logoCor})");
    }

    private function buildLogo(array $cfg, string $type, string $logoCor): string
    {
        $imgKey = "logo_{$type}_img";
        if (! empty($cfg[$imgKey])) {
            $src = $this->resolveStorageAsset($cfg[$imgKey], 'logos');

            if ($src !== '') {
                $srcEsc = htmlspecialchars($src, ENT_QUOTES, 'UTF-8');
                return match ($type) {
                    'header' => "<img src=\"{$srcEsc}\" alt=\"Logo Paróquia Nossa Senhora dos Remédios\" style=\"max-height:55px;\" loading=\"lazy\">",
                    'loader' => "<img src=\"{$srcEsc}\" alt=\"\" style=\"width:66px;height:auto;\">",
                    default  => "<img src=\"{$srcEsc}\" alt=\"Paróquia Nossa Senhora dos Remédios\" style=\"height:auto;max-height:80px;width:auto;\" loading=\"lazy\">",
                };
            }
        }

        // SVG inline com troca de cor
        [$svgFile, $fillFrom, $extraAttrs] = match ($type) {
            'header' => ['images/logo.svg', '#acaa59', 'style="max-height:55px;" role="img" aria-label="Logo Paróquia Nossa Senhora dos Remédios"'],
            'loader' => ['images/loader.svg', 'white', 'role="img" aria-hidden="true"'],
            default  => ['images/footer-logo.svg', '#acaa59', 'style="height:auto;max-height:80px;width:auto;" role="img" aria-label="Paróquia Nossa Senhora dos Remédios"'],
        };

        $svgPath = "{$this->root}/{$svgFile}";
        if (file_exists($svgPath)) {
            $svg = file_get_contents($svgPath);
            $svg = str_replace("fill=\"{$fillFrom}\"", "fill=\"{$logoCor}\"", $svg);
            $svg = str_replace('<svg ', "<svg {$extraAttrs} ", $svg);
            return $svg;
        }

        // Fallback PNG
        return match ($type) {
            'header' => '<img src="images/logo.png" alt="Logo Paróquia Nossa Senhora dos Remédios" style="max-height:55px;" width="140" height="55">',
            'loader' => '<img src="images/loader.png" alt="">',
            default  => '<img src="images/footer-logo.png" alt="Paróquia Nossa Senhora dos Remédios" width="200" height="102" style="height:auto;max-height:80px;width:auto;">',
        };
    }

    /**
     * Resolve um caminho de imagem salvo pelo CMS.
     *
     * Caminhos que começam com "storage/" foram enviados via disco "public" do Laravel
     * e vivem em cms/storage/app/public/{rest}. Este método:
     *   1. Copia o arquivo para {siteRoot}/images/uploads/{destSubDir}/{filename}
     *   2. Retorna o caminho relativo para uso no HTML ("images/uploads/{destSubDir}/{filename}")
     *
     * Caminhos que já começam com "images/" são retornados sem modificação.
     *
     * @param  string  $path       Valor armazenado no JSON/banco (ex: "storage/uploads/logos/x.png")
     * @param  string  $destSubDir Subpasta de destino em images/uploads/ (ex: "artigos")
     * @return string              Caminho relativo para uso em src=""
     */
    private function resolveStorageAsset(string $path, string $destSubDir): string
    {
        $path = ltrim($path, '/');

        if (! str_starts_with($path, 'storage/')) {
            return $path; // caminho legado (images/uploads/...) — usar como está
        }

        $relativeToPub  = substr($path, strlen('storage/')); // ex: "uploads/artigos/x.jpg"
        $cmsStoragePath = storage_path('app/public/' . $relativeToPub);
        $filename       = basename($cmsStoragePath);
        $destDir        = "{$this->root}/images/uploads/{$destSubDir}/";
        $destPath       = $destDir . $filename;

        if (file_exists($cmsStoragePath)) {
            if (! is_dir($destDir)) {
                mkdir($destDir, 0775, true);
            }
            if (! file_exists($destPath) || md5_file($cmsStoragePath) !== md5_file($destPath)) {
                copy($cmsStoragePath, $destPath);
            }
        }

        return "images/uploads/{$destSubDir}/{$filename}";
    }

    // ─── Single-page templates ────────────────────────────────────────────────

    /**
     * Processa templates que geram um único arquivo HTML na raiz do site
     * (ex: historia.html). O JSON correspondente representa um objeto único,
     * não uma coleção.
     *
     * Plano: [dataFile => templates/X.html => raiz/Y.html]
     * - Sem rewrite de paths (ficheiro fica na raiz, caminhos relativos já corretos)
     * - Sem remoção de órfãos (arquivo é único e fixo)
     */
    private function buildSinglePageTemplates(): void
    {
        $this->line("\n  - Processando single-page templates...");

        $plan = [
            [
                'dataFile' => 'historia.json',
                'template' => 'historia.html',
                'outFile'  => 'historia.html',
            ],
        ];

        foreach ($plan as $p) {
            $dataPath = "{$this->data}/{$p['dataFile']}";
            $tplPath  = "{$this->tpl}/{$p['template']}";

            if (! file_exists($dataPath) || ! file_exists($tplPath)) {
                $this->line("  · pulando {$p['template']} (data ou template ausente)");
                continue;
            }

            $data     = json_decode(file_get_contents($dataPath), true) ?? [];
            $tpl      = file_get_contents($tplPath);
            foreach (['about_imagem1', 'about_imagem2', 'missao_imagem', 'paroco_imagem', 'paroco_assinatura'] as $field) {
                if (! empty($data[$field]) && is_string($data[$field])) {
                    $data[$field] = $this->resolveStorageAsset($data[$field], 'historia');
                }
            }
            $rendered = $this->render($tpl, $data);
            $rendered = $this->expandPartials($rendered);
            // single-pages ficam na raiz — sem rewritePaths

            $banner  = "<!-- GENERATED FROM data/{$p['dataFile']} — DO NOT EDIT MANUALLY. Run: php artisan content:build-php -->\n";
            $out     = $banner . $rendered;
            $outPath = "{$this->root}/{$p['outFile']}";

            $before = file_exists($outPath) ? file_get_contents($outPath) : null;
            if ($before !== $out) {
                file_put_contents($outPath, $out);
                $this->line("  ✓ {$p['outFile']} (single-page)");
            } else {
                $this->line("  · {$p['outFile']} (single-page, sem alterações)");
            }
        }
    }

    // ─── Rebuild de partials (propaga header/footer em todas as páginas) ──────

    /**
     * Porta PHP do build.js:
     * Varre todos os *.html da raiz do site e expande/atualiza os blocos
     * <!-- @include-start partials/X.html --> ... <!-- @include-end partials/X.html -->
     * com o conteúdo atual de partials/X.html.
     * Idempotente — só grava se o conteúdo mudou.
     */
    private function rebuildPartials(): void
    {
        $this->line("\n  - Propagando partials em todas as páginas (PHP puro)...");

        $excludes = [
            '_template-avenix', '_template-paroquia', 'partials', 'templates',
            'node_modules', '.git', 'images', 'css', 'js', 'webfonts',
            'data', 'schemas', 'scripts', 'eventos', 'artigos', 'homilias',
            'docker-plesk', 'cms',
        ];

        $files   = $this->listHtmlFiles($this->root, $excludes);
        $changed = 0;

        foreach ($files as $file) {
            $original = file_get_contents($file);

            // Ignora arquivos sem nenhum marcador @include
            if (! preg_match('/<!--\s*@include(?:-start)?\s+/', $original)) {
                continue;
            }

            $updated = $this->expandIncludes($original);

            if ($updated !== $original) {
                file_put_contents($file, $updated);
                $rel = str_replace($this->root . '/', '', $file);
                $this->line("  ✓ {$rel}");
                $changed++;
            }
        }

        $this->line("  - Partials: {$changed} página(s) atualizada(s).");
    }

    private function listHtmlFiles(string $dir, array $excludes): array
    {
        $files = [];
        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (in_array($entry, $excludes, true) || str_starts_with($entry, '.')) {
                continue;
            }
            $full = "{$dir}/{$entry}";
            if (is_dir($full)) {
                $files = array_merge($files, $this->listHtmlFiles($full, $excludes));
            } elseif (str_ends_with($entry, '.html')) {
                $files[] = $full;
            }
        }
        return $files;
    }

    private function expandIncludes(string $html): string
    {
        // 1) Pares -start/-end existentes: regenera o miolo
        $html = preg_replace_callback(
            '/[ \t]*<!--\s*@include-start\s+([^\s]+?)\s*-->[\s\S]*?<!--\s*@include-end\s+\1\s*-->[ \t]*\n?/',
            fn (array $m) => $this->buildIncludeBlock($m[1]),
            $html
        );

        // 2) Marcadores simples: insere bloco completo
        $html = preg_replace_callback(
            '/[ \t]*<!--\s*@include\s+([^\s]+?)\s*-->[ \t]*\n?/',
            fn (array $m) => $this->buildIncludeBlock($m[1]),
            $html
        );

        return $html;
    }

    private function buildIncludeBlock(string $rel): string
    {
        $path = "{$this->root}/{$rel}";
        if (! file_exists($path)) {
            // mantém o marcador original se o partial não existir
            return "<!-- @include {$rel} -->\n";
        }
        $content = trim(file_get_contents($path));
        // Resolve aninhamento de 1 nível (partials que incluem outros partials)
        $content = $this->expandIncludes($content);
        return "<!-- @include-start {$rel} -->\n{$content}\n<!-- @include-end {$rel} -->\n";
    }

    // ─── Inject section helper ────────────────────────────────────────────────

    private function injectSection(string $filePath, string $sectionName, string $content): void
    {
        $start = "<!-- @section-start {$sectionName} -->";
        $end   = "<!-- @section-end {$sectionName} -->";

        if (! file_exists($filePath)) {
            $this->line("  ! " . basename($filePath) . ": arquivo não encontrado");
            return;
        }

        $html = file_get_contents($filePath);
        $si   = strpos($html, $start);
        $ei   = strpos($html, $end);

        if ($si === false || $ei === false || $si >= $ei) {
            $this->line("  ! " . basename($filePath) . ": marcador \"{$sectionName}\" não encontrado");
            return;
        }

        $newHtml = substr($html, 0, $si + strlen($start)) . "\n" . $content . "\n" . substr($html, $ei);

        if ($newHtml === $html) {
            $this->line("  . " . basename($filePath) . ": {$sectionName} (sem alterações)");
            return;
        }

        file_put_contents($filePath, $newHtml);
        $this->line("  ✓ " . basename($filePath) . ": {$sectionName}");
    }

    // ─── Utilitários ─────────────────────────────────────────────────────────

    private function formatDateBR(string $iso): string
    {
        if (! $iso) {
            return '';
        }
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $iso, $m)) {
            return $iso;
        }
        $d  = (int) $m[3];
        $mo = (int) $m[2];
        $y  = $m[1];
        if ($mo < 1 || $mo > 12) {
            return $iso;
        }
        return "{$d} de " . self::MESES[$mo] . " de {$y}";
    }
}
