#!/usr/bin/env node
/**
 * scripts/build-content.js
 *
 * Gerador estatico data-driven.
 * Le data/{eventos,artigos,homilias}.json + templates/{evento,artigo,homilia}.html
 * e produz arquivos em {eventos,artigos,homilias}/{slug}.html.
 *
 * Cada arquivo gerado:
 *   - tem cabecalho <!-- GENERATED FROM data/X.json#slug — DO NOT EDIT -->
 *   - tem partials expandidos inline (mesmas regras do build.js, marcadores @include-start/-end)
 *   - tem paths relativos reescritos com ../ (ja que esta um nivel abaixo da raiz)
 *
 * Mini template engine (sem dependencias):
 *   {{var}}              -> data.var (escapa HTML por padrao)
 *   {{{var}}}            -> data.var (sem escape — usar para HTML)
 *   {{var.sub.path}}     -> data.var.sub.path
 *   {{#chave}}...{{/}}   -> renderiza bloco se chave truthy / itera array
 *   {{^chave}}...{{/}}   -> renderiza se chave for falsy
 *   {{.}}                -> item corrente do array
 *
 * Idempotente.
 */
'use strict';

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const DATA = path.join(ROOT, 'data');
const TPL  = path.join(ROOT, 'templates');
const PARTIALS = path.join(ROOT, 'partials');

const PLAN = [
    { dataFile: 'eventos.json',  collection: 'eventos',  template: 'evento.html',  outDir: 'eventos',  enrich: enrichEvento },
    { dataFile: 'artigos.json',  collection: 'artigos',  template: 'artigo.html',  outDir: 'artigos',  enrich: enrichArtigo },
    { dataFile: 'homilias.json', collection: 'homilias', template: 'homilia.html', outDir: 'homilias', enrich: enrichHomilia },
];

// Modo preview: node build-content.js --preview-stdin --type evento
const PREVIEW_MODE = process.argv.includes('--preview-stdin');

// =============================================================
// Utils
// =============================================================

