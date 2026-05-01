#!/usr/bin/env node
/**
 * scripts/migrate-urls-pt-br.js
 *
 * One-time. Renomeia arquivos .html EN -> PT-BR, atualiza links internos
 * em partials, páginas, seo-data.json, sitemap.xml e js/proximos-eventos.js,
 * e gera STUBS de redirect (meta refresh + canonical) nos nomes antigos.
 *
 * Execução:
 *   node scripts/migrate-urls-pt-br.js
 *   node build.js
 *   node scripts/seo-fill.js
 */
'use strict';
const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');

const MAP = {
    'about.html':            'historia.html',
    'service.html':          'sacramentos.html',
    'service-single.html':   'sacramento-detalhe.html',
    'ministries.html':       'ministerios.html',
    'ministry-single.html':  'ministerio-detalhe.html',
    'evento-single.html':    'evento-detalhe.html',
    'blog.html':             'artigos.html',
    'blog-single.html':      'artigo-detalhe.html',
    'sermons.html':          'homilias.html',
    'sermons-single.html':   'homilia-detalhe.html',
    'campaign.html':         'campanhas.html',
    'campaign-single.html':  'campanha-detalhe.html',
    'gallery.html':          'galeria.html',
    'contact.html':          'contato.html',
};

// Arquivos a varrer e atualizar (relativos à ROOT)
const SCAN_PATTERNS = [
    'partials/header.html',
    'partials/footer.html',
    'sitemap.xml',
    'seo-data.json',
    'js/proximos-eventos.js',
    '.htaccess',
];

function listHtmlInRoot() {
    return fs.readdirSync(ROOT)
        .filter(f => f.endsWith('.html'))
        .filter(f => fs.statSync(path.join(ROOT, f)).isFile());
}

function listExtraScanFiles() {
    // Inclui as próprias páginas HTML para atualizar links cruzados
    const extras = listHtmlInRoot().map(f => f);
    return [...SCAN_PATTERNS, ...extras];
}

function replaceAll(src, fromName, toName) {
    // Substitui ocorrências como href="about.html", href='about.html',
    // url "/about.html" etc. Usa regex word-boundary aproximado.
    const escaped = fromName.replace(/[.]/g, '\\.');
    const re = new RegExp('([\'"\\s/>])' + escaped + '\\b', 'g');
    return src.replace(re, '$1' + toName);
}

console.log('1) Renomeando arquivos...');
for (const [from, to] of Object.entries(MAP)) {
    const fromPath = path.join(ROOT, from);
    const toPath   = path.join(ROOT, to);
    if (!fs.existsSync(fromPath)) {
        if (fs.existsSync(toPath)) console.log(`   - ${from} → ${to} (já renomeado)`);
        else console.log(`   ! ${from} não existe`);
        continue;
    }
    if (fs.existsSync(toPath)) {
        console.log(`   ! ${to} já existe — pulando`);
        continue;
    }
    fs.renameSync(fromPath, toPath);
    console.log(`   ✓ ${from} → ${to}`);
}

console.log('\n2) Atualizando links internos...');
for (const file of listExtraScanFiles()) {
    const full = path.join(ROOT, file);
    if (!fs.existsSync(full)) continue;
    let content = fs.readFileSync(full, 'utf8');
    const before = content;
    for (const [from, to] of Object.entries(MAP)) {
        content = replaceAll(content, from, to);
    }
    if (content !== before) {
        fs.writeFileSync(full, content, 'utf8');
        console.log(`   ✓ ${file}`);
    }
}

console.log('\n3) Atualizando chaves de seo-data.json...');
{
    const seoPath = path.join(ROOT, 'seo-data.json');
    const data = JSON.parse(fs.readFileSync(seoPath, 'utf8'));
    const newPages = {};
    for (const [k, v] of Object.entries(data.pages)) {
        const newKey = MAP[k] || k;
        // Atualiza url interna se referenciava o antigo
        if (v.url) {
            for (const [from, to] of Object.entries(MAP)) {
                v.url = v.url.replace('/' + from, '/' + to);
            }
        }
        newPages[newKey] = v;
    }
    data.pages = newPages;
    fs.writeFileSync(seoPath, JSON.stringify(data, null, 2) + '\n', 'utf8');
    console.log('   ✓ seo-data.json');
}

console.log('\n4) Gerando stubs de redirect (meta refresh) nos nomes antigos...');
for (const [from, to] of Object.entries(MAP)) {
    const fromPath = path.join(ROOT, from);
    const stub = `<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Redirecionando…</title>
  <link rel="canonical" href="https://pascomjerico.com.br/${to}">
  <meta name="robots" content="noindex,nofollow">
  <meta http-equiv="refresh" content="0; url=/${to}">
  <script>window.location.replace('/${to}');</script>
</head>
<body>
  <p>Esta página foi movida. Se você não for redirecionado, <a href="/${to}">clique aqui</a>.</p>
</body>
</html>
`;
    fs.writeFileSync(fromPath, stub, 'utf8');
    console.log(`   ✓ ${from} (stub → ${to})`);
}

console.log('\nConcluído. Próximos passos: node build.js && node scripts/seo-fill.js');
