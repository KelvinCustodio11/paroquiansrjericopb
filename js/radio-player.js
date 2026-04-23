/* ==========================================================================
 * Radio Player — Paróquia NSR Jericó/PB
 * Auto-injetável em qualquer página. Persiste estado via localStorage.
 *
 * Para ativar o stream da Rádio Itacambarí FM, edite ITACAMBARI_STREAM abaixo.
 * Exemplo: 'http://streaming.radio.com.br:8000/radio.mp3'
 * ========================================================================== */
(function () {
    'use strict';

    /* ── Configuração ─────────────────────────────────────────────────────── */
    var ITACAMBARI_STREAM = 'URL_DO_STREAM_AQUI'; // ← URL do stream da Rádio Itacambarí FM
    var ITACAMBARI_NAME   = 'Rádio Itacambarí FM — Jericó/PB';
    var STORAGE_KEY       = 'nsr_radio_state';

    /* ── Injeta HTML ──────────────────────────────────────────────────────── */
    var tpl = document.createElement('div');
    tpl.innerHTML =
        '<div class="fab-container" id="fabContainer">' +
            '<button class="fab-btn fab-radio" data-radio-trigger aria-label="Ouvir Rádio ao Vivo">' +
                '<i class="fa-solid fa-tower-broadcast"></i>' +
            '</button>' +
            '<button class="fab-btn fab-top" id="fabTopBtn" aria-label="Voltar ao topo">' +
                '<i class="fa-solid fa-arrow-up"></i>' +
            '</button>' +
        '</div>' +
        '<div class="radio-player-bar" id="radioPlayerBar" role="region" aria-label="Player de Rádio">' +
            '<div class="radio-station-panel" id="radioStationPanel" aria-label="Lista de rádios católicas">' +
                '<div class="radio-station-panel-header">' +
                    '<span>Rádios Católicas ao Vivo</span>' +
                    '<button class="radio-panel-close" id="radioPanelClose" aria-label="Fechar lista">' +
                        '<i class="fa-solid fa-chevron-down"></i>' +
                    '</button>' +
                '</div>' +
                '<div class="radio-station-list" id="radioStationList"></div>' +
            '</div>' +
            '<audio id="radioAudio" preload="none"></audio>' +
            '<div class="radio-player-content">' +
                '<div class="radio-player-info">' +
                    '<div class="radio-player-status-row">' +
                        '<div class="radio-live-badge" id="radioLiveBadge">' +
                            '<span class="radio-badge-dot"></span>' +
                            '<span id="radioBadgeText">AGUARDANDO</span>' +
                        '</div>' +
                        '<div class="radio-state-indicator">' +
                            '<div class="radio-bars" id="radioBars">' +
                                '<span></span><span></span><span></span><span></span>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="radio-station-name" id="radioStationName">' + ITACAMBARI_NAME + '</div>' +
                    '<div class="radio-subtitle" id="radioProgram">' +
                        'Transmissão da Santa Missa ' +
                        '<span class="radio-time"><i class="fa-regular fa-clock"></i> 8h00 às 9h30</span>' +
                    '</div>' +
                '</div>' +
                '<div class="radio-player-controls">' +
                    '<button class="radio-list-btn" id="radioListBtn" aria-label="Ver lista de rádios" title="Mais rádios católicas">' +
                        '<i class="fa-solid fa-list-ul"></i>' +
                    '</button>' +
                    '<button class="radio-play-btn" id="radioPlayBtn" aria-label="Reproduzir ou Pausar">' +
                        '<i class="fa-solid fa-play" id="radioPlayIcon"></i>' +
                    '</button>' +
                    '<div class="radio-volume-wrap">' +
                        '<i class="fa-solid fa-volume-high" id="radioVolumeIcon" title="Mutar/desmutar" role="button" tabindex="0"></i>' +
                        '<input type="range" id="radioVolume" min="0" max="1" step="0.05" value="1" aria-label="Volume">' +
                    '</div>' +
                '</div>' +
                '<button class="radio-close-btn" id="radioCloseBtn" aria-label="Fechar player">' +
                    '<i class="fa-solid fa-xmark"></i>' +
                '</button>' +
            '</div>' +
        '</div>';

    while (tpl.firstChild) {
        document.body.appendChild(tpl.firstChild);
    }

    /* ── Refs ─────────────────────────────────────────────────────────────── */
    var audio         = document.getElementById('radioAudio');
    var playBtn       = document.getElementById('radioPlayBtn');
    var playIcon      = document.getElementById('radioPlayIcon');
    var volSlider     = document.getElementById('radioVolume');
    var volIcon       = document.getElementById('radioVolumeIcon');
    var closeBtn      = document.getElementById('radioCloseBtn');
    var playerBar     = document.getElementById('radioPlayerBar');
    var stationNameEl = document.getElementById('radioStationName');
    var programEl     = document.getElementById('radioProgram');
    var badgeText     = document.getElementById('radioBadgeText');
    var liveBadge     = document.getElementById('radioLiveBadge');
    var listBtn       = document.getElementById('radioListBtn');
    var panel         = document.getElementById('radioStationPanel');
    var panelClose    = document.getElementById('radioPanelClose');
    var stationList   = document.getElementById('radioStationList');
    var radioBars     = document.getElementById('radioBars');
    var fabTop        = document.getElementById('fabTopBtn');

    /* ── State ────────────────────────────────────────────────────────────── */
    var isPlaying      = false;
    var isLoading      = false;
    var currentUrl     = ITACAMBARI_STREAM;
    var currentName    = ITACAMBARI_NAME;
    var stationsLoaded = false;

    /* ── Status visual (badge + indicador, NÃO toca no subtítulo) ────────── */
    function setStatus(type) {
        /* Texto + ícone do badge */
        if (type === 'playing') {
            badgeText.textContent = 'AO VIVO';
        } else if (type === 'idle') {
            badgeText.innerHTML = '<i class="fa-solid fa-pause" style="font-size:9px;opacity:.7"></i> PAUSADO';
        } else if (type === 'loading') {
            badgeText.textContent = 'AGUARDANDO';
        } else if (type === 'error') {
            badgeText.textContent = 'NÃO CONECTADO';
        }

        /* Cor/estado do badge */
        liveBadge.classList.toggle('badge-error',   type === 'error');
        liveBadge.classList.toggle('badge-idle',    type === 'idle');
        liveBadge.classList.toggle('badge-loading', type === 'loading');
        liveBadge.classList.toggle('badge-playing', type === 'playing');

        /* Barras animadas (visíveis só tocando) */
        radioBars.classList.toggle('playing', type === 'playing');

        /* Ícone do botão play */
        if (type === 'loading') {
            playIcon.className = 'fa-solid fa-circle-notch fa-spin';
        } else if (type === 'playing') {
            playIcon.className = 'fa-solid fa-pause';
        } else {
            playIcon.className = 'fa-solid fa-play';
        }
    }

    /* ── Programação da rádio selecionada (subtítulo fixo por rádio) ──────── */
    function setProgramInfo(station) {
        if (!station) { return; }
        var url = station.url_resolved || station.url || '';
        if (url === ITACAMBARI_STREAM) {
            programEl.innerHTML = 'Transmissão da Santa Missa ' +
                '<span class="radio-time"><i class="fa-regular fa-clock"></i> 8h00 às 9h30</span>';
        } else {
            var tags = station.tags
                ? station.tags.split(',').slice(0, 3).map(function (t) { return t.trim(); }).join(' · ')
                : '';
            programEl.textContent = tags || station.state || station.country || '';
        }
    }

    /* ── Volume ───────────────────────────────────────────────────────────── */
    function updateVolIcon(vol) {
        if (vol <= 0)       volIcon.className = 'fa-solid fa-volume-xmark';
        else if (vol < 0.5) volIcon.className = 'fa-solid fa-volume-low';
        else                volIcon.className = 'fa-solid fa-volume-high';
    }

    /* ── Play stream ──────────────────────────────────────────────────────── */
    function playStream(url, name, station) {
        currentUrl  = url;
        currentName = name;
        stationNameEl.textContent = name;
        if (station) { setProgramInfo(station); }
        setStatus('loading');
        audio.src = url;
        audio.play()
            .then(function () { /* playing event handles state */ })
            .catch(function () {
                isPlaying = false;
                isLoading = false;
                setStatus('error');
            });
        updateActiveItem(url);
    }

    function updateActiveItem(url) {
        document.querySelectorAll('.radio-station-item').forEach(function (el) {
            el.classList.toggle('active', el.dataset.url === url);
        });
    }

    /* ── Eventos do áudio ─────────────────────────────────────────────────── */
    audio.addEventListener('waiting', function () {
        isLoading = true;
        setStatus('loading');
    });

    audio.addEventListener('playing', function () {
        isPlaying = true;
        isLoading = false;
        setStatus('playing');
        saveState();
    });

    audio.addEventListener('pause', function () {
        isPlaying = false;
        isLoading = false;
        setStatus('idle');
        saveState();
    });

    audio.addEventListener('ended', function () {
        isPlaying = false;
        setStatus('idle');
        saveState();
    });

    audio.addEventListener('error', function () {
        isPlaying = false;
        isLoading = false;
        setStatus('error');
    });

    /* ── Botão play ───────────────────────────────────────────────────────── */
    playBtn.addEventListener('click', function () {
        if (isPlaying) {
            audio.pause();
        } else {
            if (!audio.src || audio.src === window.location.href) {
                audio.src = currentUrl;
            }
            setStatus('loading');
            audio.play().catch(function () { setStatus('error'); });
        }
    });

    /* ── Volume ───────────────────────────────────────────────────────────── */
    volSlider.addEventListener('input', function () {
        audio.volume = parseFloat(this.value);
        updateVolIcon(audio.volume);
        saveState();
    });

    volIcon.addEventListener('click', function () {
        audio.volume = audio.volume > 0 ? 0 : 1;
        volSlider.value = audio.volume;
        updateVolIcon(audio.volume);
        saveState();
    });

    volIcon.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { this.click(); }
    });

    /* ── Fechar player ────────────────────────────────────────────────────── */
    closeBtn.addEventListener('click', function () {
        audio.pause();
        audio.src = '';
        isPlaying = false;
        setStatus('idle');
        togglePanel(false);
        playerBar.classList.remove('active');
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
    });

    /* ── Triggers externos (delegado — funciona após PJAX também) ────────── */
    document.addEventListener('click', function (e) {
        if (!e.target.closest('[data-radio-trigger]')) { return; }
        e.preventDefault();
        playerBar.classList.add('active');
        if (!isPlaying && !isLoading) {
            var itacambari = { url_resolved: ITACAMBARI_STREAM, tags: '', state: 'Jericó/PB' };
            playStream(currentUrl, currentName, currentUrl === ITACAMBARI_STREAM ? itacambari : null);
        }
    });

    /* ── FAB topo ─────────────────────────────────────────────────────────── */
    window.addEventListener('scroll', function () {
        fabTop.classList.toggle('visible', window.scrollY > 300);
    }, { passive: true });

    fabTop.addEventListener('click', function () {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    /* ── Painel de rádios: domingo das 8h–9h30 ────────────────────────────── */
    function isItacambariLive() {
        var now      = new Date();
        var totalMin = now.getHours() * 60 + now.getMinutes();
        return now.getDay() === 0 && totalMin >= 480 && totalMin < 570;
    }

    /* ── Monta item da lista ──────────────────────────────────────────────── */
    function buildItem(station, isFeatured, badgeText) {
        var url = station.url_resolved || station.url || '';
        var btn = document.createElement('button');
        btn.className = 'radio-station-item' + (isFeatured ? ' featured' : '');
        btn.dataset.url = url;
        if (currentUrl === url) { btn.classList.add('active'); }

        /* Favicon — usando DOM para evitar XSS */
        if (station.favicon) {
            var img = document.createElement('img');
            img.className = 'radio-station-favicon';
            img.alt       = '';
            img.src       = station.favicon;
            img.addEventListener('error', function () { this.style.display = 'none'; });
            btn.appendChild(img);
        } else {
            var ph = document.createElement('div');
            ph.className = 'radio-station-favicon';
            btn.appendChild(ph);
        }

        var infoDiv = document.createElement('div');
        infoDiv.className = 'radio-station-item-info';

        var nameDiv = document.createElement('div');
        nameDiv.className   = 'radio-station-item-name';
        nameDiv.textContent = station.name;

        var metaDiv = document.createElement('div');
        metaDiv.className = 'radio-station-item-meta';
        var tags = station.tags
            ? station.tags.split(',').slice(0, 2).map(function (t) { return t.trim(); }).join(' · ')
            : '';
        metaDiv.textContent = tags || station.state || station.country || '';

        infoDiv.appendChild(nameDiv);
        infoDiv.appendChild(metaDiv);
        btn.appendChild(infoDiv);

        if (badgeText) {
            var badge = document.createElement('span');
            badge.className   = 'radio-station-badge ' + (isItacambariLive() ? 'badge-live' : 'badge-featured');
            badge.textContent = badgeText;
            btn.appendChild(badge);
        }

        btn.addEventListener('click', function () {
            playStream(url, station.name, station);
            /* Lista permanece aberta (item 5) */
        });

        return btn;
    }

    /* ── Carrega rádios via Radio Browser API ─────────────────────────────── */
    function loadStations() {
        if (stationsLoaded) { return; }
        stationsLoaded = true;

        stationList.innerHTML = '';
        var loadingEl = document.createElement('div');
        loadingEl.className = 'radio-station-loading';
        loadingEl.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Buscando rádios...';
        stationList.appendChild(loadingEl);

        fetch('https://de1.api.radio-browser.info/json/stations/search?tag=catholic&countrycode=BR&limit=30&order=votes&reverse=true&hidebroken=true')
            .then(function (res) { return res.json(); })
            .then(function (stations) {
                stationList.innerHTML = '';
                var featured = { name: ITACAMBARI_NAME, url_resolved: ITACAMBARI_STREAM, favicon: '', tags: '', state: 'Jericó/PB' };
                stationList.appendChild(buildItem(featured, true, isItacambariLive() ? 'AO VIVO' : 'Paróquial'));

                if (stations && stations.length) {
                    stations.forEach(function (s) { stationList.appendChild(buildItem(s, false, null)); });
                } else {
                    var emptyEl = document.createElement('div');
                    emptyEl.className   = 'radio-station-loading';
                    emptyEl.textContent = 'Nenhuma outra rádio encontrada.';
                    stationList.appendChild(emptyEl);
                }
            })
            .catch(function () {
                stationList.innerHTML = '';
                var featured = { name: ITACAMBARI_NAME, url_resolved: ITACAMBARI_STREAM, favicon: '', tags: '', state: 'Jericó/PB' };
                stationList.appendChild(buildItem(featured, true, isItacambariLive() ? 'AO VIVO' : 'Paróquial'));
                var errEl = document.createElement('div');
                errEl.className   = 'radio-station-loading';
                errEl.textContent = 'Não foi possível carregar outras rádios.';
                stationList.appendChild(errEl);
            });
    }

    /* ── Painel toggle com animação ───────────────────────────────────────── */
    function togglePanel(forceOpen) {
        var open = forceOpen !== undefined ? forceOpen : !panel.classList.contains('open');
        panel.classList.toggle('open', open);
        listBtn.classList.toggle('active', open);
        if (open) { loadStations(); }
    }

    listBtn.addEventListener('click', function () { togglePanel(); });
    panelClose.addEventListener('click', function () { togglePanel(false); });

    /* ── Persistência de estado ───────────────────────────────────────────── */
    function saveState() {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({
                url:     currentUrl,
                name:    currentName,
                volume:  audio.volume,
                playing: isPlaying,
                visible: playerBar.classList.contains('active')
            }));
        } catch (e) {}
    }

    function restoreState() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) { return; }
            var state = JSON.parse(raw);
            if (!state) { return; }

            currentUrl  = state.url  || ITACAMBARI_STREAM;
            currentName = state.name || ITACAMBARI_NAME;
            stationNameEl.textContent = currentName;

            if (typeof state.volume === 'number') {
                audio.volume    = state.volume;
                volSlider.value = state.volume;
                updateVolIcon(state.volume);
            }

            if (state.visible || state.playing) {
                playerBar.classList.add('active');
            }

            if (state.playing) {
                /* Pequeno delay para a página estabilizar */
                setTimeout(function () { playStream(currentUrl, currentName); }, 400);
            } else if (state.visible) {
                setStatus('idle');
            }
        } catch (e) {}
    }

    restoreState();

    /* ── PJAX: navegação sem recarregar (mantém rádio tocando) ──────────── */

    /* Overlay para fade entre páginas (fica abaixo do player) */
    var pjaxOverlay = document.createElement('div');
    pjaxOverlay.style.cssText = 'position:fixed;inset:0;z-index:99998;background:#000;' +
        'opacity:0;pointer-events:none;transition:opacity .18s ease';
    document.body.appendChild(pjaxOverlay);

    function pjaxNavigate(url) {
        /* Barra de progresso fina no topo */
        var prog = document.createElement('div');
        prog.style.cssText = 'position:fixed;top:0;left:0;height:3px;width:0;z-index:999999;' +
            'background:var(--accent-color);transition:width .5s ease,opacity .3s ease;pointer-events:none';
        document.body.appendChild(prog);
        requestAnimationFrame(function () { prog.style.width = '60%'; });

        /* Fade-out suave — player fica visível por cima */
        pjaxOverlay.style.pointerEvents = 'all';
        pjaxOverlay.style.opacity = '0.5';

        fetch(url)
            .then(function (r) { return r.text(); })
            .then(function (html) {
                var parser = new DOMParser();
                var newDoc = parser.parseFromString(html, 'text/html');

                document.title = newDoc.title;

                /* Salva player e overlay antes de trocar o body */
                var fabEl  = document.getElementById('fabContainer');
                var barEl  = document.getElementById('radioPlayerBar');
                var ovEl   = pjaxOverlay;
                fabEl.parentNode.removeChild(fabEl);
                barEl.parentNode.removeChild(barEl);
                ovEl.parentNode.removeChild(ovEl);

                /* Mata ScrollTrigger para evitar vazamento */
                if (window.ScrollTrigger) {
                    window.ScrollTrigger.getAll().forEach(function (t) { t.kill(); });
                }

                /* Troca conteúdo */
                document.body.innerHTML = newDoc.body.innerHTML;

                var pre = document.querySelector('.preloader');
                if (pre) { pre.style.display = 'none'; }

                /* Reanexa */
                document.body.appendChild(ovEl);
                document.body.appendChild(fabEl);
                document.body.appendChild(barEl);

                history.pushState({ pjax: url }, document.title, url);
                window.scrollTo(0, 0);

                /* Fade-in */
                requestAnimationFrame(function () {
                    ovEl.style.opacity = '0';
                    ovEl.style.pointerEvents = 'none';
                });

                /* Completa barra */
                prog.style.width = '100%';
                setTimeout(function () {
                    prog.style.opacity = '0';
                    setTimeout(function () { if (prog.parentNode) prog.parentNode.removeChild(prog); }, 300);
                }, 200);

                /* Re-executa function.js */
                var s = document.createElement('script');
                s.src = 'js/function.js?' + Date.now();
                document.body.appendChild(s);
            })
            .catch(function () {
                pjaxOverlay.style.opacity = '0';
                pjaxOverlay.style.pointerEvents = 'none';
                window.location.href = url;
            });
    }

    /* Intercepta cliques em links internos */
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a');
        if (!link) { return; }
        /* Ignora links especiais */
        if (link.target === '_blank' || link.download || e.ctrlKey || e.metaKey || e.shiftKey) { return; }
        var href = link.getAttribute('href');
        if (!href || href.charAt(0) === '#' || /^(mailto|tel|javascript):/.test(href)) { return; }
        try {
            var dest = new URL(href, window.location.href);
            if (dest.origin !== window.location.origin) { return; }
            /* Mesma página, apenas âncora */
            if (dest.pathname === window.location.pathname && dest.hash) { return; }
            /* Só páginas .html ou raiz */
            if (!dest.pathname.endsWith('.html') && dest.pathname.slice(-1) !== '/') { return; }
            /* Não recarrega a mesma página */
            if (dest.href === window.location.href) { e.preventDefault(); return; }
            e.preventDefault();
            pjaxNavigate(dest.href);
        } catch (err) { /* deixa navegar normalmente */ }
    });

    /* Botão voltar/avançar do browser */
    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.pjax) {
            pjaxNavigate(e.state.pjax);
        } else {
            pjaxNavigate(window.location.href);
        }
    });

    /* Marca estado inicial para o popstate funcionar corretamente */
    history.replaceState({ pjax: window.location.href }, document.title, window.location.href);

}());
