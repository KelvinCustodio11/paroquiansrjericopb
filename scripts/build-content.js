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
