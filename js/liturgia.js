/**
 * Liturgia Diária — Paróquia NSR Jericó/PB
 * Fonte: https://liturgia.up.railway.app/
 * Cacheia no sessionStorage pelo dia atual.
 */
(function () {
  'use strict';

  var STORAGE_KEY_PREFIX = 'liturgia_';
  var API_URL = 'https://liturgia.up.railway.app/';

  /* Mapeamento de cor litúrgica para estilo visual */
  var COR_MAP = {
    'Branco': { bg: '#fff9f0', border: '#F5C518', badge: '#F5C518', text: '#222' },
    'Vermelho': { bg: '#fff5f5', border: '#c0392b', badge: '#c0392b', text: '#fff' },
    'Roxo': { bg: '#f9f5ff', border: '#6A0DAD', badge: '#6A0DAD', text: '#fff' },
    'Verde': { bg: '#f5fff8', border: '#2E7D32', badge: '#2E7D32', text: '#fff' },
    'Rosa': { bg: '#fff5fb', border: '#e91e8c', badge: '#e91e8c', text: '#fff' },
    'Preto': { bg: '#f5f5f5', border: '#111', badge: '#111', text: '#fff' },
  };

  function todayKey() {
    var d = new Date();
    return STORAGE_KEY_PREFIX + d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate();
  }

  function getFromCache() {
    try {
      var raw = sessionStorage.getItem(todayKey());
      return raw ? JSON.parse(raw) : null;
    } catch (e) { return null; }
  }

  function saveToCache(data) {
    try { sessionStorage.setItem(todayKey(), JSON.stringify(data)); } catch (e) {}
  }

  /* ------------------------------------------------------------------ */
  /* Render helpers                                                        */
  /* ------------------------------------------------------------------ */

  function escHtml(str) {
    if (!str) return '';
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  /* Converte texto com números de versículo (ex: "38Pedro") em parágrafos limpos */
  function formatVerseText(texto) {
    if (!texto) return '';
    // Quebra em linhas por padrão, conservando parágrafos separados por \n
    var lines = texto.split(/\n+/).filter(function (l) { return l.trim(); });
    return lines.map(function (l) {
      return '<p style="margin: 0 0 .8em; line-height: 1.7;">' + escHtml(l.trim()) + '</p>';
    }).join('');
  }

  /* Formata texto do salmo: refrain em destaque, estrofes em parágrafo */
  function formatSalmoText(salmo) {
    if (!salmo) return '';
    var html = '';
    if (salmo.refrao) {
      html += '<p style="font-style:italic; font-weight:600; margin:0 0 .8em; line-height:1.7; padding: 10px 14px; background: rgba(0,0,0,.04); border-left: 3px solid var(--accent-color); border-radius: 3px;">'
        + escHtml(salmo.refrao) + '</p>';
    }
    if (salmo.texto) {
      var strofes = salmo.texto.split(/\n+/).filter(function (l) { return l.trim(); });
      strofes.forEach(function (s) {
        html += '<p style="margin: 0 0 .8em; line-height: 1.7;">' + escHtml(s.trim()) + '</p>';
      });
    }
    return html;
  }

  /* Cria um bloco de leitura colapsável */
  function buildReadingBlock(id, icon, label, referencia, titulo, bodyHtml, accentColor) {
    var headId = 'lit-head-' + id;
    var bodyId = 'lit-body-' + id;
    return '<div class="lit-reading-block" id="' + headId + '-wrap" style="margin-bottom:12px; border:1px solid #e9e9e9; border-radius:8px; overflow:hidden;">'
      + '<button type="button" class="lit-reading-toggle" '
      + 'aria-expanded="false" aria-controls="' + bodyId + '" '
      + 'onclick="LiturgiaPlayer.toggle(\'' + bodyId + '\', this)" '
      + 'style="width:100%; text-align:left; background:#fff; border:none; padding:16px 20px; cursor:pointer; display:flex; align-items:center; gap:12px;">'
      + '<span style="font-size:1.3em;">' + icon + '</span>'
      + '<span style="flex:1;">'
      + '<span style="display:block; font-weight:700; font-size:.95rem; color:#111;">' + escHtml(label) + '</span>'
      + '<span style="display:block; font-size:.82rem; color:#888;">' + escHtml(referencia || '') + '</span>'
      + '</span>'
      + '<i class="fa-solid fa-chevron-down lit-chevron" style="font-size:.8rem; color:#aaa; transition:transform .25s;"></i>'
      + '</button>'
      + '<div id="' + bodyId + '" class="lit-reading-body" style="display:none; padding:20px; background:#fafafa; border-top:1px solid #e9e9e9;">'
      + (titulo ? '<p style="font-size:.82rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#999; margin:0 0 12px;">' + escHtml(titulo) + '</p>' : '')
      + bodyHtml
      + '</div>'
      + '</div>';
  }

  /* ------------------------------------------------------------------ */
  /* Render principal                                                      */
  /* ------------------------------------------------------------------ */

  function renderLiturgia(data, container) {
    var cor = (data.cor || 'Verde').trim();
    var corStyle = COR_MAP[cor] || COR_MAP['Verde'];

    var html = '';

    /* Cabeçalho com data, celebração e cor litúrgica */
    html += '<div style="background:' + corStyle.bg + '; border-left: 5px solid ' + corStyle.border + '; border-radius:8px; padding:24px 28px; margin-bottom:28px;">';
    html += '<div style="display:flex; align-items:flex-start; gap:16px; flex-wrap:wrap;">';
    html += '<div style="flex:1;">';
    html += '<p style="margin:0 0 4px; font-size:.8rem; text-transform:uppercase; letter-spacing:.06em; color:#999;">'
      + escHtml(data.data || '') + '</p>';
    html += '<h3 style="margin:0 0 8px; font-size:1.4rem; color:#111;">' + escHtml(data.liturgia || '') + '</h3>';
    html += '</div>';
    html += '<span style="display:inline-flex; align-items:center; gap:6px; background:' + corStyle.badge + '; color:' + corStyle.text + '; font-size:.78rem; font-weight:700; padding: 4px 14px; border-radius:30px; text-transform:uppercase; letter-spacing:.08em; white-space:nowrap;">'
      + '<span style="width:8px;height:8px;border-radius:50%;background:' + corStyle.text + ';opacity:.6;display:inline-block;"></span>'
      + escHtml(cor)
      + '</span>';
    html += '</div>';
    /* Antífona de entrada */
    if (data.antifonas && data.antifonas.entrada) {
      html += '<p style="margin:14px 0 0; font-size:.9rem; font-style:italic; color:#555; line-height:1.6;">'
        + '<strong>Antífona de Entrada:</strong> ' + escHtml(data.antifonas.entrada) + '</p>';
    }
    html += '</div>';

    /* Leituras */
    html += '<div class="lit-readings-list">';

    if (data.primeiraLeitura && data.primeiraLeitura.texto) {
      html += buildReadingBlock(
        'primeira', '📖',
        '1ª Leitura',
        data.primeiraLeitura.referencia,
        data.primeiraLeitura.titulo,
        formatVerseText(data.primeiraLeitura.texto),
        corStyle.border
      );
    }

    if (data.salmo && (data.salmo.texto || data.salmo.refrao)) {
      html += buildReadingBlock(
        'salmo', '🎵',
        'Salmo Responsorial',
        data.salmo.referencia,
        null,
        formatSalmoText(data.salmo),
        corStyle.border
      );
    }

    if (data.segundaLeitura && data.segundaLeitura.texto) {
      html += buildReadingBlock(
        'segunda', '📜',
        '2ª Leitura',
        data.segundaLeitura.referencia,
        data.segundaLeitura.titulo,
        formatVerseText(data.segundaLeitura.texto),
        corStyle.border
      );
    }

    if (data.evangelho && data.evangelho.texto) {
      html += buildReadingBlock(
        'evangelho', '✝',
        'Evangelho',
        data.evangelho.referencia,
        data.evangelho.titulo,
        formatVerseText(data.evangelho.texto),
        corStyle.border
      );
    }

    html += '</div>';

    /* Orações (dia, oferendas, comunhão) num acordeão menor */
    var oracoes = [];
    if (data.dia) oracoes.push({ id: 'dia', label: 'Oração do Dia', texto: data.dia });
    if (data.oferendas) oracoes.push({ id: 'ofert', label: 'Oração sobre as Oferendas', texto: data.oferendas });
    if (data.comunhao) oracoes.push({ id: 'comunhao', label: 'Oração após a Comunhão', texto: data.comunhao });
    if (data.antifonas && data.antifonas.comunhao) oracoes.push({ id: 'antcom', label: 'Antífona da Comunhão', texto: data.antifonas.comunhao });

    if (oracoes.length) {
      html += '<div style="margin-top:20px;">';
      html += '<h4 style="font-size:.82rem; text-transform:uppercase; letter-spacing:.07em; color:#999; margin-bottom:12px;">Orações da Missa</h4>';
      oracoes.forEach(function (o) {
        html += buildReadingBlock(
          o.id, '🙏',
          o.label,
          null,
          null,
          '<p style="margin:0; line-height:1.7; font-style:italic;">' + escHtml(o.texto) + '</p>',
          corStyle.border
        );
      });
      html += '</div>';
    }

    /* Crédito da fonte */
    html += '<p style="margin-top:28px; font-size:.78rem; color:#bbb; text-align:right;">'
      + 'Leituras disponibilizadas pelo <a href="https://www.cnbb.org.br/liturgia-diaria/" target="_blank" rel="noopener" style="color:var(--accent-color);">CNBB</a>'
      + ' via <a href="https://liturgia.up.railway.app/" target="_blank" rel="noopener" style="color:var(--accent-color);">liturgia.up.railway.app</a>.</p>';

    container.innerHTML = html;
  }

  /* Renderiza estado de carregamento */
  function renderLoading(container) {
    container.innerHTML = '<div style="text-align:center; padding:60px 20px;">'
      + '<div style="display:inline-block; width:32px; height:32px; border:3px solid #e9e9e9; border-top-color:var(--accent-color); border-radius:50%; animation:lit-spin .8s linear infinite;"></div>'
      + '<p style="margin-top:16px; color:#888; font-size:.9rem;">Carregando a liturgia de hoje…</p>'
      + '</div>'
      + '<style>@keyframes lit-spin{to{transform:rotate(360deg)}}</style>';
  }

  /* Renderiza estado de erro */
  function renderError(container) {
    container.innerHTML = '<div style="text-align:center; padding:48px 20px;">'
      + '<p style="font-size:2rem; margin-bottom:12px;">📵</p>'
      + '<p style="color:#888; font-size:.95rem;">Não foi possível carregar a liturgia do dia.<br>Verifique sua conexão e tente novamente.</p>'
      + '<button onclick="LiturgiaPlayer.load()" style="margin-top:16px; padding:10px 28px; background:var(--accent-color); color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:.9rem;">Tentar novamente</button>'
      + '</div>';
  }

  /* ------------------------------------------------------------------ */
  /* API pública                                                           */
  /* ------------------------------------------------------------------ */

  window.LiturgiaPlayer = {
    /* Alterna visibilidade de um bloco de leitura */
    toggle: function (bodyId, btn) {
      var body = document.getElementById(bodyId);
      if (!body) return;
      var open = body.style.display === 'none' || body.style.display === '';
      body.style.display = open ? 'block' : 'none';
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      var chevron = btn.querySelector('.lit-chevron');
      if (chevron) chevron.style.transform = open ? 'rotate(180deg)' : '';
    },

    /* Carrega e renderiza num container */
    load: function (containerId) {
      var id = containerId || 'liturgiaContainer';
      var container = document.getElementById(id);
      if (!container) return;

      var cached = getFromCache();
      if (cached) {
        renderLiturgia(cached, container);
        return;
      }

      renderLoading(container);

      fetch(API_URL)
        .then(function (r) {
          if (!r.ok) throw new Error('HTTP ' + r.status);
          return r.json();
        })
        .then(function (data) {
          saveToCache(data);
          renderLiturgia(data, container);
        })
        .catch(function () {
          renderError(container);
        });
    },

    /* Renderiza widget compacto (homepage) */
    loadWidget: function (containerId) {
      var id = containerId || 'liturgiaWidget';
      var container = document.getElementById(id);
      if (!container) return;

      var cached = getFromCache();
      if (cached) {
        renderWidget(cached, container);
        return;
      }

      container.innerHTML = '<div style="text-align:center;padding:24px;">'
        + '<div style="display:inline-block;width:20px;height:20px;border:2px solid #e9e9e9;border-top-color:var(--accent-color);border-radius:50%;animation:lit-spin .8s linear infinite;"></div>'
        + '</div><style>@keyframes lit-spin{to{transform:rotate(360deg)}}</style>';

      fetch(API_URL)
        .then(function (r) {
          if (!r.ok) throw new Error('HTTP ' + r.status);
          return r.json();
        })
        .then(function (data) {
          saveToCache(data);
          renderWidget(data, container);
        })
        .catch(function () {
          container.innerHTML = '<p style="color:#888;font-size:.85rem;text-align:center;padding:16px;">Indisponível no momento.</p>';
        });
    }
  };

  /* ------------------------------------------------------------------ */
  /* Widget compacto para homepage                                         */
  /* ------------------------------------------------------------------ */

  function renderWidget(data, container) {
    var cor = (data.cor || 'Verde').trim();
    var corStyle = COR_MAP[cor] || COR_MAP['Verde'];

    var html = '';
    html += '<div style="border-left:4px solid ' + corStyle.border + '; padding-left:16px; margin-bottom:16px;">';
    html += '<span style="font-size:.75rem; text-transform:uppercase; letter-spacing:.07em; color:#999;">' + escHtml(data.data || '') + '</span>';
    html += '<h4 style="margin:4px 0 0; font-size:1.05rem; color:#111; font-weight:700;">' + escHtml(data.liturgia || '') + '</h4>';
    html += '<span style="display:inline-block; margin-top:4px; font-size:.72rem; background:' + corStyle.badge + '; color:' + corStyle.text + '; padding:2px 10px; border-radius:20px; font-weight:700; text-transform:uppercase; letter-spacing:.06em;">' + escHtml(cor) + '</span>';
    html += '</div>';

    if (data.evangelho) {
      html += '<div style="margin-bottom:16px;">';
      html += '<p style="margin:0 0 4px; font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#999;">Evangelho</p>';
      html += '<p style="margin:0 0 6px; font-size:.82rem; color:#888;">' + escHtml(data.evangelho.referencia || '') + '</p>';
      if (data.evangelho.texto) {
        var excerpt = data.evangelho.texto.substring(0, 180).trim();
        if (data.evangelho.texto.length > 180) excerpt += '…';
        html += '<p style="margin:0; font-size:.9rem; line-height:1.65; color:#444;">' + escHtml(excerpt) + '</p>';
      }
      html += '</div>';
    }

    html += '<a href="agenda-liturgica.html?tab=liturgia" style="display:inline-flex; align-items:center; gap:6px; font-size:.82rem; color:var(--accent-color); font-weight:600; text-decoration:none;">'
      + 'Ver leituras completas <i class="fa-solid fa-arrow-right" style="font-size:.72rem;"></i></a>';

    container.innerHTML = html;
  }

  /* ------------------------------------------------------------------ */
  /* Auto-inicialização                                                    */
  /* ------------------------------------------------------------------ */

  function init() {
    /* Carrega aba completa se container existir */
    if (document.getElementById('liturgiaContainer')) {
      LiturgiaPlayer.load('liturgiaContainer');
    }
    /* Carrega widget compacto se existir */
    if (document.getElementById('liturgiaWidget')) {
      LiturgiaPlayer.loadWidget('liturgiaWidget');
    }

    /* Suporte para link com ?tab=liturgia abrir a aba corretamente */
    if (typeof URLSearchParams !== 'undefined') {
      var params = new URLSearchParams(window.location.search);
      if (params.get('tab') === 'liturgia') {
        var btn = document.getElementById('liturgia-tab');
        if (btn) {
          btn.click();
          setTimeout(function () { btn.scrollIntoView({ behavior: 'smooth', block: 'center' }); }, 300);
        }
      }
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
