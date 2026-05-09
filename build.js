#!/usr/bin/env node
/**
 * build.js
 * Resolve marcadores `<!-- @include path/to/partial.html -->` em arquivos HTML.
 *
 * Modo de operação (idempotente):
 *  - Se a página tem APENAS `<!-- @include partials/X.html -->`, expande inserindo
 *    o conteúdo entre marcadores `<!-- @include-start partials/X.html -->` e
 *    `<!-- @include-end partials/X.html -->`.
 *  - Se já tem os marcadores -start/-end, substitui o miolo pelo conteúdo atualizado.
 *
 * Sem dependências externas. Roda com `node build.js` ou `npm run build`.
 *
 * Saída: edita os HTMLs in-place na raiz (não cria pasta dist/).
 *
 * Exclusões: ignora _template-avenix/, _template-paroquia/, partials/, node_modules/.
 */
'use strict';

const fs = require('fs');
const path = require('path');

const ROOT = __dirname;
const EXCLUDES = new Set(['_template-avenix', '_template-paroquia', 'partials', 'templates', 'node_modules', '.git', 'images', 'css', 'js', 'webfonts', 'data', 'schemas', 'scripts', 'eventos', 'artigos', 'homilias']);

function listHtmlFiles(dir) {
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    let files = [];
    for (const ent of entries) {
        if (EXCLUDES.has(ent.name) || ent.name.startsWith('.')) continue;
        const full = path.join(dir, ent.name);
        if (ent.isDirectory()) {
            files = files.concat(listHtmlFiles(full));
        } else if (ent.isFile() && ent.name.endsWith('.html')) {
            files.push(full);
        }
    }
    return files;
}

function readPartial(relPath) {
    const full = path.resolve(ROOT, relPath);
    if (!fs.existsSync(full)) {
        throw new Error(`Partial não encontrado: ${relPath}`);
    }
    let content = fs.readFileSync(full, 'utf8').trim();
    // Resolve includes aninhados (no máximo 1 nível de aninhamento esperado)
    content = expandIncludes(content, path.dirname(full));
    return content;
}

const SIMPLE_RE = /[ \t]*<!--\s*@include\s+([^\s]+?)\s*-->[ \t]*\n?/g;
const PAIR_RE = /[ \t]*<!--\s*@include-start\s+([^\s]+?)\s*-->[\s\S]*?<!--\s*@include-end\s+\1\s*-->[ \t]*\n?/g;

function expandIncludes(html, _basedir) {
    // 1) Pares -start/-end existentes: regenera o miolo
    html = html.replace(PAIR_RE, (_, partial) => buildBlock(partial));
    // 2) Marcadores simples: insere bloco completo
    html = html.replace(SIMPLE_RE, (_, partial) => buildBlock(partial));
    return html;
}

function buildBlock(partial) {
    const content = readPartial(partial);
    return `<!-- @include-start ${partial} -->\n${content}\n<!-- @include-end ${partial} -->\n`;
}

function processFile(file) {
    const original = fs.readFileSync(file, 'utf8');
    if (!/<!--\s*@include(?:-start)?\s+/.test(original)) {
        return { file, changed: false, skipped: 'sem marcadores' };
    }
    const updated = expandIncludes(original);
    if (updated !== original) {
        fs.writeFileSync(file, updated, 'utf8');
        return { file, changed: true };
    }
    return { file, changed: false };
}

function main() {
    const files = listHtmlFiles(ROOT);
    let changed = 0, skipped = 0, untouched = 0;
    const results = [];
    for (const f of files) {
        try {
            const r = processFile(f);
            if (r.changed) { changed++; results.push(`✓ ${path.relative(ROOT, f)}`); }
            else if (r.skipped) { skipped++; }
            else { untouched++; }
        } catch (e) {
            console.error(`✗ ${path.relative(ROOT, f)}: ${e.message}`);
            process.exitCode = 1;
        }
    }
    console.log(results.join('\n'));
    console.log(`\nResumo: ${changed} atualizados, ${skipped} sem marcadores, ${untouched} sem alteração.`);
}

main();
