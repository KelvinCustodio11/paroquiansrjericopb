/**
 * Santo do Dia — carrega o santo/beato do dia a partir do Calendário Litúrgico
 * via Wikipedia PT (artigo completo com seções biográficas).
 *
 * Expõe window.SantoDia.fetchForDate(dateObj, onSuccess, onError)
 *
 * Dados retornados: { nome, descricao, imagem, imagemGrande, resumo, secoes[] }
 * secoes[]: Array de { title, paras[] } — conteúdo estruturado do artigo
 *
 * Cache: localStorage com chave "santoDia_YYYY-M-D"
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'santoDia_';
    var MESES = [
        'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho',
        'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'
    ];

    /* ── Seções do artigo que queremos exibir ───────────────────── */
    var WANTED_SECTIONS = [
        'biografia', 'vida', 'vida e obra', 'história', 'infância', 'infancia',
        'juventude', 'espiritualidade', 'espiritualidade e mística', 'missão',
        'apostolado', 'reforma da igreja', 'virtudes', 'martírio', 'martirio',
        'morte', 'canonização', 'canonizacao', 'veneração', 'veneracao',
        'padroeira', 'padroeiro', 'patronagem', 'obras', 'escritos',
        'legado', 'herança espiritual', 'milagres', 'beatificação', 'beatificacao',
        'doutora da igreja', 'doutor da igreja', 'iconografia', 'relíquias',
        'reliquias', 'culto', 'tentações', 'tentacoes', 'dom das lágrimas',
        'ordem dominicana', 'fontes para vida', 'reforma'
    ];
    /* Seções que ignoramos */
    var SKIP_SECTIONS = [
        'ver também', 'ver tambem', 'notas', 'referências', 'referencias',
        'bibliografia', 'fontes', 'ligações externas', 'ligacoes externas',
        'leitura adicional'
    ];

    /* ── Filtro de descrições inválidas ─────────────────────────── */
    var SKIP_DESC = [
        'imagem de', 'imagem da', 'imagem mariana', 'imagem de maria',
        'é uma imagem', 'ícone de', 'icone de',
        'ícone mariano', 'icone mariano', 'santuário', 'santuario',
        'localidade', 'município', 'municipio', 'cidade de', 'vila de',
        'estátua de', 'estatua de', 'pintura de', 'monumento', 'obra de arte',
        'paróquia', 'paroquia', 'diocese', 'bairro', 'distrito',
        'virgem negra', 'mosteiro de', 'basílica de', 'basilica de',
        'escultura de', 'representação de', 'representacao de'
    ];

    /* ── Termos que confirmam um santo/beato católico ────────────── */
    var SAINT_POSITIVE = [
        'santo ', 'santa ', 'santos ', 'santas ',
        'beato ', 'beata ', 'beatos ', 'beatas ',
        'mártir', 'martir', 'mártires', 'martires',
        'sacerdote', 'presbítero', 'presbitero',
        'bispo católico', 'arcebispo', 'cardeal',
        'papa ', 'pontífice', 'pontifice',
        'monge ', 'monja ', 'freira ', 'religiosa ', 'religioso ',
        'virgem consagrada', 'virgem e mártir',
        'confessor ', 'diácono ', 'diacono ',
        'apóstolo', 'apostolo', 'evangelista',
        'abade ', 'abadessa ',
        'canonizado', 'canonizada', 'beatificado', 'beatificada',
        'padroeiro', 'padroeira',
        'missionário', 'missionaria',
        'fundador da ordem', 'fundadora da ordem',
        'doutor da igreja', 'doutora da igreja',
        'ordem religiosa', 'ordem franciscana', 'ordem dominicana',
        'ordem beneditina', 'ordem agostiniana', 'ordem carmelita',
        'companhia de jesus', 'jesuíta', 'jesuita',
        'calendário romano', 'festejo litúrgico', 'festa litúrgica'
    ];

    /* ── Cache ──────────────────────────────────────────────────── */

    function cacheKey(d) {
        return STORAGE_KEY + d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate();
    }

    function getFromCache(d) {
        try {
            var raw = localStorage.getItem(cacheKey(d));
            if (!raw) return null;
            var parsed = JSON.parse(raw);
            return (parsed && parsed.secoes) ? parsed : null;
        } catch (e) { return null; }
    }

    function saveToCache(d, data) {
        try { localStorage.setItem(cacheKey(d), JSON.stringify(data)); } catch (e) {}
    }

    /* ── Utilitários ────────────────────────────────────────────── */

    function fetchJson(url, timeoutMs) {
        var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var opts = controller ? { signal: controller.signal } : {};
        var timer = controller
            ? setTimeout(function () { controller.abort(); }, timeoutMs || 12000)
            : null;
        return fetch(url, opts).then(function (r) {
            if (timer) clearTimeout(timer);
            if (!r.ok) throw new Error('http_' + r.status);
            return r.json();
        }).catch(function (err) {
            if (timer) clearTimeout(timer);
            throw err;
        });
    }

    function datePageTitle(d) {
        return d.getDate() + '_de_' + MESES[d.getMonth()];
    }

    function isValidSaint(summary) {
        if (!summary || summary.type === 'disambiguation') return false;
        // Verifica tanto description quanto os primeiros 300 chars do extract
        var desc    = (summary.description || '').toLowerCase();
        var extract = (summary.extract || '').substring(0, 300).toLowerCase();
        var combined = desc + ' ' + extract;
        for (var i = 0; i < SKIP_DESC.length; i++) {
            if (combined.indexOf(SKIP_DESC[i]) >= 0) return false;
        }
        return true;
    }

    /* Filtro mais estrito para busca textual: exige ao menos 1 termo positivo */
    function isValidSaintSearch(summary) {
        if (!isValidSaint(summary)) return false;
        var desc    = (summary.description || '').toLowerCase();
        var extract = (summary.extract || '').substring(0, 500).toLowerCase();
        var combined = desc + ' ' + extract;
        for (var i = 0; i < SAINT_POSITIVE.length; i++) {
            if (combined.indexOf(SAINT_POSITIVE[i]) >= 0) return true;
        }
        return false;
    }

    /* ── Limpa HTML da Wikipedia ────────────────────────────────── */

    function cleanWikiHtml(html) {
        var tmp = document.createElement('div');
        tmp.innerHTML = html;

        // Remove elementos desnecessários
        var toRemove = tmp.querySelectorAll(
            'sup, .mw-editsection, .reference, .references, ' +
            '.reflist, .navbox, .infobox, .sistersitebox, ' +
            '.hatnote, .ambox, .tmbox, .ombox, .fmbox, ' +
            'table, .thumb, .gallery, .noprint, style, script, [role="note"]'
        );
        toRemove.forEach(function (el) { if (el.parentNode) el.parentNode.removeChild(el); });

        // Converte todos os links em texto simples (sem links externos)
        var links = tmp.querySelectorAll('a');
        links.forEach(function (a) {
            var span = document.createElement('span');
            span.textContent = a.textContent;
            if (a.parentNode) a.parentNode.replaceChild(span, a);
        });

        return tmp;
    }

    function extractParagraphs(el) {
        var paras = [];
        el.querySelectorAll('p, li').forEach(function (p) {
            var text = p.textContent.trim()
                .replace(/\[\d+\]/g, '')    // remove [1], [2] etc.
                .replace(/\s+/g, ' ')
                .trim();
            if (text.length > 40) paras.push(text);
        });
        return paras;
    }

    /* ── Busca artigo completo com seções biográficas ───────────── */

    function fetchFullArticle(pageTitle, onSuccess, onError) {
        var wikiApi = 'https://pt.wikipedia.org/w/api.php';
        var encoded = encodeURIComponent(pageTitle.replace(/ /g, '_'));

        fetchJson(
            wikiApi + '?action=parse&page=' + encoded +
            '&prop=sections&format=json&origin=*',
            12000
        ).then(function (data) {
            var sections = (data.parse && data.parse.sections) || [];
            var wanted = [];

            sections.forEach(function (sec) {
                var plain = (sec.line || '').replace(/<[^>]+>/g, '').toLowerCase().trim();
                if (SKIP_SECTIONS.indexOf(plain) >= 0) return;
                for (var i = 0; i < WANTED_SECTIONS.length; i++) {
                    if (plain.indexOf(WANTED_SECTIONS[i]) >= 0 || WANTED_SECTIONS[i].indexOf(plain) >= 0) {
                        wanted.push({
                            index: sec.index,
                            title: sec.line.replace(/<[^>]+>/g, ''),
                            level: sec.level
                        });
                        return;
                    }
                }
            });

            // Sempre inclui introdução (seção 0)
            var allSecs = [{ index: '0', title: '', level: '2' }].concat(wanted.slice(0, 8));

            var promises = allSecs.map(function (sec) {
                return fetchJson(
                    wikiApi + '?action=parse&page=' + encoded +
                    '&prop=text&section=' + sec.index +
                    '&format=json&origin=*',
                    12000
                ).then(function (d) {
                    var html = (d.parse && d.parse.text && d.parse.text['*']) || '';
                    var cleaned = cleanWikiHtml(html);
                    var paras = extractParagraphs(cleaned);
                    return paras.length ? { title: sec.title, level: sec.level, paras: paras } : null;
                }).catch(function () { return null; });
            });

            return Promise.all(promises);
        }).then(function (results) {
            var secoes = results.filter(function (r) { return r !== null; });
            if (!secoes.length) throw new Error('no_content');
            onSuccess(secoes);
        }).catch(function () { onError(); });
    }

    /* ── Constrói resultado a partir de um summary Wikipedia ────── */

    function buildSaintResult(summary, d, onSuccess, onError) {
        var pageTitle    = summary.title || summary.displaytitle || '';
        var imagem       = (summary.thumbnail && summary.thumbnail.source) || '';
        var imagemGrande = (summary.originalimage && summary.originalimage.source) || imagem;
        var descricao    = summary.description || '';
        var introText    = summary.extract || '';

        fetchFullArticle(pageTitle, function (secoes) {
            var resumo = introText;
            if (!resumo && secoes.length > 0 && secoes[0].paras.length > 0) {
                resumo = secoes[0].paras[0];
            }
            if (resumo.length > 320) resumo = resumo.substring(0, 317) + '…';
            var result = {
                nome: pageTitle,
                descricao: descricao,
                imagem: imagem,
                imagemGrande: imagemGrande,
                resumo: resumo,
                secoes: secoes
            };
            saveToCache(d, result);
            onSuccess(result);
        }, function () {
            // Fallback sem seções detalhadas
            var result = {
                nome: pageTitle,
                descricao: descricao,
                imagem: imagem,
                imagemGrande: imagemGrande,
                resumo: introText.substring(0, 320),
                secoes: introText
                    ? [{ title: '', level: '2', paras: introText.split('\n').filter(function (s) { return s.trim().length > 10; }) }]
                    : []
            };
            saveToCache(d, result);
            onSuccess(result);
        });
    }

    /* ── Tenta um nome pelo REST summary; usa search se ambíguo ─── */

    function trySaintByName(name, onFound, onFail) {
        fetch('https://pt.wikipedia.org/api/rest_v1/page/summary/' +
            encodeURIComponent(name.replace(/ /g, '_')))
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (summary) {
            if (summary && summary.type === 'disambiguation') {
                // Tenta pesquisa para desambiguar
                trySaintBySearch(name, onFound, onFail);
                return;
            }
            if (isValidSaint(summary)) { onFound(summary); } else { onFail(); }
        })
        .catch(function () { onFail(); });
    }

    /* ── Pesquisa Wikipedia quando o título é ambíguo ───────────── */

    function trySaintBySearch(name, onFound, onFail) {
        fetchJson(
            'https://pt.wikipedia.org/w/api.php?action=query&list=search' +
            '&srsearch=' + encodeURIComponent(name) +
            '&srlimit=5&format=json&origin=*',
            10000
        ).then(function (data) {
            var hits = (data.query && data.query.search) || [];
            var found = false;
            var pending = hits.length;
            if (!pending) { onFail(); return; }

            // Tenta o primeiro resultado que seja artigo válido
            function tryNext(idx) {
                if (idx >= hits.length) { onFail(); return; }
                var hit = hits[idx];
                fetch('https://pt.wikipedia.org/api/rest_v1/page/summary/' +
                    encodeURIComponent(hit.title.replace(/ /g, '_')))
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (s) {
                    if (!found && isValidSaint(s)) {
                        found = true;
                        onFound(s);
                    } else if (!found) {
                        tryNext(idx + 1);
                    }
                })
                .catch(function () { tryNext(idx + 1); });
            }
            tryNext(0);
        }).catch(function () { onFail(); });
    }

    /* ── Tenta lista de nomes do CRG em sequência ────────────────── */

    function tryCRGSaints(names, idx, d, onSuccess, onError) {
        if (idx >= names.length) {
            // Todos os nomes do CRG falharam → fallback pela página da data
            fetchFromWikiDatePage(d, onSuccess, onError);
            return;
        }
        trySaintByName(names[idx], function (summary) {
            buildSaintResult(summary, d, onSuccess, onError);
        }, function () {
            tryCRGSaints(names, idx + 1, d, onSuccess, onError);
        });
    }

    /* ── Busca pela página da data na Wikipedia (fallback) ───────── */

    function fetchFromWikiDatePage(d, onSuccess, onError) {
        var pageName = datePageTitle(d);
        var wikiApi  = 'https://pt.wikipedia.org/w/api.php';

        fetchJson(
            wikiApi + '?action=parse&page=' + encodeURIComponent(pageName) +
            '&prop=sections&format=json&origin=*',
            12000
        )
        .then(function (data) {
            var sections   = (data.parse && data.parse.sections) || [];
            var sectionIdx = null;

            for (var i = 0; i < sections.length; i++) {
                var plain = (sections[i].line || '').replace(/<[^>]+>/g, '').toLowerCase();
                if (plain.indexOf('santo') >= 0 || plain.indexOf('beato') >= 0 ||
                    plain.indexOf('cristian') >= 0 || plain.indexOf('religios') >= 0) {
                    sectionIdx = sections[i].index;
                    break;
                }
            }

            if (sectionIdx === null) throw new Error('no_section');

            return fetchJson(
                wikiApi + '?action=parse&page=' + encodeURIComponent(pageName) +
                '&prop=text&section=' + sectionIdx +
                '&format=json&origin=*',
                12000
            );
        })
        .then(function (data) {
            var html = (data.parse && data.parse.text && data.parse.text['*']) || '';
            var tmp  = document.createElement('div');
            tmp.innerHTML = html;

            var saints = [];
            var items  = tmp.querySelectorAll('li');
            for (var i = 0; i < items.length; i++) {
                var a = items[i].querySelector('a[title]') || items[i].querySelector('a');
                if (a) {
                    var title = (a.getAttribute('title') || a.textContent || '').trim();
                    if (title && title.indexOf(':') < 0 && title.length > 2) saints.push(title);
                }
            }

            if (!saints.length) throw new Error('no_saints');

            // Reutiliza tryCRGSaints sem índice de calendário (lista vinda da Wikipedia)
            var idx = 0;
            function tryNext() {
                if (idx >= Math.min(saints.length, 8)) { onError(); return; }
                trySaintByName(saints[idx++], function (summary) {
                    buildSaintResult(summary, d, onSuccess, onError);
                }, tryNext);
            }
            tryNext();
        })
        .catch(function () { onError(); });
    }

    /* ── Evangelizo API (fonte principal) ───────────────────────── */

    function fetchFromEvangelizo(d, onSuccess, onError) {
        var yyyy = d.getFullYear();
        var mm   = String(d.getMonth() + 1).padStart(2, '0');
        var dd   = String(d.getDate()).padStart(2, '0');
        var url  = 'https://publication.evangelizo.ws/PT/days/' + yyyy + '-' + mm + '-' + dd + '/saints';

        var evController = typeof AbortController !== 'undefined' ? new AbortController() : null;
        var evTimer = evController
            ? setTimeout(function () { evController.abort(); }, 8000)
            : null;

        fetch(url, evController ? { signal: evController.signal } : {})
            .then(function (r) {
                if (evTimer) clearTimeout(evTimer);
                return r.ok ? r.json() : Promise.reject('http');
            })
            .catch(function (err) {
                if (evTimer) clearTimeout(evTimer);
                throw err;
            })
            .then(function (json) {
                var saints = (json && json.data) || [];
                if (!saints.length) { onError(); return; }

                /* Prioriza 1º santo com order1=1 e bio; depois qualquer um com bio */
                var main = null;
                for (var i = 0; i < saints.length; i++) {
                    if (saints[i].order1 === 1 && saints[i].bio) { main = saints[i]; break; }
                }
                if (!main) {
                    for (var j = 0; j < saints.length; j++) {
                        if (saints[j].bio) { main = saints[j]; break; }
                    }
                }
                if (!main) main = saints[0];

                var imgLinks = main.image_links || {};
                var imagem   = imgLinks.large || imgLinks.face || imgLinks.ico || '';

                /* Limpa HTML da bio (remove inline styles, links, hr) */
                var bioHtml = '';
                if (main.bio) {
                    var cleaned = cleanWikiHtml(main.bio);
                    cleaned.querySelectorAll('hr').forEach(function (el) {
                        if (el.parentNode) el.parentNode.removeChild(el);
                    });
                    bioHtml = cleaned.innerHTML.trim();
                }

                /* Tenta enriquecer com seções da Wikipedia (melhor esforço) */
                var wikiName = (main.name || '')
                    .replace(/^(S\.\s*|São\s+|Santa\s+|Sto\.\s*|Sta\.\s*|Beato\s+|Beata\s+|B\.\s*)/i, '')
                    .trim();

                function buildResult(secoes) {
                    return {
                        nome:         main.name || '',
                        descricao:    main.short_description || '',
                        imagem:       imagem,
                        imagemGrande: imagem,
                        resumo:       '',
                        bioHtml:      bioHtml,
                        secoes:       secoes
                    };
                }

                fetchFullArticle(wikiName, function (secoes) {
                    var r = buildResult(secoes);
                    saveToCache(d, r);
                    onSuccess(r);
                }, function () {
                    var r = buildResult([]);
                    saveToCache(d, r);
                    onSuccess(r);
                });
            })
            .catch(function () { onError(); });
    }

    /* ── Fetch principal ────────────────────────────────────────── */

    /**
     * Busca o santo do dia para a data fornecida.
     * 1. Tenta Evangelizo.org (fonte católica oficial, conteúdo em PT)
     * 2. Fallback: Calendário Romano Geral + Wikipedia
     * 3. Fallback final: página da data na Wikipedia
     *
     * @param {Date}     dateObj   - Data desejada
     * @param {Function} onSuccess - Chamado com { nome, descricao, imagem, imagemGrande, resumo, bioHtml, secoes }
     * @param {Function} onError   - Chamado em caso de falha total
     */
    function fetchForDate(dateObj, onSuccess, onError) {
        var d = dateObj || new Date();
        var cached = getFromCache(d);
        if (cached) { onSuccess(cached); return; }

        /* 1. Tenta Evangelizo */
        fetchFromEvangelizo(d, onSuccess, function () {
            /* 2. Fallback: Calendário Romano Geral + Wikipedia */
            var calKey   = (d.getMonth() + 1) + '-' + d.getDate();
            var calEntry = window.CalendarioRomano && window.CalendarioRomano[calKey];
            if (calEntry) {
                var names = Array.isArray(calEntry) ? calEntry : [calEntry];
                tryCRGSaints(names, 0, d, onSuccess, onError);
                return;
            }

            /* 3. Fallback final: página da data na Wikipedia */
            fetchFromWikiDatePage(d, onSuccess, onError);
        });
    }

    /* ── Banner .verse-church (index.html) ──────────────────────── */

    function escHtml(s) {
        return (s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function render(data, container) {
        var h3   = container.querySelector('.section-title h3');
        var h2   = container.querySelector('.section-title h2');
        var p    = container.querySelector('.section-title p');
        var btnA = container.querySelector('.verse-church-btn a');

        if (h3) {
            h3.textContent = 'santo do dia';
            /* Garante visibilidade: WOW.js pode ter ocultado este elemento */
            h3.style.visibility = 'visible';
            h3.style.opacity    = '1';
        }

        if (h2) {
            var spanHtml = data.descricao
                ? ' <span>' + escHtml(data.descricao) + '</span>'
                : '';
            /* Substituir innerHTML destrói os chars do GSAP SplitText (que
             * estavam com autoAlpha:0). O novo texto não tem estilos inline
             * de GSAP, mas forçamos visibilidade por segurança. */
            h2.innerHTML = escHtml(data.nome) + spanHtml;
            h2.style.visibility = 'visible';
            h2.style.opacity    = '1';
        }

        if (p) {
            p.textContent = data.resumo || '';
            /* WOW.js pode ter ocultado este parágrafo */
            p.style.visibility = 'visible';
            p.style.opacity    = '1';
        }

        /* Garante que o container do botão (wow fadeInUp) fique visível */
        var btnWrap = container.querySelector('.verse-church-btn');
        if (btnWrap) {
            btnWrap.style.visibility = 'visible';
            btnWrap.style.opacity    = '1';
        }

        if (btnA) {
            var newBtn = document.createElement('a');
            newBtn.className   = btnA.className;
            newBtn.href        = 'agenda-liturgica.html#santo-dia';
            newBtn.textContent = 'Ver o santo do dia';
            btnA.parentNode.replaceChild(newBtn, btnA);
        }

        var section = container.closest('.verse-church');
        if (section && data.imagem) {
            section.style.backgroundImage = "url('" + data.imagem.replace(/'/g, "\\'") + "')";
        }
    }

    function initBanner() {
        var container = document.querySelector('.verse-church .verse-church-content');
        if (!container) return;
        fetchForDate(new Date(),
            function (data) { render(data, container); },
            function () { /* falha silenciosa */ }
        );
    }

    /* ── Inicialização ──────────────────────────────────────────── */

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBanner);
    } else {
        initBanner();
    }

    document.addEventListener('pjax:ready', initBanner);
    window.addEventListener('pageshow', function (e) { if (e.persisted) initBanner(); });

    /* ── Busca de santos por nome / vocação ─────────────────────── */

    /**
     * Pesquisa santos pelo nome ou vocação litúrgica.
     * Consulta a Wikipedia PT e filtra resultados válidos.
     *
     * @param {string}   query     - Texto livre (nome, vocação, título)
     * @param {Function} onResults - Chamado com array de { title, nome, descricao, imagem }
     * @param {Function} onError   - Chamado quando não há resultados
     */
    function searchSaints(query, onResults, onError) {
        var q = (query || '').trim();
        if (!q) { onError(); return; }

        /* Adiciona contexto católico à busca para reduzir ruído */
        var biasedQ = q + ' santo católico';

        fetchJson(
            'https://pt.wikipedia.org/w/api.php?action=query&list=search' +
            '&srsearch=' + encodeURIComponent(biasedQ) +
            '&srnamespace=0&srlimit=10&format=json&origin=*',
            10000
        ).then(function (data) {
            var hits = (data.query && data.query.search) || [];
            if (!hits.length) { onError(); return; }

            /* Preserva a ordem de relevância da busca usando slots indexados */
            var slots     = new Array(hits.length).fill(null);
            var remaining = hits.length;

            function checkDone() {
                remaining--;
                if (remaining === 0) {
                    var results = slots.filter(function (r) { return r !== null; });
                    if (results.length) onResults(results);
                    else onError();
                }
            }

            hits.forEach(function (hit, idx) {
                fetch('https://pt.wikipedia.org/api/rest_v1/page/summary/' +
                    encodeURIComponent(hit.title.replace(/ /g, '_')))
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (s) {
                    if (isValidSaintSearch(s)) {
                        slots[idx] = {
                            title:     s.title,
                            nome:      s.title,
                            descricao: s.description || '',
                            imagem:    (s.thumbnail && s.thumbnail.source) || ''
                        };
                    }
                    checkDone();
                })
                .catch(function () { checkDone(); });
            });
        }).catch(function () { onError(); });
    }

    /**
     * Carrega dados completos de um santo a partir do título Wikipedia.
     * Usa cache localStorage keyed pelo título.
     *
     * @param {string}   wikiTitle - Título exato do artigo na Wikipedia PT
     * @param {Function} onSuccess - Chamado com { nome, descricao, imagem, imagemGrande, resumo, secoes }
     * @param {Function} onError   - Chamado em caso de falha
     */
    function fetchForTitle(wikiTitle, onSuccess, onError) {
        var cacheK = STORAGE_KEY + 'title_' + wikiTitle;
        try {
            var raw = localStorage.getItem(cacheK);
            if (raw) {
                var parsed = JSON.parse(raw);
                if (parsed && parsed.secoes) { onSuccess(parsed); return; }
            }
        } catch (e) {}

        fetch('https://pt.wikipedia.org/api/rest_v1/page/summary/' +
            encodeURIComponent(wikiTitle.replace(/ /g, '_')))
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (summary) {
            if (!summary) { onError(); return; }

            var imagem       = (summary.thumbnail && summary.thumbnail.source) || '';
            var imagemGrande = (summary.originalimage && summary.originalimage.source) || imagem;
            var resumo       = summary.extract || '';
            if (resumo.length > 320) resumo = resumo.substring(0, 317) + '…';

            function persist(secoes) {
                var result = {
                    nome:         summary.title,
                    descricao:    summary.description || '',
                    imagem:       imagem,
                    imagemGrande: imagemGrande,
                    resumo:       resumo,
                    secoes:       secoes
                };
                try { localStorage.setItem(cacheK, JSON.stringify(result)); } catch (e) {}
                onSuccess(result);
            }

            fetchFullArticle(wikiTitle, persist, function () {
                persist(summary.extract
                    ? [{ title: '', level: '2', paras: summary.extract.split('\n').filter(function (s) { return s.trim().length > 10; }) }]
                    : []);
            });
        })
        .catch(function () { onError(); });
    }

    /* ── API pública ────────────────────────────────────────────── */
    window.SantoDia = {
        fetchForDate:  fetchForDate,
        searchSaints:  searchSaints,
        fetchForTitle: fetchForTitle
    };

}());
