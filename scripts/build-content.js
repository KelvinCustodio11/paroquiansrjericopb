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
    return `${parseInt(d,10)} de ${MESES[parseInt(mo,10)-1]} de ${y}`;
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
    const RAW_FIELDS = new Set(['conteudo', 'descricao_completa', 'transcricao', 'transcricao_or_resumo']);
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
    return Object.assign({}, item, {
        data_inicio_formatada: formatDateBR(item.data_inicio),
        data_fim_formatada: formatDateBR(item.data_fim),
        inscricao_obrigatoria: item.inscricao && item.inscricao.obrigatoria,
        local_mapa_url: item.local && item.local.mapa && item.local.mapa.google_maps_url,
        programacao: (item.programacao && item.programacao.length > 0) ? { items: item.programacao } : null,
    });
}

function enrichArtigo(item) {
    return Object.assign({}, item, {
        data_publicacao_formatada: formatDateBR(item.data_publicacao),
        data_atualizacao_or_publicacao: item.data_atualizacao || item.data_publicacao,
        tags_list: (item.tags && item.tags.length > 0) ? { items: item.tags } : null,
    });
}

function enrichHomilia(item) {
    return Object.assign({}, item, {
        data_formatada: formatDateBR(item.data),
        leitura_evangelho_referencia: item.leitura_evangelho && item.leitura_evangelho.referencia,
        transcricao_or_resumo: item.transcricao || `<p>${escapeHtml(item.resumo)}</p>`,
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
// Loop principal
// =============================================================

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
        const img   = escapeHtml(imgPath(ev.imagem_capa));
        const tit   = escapeHtml(ev.titulo || '');
        const label = escapeHtml(ev.categoria || 'próximo evento');
        const dt    = escapeHtml(dataHoraStr(ev));
        const loc   = escapeHtml(localStr(ev.local));
        const res   = escapeHtml(ev.resumo || '');
        const link  = `eventos/${escapeHtml(ev.slug)}.html`;
        const colImg = reverseLayout ? 'col-lg-6 order-lg-2' : 'col-lg-6';
        const colTxt = reverseLayout ? 'col-lg-6 order-lg-1' : 'col-lg-6';
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
                            <h2 class="text-anime-style-2" data-cursor="-opaque">${tit}</h2>
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
                            <h3>${tit}</h3>
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
                const res   = escapeHtml(ev.resumo || '');
                const dt    = escapeHtml(formatDateBR(ev.data_inicio));
                const hr    = escapeHtml(ev.hora_inicio || '');
                const loc   = escapeHtml(localStr(ev.local));
                const fb    = fallbacks[i % fallbacks.length];
                const delay = i > 0 ? ` data-wow-delay="${(i * 0.25).toFixed(2)}s"` : '';
                const resRow = res ? `\n                                <p>${res}</p>` : '';
                const hrCell = hr ? `\n                                    <div class="skill-no">${hr}</div>` : '';
                const locRow = loc
                    ? `\n                                <div class="skill-data" style="margin-top:8px; margin-bottom:0;">\n                                    <div class="skill-title"><i class="fa-solid fa-location-dot"></i> &nbsp;${loc}</div>\n                                </div>`
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
                            <div class="campaign-content">
                                <h2>${tit}</h2>${resRow}
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
})();
