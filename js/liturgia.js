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
    'Branco': { bg: '#fffef5', border: '#f5f0d8', badge: '#f5f0d8', text: '#5a4a1a' },
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
      var raw = localStorage.getItem(todayKey());
      return raw ? JSON.parse(raw) : null;
    } catch (e) { return null; }
  }

  function saveToCache(data) {
    try {
      var key = todayKey();
      localStorage.setItem(key, JSON.stringify(data));
      /* Limpar chaves antigas do mesmo prefixo para não acumular no storage */
      var toRemove = [];
      for (var i = 0; i < localStorage.length; i++) {
        var k = localStorage.key(i);
        if (k && k.indexOf(STORAGE_KEY_PREFIX) === 0 && k !== key) toRemove.push(k);
      }
      toRemove.forEach(function (k) { localStorage.removeItem(k); });
    } catch (e) {}
  }

  /* Valida estrutura mínima dos dados da API */
  function validateData(data) {
    return data && typeof data === 'object'
      && (data.evangelho || data.primeiraLeitura || data.liturgia);
  }

  /* Fetch com timeout de 12s e até 3 tentativas automáticas */
  function fetchLiturgia(onSuccess, onError) {
    var attempts = 0;
    function doFetch() {
      attempts++;
      var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
      var opts = controller ? { signal: controller.signal, cache: 'no-cache' } : { cache: 'no-cache' };
      var timer = controller ? setTimeout(function () { controller.abort(); }, 12000) : null;
      fetch(API_URL, opts)
        .then(function (r) {
          if (timer) clearTimeout(timer);
          if (!r.ok) throw new Error('HTTP ' + r.status);
          return r.json();
        })
        .then(function (data) {
          if (!validateData(data)) throw new Error('invalid_data');
          saveToCache(data);
          onSuccess(data);
        })
        .catch(function () {
          if (timer) clearTimeout(timer);
          if (attempts < 3) {
            setTimeout(doFetch, 2500);
          } else {
            onError();
          }
        });
    }
    doFetch();
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
  var _ttsWordEls = [];  /* spans lit-word da leitura ativa */
  var _ttsCharMap = [];  /* [{charStart, el}] posição de cada span em ttsText */
  var _ttsTimer = null;
  var _ttsBoundaryFired = false;
  var _ttsStartMs = 0;

  /* Remove destaque visual de palavras ativas e cancela timer */
  function clearTtsHighlights() {
    if (_ttsTimer) { clearInterval(_ttsTimer); _ttsTimer = null; }
    _ttsWordEls.forEach(function (el) { el.classList.remove('tts-word-active'); });
    _ttsWordEls = [];
    _ttsCharMap = [];
  }

  /* Envolve cada palavra em <span class="lit-word"> para efeito karaokê */
  function wrapWords(text) {
    return text.split(/([\s]+)/).map(function (chunk) {
      return /\S/.test(chunk)
        ? '<span class="lit-word">' + escHtml(chunk) + '</span>'
        : escHtml(chunk);
    }).join('');
  }

  /* Mapeia cada span.lit-word → charStart em bodyText (texto do corpo, sem prefixo) */
  function buildWordMap(ttsId, bodyText) {
    var root = ttsId === 'pddia-gospel'
      ? document.querySelector('.pddia-full-text')
      : document.getElementById(ttsId);
    _ttsWordEls = [];
    _ttsCharMap = [];
    if (!root) return;
    _ttsWordEls = Array.prototype.slice.call(root.querySelectorAll('span.lit-word'));
    if (!_ttsWordEls.length) return;
    /* O bodyText é exatamente o texto dos spans — mapeamento direto sem offset */
    var searchFrom = 0;
    _ttsWordEls.forEach(function (el) {
      var t = el.textContent;
      if (!t.trim()) return;
      var idx = bodyText.indexOf(t, searchFrom);
      if (idx >= 0) {
        _ttsCharMap.push({ charStart: idx, el: el });
        searchFrom = idx + t.length;
      } else {
        _ttsCharMap.push({ charStart: searchFrom, el: el });
        searchFrom += t.length + 1;
      }
    });
  }
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

  /* ---- Seleção de voz natural (pt-BR) ---- */
  var _ttsVoice = null;
  function loadBestVoice() {
    if (!window.speechSynthesis) return null;
    var voices = window.speechSynthesis.getVoices();
    if (!voices || !voices.length) return null;
    /* Verificar preferência salva pelo usuário */
    try {
      var saved = localStorage.getItem('lit_tts_voice');
      if (saved) {
        for (var v = 0; v < voices.length; v++) {
          if (voices[v].name === saved) { _ttsVoice = voices[v]; return voices[v]; }
        }
      }
    } catch (e) {}
    /* Prioridade padrão: vozes online pt-BR primeiro */
    var preferred = ['Google português do Brasil', 'Microsoft Francisca', 'Microsoft Maria', 'Google Portuguese', 'Luciana', 'Reed', 'Daniel'];
    for (var p = 0; p < preferred.length; p++) {
      for (var v = 0; v < voices.length; v++) {
        if (voices[v].name.indexOf(preferred[p]) !== -1) { _ttsVoice = voices[v]; return voices[v]; }
      }
    }
    for (var v = 0; v < voices.length; v++) {
      if (voices[v].lang === 'pt-BR') { _ttsVoice = voices[v]; return voices[v]; }
    }
    for (var v = 0; v < voices.length; v++) {
      if (voices[v].lang && voices[v].lang.toLowerCase().indexOf('pt') === 0) { _ttsVoice = voices[v]; return voices[v]; }
    }
    return null;
  }

  /* ---- Ordem de leituras para avanço automático ---- */
  var TTS_ORDER = ['lit-body-primeira', 'lit-body-salmo', 'lit-body-segunda', 'lit-body-evangelho',
    'lit-body-dia', 'lit-body-ofert', 'lit-body-comunhao', 'lit-body-antcom'];

  function getNextTtsId(currentId) {
    var idx = TTS_ORDER.indexOf(currentId);
    if (idx < 0) return null;
    for (var i = idx + 1; i < TTS_ORDER.length; i++) {
      if (document.getElementById(TTS_ORDER[i])) return TTS_ORDER[i];
    }
    return null;
  }

  /* Monta o texto da leitura separado em prefixo (cabeçalho) e corpo (texto visível) */
  function getTtsText(ttsId) {
    if (!_lastData) return null;
    var d = _lastData;
    var map = {
      'lit-body-primeira': function () {
        var r = d.primeiraLeitura;
        return r ? {
          prefix: 'Primeira Leitura. ' + (r.referencia || '') + '. ' + (r.titulo || '') + '.',
          body: (r.texto || '').trim()
        } : null;
      },
      'lit-body-salmo': function () {
        var r = d.salmo;
        if (!r) return null;
        var body = (r.refrao ? r.refrao + '. ' : '') + (r.texto || '');
        return { prefix: 'Salmo Responsorial. ' + (r.referencia || '') + '.', body: body.trim() };
      },
      'lit-body-segunda': function () {
        var r = d.segundaLeitura;
        return r ? {
          prefix: 'Segunda Leitura. ' + (r.referencia || '') + '. ' + (r.titulo || '') + '.',
          body: (r.texto || '').trim()
        } : null;
      },
      'lit-body-evangelho': function () {
        var r = d.evangelho;
        return r ? {
          prefix: 'Evangelho. ' + (r.referencia || '') + '. ' + (r.titulo || '') + '.',
          body: (r.texto || '').trim()
        } : null;
      },
      'lit-body-dia': function () {
        return { prefix: 'Oração do Dia.', body: (d.dia || '').trim() };
      },
      'lit-body-ofert': function () {
        return { prefix: 'Oração sobre as Oferendas.', body: (d.oferendas || '').trim() };
      },
      'lit-body-comunhao': function () {
        return { prefix: 'Oração após a Comunhão.', body: (d.comunhao || '').trim() };
      },
      'lit-body-antcom': function () {
        return d.antifonas ? { prefix: 'Antífona da Comunhão.', body: (d.antifonas.comunhao || '').trim() } : null;
      },
      'pddia-gospel': function () {
        var r = d.evangelho;
        return r ? {
          prefix: 'Evangelho. ' + (r.referencia || '') + '. ' + (r.titulo || '') + '.',
          body: (r.texto || '').trim()
        } : null;
      }
    };
    var fn = map[ttsId];
    return fn ? fn() : null;
  }

  /* Expande um bloco colapsável pelo bodyId e volta o chevron */
  function expandBlock(bodyId) {
    var bodyEl = document.getElementById(bodyId);
    if (!bodyEl) return;
    bodyEl.style.display = 'block';
    /* ID do wrapper: lit-head-XXXX-wrap */
    var wrapId = bodyId.replace('lit-body-', 'lit-head-') + '-wrap';
    var wrap = document.getElementById(wrapId);
    if (wrap) {
      var toggleBtn = wrap.querySelector('.lit-reading-toggle');
      if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
      var chevron = wrap.querySelector('.lit-chevron');
      if (chevron) chevron.style.transform = 'rotate(180deg)';
    }
  }

  /* Avança automaticamente para a próxima leitura e expande o bloco */
  function advanceToNext(currentId) {
    var nextId = getNextTtsId(currentId);
    if (!nextId) return;
    if (!document.getElementById(nextId)) return;
    expandBlock(nextId);
    var wrapId = nextId.replace('lit-body-', 'lit-head-') + '-wrap';
    var wrap = document.getElementById(wrapId);
    if (wrap) {
      setTimeout(function () { wrap.scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 80);
    }
    setTimeout(function () { LiturgiaPlayer.ttsToggle(nextId); }, 600);
  }
  var _shrCount = 0;

  /* Constrói botão de compartilhar com dropdown */
  function buildShareTooltip(mode, dark, showText) {
    var uid = 'shr-' + mode + '-' + (++_shrCount);
    var wrapCls = dark ? 'lit-shr-wrap lit-shr-dark' : 'lit-shr-wrap';
    var triggerCls = showText ? 'lit-shr-trigger lit-shr-trigger--text' : 'lit-shr-trigger';
    return '<div class="' + wrapCls + '" id="' + uid + '">'
      + '<button class="' + triggerCls + '" onclick="LiturgiaPlayer.toggleShare(event,\'' + uid + '\')" title="Compartilhar" aria-label="Compartilhar">'
      + '<i class="fa-solid fa-share-nodes"></i>'
      + (showText ? '<span>Compartilhar</span>' : '')
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

  /* Formata texto para compartilhamento: versículos em linhas separadas e limpas */
  function formatTextForShare(texto) {
    if (!texto) return '';
    return splitVerseLines(texto).map(function (l) {
      var p = parseVerseLine(l.trim());
      return p.num ? '*' + p.num + '* ' + p.text : p.text;
    }).join('\n');
  }

  /* Texto para compartilhar o Evangelho do dia */
  function buildVerseShareText(data) {
    var ref = data.evangelho ? (data.evangelho.referencia || '') : '';
    var titulo = data.evangelho ? (data.evangelho.titulo || 'Evangelho') : 'Evangelho';
    var texto = data.evangelho ? (data.evangelho.texto || '').trim() : '';
    var url = siteUrl('agenda-liturgica.html?tab=liturgia');
    return '\u2720 *' + titulo + '*\n'
      + (data.liturgia || '') + ' \u00b7 ' + (data.data || '') + '\n'
      + '_' + ref + '_\n\n'
      + formatTextForShare(texto) + '\n\n'
      + '\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\n'
      + 'Par\u00f3quia NSR Jeric\u00f3/PB\n\n'
      + url;
  }

  /* Texto para compartilhar a página completa (inclui textos de todas as leituras) */
  function buildPageShareText(data) {
    var url = siteUrl('agenda-liturgica.html?tab=liturgia');
    var out = '\ud83d\udcd6 *Liturgia do Dia \u2014 ' + (data.liturgia || '') + '*\n'
      + (data.data || '');

    if (data.primeiraLeitura && data.primeiraLeitura.texto) {
      out += '\n\n\ud83d\udcd6 *1\u00aa Leitura \u2014 ' + (data.primeiraLeitura.referencia || '') + '*';
      if (data.primeiraLeitura.titulo) out += '\n_' + data.primeiraLeitura.titulo + '_';
      out += '\n\n' + formatTextForShare(data.primeiraLeitura.texto);
    }

    if (data.salmo && (data.salmo.refrao || data.salmo.texto)) {
      out += '\n\n\ud83c\udfb5 *Salmo \u2014 ' + (data.salmo.referencia || '') + '*';
      if (data.salmo.refrao) out += '\n_Refr\u00e3o: ' + data.salmo.refrao + '_';
      if (data.salmo.texto) out += '\n\n' + formatTextForShare(data.salmo.texto);
    }

    if (data.segundaLeitura && data.segundaLeitura.texto) {
      out += '\n\n\ud83d\udcdc *2\u00aa Leitura \u2014 ' + (data.segundaLeitura.referencia || '') + '*';
      if (data.segundaLeitura.titulo) out += '\n_' + data.segundaLeitura.titulo + '_';
      out += '\n\n' + formatTextForShare(data.segundaLeitura.texto);
    }

    if (data.evangelho && data.evangelho.texto) {
      out += '\n\n\u271d *Evangelho \u2014 ' + (data.evangelho.referencia || '') + '*';
      if (data.evangelho.titulo) out += '\n_' + data.evangelho.titulo + '_';
      out += '\n\n' + formatTextForShare(data.evangelho.texto);
    }

    out += '\n\n\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\u2500\n'
      + 'Par\u00f3quia NSR Jeric\u00f3/PB\n\n'
      + url;
    return out;
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

  /* Detecta número de versículo no início de uma linha de texto bíblico */
  function parseVerseLine(line) {
    /* Aceita número colado ao texto (sem espaço) ou com espaço: "11Eu sou" ou "11 Eu sou" */
    var m = line.match(/^(\d{1,3})\s*([^\d].*)$/);
    if (m) return { num: m[1], text: m[2] };
    /* Sobrescritos unicode: ¹²³⁴⁵⁶⁷⁸⁹⁰ */
    m = line.match(/^([\u00b9\u00b2\u00b3\u2074-\u2079\u2070]+)\s*(.+)$/);
    if (m) return { num: m[1], text: m[2] };
    return { num: '', text: line };
  }

  /* Divide texto em linhas por versículo — suporta \n explícitos e números inline
     (API retorna texto como "Naquele tempo: 11Eu sou... 12O mercenário...") */
  function splitVerseLines(texto) {
    if (!texto) return [];
    /* Injeta \n antes de número de versículo inline: " 12O " → "\n12O " */
    var normalized = texto.replace(
      /[ \t]+(\d{1,3})(?=[^\d,.:;\-\u2013\u2014])/g,
      '\n$1'
    );
    return normalized.split(/\n+/).filter(function (l) { return l.trim(); });
  }

  /* Converte texto em parágrafos com palavras envoltas em span para karaokê */
  function formatVerseText(texto) {
    if (!texto) return '';
    var lines = splitVerseLines(texto);
    return lines.map(function (l, i) {
      var p = parseVerseLine(l.trim());
      if (p.num) {
        return '<p data-lit-par="' + i + '" class="lit-verse-p">'
          + '<span class="lit-vnum">' + escHtml(p.num) + '</span>'
          + '<span class="lit-vtext">' + wrapWords(p.text) + '</span>'
          + '</p>';
      }
      return '<p data-lit-par="' + i + '" style="margin: 0 0 .8em; line-height: 1.7;">' + wrapWords(l.trim()) + '</p>';
    }).join('');
  }

  /* Formata texto do salmo com palavras envoltas em span */
  function formatSalmoText(salmo) {
    if (!salmo) return '';
    var html = '';
    var idx = 0;
    if (salmo.refrao) {
      html += '<p data-lit-par="' + (idx++) + '" style="font-style:italic; font-weight:600; margin:0 0 .8em; line-height:1.7; padding: 10px 14px; background: rgba(0,0,0,.04); border-left: 3px solid var(--accent-color); border-radius: 3px;">'
        + wrapWords(salmo.refrao) + '</p>';
    }
    if (salmo.texto) {
      var strofes = splitVerseLines(salmo.texto);
      strofes.forEach(function (s) {
        var p = parseVerseLine(s.trim());
        if (p.num) {
          html += '<p data-lit-par="' + (idx++) + '" class="lit-verse-p">'
            + '<span class="lit-vnum">' + escHtml(p.num) + '</span>'
            + '<span class="lit-vtext">' + wrapWords(p.text) + '</span>'
            + '</p>';
        } else {
          html += '<p data-lit-par="' + (idx++) + '" style="margin: 0 0 .8em; line-height: 1.7;">' + wrapWords(s.trim()) + '</p>';
        }
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
      var lines = splitVerseLines(data.evangelho.texto)
        .map(function (l, i) {
          var p = parseVerseLine(l.trim());
          if (p.num) {
            return '<p data-lit-par="' + i + '" class="lit-verse-p" style="margin:0 0 .65em;line-height:1.65;">'
              + '<span class="lit-vnum">' + escHtml(p.num) + '</span>'
              + '<span class="lit-vtext">' + wrapWords(p.text) + '</span>'
              + '</p>';
          }
          return '<p data-lit-par="' + i + '" style="margin:0 0 .65em;line-height:1.65;">' + wrapWords(l.trim()) + '</p>';
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
      + '<span class="pddia-data">' + escHtml(data.data || '') + '</span>'
      + '</div>'
      + '<div class="pddia-divider"></div>'
      + excerptHtml
      + (refs.length ? '<p class="pddia-refs" style="margin-top:8px;">Leituras: ' + refs.map(escHtml).join(' \u00b7 ') + '</p>' : '');

    /* Preenche o botão de compartilhar no container externo (CTA col) se existir */
    var shareHolder = document.getElementById('liturgiaBannerShareBtn');
    if (shareHolder) {
      shareHolder.innerHTML = buildShareTooltip('verse', true, true);
    }
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

    /* Leitura em voz alta (TTS) — prefixo + corpo em duas utterances */
    ttsToggle: function (ttsId) {
      if (!window.speechSynthesis) {
        showToast('Leitura de voz não disponível neste navegador.');
        return;
      }
      /* Parar leitura ativa */
      if (_ttsActive === ttsId && window.speechSynthesis.speaking) {
        window.speechSynthesis.cancel();
        _ttsActive = null;
        updateTtsBtn(ttsId, false);
        clearTtsHighlights();
        return;
      }
      /* Parar qualquer outra leitura */
      if (_ttsActive) {
        window.speechSynthesis.cancel();
        updateTtsBtn(_ttsActive, false);
        clearTtsHighlights();
      }
      _ttsActive = ttsId;
      /* Expandir bloco de leitura se ainda estiver fechado */
      expandBlock(ttsId);

      var parts = getTtsText(ttsId);
      if (!parts || !parts.body) { _ttsActive = null; return; }

      var voice = _ttsVoice || loadBestVoice();

      function makeUtt(text) {
        var u = new SpeechSynthesisUtterance(text);
        u.lang = 'pt-BR';
        u.rate = 0.88;
        u.pitch = 1.0;
        if (voice) u.voice = voice;
        return u;
      }

      /* ---- Utterance 2: CORPO (texto visível) — karaokê ativo ---- */
      var uttBody = makeUtt(parts.body);
      buildWordMap(ttsId, parts.body);  /* mapeamento 1:1 sem offset */
      _ttsBoundaryFired = false;
      _ttsStartMs = 0;

      function activateAtCharIdx(ci) {
        var best = -1;
        for (var i = 0; i < _ttsCharMap.length; i++) {
          if (_ttsCharMap[i].charStart <= ci) best = i;
          else break;
        }
        _ttsWordEls.forEach(function (el) { el.classList.remove('tts-word-active'); });
        if (best >= 0) {
          _ttsCharMap[best].el.classList.add('tts-word-active');
          _ttsCharMap[best].el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      }

      uttBody.onstart = function () {
        _ttsStartMs = Date.now();
        /* Fallback por timer se onboundary não disparar (Firefox/vozes locais) */
        setTimeout(function () {
          if (_ttsBoundaryFired || !window.speechSynthesis.speaking || _ttsActive !== ttsId) return;
          var charsPerMs = (11 * uttBody.rate) / 1000;
          _ttsTimer = setInterval(function () {
            if (!window.speechSynthesis.speaking || _ttsActive !== ttsId) {
              clearInterval(_ttsTimer); _ttsTimer = null; return;
            }
            activateAtCharIdx(Math.floor((Date.now() - _ttsStartMs) * charsPerMs));
          }, 150);
        }, 600);
      };

      uttBody.onboundary = function (e) {
        if (e.name && e.name !== 'word') return;
        _ttsBoundaryFired = true;
        if (_ttsTimer) { clearInterval(_ttsTimer); _ttsTimer = null; }
        activateAtCharIdx(e.charIndex);
      };

      uttBody.onend = function () {
        if (_ttsActive !== ttsId) return;
        _ttsActive = null;
        updateTtsBtn(ttsId, false);
        clearTtsHighlights();
        advanceToNext(ttsId);
      };

      uttBody.onerror = function () {
        _ttsActive = null;
        updateTtsBtn(ttsId, false);
        clearTtsHighlights();
      };

      /* ---- Utterance 1: PREFIXO (cabeçalho) — sem karaokê ---- */
      if (parts.prefix) {
        var uttPrefix = makeUtt(parts.prefix);
        uttPrefix.onend = function () {
          if (_ttsActive !== ttsId) return; /* foi cancelado entre as duas */
          window.speechSynthesis.speak(uttBody);
        };
        uttPrefix.onerror = function () {
          if (_ttsActive !== ttsId) return;
          window.speechSynthesis.speak(uttBody); /* tenta o corpo mesmo assim */
        };
        updateTtsBtn(ttsId, true);
        window.speechSynthesis.speak(uttPrefix);
      } else {
        updateTtsBtn(ttsId, true);
        window.speechSynthesis.speak(uttBody);
      }
    },

    /* Lista vozes disponíveis no console para escolha */
    voices: function () {
      var vs = window.speechSynthesis ? window.speechSynthesis.getVoices() : [];
      var pt = vs.filter(function (v) { return v.lang && v.lang.toLowerCase().indexOf('pt') === 0; });
      console.log('=== Vozes pt-* disponíveis ===');
      pt.forEach(function (v, i) {
        console.log(i + ': "' + v.name + '" | ' + v.lang + (v.localService ? ' [local]' : ' [online]'));
      });
      if (!pt.length) {
        console.log('Nenhuma voz pt encontrada. Todas as vozes:');
        vs.forEach(function (v, i) { console.log(i + ': "' + v.name + '" | ' + v.lang); });
      }
      try { console.log('Voz atual:', localStorage.getItem('lit_tts_voice') || '(automática)'); } catch (e) {}
      return pt.map(function (v) { return v.name; });
    },

    /* Define voz preferida pelo nome exato (persistido em localStorage) */
    setVoice: function (name) {
      try { localStorage.setItem('lit_tts_voice', name); } catch (e) {}
      _ttsVoice = null;
      loadBestVoice();
      console.log('Voz definida:', name, '| Reinicie a leitura para aplicar.');
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
      if (cached && validateData(cached)) {
        renderBanner(cached, container);
        return;
      }

      container.innerHTML = '<div class="pddia-loading">'
        + '<div class="pddia-spinner"></div>'
        + '<span>Carregando a liturgia de hoje…</span>'
        + '</div>';

      fetchLiturgia(
        function (data) { renderBanner(data, container); },
        function () {
          container.innerHTML = '<p style="color:rgba(255,255,255,.4);font-size:.85rem;">Indisponível no momento. Tente recarregar a página.</p>';
        }
      );
    },

    /* Carrega aba completa se container existir */
    load: function (containerId) {
      var id = containerId || 'liturgiaContainer';
      var container = document.getElementById(id);
      if (!container) return;

      var cached = getFromCache();
      if (cached && validateData(cached)) {
        renderLiturgia(cached, container);
        return;
      }

      renderLoading(container);

      fetchLiturgia(
        function (data) { renderLiturgia(data, container); },
        function () { renderError(container); }
      );
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

  /* Re-inicializa após navegação PJAX */
  document.addEventListener('pjax:ready', function () {
    if (window.speechSynthesis && window.speechSynthesis.speaking) {
      window.speechSynthesis.cancel();
    }
    init();
  });

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

    /* Pré-carregar voz TTS */
    if (window.speechSynthesis) {
      if (window.speechSynthesis.onvoiceschanged !== undefined) {
        window.speechSynthesis.onvoiceschanged = function () { loadBestVoice(); };
      }
      loadBestVoice();
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

  /* Recarregar conteúdo ao restaurar página do bfcache (botão voltar/avançar) */
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) { init(); }
  });

})();
