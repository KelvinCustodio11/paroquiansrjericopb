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

  /* Dado mais recente carregado (para os handlers de compartilhamento) */
  var _lastData = null;

  /* URL base do site — funciona em qualquer subpath de hospedagem */
  function siteUrl(path) {
    return window.location.href.replace(/\/[^/]*(\?.*)?$/, '/') + path;
  }

  /* Toast discreto de notificação */
  function showToast(msg) {
    var old = document.getElementById('lit-toast');
    if (old) old.remove();
    var el = document.createElement('div');
    el.id = 'lit-toast';
    el.textContent = msg;
    el.style.cssText = 'position:fixed;bottom:100px;left:50%;transform:translateX(-50%);'
      + 'background:#111;color:#fff;padding:10px 22px;border-radius:6px;'
      + 'font-size:.85rem;z-index:99999;white-space:nowrap;'
      + 'box-shadow:0 4px 16px rgba(0,0,0,.4);pointer-events:none;';
    document.body.appendChild(el);
    setTimeout(function () { if (el.parentNode) el.remove(); }, 2800);
  }

  /* Cópia de fallback para navegadores sem clipboard API */
  function fallbackCopy(text, msg) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.cssText = 'position:fixed;top:-9999px;left:-9999px;opacity:0;';
    document.body.appendChild(ta);
    ta.focus();
    ta.select();
    try { document.execCommand('copy'); showToast(msg || '\u2713 Copiado!'); }
    catch (e) { showToast('N\u00e3o foi poss\u00edvel copiar.'); }
    document.body.removeChild(ta);
  }

  /* Copia texto para clipboard com mensagem de sucesso */
  function doCopy(text, msg) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text)
        .then(function () { showToast(msg || '\u2713 Copiado!'); })
        .catch(function () { fallbackCopy(text, msg); });
    } else {
      fallbackCopy(text, msg);
    }
  }

  /* Atualiza visual do botão TTS */
  var _ttsActive = null;
  function updateTtsBtn(ttsId, playing) {
    var btn = document.querySelector('[data-tts-for="' + ttsId + '"]');
    if (!btn) return;
    var icon = btn.querySelector('i');
    if (icon) icon.className = playing ? 'fa-solid fa-volume-xmark' : 'fa-solid fa-volume-high';
    if (playing) btn.classList.add('tts-playing');
    else btn.classList.remove('tts-playing');
  }

  /* Constrói botão TTS discreto */
  function buildTtsBtn(ttsId) {
    return '<button class="lit-tts-btn" data-tts-for="' + ttsId + '" '
      + 'onclick="LiturgiaPlayer.ttsToggle(\'' + ttsId + '\')" '
      + 'title="Ouvir leitura" aria-label="Ouvir leitura">'
      + '<i class="fa-solid fa-volume-high"></i>'
      + '</button>';
  }

  /* Contador p/ IDs únicos dos share dropdowns */
  var _shrCount = 0;

  /* Constrói botão de compartilhar com dropdown */
  function buildShareTooltip(mode, dark) {
    var uid = 'shr-' + mode + '-' + (++_shrCount);
    var wrapCls = dark ? 'lit-shr-wrap lit-shr-dark' : 'lit-shr-wrap';
    return '<div class="' + wrapCls + '" id="' + uid + '">'
      + '<button class="lit-shr-trigger" onclick="LiturgiaPlayer.toggleShare(event,\'' + uid + '\')" title="Compartilhar" aria-label="Compartilhar">'
      + '<i class="fa-solid fa-share-nodes"></i>'
      + '</button>'
      + '<div class="lit-shr-dropdown" id="' + uid + '-dd" role="menu">'
      + '<button class="lit-shr-opt" onclick="LiturgiaPlayer.shareAction(\'copy\',\''
        + mode + '\')" role="menuitem">'
      + '<i class="fa-regular fa-copy"></i><span>Copiar leitura</span></button>'
      + '<button class="lit-shr-opt lit-shr-wpp" onclick="LiturgiaPlayer.shareAction(\'wpp\',\''
        + mode + '\')" role="menuitem">'
      + '<i class="fa-brands fa-whatsapp"></i><span>Enviar pelo WhatsApp</span></button>'
      + '<button class="lit-shr-opt lit-shr-wppst" onclick="LiturgiaPlayer.shareAction(\'wppstatus\',\''
        + mode + '\')" role="menuitem">'
      + '<i class="fa-brands fa-whatsapp"></i><span>Status do WhatsApp</span></button>'
      + '<button class="lit-shr-opt lit-shr-ig" onclick="LiturgiaPlayer.shareAction(\'ig\',\''
        + mode + '\')" role="menuitem">'
      + '<i class="fa-brands fa-instagram"></i><span>Instagram</span></button>'
      + '</div>'
      + '</div>';
  }

  /* Texto para compartilhar o Evangelho do dia */
  function buildVerseShareText(data) {
    var ref = data.evangelho ? (data.evangelho.referencia || '') : '';
    var titulo = data.evangelho ? (data.evangelho.titulo || 'Evangelho') : 'Evangelho';
    var texto = data.evangelho ? (data.evangelho.texto || '').trim() : '';
    var url = siteUrl('agenda-liturgica.html?tab=liturgia');
    return '\u2720 ' + titulo + '\n'
      + (data.liturgia || '') + ' \u00b7 ' + (data.data || '') + '\n'
      + ref + '\n\n'
      + '\u201c' + texto + '\u201d\n\n'
      + '\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\n'
      + 'Par\u00f3quia NSR Jeric\u00f3/PB\n'
      + url;
  }

  /* Texto para compartilhar a página completa */
  function buildPageShareText(data) {
    var refs = [];
    if (data.primeiraLeitura && data.primeiraLeitura.referencia) refs.push(data.primeiraLeitura.referencia);
    if (data.salmo && data.salmo.referencia) refs.push(data.salmo.referencia);
    if (data.segundaLeitura && data.segundaLeitura.referencia) refs.push(data.segundaLeitura.referencia);
    if (data.evangelho && data.evangelho.referencia) refs.push(data.evangelho.referencia);
    var url = siteUrl('agenda-liturgica.html?tab=liturgia');
    return '\ud83d\udcd6 Liturgia do Dia \u2014 ' + (data.liturgia || '') + '\n'
      + (data.data || '') + '\n\n'
      + (refs.length ? 'Leituras: ' + refs.join(' \u00b7 ') + '\n\n' : '')
      + 'Acesse as leituras completas:\n'
      + url + '\n\n'
      + 'Par\u00f3quia NSR Jeric\u00f3/PB';
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
      + '<div class="lit-block-tools">'
      + buildTtsBtn(bodyId)
      + '</div>'
      + (titulo ? '<p style="font-size:.82rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#999; margin:0 0 12px;">' + escHtml(titulo) + '</p>' : '')
      + bodyHtml
      + '</div>'
      + '</div>';
  }

  /* ------------------------------------------------------------------ */
  /* Render principal                                                      */
  /* ------------------------------------------------------------------ */

  function renderLiturgia(data, container) {
    _lastData = data;
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

    /* Share + área de ferramentas do cabeçalho */
    html += '<div class="lit-page-tools">' + buildShareTooltip('page') + '</div>';

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

  /* ------------------------------------------------------------------ */
  /* Banner destaque (homepage — após ticker)                           */
  /* ------------------------------------------------------------------ */

  function renderBanner(data, container) {
    _lastData = data;
    var cor = (data.cor || 'Verde').trim();
    var corStyle = COR_MAP[cor] || COR_MAP['Verde'];

    /* Texto completo do evangelho, formatado em parágrafos sem números de versículo soltos */
    var excerptHtml = '';
    if (data.evangelho && data.evangelho.texto) {
      var lines = data.evangelho.texto
        .split(/\n+/)
        .filter(function (l) { return l.trim(); })
        .map(function (l) {
          return '<p style="margin:0 0 .65em;line-height:1.65;">' + escHtml(l.trim()) + '</p>';
        });
      excerptHtml = '<div class="pddia-gospel-row">'
        + '<span class="pddia-gospel-ref">'
        + escHtml((data.evangelho.titulo || 'Evangelho') + ' \u2014 ' + (data.evangelho.referencia || ''))
        + '</span>'
        + buildTtsBtn('pddia-gospel')
        + '</div>'
        + '<div class="pddia-full-text">' + lines.join('') + '</div>';
    }

    /* Referências das leituras */
    var refs = [];
    if (data.primeiraLeitura && data.primeiraLeitura.referencia) refs.push(data.primeiraLeitura.referencia);
    if (data.salmo && data.salmo.referencia) refs.push(data.salmo.referencia);
    if (data.segundaLeitura && data.segundaLeitura.referencia) refs.push(data.segundaLeitura.referencia);
    if (data.evangelho && data.evangelho.referencia) refs.push(data.evangelho.referencia);

    container.innerHTML = '<div class="pddia-meta">'
      + '<span class="pddia-badge" style="background:' + corStyle.badge + ';color:' + corStyle.text + ';">'
      + '<span class="pddia-badge-dot" style="background:' + corStyle.text + ';"></span>'
      + escHtml(cor)
      + '</span>'
      + '<span class="pddia-celebracao">' + escHtml(data.liturgia || '') + '</span>'
      + '<span class="pddia-data">' + escHtml(data.data || '') + '</span>'
      + '</div>'
      + '<div class="pddia-divider"></div>'
      + excerptHtml
      + '<div class="pddia-bottom-row">'
      + (refs.length ? '<p class="pddia-refs">Leituras: ' + refs.map(escHtml).join(' \u00b7 ') + '</p>' : '<p></p>')
      + buildShareTooltip('verse', true)
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

    /* Leitura em voz alta (TTS) */
    ttsToggle: function (ttsId) {
      if (!window.speechSynthesis) {
        showToast('Leitura de voz n\u00e3o dispon\u00edvel neste navegador.');
        return;
      }
      /* Parar leitura ativa */
      if (_ttsActive === ttsId && window.speechSynthesis.speaking) {
        window.speechSynthesis.cancel();
        _ttsActive = null;
        updateTtsBtn(ttsId, false);
        return;
      }
      /* Parar qualquer outra leitura */
      if (_ttsActive) {
        window.speechSynthesis.cancel();
        updateTtsBtn(_ttsActive, false);
      }
      _ttsActive = ttsId;
      /* Obter texto */
      var text = '';
      if (ttsId === 'pddia-gospel') {
        text = _lastData && _lastData.evangelho ? (_lastData.evangelho.texto || '') : '';
      } else {
        var el = document.getElementById(ttsId);
        text = el ? (el.innerText || el.textContent || '') : '';
      }
      if (!text.trim()) { _ttsActive = null; return; }
      var utt = new SpeechSynthesisUtterance(text.trim());
      utt.lang = 'pt-BR';
      utt.rate = 0.88;
      utt.onend = function () { _ttsActive = null; updateTtsBtn(ttsId, false); };
      utt.onerror = function () { _ttsActive = null; updateTtsBtn(ttsId, false); };
      updateTtsBtn(ttsId, true);
      window.speechSynthesis.speak(utt);
    },

    /* Abre/fecha dropdown de compartilhar */
    toggleShare: function (event, wrapId) {
      event.stopPropagation();
      var dd = document.getElementById(wrapId + '-dd');
      if (!dd) return;
      var isOpen = dd.classList.contains('open');
      /* Fechar todos */
      document.querySelectorAll('.lit-shr-dropdown.open').forEach(function (el) {
        el.classList.remove('open');
      });
      if (!isOpen) dd.classList.add('open');
    },

    /* Executa ação de compartilhamento */
    shareAction: function (action, mode) {
      if (!_lastData) return;
      /* Fechar dropdown */
      document.querySelectorAll('.lit-shr-dropdown.open').forEach(function (el) {
        el.classList.remove('open');
      });
      var text = mode === 'page' ? buildPageShareText(_lastData) : buildVerseShareText(_lastData);
      if (action === 'copy') {
        doCopy(text, '\u2713 Leitura copiada!');
      } else if (action === 'wpp') {
        window.open('https://wa.me/?text=' + encodeURIComponent(text), '_blank', 'noopener,noreferrer');
      } else if (action === 'wppstatus') {
        doCopy(text, '\u2713 Copiado! Abra o WhatsApp \u2192 Status e cole.');
        setTimeout(function () {
          window.open('https://wa.me/', '_blank', 'noopener,noreferrer');
        }, 700);
      } else if (action === 'ig') {
        doCopy(text, '\u2713 Copiado! Abra o Instagram e cole na hist\u00f3ria ou direct.');
      }
    },

    /* Carrega banner destaque (homepage) */
    loadBanner: function (containerId) {
      var id = containerId || 'liturgiaBannerContent';
      var container = document.getElementById(id);
      if (!container) return;

      var cached = getFromCache();
      if (cached) {
        renderBanner(cached, container);
        return;
      }

      container.innerHTML = '<div class="pddia-loading">'
        + '<div class="pddia-spinner"></div>'
        + '<span>Carregando a liturgia de hoje…</span>'
        + '</div>';

      fetch(API_URL)
        .then(function (r) {
          if (!r.ok) throw new Error('HTTP ' + r.status);
          return r.json();
        })
        .then(function (data) {
          saveToCache(data);
          renderBanner(data, container);
        })
        .catch(function () {
          container.innerHTML = '<p style="color:rgba(255,255,255,.4);font-size:.85rem;">Indisponível no momento.</p>';
        });
    },

    /* Carrega aba completa se container existir */
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
    /* Carrega banner destaque */
    if (document.getElementById('liturgiaBannerContent')) {
      LiturgiaPlayer.loadBanner('liturgiaBannerContent');
    }
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

    /* Fechar dropdowns de share ao clicar fora */
    document.addEventListener('click', function () {
      document.querySelectorAll('.lit-shr-dropdown.open').forEach(function (el) {
        el.classList.remove('open');
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