function escapeHtml(s) {
    if (s == null) return '';
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function getPath(obj, dotted) {
    if (obj == null) return undefined;
    return dotted.split('.').reduce((acc, k) => (acc == null ? undefined : acc[k]), obj);
}

const MESES = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
function formatDateBR(iso) {
    if (!iso) return '';
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(iso);
    if (!m) return iso;
    const [_, y, mo, d] = m;
    const month = parseInt(mo, 10);
    return month >= 1 && month <= 12 ? `${parseInt(d,10)} de ${MESES[month-1]} de ${y}` : iso;
}

function resolveStorageAsset(value, destination) {
    if (!value) return '';
    const normalized = String(value).replace(/^\/+/, '');
    if (!normalized.startsWith('storage/')) return normalized;
    const relative = normalized.slice('storage/'.length);
    const source = path.join(ROOT, 'cms', 'storage', 'app', 'public', relative);
    const filename = path.basename(relative);
    const targetDir = path.join(ROOT, 'images', 'uploads', destination);
    const target = path.join(targetDir, filename);
    if (fs.existsSync(source)) {
        fs.mkdirSync(targetDir, { recursive: true });
        if (!fs.existsSync(target) || fs.readFileSync(source).compare(fs.readFileSync(target)) !== 0) {
            fs.copyFileSync(source, target);
        }
    }
    return `images/uploads/${destination}/${filename}`;
}

// =============================================================
// Mini template engine (inspirado em Mustache, subset)
// =============================================================

function render(template, context) {
    return renderSection(template, context);
}

function renderSection(tpl, ctx) {
    // Processa primeiro os blocos {{#X}}...{{/X}} e {{^X}}...{{/X}}
    const sectionRe = /\{\{([#^])([\w.]+)\}\}([\s\S]*?)\{\{\/\2\}\}/g;
    let out = tpl.replace(sectionRe, (full, sigil, key, inner) => {
        const value = getPath(ctx, key);
        if (sigil === '^') {
            return (!value || (Array.isArray(value) && value.length === 0)) ? renderSection(inner, ctx) : '';
        }
        // sigil === '#'
        if (!value) return '';
        if (Array.isArray(value)) {
            return value.map(item => {
                // contexto local merge: campos do item + raiz
                const localCtx = (typeof item === 'object' && item !== null)
                    ? Object.assign({}, ctx, item, { '.': item })
                    : Object.assign({}, ctx, { '.': item });
                return renderSection(inner, localCtx);
            }).join('');
        }
        if (typeof value === 'object') {
            const localCtx = Object.assign({}, ctx, value);
            return renderSection(inner, localCtx);
        }
        // truthy primitivo (boolean true, string, numero) → renderiza inner
        return renderSection(inner, ctx);
    });

    // Substitui {{{var}}} sem escape
    out = out.replace(/\{\{\{([\w.]+)\}\}\}/g, (full, key) => {
        const v = getPath(ctx, key);
        return v == null ? '' : String(v);
    });

    // Substitui {{var}} com escape (mas pula campos que sabemos serem HTML)
    const RAW_FIELDS = new Set(['conteudo', 'descricao_completa', 'transcricao', 'transcricao_or_resumo', 'texto_pos_topicos']);
    out = out.replace(/\{\{([\w.]+)\}\}/g, (full, key) => {
        if (key === '.') return escapeHtml(ctx['.'] != null ? ctx['.'] : '');
        const v = getPath(ctx, key);
        if (v == null) return '';
        if (RAW_FIELDS.has(key) || RAW_FIELDS.has(key.split('.').pop())) return String(v);
        if (typeof v === 'object') return ''; // objeto sem path completo
        return escapeHtml(v);
    });

    return out;
}

// =============================================================
// Enrichers — adicionam campos derivados ao item antes do render
// =============================================================

function enrichEvento(item) {
    // Normaliza imagem_capa: aceita string ou objeto {url, alt}
    const imgCapa = (typeof item.imagem_capa === 'string')
        ? { url: item.imagem_capa ? '/' + resolveStorageAsset(item.imagem_capa, 'events') : '', alt: item.titulo || '' }
        : (item.imagem_capa || { url: '', alt: '' });

    // Normaliza local: aceita string simples ou objeto {nome, endereco, cidade, estado}
    const local = (typeof item.local === 'string')
        ? { nome: item.local, endereco: '', bairro: '', cidade: 'Jericó', estado: 'PB', pais: 'BR' }
        : (item.local || {});

    // Aceita hora_inicio ou horario_inicio (compatibilidade)
    const horarioInicio = item.horario_inicio || item.hora_inicio || '';
    const horarioFim    = item.horario_fim    || item.hora_fim    || '';

    // descricao_completa: aceita conteudo ou descricao_completa
    const descricaoCompleta = item.descricao_completa || item.conteudo || '';

    // descricao_curta: aceita resumo ou descricao_curta
    const descricaoCurta = item.descricao_curta || item.resumo || '';

    // --- Campos ricos opcionais ---

    // Barra de estatísticas (stats_bar): array de {valor, legenda}
    const statsBar = (item.stats_bar && item.stats_bar.length > 0) ? item.stats_bar : null;

    // Tópicos em destaque (checkmarks): envolve em {items} para iteração no template
    const topicosDestaque = (item.topicos_destaque && item.topicos_destaque.length > 0)
        ? { items: item.topicos_destaque }
        : null;

    // Galeria de fotos: normaliza URLs das imagens adicionando '/'
    let galeria = null;
    if (item.galeria && item.galeria.imagens && item.galeria.imagens.length > 0) {
        galeria = Object.assign({}, item.galeria, {
            imagens: item.galeria.imagens.map(img => ({
                url: img.url ? '/' + resolveStorageAsset(img.url, 'galeria') : '',
                alt: img.alt || '',
            })),
        });
    }

    // Títulos da seção de programação
    const programacaoTitulo         = item.programacao_titulo          || 'Cronograma';
    const programacaoTituloDestaque = item.programacao_titulo_destaque || 'do Evento';
    const programacaoSubtitulo      = item.programacao_subtitulo       || 'programação';

    // Sidebar
    const sidebarDescricao = item.sidebar_descricao || descricaoCurta;
    const sidebarItemsList = (item.sidebar_items && item.sidebar_items.length > 0)
        ? { items: item.sidebar_items }
        : null;
    const sidebarMilestonesList = (item.sidebar_milestones && item.sidebar_milestones.length > 0)
        ? { items: item.sidebar_milestones }
        : null;

    return Object.assign({}, item, {
        imagem_capa:                 imgCapa,
        local:                       local,
        horario_inicio:              horarioInicio,
        horario_fim:                 horarioFim,
        descricao_completa:          descricaoCompleta,
        descricao_curta:             descricaoCurta,
        data_inicio_formatada:       formatDateBR(item.data_inicio),
        data_fim_formatada:          formatDateBR(item.data_fim),
        inscricao_obrigatoria:       item.inscricao && item.inscricao.obrigatoria,
        local_mapa_url:              local && local.mapa && local.mapa.google_maps_url,
        programacao:                 (item.programacao && item.programacao.length > 0) ? { items: item.programacao } : null,
        // Campos ricos
        stats_bar:                   statsBar,
        topicos_destaque_list:       topicosDestaque,
        galeria:                     galeria,
        programacao_titulo:          programacaoTitulo,
        programacao_titulo_destaque: programacaoTituloDestaque,
        programacao_subtitulo:       programacaoSubtitulo,
        sidebar_descricao:           sidebarDescricao,
        sidebar_items_list:          sidebarItemsList,
        sidebar_milestones_list:     sidebarMilestonesList,
        // JSON-LD eventStatus derivado do campo status
        jsonld_event_status: ({ agendado: 'EventScheduled', 'em-andamento': 'EventScheduled', encerrado: 'EventScheduled', cancelado: 'EventCancelled' })[item.status] || 'EventScheduled',
    });
}

function enrichArtigo(item) {
    // Normaliza imagem_capa: aceita string ou objeto {url, alt}
    const imgCapa = (typeof item.imagem_capa === 'string')
        ? { url: item.imagem_capa ? '/' + resolveStorageAsset(item.imagem_capa, 'artigos') : '', alt: item.titulo || '' }
        : (item.imagem_capa || { url: '', alt: '' });

    return Object.assign({}, item, {
        imagem_capa:                     imgCapa,
        data_publicacao_formatada:       formatDateBR(item.data_publicacao),
        data_atualizacao_or_publicacao:  item.data_atualizacao || item.data_publicacao,
        tags_list: (item.tags && item.tags.length > 0) ? { items: item.tags } : null,
    });
}

function enrichHomilia(item) {
    // Normaliza imagem_capa: aceita string ou objeto {url}
    const imgCapaUrl = (typeof item.imagem_capa === 'string')
        ? (item.imagem_capa ? '/' + resolveStorageAsset(item.imagem_capa, 'homilias') : '')
        : (item.imagem_capa && item.imagem_capa.url ? item.imagem_capa.url : '');

    return Object.assign({}, item, {
        imagem_capa_url:             imgCapaUrl,
        data_formatada:              formatDateBR(item.data),
        leitura_evangelho_referencia: item.leitura_evangelho && item.leitura_evangelho.referencia,
        transcricao_or_resumo:       item.transcricao || `<p>${escapeHtml(item.resumo)}</p>`,
    });
}

// =============================================================
// Partial expansion + path rewriting (subpasta -> ../)
// =============================================================

const PARTIAL_RE = /<!--\s*@include\s+(partials\/[^\s]+\.html)\s*-->/g;

function expandPartials(html) {
    return html.replace(PARTIAL_RE, (full, rel) => {
        const p = path.join(ROOT, rel);
        if (!fs.existsSync(p)) return full;
        const content = fs.readFileSync(p, 'utf8');
        return `<!-- @include-start ${rel} -->\n${content}\n<!-- @include-end ${rel} -->`;
    });
}

/**
 * Reescreve paths relativos a raiz para usar ../ (arquivos gerados estao 1 nivel abaixo).
 * Atinge: href="X.html", href="X/", src="images/...", src="css/...", src="js/...",
 * src="webfonts/...", action="form-process.php", url('images/...'), url("images/...")
 * NAO altera: caminhos absolutos (/, http, https), com #, mailto, tel, ja com ../
 */
function rewritePaths(html) {
    // Lista de prefixos de pastas / arquivos a prefixar com ../
    const ROOTS = ['images/', 'css/', 'js/', 'webfonts/', 'partials/'];
    const FILE_EXT = /\.(html|php|xml|ico|png|jpg|jpeg|webp|svg|gif|css|js|woff2?|ttf)$/i;

    function shouldRewrite(value) {
        if (!value) return false;
        if (value.startsWith('../') || value.startsWith('/') || value.startsWith('http://') || value.startsWith('https://')
            || value.startsWith('#') || value.startsWith('mailto:') || value.startsWith('tel:')
            || value.startsWith('data:') || value.startsWith('javascript:')) return false;
        if (value.startsWith('./')) value = value.slice(2);
        if (ROOTS.some(r => value.startsWith(r))) return true;
        // arquivo na raiz com extensao conhecida (ex.: index.html, contato.html, form-process.php)
        if (!value.includes('/') && FILE_EXT.test(value)) return true;
        return false;
    }

    // attr="value"
    html = html.replace(/\b(href|src|action|data-src|poster)\s*=\s*"([^"#?]+)([?#][^"]*)?"/g, (m, attr, val, qf) => {
        if (!shouldRewrite(val)) return m;
        return `${attr}="../${val.replace(/^\.\//,'')}${qf||''}"`;
    });
    // attr='value'
    html = html.replace(/\b(href|src|action|data-src|poster)\s*=\s*'([^'#?]+)([?#][^']*)?'/g, (m, attr, val, qf) => {
        if (!shouldRewrite(val)) return m;
        return `${attr}='../${val.replace(/^\.\//,'')}${qf||''}'`;
    });
    // url("...") e url('...') em CSS inline
    html = html.replace(/url\(\s*(['"]?)([^'")]+)\1\s*\)/g, (m, q, val) => {
        if (!shouldRewrite(val)) return m;
        return `url(${q}../${val.replace(/^\.\//,'')}${q})`;
    });
    return html;
}

// =============================================================
// Loop principal + secoes dinamicas (pulado no modo preview)
// =============================================================

if (!PREVIEW_MODE) {

let totalGerado = 0;

for (const plan of PLAN) {
    const dataPath = path.join(DATA, plan.dataFile);
    const tplPath  = path.join(TPL, plan.template);
    if (!fs.existsSync(dataPath) || !fs.existsSync(tplPath)) {
        console.log(`- pulando ${plan.dataFile} (data ou template ausente)`);
        continue;
    }
    const data = JSON.parse(fs.readFileSync(dataPath, 'utf8'));
    const tpl  = fs.readFileSync(tplPath, 'utf8');
    const items = data[plan.collection] || [];

    const outDirPath = path.join(ROOT, plan.outDir);
    if (!fs.existsSync(outDirPath)) fs.mkdirSync(outDirPath, { recursive: true });

    let count = 0;
    for (const item of items) {
        if (item.publicado === false) {
            console.log(`  · ${plan.outDir}/${item.slug}.html — rascunho (publicado=false), pulando`);
            continue;
        }
        const enriched = plan.enrich(item);
        let rendered = render(tpl, enriched);
        rendered = expandPartials(rendered);
        rendered = rewritePaths(rendered);

        const banner = `<!-- GENERATED FROM data/${plan.dataFile}#${item.slug} — DO NOT EDIT MANUALLY. Run: npm run build:content -->\n`;
        const out = banner + rendered;

        const outFile = path.join(outDirPath, `${item.slug}.html`);
        const before = fs.existsSync(outFile) ? fs.readFileSync(outFile, 'utf8') : null;
        if (before !== out) {
            fs.writeFileSync(outFile, out, 'utf8');
            console.log(`  ✓ ${plan.outDir}/${item.slug}.html`);
        } else {
            console.log(`  · ${plan.outDir}/${item.slug}.html (sem alteracoes)`);
        }
        count++;
    }

    // Remove arquivos órfãos (slugs que não existem mais no JSON)
    const slugsAtivos = new Set(
        items.filter(i => i.publicado !== false).map(i => `${i.slug}.html`)
    );
    const existentes = fs.readdirSync(outDirPath).filter(f => f.endsWith('.html'));
    for (const file of existentes) {
        if (!slugsAtivos.has(file)) {
            fs.unlinkSync(path.join(outDirPath, file));
            console.log(`  🗑 ${plan.outDir}/${file} (órfão removido)`);
        }
    }

    console.log(`- ${plan.dataFile}: ${count} item(ns) processado(s)\n`);
    totalGerado += count;
}

console.log(`Total: ${totalGerado} pagina(s) gerada(s).`);

// =============================================================
// Injecao de secoes dinamicas em index.html e eventos.html
// Substitui o conteudo entre <!-- @section-start X --> e
// <!-- @section-end X --> com HTML gerado a partir de eventos.json
// =============================================================
(function injectDynamicSections() {
    const eventosDataPath = path.join(DATA, 'eventos.json');
    if (!fs.existsSync(eventosDataPath)) {
        console.log('\n- eventos.json ausente, pulando injecao de secoes dinamicas');
        return;
    }

    const todos    = JSON.parse(fs.readFileSync(eventosDataPath, 'utf8')).eventos || [];
    const visiveis = todos.filter(e => e.publicado !== false);

    console.log(`\n- Injetando secoes dinamicas (${visiveis.length} evento(s) visivel(is))...`);

    // ── helpers ────────────────────────────────────────────────

    function localStr(local) {
        if (!local) return '';
        if (typeof local === 'string') return local;
        const nome = local.nome || '';
        const uf   = [local.cidade, local.estado].filter(Boolean).join('/');
        return [nome, uf].filter(Boolean).join(' — ');
    }

    function imgPath(img) {
        if (!img) return 'images/event-image.jpg';
        return String(img).replace(/^\//, '');
    }

    function dataHoraStr(ev) {
        const d = formatDateBR(ev.data_inicio);
        const h = ev.hora_inicio ? ` — ${ev.hora_inicio}` : '';
        return d + h;
    }

    /**
     * Substitui o conteudo entre os marcadores @section-start/end.
     * Idempotente: so grava se o conteudo mudou.
     */
    function injectSection(filePath, sectionName, content) {
        const startMarker = `<!-- @section-start ${sectionName} -->`;
        const endMarker   = `<!-- @section-end ${sectionName} -->`;
        let html;
        try { html = fs.readFileSync(filePath, 'utf8'); }
        catch (_) { console.log(`  ! ${path.basename(filePath)}: arquivo nao encontrado`); return; }
        const si = html.indexOf(startMarker);
        const ei = html.indexOf(endMarker);
        if (si === -1 || ei === -1 || si >= ei) {
            console.log(`  ! ${path.basename(filePath)}: marcador "${sectionName}" nao encontrado`);
            return;
        }
        const newHtml = html.slice(0, si + startMarker.length) + '\n' + content + '\n' + html.slice(ei);
        if (newHtml === html) {
            console.log(`  . ${path.basename(filePath)}: ${sectionName} (sem alteracoes)`);
            return;
        }
        fs.writeFileSync(filePath, newHtml, 'utf8');
        console.log(`  ✓ ${path.basename(filePath)}: ${sectionName}`);
    }

    /** Evento destaque: prefere destaque=true futuro, senão primeiro futuro, senão último */
    function selecionarDestaque(lista) {
        if (!lista.length) return null;
        const hoje = new Date(); hoje.setHours(0, 0, 0, 0);
        const futuros = lista.filter(e => {
            const fim = e.data_fim || e.data_inicio;
            return fim ? new Date(fim) >= hoje : true;
        });
        return futuros.find(e => e.destaque) || futuros[0] || lista[lista.length - 1];
    }

    /**
     * Gera um bloco <div class="our-event"> completo com o padrao visual Avenix.
     * reverseLayout=true coloca imagem a direita (order-lg-2/order-lg-1).
     */
    function ourEventHtml(ev, reverseLayout) {
        const img    = escapeHtml(imgPath(ev.imagem_capa));
        const tit    = escapeHtml(ev.titulo || '');
        const label  = escapeHtml(ev.categoria || 'próximo evento');
        const status = ev.status || '';
        // data_fim só exibe quando diferente de data_inicio
        const dtParts = [formatDateBR(ev.data_inicio)];
        if (ev.data_fim && ev.data_fim !== ev.data_inicio) dtParts.push(formatDateBR(ev.data_fim));
        const dt  = escapeHtml(dtParts.join(' – ') + (ev.hora_inicio ? ` — ${ev.hora_inicio}` : ''));
        const loc = escapeHtml(localStr(ev.local));
        const res = escapeHtml(ev.resumo || ev.subtitulo || '');
        const link  = `eventos/${escapeHtml(ev.slug)}.html`;
        const colImg = reverseLayout ? 'col-lg-6 order-lg-2' : 'col-lg-6';
        const colTxt = reverseLayout ? 'col-lg-6 order-lg-1' : 'col-lg-6';
        const statusBadgeMap = { cancelado: ['danger', 'Cancelado'], encerrado: ['secondary', 'Encerrado'] };
        const statusBadge = statusBadgeMap[status]
            ? `\n                        <span class="badge bg-${statusBadgeMap[status][0]} mb-2">${statusBadgeMap[status][1]}</span>`
            : '';
        const resHtml = res
            ? `\n                    <div class="event-footer">\n                        <p class="wow fadeInUp" data-wow-delay="0.5s">${res}</p>\n                    </div>`
            : '';
        return `    <div class="our-event">
        <div class="container">
            <div class="row align-items-center">
                <div class="${colImg}">
                    <div class="event-image">
                        <figure class="image-anime reveal">
                            <img loading="lazy" decoding="async" src="${img}" onerror="this.src='images/event-image.jpg'" alt="${tit}">
                        </figure>
                    </div>
                </div>
                <div class="${colTxt}">
                    <div class="event-content">
                        <div class="section-title">
                            <h3 class="wow fadeInUp">${label}</h3>
                            <h2 class="text-anime-style-2" data-cursor="-opaque">${tit}</h2>${statusBadge}
                        </div>
                        <div class="event-body">
                            <div class="event-item wow fadeInUp">
                                <div class="icon-box"><i class="fa-solid fa-calendar-days"></i></div>
                                <div class="event-item-content"><p>${dt}</p></div>
                            </div>
                            <div class="event-item wow fadeInUp" data-wow-delay="0.25s">
                                <div class="icon-box"><i class="fa-solid fa-location-dot"></i></div>
                                <div class="event-item-content"><p>${loc}</p></div>
                            </div>
                        </div>${resHtml}
                        <div class="event-btn wow fadeInUp" data-wow-delay="0.75s">
                            <a href="${link}" class="btn-default">ver programação completa</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
    }

    const indexHtml  = path.join(ROOT, 'index.html');
    const eventosHtml = path.join(ROOT, 'eventos.html');

    // ── 1. index.html: our-event destaque ──────────────────────
    {
        const ev = selecionarDestaque(visiveis);
        const html = ev ? ourEventHtml(ev, false)
            : `    <div class="our-event">
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
                            <h3 class="wow fadeInUp">próximo evento</h3>
                            <h2 class="text-anime-style-2" data-cursor="-opaque">Nenhum evento <span>agendado</span></h2>
                        </div>
                        <div class="event-btn wow fadeInUp" data-wow-delay="0.5s">
                            <a href="eventos.html" class="btn-default">ver todos os eventos</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
        injectSection(indexHtml, 'index:evento-destaque', html);
    }

    // ── 2. index.html: grade de eventos (.ministries-item) ─────
    {
        const fallbacks = ['images/campaign-img-1.jpg', 'images/campaign-img-3.jpg', 'images/campaign-img-2.jpg'];
        const delays    = ['0.1s', '0.25s', '0.5s'];

        let inner = '';
        if (visiveis.length) {
            inner = visiveis.slice(0, 3).map((ev, i) => {
                const img  = escapeHtml(imgPath(ev.imagem_capa));
                const tit  = escapeHtml(ev.titulo || '');
                const link = `eventos/${escapeHtml(ev.slug)}.html`;
                const sub  = escapeHtml([formatDateBR(ev.data_inicio), ev.hora_inicio, localStr(ev.local)].filter(Boolean).join(' — '));
                const fb   = fallbacks[i] || fallbacks[0];
                const statusMap = { cancelado: ['danger', 'Cancelado'], encerrado: ['secondary', 'Encerrado'] };
                const statusBadge = statusMap[ev.status]
                    ? ` <span class="badge bg-${statusMap[ev.status][0]}" style="font-size:0.65em;">${statusMap[ev.status][1]}</span>`
                    : '';
                return `                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="${delays[i]}">
                    <div class="ministries-item">
                        <div class="ministries-image" data-cursor-text="Ver">
                            <a href="${link}">
                                <figure>
                                    <img loading="lazy" decoding="async" src="${img}" onerror="this.src='${fb}'" alt="${tit}">
                                </figure>
                            </a>
                        </div>
                        <div class="ministries-content">
                            <h3>${tit}${statusBadge}</h3>
                            <p>${sub}</p>
                        </div>
                        <div class="ministries-btn">
                            <a href="${link}" class="readmore-btn"><img loading="lazy" decoding="async" src="images/arrow-white.svg" alt=""></a>
                        </div>
                    </div>
                </div>`;
            }).join('\n');
        } else {
            inner = `                <div class="col-lg-12">
                    <p class="text-center">Nenhum evento agendado no momento.</p>
                </div>`;
        }

        const footer = `                <div class="col-lg-12">
                    <div class="our-ministries-footer wow fadeInUp" data-wow-delay="0.75s">
                        <p>Confira todos os eventos e novidades da nossa paróquia e participe das celebrações. <a href="eventos.html">Ver Todos os Eventos</a></p>
                    </div>
                </div>`;

        const html = `            <div class="row">\n${inner}\n${footer}\n            </div>`;
        injectSection(indexHtml, 'index:eventos-grade', html);
    }

    // ── 3. eventos.html: our-event destaques (até 2, lados alternados) ──
    {
        let html;
        if (visiveis.length) {
            html = visiveis.slice(0, 2).map((ev, i) => ourEventHtml(ev, i % 2 !== 0)).join('\n');
        } else {
            html = `    <div class="our-event">
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
                            <h3 class="wow fadeInUp">próximos eventos</h3>
                            <h2 class="text-anime-style-2" data-cursor="-opaque">Nenhum evento <span>agendado</span></h2>
                        </div>
                        <div class="event-btn wow fadeInUp" data-wow-delay="0.5s">
                            <a href="contato.html" class="btn-default">fale conosco</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>`;
        }
        injectSection(eventosHtml, 'eventos:destaques', html);
    }

    // ── 4. eventos.html: ticker ─────────────────────────────────
    {
        const aster = `<img loading="lazy" decoding="async" src="images/icon-asterisk.svg" alt="">`;
        const spans = visiveis.length
            ? visiveis.map(ev => `                    <span>${aster}${escapeHtml(ev.titulo)}</span>`).join('\n')
            : `                    <span>${aster}Confira os próximos eventos da paróquia</span>`;
        const block = `                <div class="scrolling-content">\n${spans}\n                </div>`;
        injectSection(eventosHtml, 'eventos:ticker-content', `${block}\n${block}`);
    }

    // ── 5. eventos.html: grade de eventos (.campaign-item) ─────
    {
        const fallbacks = ['images/campaign-img-1.jpg', 'images/campaign-img-3.jpg', 'images/campaign-img-2.jpg'];
        let cards;
        if (visiveis.length) {
            cards = visiveis.map((ev, i) => {
                const img   = escapeHtml(imgPath(ev.imagem_capa));
                const tit   = escapeHtml(ev.titulo || '');
                const link  = `eventos/${escapeHtml(ev.slug)}.html`;
                const res   = escapeHtml(ev.resumo || ev.subtitulo || '');
                const dtParts = [formatDateBR(ev.data_inicio)];
                if (ev.data_fim && ev.data_fim !== ev.data_inicio) dtParts.push(formatDateBR(ev.data_fim));
                const dt    = escapeHtml(dtParts.join(' – '));
                const hr    = escapeHtml(ev.hora_inicio || '');
                const loc   = escapeHtml(localStr(ev.local));
                const fb    = fallbacks[i % fallbacks.length];
                const delay = i > 0 ? ` data-wow-delay="${(i * 0.25).toFixed(2)}s"` : '';
                const resRow = res ? `\n                                <p>${res}</p>` : '';
                const hrCell = hr ? `\n                                    <div class="skill-no">${hr}</div>` : '';
                const locRow = loc
                    ? `\n                                <div class="skill-data" style="margin-top:8px; margin-bottom:0;">\n                                    <div class="skill-title"><i class="fa-solid fa-location-dot"></i> &nbsp;${loc}</div>\n                                </div>`
                    : '';
                const catMap   = { liturgico: 'Litúrgico', pastoral: 'Pastoral', social: 'Social', formativo: 'Formativo', festivo: 'Festivo', outro: 'Outro' };
                const catLabel = catMap[ev.categoria] || escapeHtml(ev.categoria || '');
                const catBadge = ev.categoria ? `\n                            <span class="badge bg-secondary mb-1" style="font-size:0.7em;font-weight:500;">${catLabel}</span>` : '';
                const statusMap = { cancelado: ['danger', 'Cancelado'], encerrado: ['secondary', 'Encerrado'] };
                const statusBadge = statusMap[ev.status]
                    ? ` <span class="badge bg-${statusMap[ev.status][0]}" style="font-size:0.65em;">${statusMap[ev.status][1]}</span>`
                    : '';
                return `                <div class="col-lg-4 col-md-6">
                    <div class="campaign-item wow fadeInUp"${delay}>
                        <div class="campaign-image">
                            <figure>
                                <a href="${link}" class="image-anime" data-cursor-text="Ver">
                                    <img loading="lazy" decoding="async" src="${img}" onerror="this.src='${fb}'" alt="${tit}">
                                </a>
                            </figure>
                        </div>
                        <div class="campaign-body">
                            <div class="campaign-content">${catBadge}
                                <h2>${tit}${statusBadge}</h2>${resRow}
                            </div>
                            <div class="campaign-btn">
                                <a href="${link}" class="read-more-btn">ver detalhes</a>
                            </div>
                            <div class="skillbar" style="pointer-events:none;">
                                <div class="skill-data">
                                    <div class="skill-title"><i class="fa-regular fa-calendar-days"></i> &nbsp;${dt}</div>${hrCell}
                                </div>${locRow}
                            </div>
                        </div>
                    </div>
                </div>`;
            }).join('\n');
        } else {
            cards = `                <div class="col-lg-12">
                    <p class="text-center py-5">Nenhum evento agendado no momento. Volte em breve!</p>
                </div>`;
        }
        injectSection(eventosHtml, 'eventos:grade', cards);
    }

    console.log('- Secoes dinamicas concluidas.\n');

    // ── Configurações do Site ─────────────────────────────────────────
    console.log('Aplicando configuracoes do site...');
    const configPath = path.join(DATA, 'configuracoes.json');
    if (fs.existsSync(configPath)) {
        const cfg = JSON.parse(fs.readFileSync(configPath, 'utf8'));

        // 1. CSS: theme-cms.css com as 4 variáveis de cor do site
        const HEX = /^#[0-9a-fA-F]{3,8}$/;
        const corAcento    = HEX.test(cfg.cor_principal    || '') ? cfg.cor_principal    : '#acaa59';
        const corEscuro    = HEX.test(cfg.cor_fundo_escuro || '') ? cfg.cor_fundo_escuro : '#000000';
        const corClaro     = HEX.test(cfg.cor_fundo_claro  || '') ? cfg.cor_fundo_claro  : '#FFF4F1';
        const corTexto     = HEX.test(cfg.cor_texto        || '') ? cfg.cor_texto        : '#525252';
        const cssContent = `:root {\n    --accent-color:    ${corAcento};\n    --primary-color:   ${corEscuro};\n    --secondary-color: ${corClaro};\n    --text-color:      ${corTexto};\n}\n`;
        const themeCssPath = path.join(ROOT, 'css', 'theme-cms.css');
        if (!fs.existsSync(themeCssPath) || fs.readFileSync(themeCssPath, 'utf8') !== cssContent) {
            fs.writeFileSync(themeCssPath, cssContent, 'utf8');
            console.log(`  ✓ css/theme-cms.css (acento:${corAcento} escuro:${corEscuro} claro:${corClaro} texto:${corTexto})`);
        } else {
            console.log(`  . css/theme-cms.css (sem alteracoes)`);
        }

        // 2. Hero section em index.html
        const heroTagline  = escapeHtml(cfg.hero_tagline  || 'Paróquia Nossa Senhora dos Remédios — Jericó/PB');
        const heroTitulo   = escapeHtml(cfg.hero_titulo   || 'Fé, Esperança e Amor no coração do Sertão Paraibano!');
        const heroDesc     = escapeHtml(cfg.hero_descricao || 'Uma comunidade de fé com mais de 66 anos de história, erguida em torno da devoção à Nossa Senhora dos Remédios, padroeira de Jericó, no sertão da Paraíba.');
        const heroBtn1Txt  = escapeHtml(cfg.hero_btn1_texto || 'Horários');
        const heroBtn1Link = escapeHtml(cfg.hero_btn1_link  || 'agenda-liturgica.html');
        const heroBtn2Txt  = escapeHtml(cfg.hero_btn2_texto || 'Calendário Litúrgico');
        const heroBtn2Link = escapeHtml(cfg.hero_btn2_link  || 'agenda-liturgica.html');
        const heroImgStyle = cfg.hero_imagem
            ? ` style="background-image:url('${escapeHtml(cfg.hero_imagem)}')"`
            : '';
        const heroContent = `\t\t<!-- Section Title Start -->
                        <div class="section-title">
                            <h3 class="wow fadeInUp">${heroTagline}</h3>
                            <h1 class="text-anime-style-2" data-cursor="-opaque">${heroTitulo}</h1>
                            <p class="wow fadeInUp" data-wow-delay="0.25s">${heroDesc}</p>
                        </div>
                        <!-- Section Title End -->

                        <!-- Hero Content Body Start -->
                        <div class="hero-content-body wow fadeInUp" data-wow-delay="0.5s">
                            <a href="${heroBtn1Link}" class="btn-default btn-highlighted"><span>${heroBtn1Txt}</span></a>
                            <a href="${heroBtn2Link}" class="btn-default"><span>${heroBtn2Txt}</span></a>
                        </div>
                        <!-- Hero Content Body End -->`;
        injectSection(path.join(ROOT, 'index.html'), 'site:hero-content', heroContent);

        // 3. Header CTA button
        const ctaTxt  = escapeHtml(cfg.header_cta_texto || 'Ouça agora');
        const ctaLink = escapeHtml(cfg.header_cta_link  || '#');
        const ctaAttr = ctaLink === '#' ? ' data-radio-trigger' : '';
        const headerCta = `\t\t\t\t\t<div class="header-btn d-inline-flex">
                        <a href="${ctaLink}" class="btn-default"${ctaAttr}><span>${ctaTxt}</span></a>
                    </div>`;
        injectSection(path.join(ROOT, 'partials', 'header.html'), 'site:header-cta', headerCta);

        // 4. Footer — descrição
        const footerDesc = escapeHtml(cfg.footer_descricao || '');
        injectSection(path.join(ROOT, 'partials', 'footer.html'), 'site:footer-descricao', `\t\t\t\t\t\t<p>${footerDesc}</p>`);

        // 5. Footer — contato
        const tel      = escapeHtml(cfg.footer_telefone || '');
        const email    = escapeHtml(cfg.footer_email    || '');
        const endereco = escapeHtml(cfg.footer_endereco || '');
        const telLink   = `tel:+55${tel.replace(/\D/g,'')}`;
        const emailLink = `mailto:${email}`;
        const footerContato = `\t\t\t\t\t\t<div class="footer-info-box">
                            <div class="icon-box"><img src="images/icon-phone.svg" alt="" width="24" height="24"></div>
                            <div class="footer-info-box-content"><p><a href="${escapeHtml(telLink)}">${tel}</a></p></div>
                        </div>
                        <div class="footer-info-box">
                            <div class="icon-box"><img src="images/icon-mail.svg" alt="" width="24" height="24"></div>
                            <div class="footer-info-box-content"><p><a href="${escapeHtml(emailLink)}">${email}</a></p></div>
                        </div>
                        <div class="footer-info-box">
                            <div class="icon-box"><img src="images/icon-location.svg" alt="" width="24" height="24"></div>
                            <div class="footer-info-box-content"><p>${endereco}</p></div>
                        </div>`;
        injectSection(path.join(ROOT, 'partials', 'footer.html'), 'site:footer-contato', footerContato);

        // 6. Footer — redes sociais
        const fb   = cfg.footer_facebook  || '';
        const ig   = cfg.footer_instagram || '';
        const wa   = cfg.footer_whatsapp  || '';
        const yt   = cfg.footer_youtube   || '';
        const socialLinks = [
            fb ? `\t\t\t\t\t\t\t<li><a href="${escapeHtml(fb)}" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i></a></li>` : '',
            ig ? `\t\t\t\t\t\t\t<li><a href="${escapeHtml(ig)}" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fa-brands fa-instagram" aria-hidden="true"></i></a></li>` : '',
            wa ? `\t\t\t\t\t\t\t<li><a href="${escapeHtml(wa)}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a></li>` : '',
            yt ? `\t\t\t\t\t\t\t<li><a href="${escapeHtml(yt)}" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><i class="fa-brands fa-youtube" aria-hidden="true"></i></a></li>` : '',
        ].filter(Boolean).join('\n');
        injectSection(path.join(ROOT, 'partials', 'footer.html'), 'site:footer-redes', socialLinks);

        console.log('- Configuracoes aplicadas.\n');

        // ── Logos ─────────────────────────────────────────────────────────
        console.log('Aplicando logos...');
        const logoCor = /^#[0-9a-fA-F]{3,8}$/.test(cfg.logo_cor || '') ? cfg.logo_cor : '#acaa59';

        /** Lê um SVG, substitui todas ocorrências de fillFrom por fillTo e adiciona atributos ao elemento root. */
        function buildInlineSvg(relPath, fillFrom, fillTo, extraAttrs) {
            const p = path.join(ROOT, relPath);
            if (!fs.existsSync(p)) return null;
            let s = fs.readFileSync(p, 'utf8');
            const regex = new RegExp(`fill="${fillFrom.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}"`, 'g');
            s = s.replace(regex, `fill="${fillTo}"`);
            if (extraAttrs) s = s.replace('<svg ', `<svg ${extraAttrs} `);
            return s;
        }

        // Logo do cabeçalho
        let headerLogoHtml;
        if (cfg.logo_header_img) {
            const src = escapeHtml(cfg.logo_header_img.replace(/^\//, ''));
            headerLogoHtml = `<img src="${src}" alt="Logo Paróquia Nossa Senhora dos Remédios" style="max-height:55px;" loading="lazy">`;
        } else {
            headerLogoHtml = buildInlineSvg(
                'images/logo.svg', '#acaa59', logoCor,
                'style="max-height:55px;" role="img" aria-label="Logo Paróquia Nossa Senhora dos Remédios"'
            ) || `<img src="images/logo.png" alt="Logo Paróquia Nossa Senhora dos Remédios" style="max-height:55px;" width="140" height="55">`;
        }
        injectSection(path.join(ROOT, 'partials', 'header.html'), 'site:header-logo', headerLogoHtml);

        // Logo do rodapé
        let footerLogoHtml;
        if (cfg.logo_footer_img) {
            const src = escapeHtml(cfg.logo_footer_img.replace(/^\//, ''));
            footerLogoHtml = `<img src="${src}" alt="Paróquia Nossa Senhora dos Remédios" style="height:auto;max-height:80px;width:auto;" loading="lazy">`;
        } else {
            footerLogoHtml = buildInlineSvg(
                'images/footer-logo.svg', '#acaa59', logoCor,
                'style="height:auto;max-height:80px;width:auto;" role="img" aria-label="Paróquia Nossa Senhora dos Remédios"'
            ) || `<img src="images/footer-logo.png" alt="Paróquia Nossa Senhora dos Remédios" width="200" height="102" style="height:auto;max-height:80px;width:auto;">`;
        }
        injectSection(path.join(ROOT, 'partials', 'footer.html'), 'site:footer-logo', footerLogoHtml);

        // Logo do preloader (SVG é todo branco; substitui branco pela cor escolhida)
        let loaderLogoHtml;
        if (cfg.logo_loader_img) {
            const src = escapeHtml(cfg.logo_loader_img.replace(/^\//, ''));
            loaderLogoHtml = `<img src="${src}" alt="">`;
        } else {
            loaderLogoHtml = buildInlineSvg(
                'images/loader.svg', 'white', logoCor,
                'role="img" aria-hidden="true"'
            ) || `<img src="images/loader.png" alt="">`;
        }
        injectSection(path.join(ROOT, 'partials', 'header.html'), 'site:loader-logo', loaderLogoHtml);

        console.log(`  ✓ logos aplicados (cor: ${logoCor})\n`);
    } else {
        console.log('  . configuracoes.json ausente — pulando configuracoes do site.\n');
    }

    // ── Pároco ─────────────────────────────────────────────────
    console.log('Injetando paginas estaticas...');
    const paracoPath = path.join(DATA, 'paroco.json');
    if (fs.existsSync(paracoPath)) {
        const p = JSON.parse(fs.readFileSync(paracoPath, 'utf8'));
        const saudacao = escapeHtml(p.saudacao || 'Pe.');
        const nome     = escapeHtml(p.nome || 'Pároco');
        const foto     = p.foto ? escapeHtml(p.foto.replace(/^\//, '')) : 'images/team-1.jpg';
        const parocoHtml = `<!-- Pároco -->
                <div class="col-lg-3 col-md-6">
                    <div class="team-member-item wow fadeInUp">
                        <div class="team-image">
                            <figure class="image-anime"><img loading="lazy" decoding="async" src="${foto}" onerror="this.src='images/team-1.jpg'" alt="${saudacao} ${nome}"></figure>
                            <div class="team-social-icon">
                                <ul>
                                    <li><a href="#" class="social-icon" aria-label="WhatsApp" tabindex="-1"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="team-content">
                            <h3>${saudacao} ${nome}</h3>
                            <p>Pároco da Paróquia</p>
                        </div>
                    </div>
                </div>`;
        injectSection(path.join(ROOT, 'paroco.html'), 'paroco:card', parocoHtml);
    } else {
        console.log('  . paroco.json ausente, pulando paroco.html');
    }

    // ── Ministérios ────────────────────────────────────────────
    const ministeriosDataPath = path.join(DATA, 'ministerios.json');
    if (fs.existsSync(ministeriosDataPath)) {
        const lista = JSON.parse(fs.readFileSync(ministeriosDataPath, 'utf8')).ministerios || [];
        const delays = ['', '0.25s', '0.5s', '0.75s', '1s', '1.25s', '1.5s', '1.75s'];
        const fallbacks = ['images/ministries-img-1.jpg', 'images/ministries-img-2.jpg', 'images/ministries-img-3.jpg'];
        const ativos = lista.filter(m => m.ativo !== false);
        let ministeriosCards;
        if (ativos.length) {
            ministeriosCards = ativos.map((m, i) => {
                const nomeMin  = escapeHtml(m.nome || '');
                const imgMin   = m.imagem ? escapeHtml(m.imagem.replace(/^\//, '')) : fallbacks[i % fallbacks.length];
                const delay = delays[i] ? ` data-wow-delay="${delays[i]}"` : '';
                const sub   = m.encontros
                    ? escapeHtml([m.encontros.dia_semana, m.encontros.horario].filter(Boolean).join(' — '))
                    : '';
                const subHtml = sub ? `\n                            <p>${sub}</p>` : '';
                return `                <div class="col-md-4">
                    <div class="ministries-item wow fadeInUp"${delay}>
                        <div class="ministries-image" data-cursor-text="Ver">
                            <a href="#">
                                <figure>
                                    <img loading="lazy" decoding="async" src="${imgMin}" onerror="this.src='${fallbacks[i % fallbacks.length]}'" alt="${nomeMin}">
                                </figure>
                            </a>
                        </div>
                        <div class="ministries-content">
                            <h3>${nomeMin}</h3>${subHtml}
                        </div>
                        <div class="ministries-btn">
                            <a href="#" class="readmore-btn"><img loading="lazy" decoding="async" src="images/arrow-white.svg" alt=""></a>
                        </div>
                    </div>
                </div>`;
            }).join('\n');
        } else {
            ministeriosCards = `                <div class="col-lg-12"><p class="text-center py-4">Nenhum ministério cadastrado no momento.</p></div>`;
        }
        injectSection(path.join(ROOT, 'ministerios.html'), 'ministerios:grade', ministeriosCards);
    } else {
        console.log('  . ministerios.json ausente, pulando ministerios.html');
    }

    console.log('- Paginas estaticas atualizadas.\n');
})();

    // =============================================================
    // Single-page templates
    // Gera arquivos raiz (ex.: historia.html) a partir de um JSON
    // que representa um objeto unico (nao uma colecao).
    // =============================================================
    (function buildSinglePageTemplates() {
        const SINGLE_PAGES = [
            {
                dataFile: 'historia.json',
                template: 'historia.html',
                outFile:  'historia.html',
                enrich: function(data) { return data; },
            },
        ];

        for (const plan of SINGLE_PAGES) {
            const dataPath = path.join(DATA, plan.dataFile);
            const tplPath  = path.join(TPL, plan.template);
            if (!fs.existsSync(dataPath) || !fs.existsSync(tplPath)) {
                console.log(`- pulando single-page ${plan.template} (data ou template ausente)`);
                continue;
            }
            const data    = JSON.parse(fs.readFileSync(dataPath, 'utf8')) || {};
            for (const field of ['about_imagem1', 'about_imagem2', 'missao_imagem', 'paroco_imagem', 'paroco_assinatura']) {
                if (typeof data[field] === 'string' && data[field]) data[field] = resolveStorageAsset(data[field], 'historia');
            }
            const tpl     = fs.readFileSync(tplPath, 'utf8');
            const enriched = plan.enrich(data);
            let rendered  = render(tpl, enriched);
            rendered = expandPartials(rendered);
            // Single-page templates ficam na raiz — sem rewrite de paths
            const outPath = path.join(ROOT, plan.outFile);
            const banner  = `<!-- GENERATED FROM data/${plan.dataFile} — DO NOT EDIT MANUALLY. Run: npm run build:content -->\n`;
            const out     = banner + rendered;
            const before  = fs.existsSync(outPath) ? fs.readFileSync(outPath, 'utf8') : null;
            if (before !== out) {
                fs.writeFileSync(outPath, out, 'utf8');
                console.log(`  ✓ ${plan.outFile} (single-page)`);
            } else {
                console.log(`  · ${plan.outFile} (single-page, sem alteracoes)`);
            }
        }
    })();

} // end if (!PREVIEW_MODE)

// =============================================================
// Modo preview: le JSON do stdin, renderiza 1 item, grava preview.html
// =============================================================
if (PREVIEW_MODE) {
    const typeArg = process.argv.indexOf('--type');
    const type = typeArg !== -1 ? process.argv[typeArg + 1] : 'evento';
    const planEntry = PLAN.find(p => p.outDir === type);
    if (!planEntry) {
        console.error('[preview] --type invalido: ' + type + '. Use: evento, artigo ou homilia');
        process.exit(1);
    }
    let inputData = '';
    process.stdin.setEncoding('utf8');
    process.stdin.on('data', chunk => { inputData += chunk; });
    process.stdin.on('end', () => {
        try {
            const item = JSON.parse(inputData);
            const tplPath = path.join(TPL, planEntry.template);
            const tpl = fs.readFileSync(tplPath, 'utf8');
            const enriched = planEntry.enrich(item);
            let rendered = render(tpl, enriched);
            rendered = expandPartials(rendered);
            // preview.html fica na raiz do site — mesmos paths do template, sem rewrite
            const outFile = path.join(ROOT, 'preview.html');
            fs.writeFileSync(outFile, '<!-- PAROQUIA PREVIEW — NAO COMMITAR -->\n' + rendered, 'utf8');
            console.log('OK');
            process.exit(0);
        } catch (e) {
            console.error('[preview] Erro: ' + e.message);
            process.exit(1);
        }
    });
}
