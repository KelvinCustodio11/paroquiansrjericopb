/**
 * page-views.js — Contador de visualizações
 *
 * Registra uma visita na API do CMS (admin.pascomjerico.com.br).
 * Deduplicação via localStorage: cada página é contada 1× por IP a cada 4 h
 * (verificação no servidor) e 1× por navegador por dia (verificação local).
 *
 * URL da API configurável via <meta name="cms-api-url" content="...">
 * ou usando o valor padrão de produção.
 */
(function () {
  'use strict';

  // ── URL da API ────────────────────────────────────────────────────────────
  // 1. Prioridade: <meta name="cms-api-url"> (override manual para testes)
  // 2. Auto-detecção pelo hostname: local → 127.0.0.1:8001, produção → admin.pascomjerico.com.br
  var metaTag  = document.querySelector('meta[name="cms-api-url"]');
  var hostname = window.location.hostname;
  var isLocal  = hostname === 'localhost'
              || hostname === '127.0.0.1'
              || hostname === ''               // file://
              || /\.local$/.test(hostname);

  var API_BASE = metaTag
    ? metaTag.getAttribute('content')
    : isLocal
      ? 'http://127.0.0.1:8001'
      : 'https://admin.pascomjerico.com.br';

  var API_URL = API_BASE.replace(/\/$/, '') + '/api/page-view';

  // ── Identificador da página ───────────────────────────────────────────────
  var pathname = window.location.pathname
    .replace(/\/index\.html$/, '/')
    .replace(/\.html$/, '')
    .replace(/^\//, '');

  var pagina = pathname || 'home';

  // Páginas de detalhe carregam o identificador do item via data attribute
  // ex: <article data-pagina="artigo:boas-vindas-novo-site">
  var detalheEl = document.querySelector('[data-pagina]');
  if (detalheEl) {
    pagina = detalheEl.getAttribute('data-pagina') || pagina;
  }

  // Sanitiza: permite apenas a-z0-9 : - _ / .
  pagina = pagina.replace(/[^a-z0-9:_\-\/\.]/gi, '-').slice(0, 150);

  // ── Deduplicação local (localStorage) ────────────────────────────────────
  var hoje = new Date().toISOString().slice(0, 10); // YYYY-MM-DD
  var chave = 'pv_' + pagina + '_' + hoje;

  try {
    if (localStorage.getItem(chave)) return;
  } catch (e) {
    // localStorage bloqueado (modo anônimo strict) — continua sem dedup local
  }

  // ── Registrar via API ─────────────────────────────────────────────────────
  var titulo = document.title || '';

  fetch(API_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ pagina: pagina, titulo: titulo }),
  })
    .then(function (r) {
      if (r.ok || r.status === 429) {
        // 429 = já contou no servidor; marcar local também para parar de tentar
        try { localStorage.setItem(chave, '1'); } catch (e) { /* noop */ }
      }
    })
    .catch(function () {
      // Falha silenciosa — API indisponível não deve afetar a experiência
    });
})();
