/**
 * devocoes-diarias.js
 * Lógica da página Devoções Diárias (abas Santo do Dia, Terço, Liturgia).
 * Extraído como arquivo externo para que o PJAX possa re-executá-lo ao
 * navegar para a página sem recarregar o navegador.
 */
(function () {
    'use strict';

    /* ── Oculta abas conforme config do CMS ─────────────────────────── */
    fetch('data/configuracoes.json?' + Date.now())
        .then(function (r) { return r.ok ? r.json() : {}; })
        .catch(function () { return {}; })
        .then(function (cfg) {
            if (cfg.habilitar_santo_dia === false) {
                var liSanto = document.getElementById('tab-santo-item');
                if (liSanto) liSanto.style.display = 'none';
            }
            if (cfg.habilitar_terco_dia === false) {
                var liTerco = document.getElementById('tab-terco-item');
                if (liTerco) liTerco.style.display = 'none';
            }
        });

    /* ── Helpers para o Santo do Dia ─────────────────────────────────── */
    var MESES_PT = [
        'janeiro','fevereiro','março','abril','maio','junho',
        'julho','agosto','setembro','outubro','novembro','dezembro'
    ];

    var SECTION_ICONS = {
        'biografia':'fa-book-open','vida':'fa-seedling','vida e obra':'fa-seedling',
        'história':'fa-scroll','infância':'fa-child','infancia':'fa-child',
        'juventude':'fa-person','espiritualidade':'fa-dove',
        'espiritualidade e mística':'fa-dove','missão':'fa-compass',
        'apostolado':'fa-hands-praying','reforma da igreja':'fa-church',
        'reforma':'fa-church','virtudes':'fa-star','martírio':'fa-cross',
        'martirio':'fa-cross','morte':'fa-cross','canonização':'fa-certificate',
        'canonizacao':'fa-certificate','beatificação':'fa-certificate',
        'beatificacao':'fa-certificate','veneração':'fa-heart','veneracao':'fa-heart',
        'padroeira':'fa-shield-halved','padroeiro':'fa-shield-halved',
        'patronagem':'fa-shield-halved','obras':'fa-feather-pointed',
        'escritos':'fa-feather-pointed','legado':'fa-landmark',
        'herança espiritual':'fa-landmark','milagres':'fa-star-of-david',
        'iconografia':'fa-image','relíquias':'fa-box','reliquias':'fa-box',
        'culto':'fa-hands-praying','tentações':'fa-fire-flame-curved',
        'tentacoes':'fa-fire-flame-curved','dom das lágrimas':'fa-droplet',
        'ordem dominicana':'fa-om','fontes para vida':'fa-book',
        'doutora da igreja':'fa-graduation-cap','doutor da igreja':'fa-graduation-cap'
    };

    function getIcon(title) {
        if (!title) return 'fa-cross';
        return SECTION_ICONS[title.toLowerCase().trim()] || 'fa-cross';
    }

    function formatDate(d) {
        return d.getDate() + ' de ' + MESES_PT[d.getMonth()] + ' de ' + d.getFullYear();
    }

    function showLoading(msg) {
        document.getElementById('sdLoading').removeAttribute('hidden');
        document.getElementById('sdCard').setAttribute('hidden', '');
        document.getElementById('sdError').setAttribute('hidden', '');
        document.getElementById('sdSearchResults').setAttribute('hidden', '');
        var txtEl = document.querySelector('#sdLoading p');
        if (txtEl) txtEl.textContent = msg || 'Buscando o santo do dia…';
    }

    function escHtml(s) {
        return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function showError(customMsg) {
        document.getElementById('sdLoading').setAttribute('hidden', '');
        document.getElementById('sdCard').setAttribute('hidden', '');
        document.getElementById('sdSearchResults').setAttribute('hidden', '');
        var errEl = document.getElementById('sdError');
        if (customMsg) {
            errEl.innerHTML =
                '<i class="fa-solid fa-magnifying-glass fa-3x d-block mb-3" style="color:#ccc;"></i>' +
                '<p>' + customMsg + '</p>';
        } else {
            errEl.innerHTML =
                '<i class="fa-solid fa-church fa-3x d-block mb-3" style="color:#ccc;"></i>' +
                '<p>Não foi possível encontrar o santo do dia para esta data.<br>Tente outra data ou verifique sua conexão.</p>';
        }
        errEl.removeAttribute('hidden');
    }

    var _lastSearchResults = null;

    function showCard(data, d, fromQuery) {
        document.getElementById('sdLoading').setAttribute('hidden', '');
        document.getElementById('sdError').setAttribute('hidden', '');
        document.getElementById('sdSearchResults').setAttribute('hidden', '');

        var backBtn = document.getElementById('sdBackBtn');
        if (fromQuery && _lastSearchResults && _lastSearchResults.length > 1) {
            backBtn._fromQuery = fromQuery;
            backBtn.removeAttribute('hidden');
        } else {
            backBtn.setAttribute('hidden', '');
        }

        document.getElementById('sdCardDate').textContent = d ? 'Festa: ' + formatDate(d) : '';
        document.getElementById('sdCardNome').textContent = data.nome || '';
        document.getElementById('sdCardDescricao').textContent = data.descricao || '';

        var imgEl = document.getElementById('sdCardImg');
        var placeholder = document.getElementById('sdImgPlaceholder');
        if (data.imagemGrande || data.imagem) {
            imgEl.src = data.imagemGrande || data.imagem;
            imgEl.alt = data.nome || 'Santo';
            imgEl.removeAttribute('hidden');
            if (placeholder) placeholder.style.display = 'none';
        } else {
            imgEl.setAttribute('hidden', '');
            if (placeholder) placeholder.style.display = 'flex';
        }

        var contentEl = document.getElementById('sdContent');
        contentEl.innerHTML = '';

        if (data.bioHtml) {
            contentEl.innerHTML = data.bioHtml;
            var supplementary = (data.secoes || []).filter(function (s) { return s.title && s.paras.length; });
            if (supplementary.length) {
                var divider = document.createElement('div');
                divider.className = 'sd-supplement-divider';
                divider.innerHTML = '<span><i class="fa-solid fa-book-open-reader me-1" aria-hidden="true"></i>Aprofunde-se</span>';
                contentEl.appendChild(divider);
                supplementary.forEach(function (sec) {
                    var h = document.createElement('h3');
                    h.textContent = sec.title;
                    contentEl.appendChild(h);
                    sec.paras.forEach(function (para) {
                        var p = document.createElement('p');
                        p.textContent = para;
                        contentEl.appendChild(p);
                    });
                });
            }
        } else if (data.secoes && data.secoes.length) {
            data.secoes.forEach(function (sec, idx) {
                if (idx === 0) {
                    sec.paras.forEach(function (para) {
                        var p = document.createElement('p');
                        p.textContent = para;
                        contentEl.appendChild(p);
                    });
                    return;
                }
                if (!sec.paras.length) return;
                if (sec.title) {
                    var h = document.createElement('h3');
                    h.textContent = sec.title;
                    contentEl.appendChild(h);
                }
                sec.paras.forEach(function (para) {
                    var p = document.createElement('p');
                    p.textContent = para;
                    contentEl.appendChild(p);
                });
            });
        } else {
            contentEl.innerHTML = '<p>Conteúdo biográfico não disponível para este santo.</p>';
        }

        document.getElementById('sdCard').removeAttribute('hidden');
    }

    function loadSanto(d) {
        showLoading();
        window.SantoDia.fetchForDate(d, function (data) { showCard(data, d, null); }, showError);
    }

    function loadSantoByTitle(title, fromQuery) {
        document.getElementById('sdSearchResults').setAttribute('hidden', '');
        showLoading('Carregando hagiografia…');
        window.SantoDia.fetchForTitle(title, function (data) {
            showCard(data, null, fromQuery);
        }, function () {
            showError('Não foi possível carregar os dados deste santo.<br>Verifique sua conexão e tente novamente.');
        });
    }

    function showSearchResults(results, query) {
        _lastSearchResults = results;
        document.getElementById('sdLoading').setAttribute('hidden', '');
        document.getElementById('sdCard').setAttribute('hidden', '');
        document.getElementById('sdError').setAttribute('hidden', '');

        var hint = document.getElementById('sdSearchHint');
        hint.innerHTML =
            '<i class="fa-solid fa-list-ul me-1"></i> ' +
            results.length + ' resultado' + (results.length > 1 ? 's' : '') +
            ' para <strong>"' + escHtml(query) + '"</strong> &mdash; selecione para ver a hagiografia:';

        var list = document.getElementById('sdSearchList');
        list.innerHTML = '';
        results.forEach(function (r) {
            var col = document.createElement('div');
            col.className = 'col-lg-3 col-md-4 col-sm-6 col-6';
            var hasImg = !!r.imagem;
            col.innerHTML =
                '<div class="sd-result-card" role="button" tabindex="0" aria-label="Ver hagiografia de ' + escHtml(r.nome) + '">' +
                    (hasImg
                        ? '<div class="sd-result-img"><img src="' + escHtml(r.imagem) + '" alt="' + escHtml(r.nome) + '" loading="lazy"></div>'
                        : '<div class="sd-result-img sd-result-img--placeholder"><i class="fa-solid fa-person-praying"></i></div>') +
                    '<div class="sd-result-info">' +
                        '<h5 class="sd-result-nome">' + escHtml(r.nome) + '</h5>' +
                        (r.descricao ? '<p class="sd-result-desc">' + escHtml(r.descricao) + '</p>' : '') +
                    '</div></div>';
            var card = col.querySelector('.sd-result-card');
            (function (title) {
                function select() { loadSantoByTitle(title, query); }
                card.addEventListener('click', select);
                card.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); select(); }
                });
            }(r.title));
            list.appendChild(col);
        });
        document.getElementById('sdSearchResults').removeAttribute('hidden');
    }

    function searchSantos(query) {
        if (!query.trim()) return;
        showLoading('Buscando santos…');
        window.SantoDia.searchSaints(query, function (results) {
            if (results.length === 1) {
                loadSantoByTitle(results[0].title, null);
            } else {
                showSearchResults(results, query);
            }
        }, function () {
            showError('Nenhum santo encontrado para <strong>"' + escHtml(query) + '"</strong>.<br>Tente outro nome ou vocação.');
        });
    }

    var _initDone = false;

    function initSantoTab() {
        var input     = document.getElementById('sdDateInput');
        var textInput = document.getElementById('sdTextInput');
        var btn       = document.getElementById('sdBuscarBtn');
        var backBtn   = document.getElementById('sdBackBtn');
        if (!input || !btn) return;

        var today = new Date();
        var yyyy = today.getFullYear();
        var mm   = String(today.getMonth() + 1).padStart(2, '0');
        var dd   = String(today.getDate()).padStart(2, '0');
        input.value = yyyy + '-' + mm + '-' + dd;

        if (!_initDone) {
            _initDone = true;
            loadSanto(today);

            btn.addEventListener('click', function () {
                var txt = textInput ? textInput.value.trim() : '';
                if (txt) {
                    searchSantos(txt);
                } else {
                    var val = input.value;
                    if (!val) return;
                    var parts = val.split('-');
                    var d = new Date(+parts[0], +parts[1] - 1, +parts[2]);
                    loadSanto(d);
                }
            });

            [input, textInput].forEach(function (el) {
                if (!el) return;
                el.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') btn.click();
                });
            });

            if (backBtn) {
                backBtn.querySelector('button').addEventListener('click', function () {
                    document.getElementById('sdCard').setAttribute('hidden', '');
                    if (_lastSearchResults && _lastSearchResults.length > 1) {
                        showSearchResults(_lastSearchResults, backBtn._fromQuery || '');
                    }
                });
            }
        }
    }

    function activateSantoTabIfHash() {
        if (window.location.hash === '#santo-dia') {
            var tab = document.getElementById('santo-dia-tab');
            if (tab && typeof bootstrap !== 'undefined') {
                new bootstrap.Tab(tab).show();
            }
        }
    }

    /* ── Terço do Dia ─────────────────────────────────────────────────── */
    /* carregarTerco() e toda a lógica do terço estão em js/terco.js     */
    var _tercoLoaded = false;

    /* ── fixWow: garante visibilidade dos elementos .wow dentro de abas ─ */
    function fixWowInTab(tabId) {
        var pane = document.getElementById(tabId);
        if (!pane) return;
        pane.querySelectorAll('.wow').forEach(function (el) {
            if (el.style.visibility === 'hidden' || getComputedStyle(el).visibility === 'hidden') {
                el.style.visibility = 'visible';
                el.style.opacity    = '1';
                if (!el.classList.contains('animated')) el.classList.add('animated');
            }
        });
    }

    /* ── Tab event listener (fixWow + lazy load do Terço) ─────────────── */
    var tabEls = document.querySelectorAll('#devTab .nav-link');
    tabEls.forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () {
            var target = btn.getAttribute('data-bs-target');
            if (target && target.charAt(0) === '#') fixWowInTab(target.slice(1));
            if (target === '#terco' && !_tercoLoaded) {
                _tercoLoaded = true;
                if (typeof window.carregarTerco === 'function') window.carregarTerco();
            }
        });
    });

    /* ── Init Santo do Dia e ativação via hash ─────────────────────────── */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initSantoTab(); activateSantoTabIfHash(); });
    } else {
        initSantoTab();
        activateSantoTabIfHash();
    }

    /* pjax:ready: só reinicia se ainda não foi iniciado nesta navegação.
     * Quando o PJAX re-executa este script, initSantoTab() já foi chamado
     * diretamente acima (readyState !== 'loading'), então _initDone === true
     * e este handler vira no-op. Em navegações futuras de volta a esta página,
     * um novo IIFE rodará e _initDone recomeça em false. */
    document.addEventListener('pjax:ready', function () {
        if (!_initDone) {
            initSantoTab();
            activateSantoTabIfHash();
        }
    });

}());
