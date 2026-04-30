#!/usr/bin/env node
/**
 * scripts/migrate-to-partials.js
 *
 * Script ONE-TIME para migrar todas as páginas .html da raiz para usar
 * `<!-- @include partials/X.html -->` em vez de blocos duplicados.
 *
 * O que faz em cada arquivo:
 *  1. Substitui o bloco de <head> (do trecho de favicon até </head>) por:
 *       <title>...</title> (preservado)
 *       <meta name="description"...> (preservado)
 *       <!-- @include partials/head-css.html -->
 *  2. Substitui o bloco Preloader+Header por <!-- @include partials/header.html -->
 *  3. Substitui o bloco Footer por <!-- @include partials/footer.html -->
 *  4. Substitui o bloco de scripts comuns por <!-- @include partials/scripts-common.html -->
 *     Mantém scripts específicos (radio-player, liturgia, santo-dia, etc.) DEPOIS do include.
 *  5. Adiciona body data-page="..." baseado no nome do arquivo (heurística).
 *
 * Após rodar, executar `node build.js` para expandir os includes.
 *
 * É seguro rodar várias vezes — após a 1ª execução o arquivo já tem o include
 * e o regex de bloco antigo não combina mais.
 */
'use strict';
const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const PAGES = fs.readdirSync(ROOT)
    .filter(f => f.endsWith('.html') && fs.statSync(path.join(ROOT, f)).isFile());

const PAGE_TO_DATAPAGE = {
    'index.html': 'home',
    'about.html': 'historia',
    'paroco.html': 'historia',
    'service.html': 'pastoral',
    'service-single.html': 'pastoral',
    'ministries.html': 'pastoral',
    'ministry-single.html': 'pastoral',
    'eventos.html': 'eventos',
    'evento-single.html': 'eventos',
    'evento-single-pascom.html': 'eventos',
    'agenda-liturgica.html': 'agenda',
    'blog.html': 'eventos',
    'blog-single.html': 'eventos',
    'sermons.html': 'eventos',
    'sermons-single.html': 'eventos',
    'campaign.html': 'eventos',
    'campaign-single.html': 'eventos',
    'gallery.html': 'eventos',
    'objetos-sagrados.html': 'historia',
    'contact.html': 'contato',
    '404.html': '',
};

