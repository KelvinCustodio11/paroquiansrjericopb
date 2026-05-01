#!/usr/bin/env node
/**
 * scripts/seo-fill.js
 *
 * Lê seo-data.json e injeta em cada página HTML, dentro do <head>:
 *   - <title>
 *   - <meta name="description">
 *   - <meta name="keywords">
 *   - <link rel="canonical">
 *   - Open Graph (og:type, og:title, og:description, og:url, og:image, og:locale, og:site_name)
 *   - Twitter Card (twitter:card, twitter:title, twitter:description, twitter:image)
 *   - <meta name="robots" content="noindex"> (se noindex=true na config)
 *   - JSON-LD WebSite (na home) ou Organization (nas demais) + BreadcrumbList
 *
 * Idempotente: usa marcadores `<!-- @seo-start -->` e `<!-- @seo-end -->`.
 * Pode rodar quantas vezes quiser; só regenera o miolo entre os marcadores.
 *
 * Uso: node scripts/seo-fill.js
 */
'use strict';
const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
const cfg = JSON.parse(fs.readFileSync(path.join(ROOT, 'seo-data.json'), 'utf8'));
const G = cfg._global;

function esc(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function buildSeo(file, page) {
    const url = G.site_url + page.url;
    const img = page.image && page.image.startsWith('http')
        ? page.image
        : G.site_url + (page.image || G.default_image);
    const type = page.type || 'website';

    const lines = [];
    lines.push(`<title>${esc(page.title)}</title>`);
    lines.push(`<meta name="description" content="${esc(page.description)}">`);
    if (page.keywords) lines.push(`<meta name="keywords" content="${esc(page.keywords)}">`);
    if (page.noindex) lines.push(`<meta name="robots" content="noindex,nofollow">`);
    lines.push(`<link rel="canonical" href="${esc(url)}">`);

    // Open Graph
    lines.push(`<meta property="og:type" content="${esc(type)}">`);
    lines.push(`<meta property="og:site_name" content="${esc(G.site_name)}">`);
    lines.push(`<meta property="og:locale" content="${esc(G.locale)}">`);
    lines.push(`<meta property="og:title" content="${esc(page.title)}">`);
    lines.push(`<meta property="og:description" content="${esc(page.description)}">`);
    lines.push(`<meta property="og:url" content="${esc(url)}">`);
    lines.push(`<meta property="og:image" content="${esc(img)}">`);

    // Twitter Card
    lines.push(`<meta name="twitter:card" content="${esc(G.twitter_card)}">`);
    if (G.twitter_site) lines.push(`<meta name="twitter:site" content="${esc(G.twitter_site)}">`);
    lines.push(`<meta name="twitter:title" content="${esc(page.title)}">`);
    lines.push(`<meta name="twitter:description" content="${esc(page.description)}">`);
    lines.push(`<meta name="twitter:image" content="${esc(img)}">`);

    // JSON-LD: Organization (sempre) + WebSite (na home) + BreadcrumbList
    const orgLd = {
        "@context": "https://schema.org",
        "@type": "Church",
        "name": G.site_name,
        "url": G.site_url,
        "logo": G.site_url + "/images/logo.png",
        "image": G.site_url + (G.default_image),
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "Rua da Matriz, s/n - Centro",
            "addressLocality": "Jericó",
            "addressRegion": "PB",
            "postalCode": "58830-000",
            "addressCountry": "BR"
        },
        "telephone": "+55-83-3435-1020",
        "sameAs": [
            "https://www.facebook.com/people/Par%C3%B3quia-Nossa-Senhora-dos-Rem%C3%A9dios/100095364065282/",
            "https://www.instagram.com/pascomremedios.jerico"
        ]
    };

    const breadcrumbList = {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": (function () {
            const items = [{
                "@type": "ListItem", "position": 1,
                "name": "Início", "item": G.site_url + "/"
            }];
            if (file !== 'index.html' && file !== '404.html') {
                items.push({
                    "@type": "ListItem", "position": 2,
                    "name": page.title.split('|')[0].trim(),
                    "item": url
                });
            }
            return items;
        })()
    };

    lines.push(`<script type="application/ld+json">\n${JSON.stringify(orgLd, null, 2)}\n</script>`);
    lines.push(`<script type="application/ld+json">\n${JSON.stringify(breadcrumbList, null, 2)}\n</script>`);

    return lines.map(l => '\t' + l).join('\n');
}

const SEO_BLOCK_RE = /[ \t]*<!--\s*@seo-start\s*-->[\s\S]*?<!--\s*@seo-end\s*-->\n?/;
const TITLE_RE = /[ \t]*<title>[\s\S]*?<\/title>\n?/i;
const DESC_RE  = /[ \t]*<meta\s+name=["']description["'][^>]*>\n?/i;
const KEYWORDS_RE = /[ \t]*<meta\s+name=["']keywords["'][^>]*>\n?/i;

let count = 0;
for (const [file, page] of Object.entries(cfg.pages)) {
    const full = path.join(ROOT, file);
    if (!fs.existsSync(full)) {
        console.warn(`! ${file} não encontrado, pulando`);
        continue;
    }
    let html = fs.readFileSync(full, 'utf8');
    const before = html;

    const seoBlock = `<!-- @seo-start -->\n${buildSeo(file, page)}\n\t<!-- @seo-end -->\n`;

    // Remover title/description/keywords legados (serão substituídos pelo bloco)
    html = html.replace(TITLE_RE, '');
    html = html.replace(DESC_RE, '');
    html = html.replace(KEYWORDS_RE, '');

    // Inserir/atualizar bloco SEO
    if (SEO_BLOCK_RE.test(html)) {
        html = html.replace(SEO_BLOCK_RE, '\t' + seoBlock);
    } else {
        // Inserir logo após <head>
        html = html.replace(/<head>\n?/i, '<head>\n\t' + seoBlock);
    }

    if (html !== before) {
        fs.writeFileSync(full, html, 'utf8');
        count++;
        console.log(`✓ ${file}`);
    } else {
        console.log(`- ${file} (sem alterações)`);
    }
}
console.log(`\n${count} arquivo(s) com SEO atualizado.`);