// 1) head: do início do <head> até </head>, preservando <title> e <meta description>
function replaceHead(html) {
    const headMatch = html.match(/<head>([\s\S]*?)<\/head>/i);
    if (!headMatch) return html;
    const headInner = headMatch[1];
    if (headInner.includes('@include partials/head-css.html')) return html; // já migrado

    const titleMatch = headInner.match(/<title>[\s\S]*?<\/title>/i);
    const descMatch  = headInner.match(/<meta\s+name=["']description["'][^>]*>/i);
    const keywordsMatch = headInner.match(/<meta\s+name=["']keywords["'][^>]*>/i);

    const newHead = [
        '\t<title>' + (titleMatch ? titleMatch[0].replace(/<\/?title>/gi, '') : 'Paróquia NSR Jericó/PB') + '</title>',
        descMatch ? '\t' + descMatch[0] : '',
        keywordsMatch ? '\t' + keywordsMatch[0] : '',
        '\t<!-- @include partials/head-css.html -->'
    ].filter(Boolean).join('\n');

    return html.replace(/<head>[\s\S]*?<\/head>/i, '<head>\n' + newHead + '\n</head>');
}

// 2) Preloader + Header → @include partials/header.html
function replaceHeader(html) {
    if (html.includes('@include partials/header.html')) return html;
    // Match: <!-- Preloader Start --> ... <!-- Header End -->
    const re = /[ \t]*<!--\s*Preloader Start\s*-->[\s\S]*?<!--\s*Header End\s*-->\n?/i;
    if (re.test(html)) {
        return html.replace(re, '\t<!-- @include partials/header.html -->\n');
    }
    // Fallback: só Header (sem preloader)
    const re2 = /[ \t]*<!--\s*Header Start\s*-->[\s\S]*?<!--\s*Header End\s*-->\n?/i;
    if (re2.test(html)) {
        return html.replace(re2, '\t<!-- @include partials/header.html -->\n');
    }
    return html;
}

// 3) Footer → @include partials/footer.html
function replaceFooter(html) {
    if (html.includes('@include partials/footer.html')) return html;
    // Apenas o último Footer Start...Footer End (não os Mission Content Footer etc.)
    // Pegar o bloco que começa com <!-- Footer Start --> e tem <footer
    const re = /[ \t]*<!--\s*Footer Start\s*-->\s*\n\s*<footer[\s\S]*?<\/footer>\s*\n[ \t]*<!--\s*Footer End\s*-->\n?/i;
    if (re.test(html)) {
        return html.replace(re, '\t<!-- @include partials/footer.html -->\n');
    }
    return html;
}

// 4) Scripts comuns → @include partials/scripts-common.html
// Detecta o bloco que começa em <script src="js/jquery-3.7.1.min.js"> e vai até function.js.
// Mantém scripts específicos (radio-player, liturgia, calendario-romano, santo-dia, proximos-eventos, contact-form) APÓS.
function replaceScripts(html) {
    if (html.includes('@include partials/scripts-common.html')) return html;
    const startRe = /[ \t]*<!--[^\n]*-->\s*\n[ \t]*<script src="js\/jquery-3\.7\.1\.min\.js"><\/script>/i;
    const m = html.match(startRe);
    if (!m) {
        // tentar match direto sem comentário
        const alt = /[ \t]*<script src="js\/jquery-3\.7\.1\.min\.js"><\/script>/i;
        const m2 = html.match(alt);
        if (!m2) return html;
        return doReplaceScripts(html, m2.index, m2[0].length);
    }
    return doReplaceScripts(html, m.index, m[0].length);
}

function doReplaceScripts(html, startIdx, _firstLen) {
    // Encontrar o último script "comum" (function.js) APÓS startIdx
    const COMMON_SCRIPTS = [
        'jquery-3.7.1.min.js', 'bootstrap.min.js', 'validator.min.js', 'jquery.slicknav.js',
        'swiper-bundle.min.js', 'jquery.waypoints.min.js', 'jquery.counterup.min.js',
        'jquery.magnific-popup.min.js', 'SmoothScroll.js', 'parallaxie.js', 'gsap.min.js',
        'magiccursor.js', 'SplitText.js', 'ScrollTrigger.min.js', 'jquery.mb.YTPlayer.min.js',
        'plyr.js', 'wow.js', 'function.js'
    ];
    const SPECIFIC_SCRIPTS = [
        'radio-player.js', 'liturgia.js', 'santo-dia.js',
        'calendario-romano.js', 'proximos-eventos.js', 'contact-form.js', 'active-nav.js'
    ];

    // Encontrar fim do bloco: o último </script> que precede </body> e contém apenas COMMON_SCRIPTS+SPECIFIC_SCRIPTS+comentários
    const bodyClose = html.lastIndexOf('</body>');
    if (bodyClose < 0) return html;

    let region = html.substring(startIdx, bodyClose);
    // Capturar scripts específicos para preservá-los
    const specificFound = [];
    for (const sp of SPECIFIC_SCRIPTS) {
        const re = new RegExp('[ \\t]*<script src="js\\/' + sp.replace(/\./g, '\\.') + '"><\\/script>', 'g');
        if (re.test(region)) specificFound.push(sp);
    }

    let replacement = '\t<!-- @include partials/scripts-common.html -->\n';
    for (const sp of specificFound) {
        // active-nav já está em scripts-common
        if (sp === 'active-nav.js') continue;
        replacement += '\t<script src="js/' + sp + '"></script>\n';
    }

    return html.substring(0, startIdx) + replacement + html.substring(bodyClose);
}

// 5) Adicionar data-page no body
function addDataPage(html, fileName) {
    const dp = PAGE_TO_DATAPAGE[fileName];
    if (!dp) return html;
    if (/<body[^>]*data-page=/i.test(html)) return html;
    return html.replace(/<body([^>]*)>/i, (m, attrs) => `<body${attrs} data-page="${dp}">`);
}

let count = 0;
for (const file of PAGES) {
    const full = path.join(ROOT, file);
    let html = fs.readFileSync(full, 'utf8');
    const before = html;

    html = replaceHead(html);
    html = replaceHeader(html);
    html = replaceFooter(html);
    html = replaceScripts(html);
    html = addDataPage(html, file);

    if (html !== before) {
        fs.writeFileSync(full, html, 'utf8');
        count++;
        console.log(`✓ ${file}`);
    } else {
        console.log(`- ${file} (nada a fazer)`);
    }
}

console.log(`\n${count} arquivo(s) migrados. Agora rode: node build.js`);
