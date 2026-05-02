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
    var ITACAMBARI_STREAM = 'https://stm10.srvvox.com.br:7218/stream'; // Rádio Itacambarí FM — Jericó/PB
    var ITACAMBARI_NAME   = 'Rádio Itacambá FM — Jericó/PB';
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
                        '<div class="radio-vol-popup" id="radioVolPopup">' +
                            '<input type="range" id="radioVolPopupSlider" min="0" max="1" step="0.05" value="1" aria-label="Volume" orient="vertical">' +
                        '</div>' +
                        '<i class="fa-solid fa-volume-high" id="radioVolumeIcon" title="Volume" role="button" tabindex="0"></i>' +
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
    var volPopup      = document.getElementById('radioVolPopup');
    var volPopupSlider = document.getElementById('radioVolPopupSlider');
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

    /* ── Busca programação ao vivo via URL de API ─────────────────────────── */
    function fetchProgramacaoLive(url, callback) {
        fetch(url)
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (data) {
                if (data && data.programa) { callback(data.programa); }
            })
            .catch(function () { /* silencioso — não quebra o player */ });
    }

    /* ── Programação da rádio selecionada (subtítulo fixo por rádio) ──────── */
    function setProgramInfo(station) {
        if (!station) { return; }
        var url     = station.url_resolved || station.url || '';
        var progUrl = station.programacao_url || '';
        var prog    = station.programacao || '';

        if (url === ITACAMBARI_STREAM) {
            programEl.innerHTML = 'Transmissão da Santa Missa ' +
                '<span class="radio-time"><i class="fa-regular fa-clock"></i> 8h00 às 9h30</span>';
        } else if (prog || progUrl) {
            /* Exibe estático imediatamente; atualiza se tiver URL ao vivo */
            programEl.textContent = prog || 'Buscando programação…';
            if (progUrl) {
                var capturedUrl = url;
                fetchProgramacaoLive(progUrl, function (nomeProg) {
                    /* Só atualiza se a rádio ainda for a mesma */
                    if (audio.src.indexOf(capturedUrl) !== -1 || currentUrl === capturedUrl) {
                        programEl.textContent = nomeProg;
                    }
                });
            }
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
    function isMobile() {
        return window.innerWidth <= 575;
    }

    function setVolume(val) {
        audio.volume    = val;
        volSlider.value = val;
        volPopupSlider.value = val;
        updateVolIcon(val);
        saveState();
    }

    volSlider.addEventListener('input', function () {
        setVolume(parseFloat(this.value));
    });

    volPopupSlider.addEventListener('input', function () {
        setVolume(parseFloat(this.value));
    });

    volIcon.addEventListener('click', function () {
        if (isMobile()) {
            /* Mobile: toggle popup vertical */
            volPopup.classList.toggle('open');
        } else {
            /* Desktop: muta/desmuta */
            setVolume(audio.volume > 0 ? 0 : 1);
        }
    });

    /* Fecha popup ao clicar fora */
    document.addEventListener('click', function (e) {
        if (volPopup.classList.contains('open') &&
            !volPopup.contains(e.target) &&
            e.target !== volIcon) {
            volPopup.classList.remove('open');
        }
    });

    volIcon.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { this.click(); }
        if (e.key === 'Escape') { volPopup.classList.remove('open'); }
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
        /* Prioridade: programacao estática > tags da API > localização */
        metaDiv.textContent = station.programacao || tags || station.state || station.country || '';

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

        /* Carrega rádios personalizadas do data/radios.json, depois complementa com Radio Browser */
        fetch('data/radios.json?' + Date.now())
            .then(function (res) { return res.ok ? res.json() : {}; })
            .catch(function () { return {}; })
            .then(function (payload) {
                stationList.innerHTML = '';

                /* Suporta formato antigo (array) e novo (objeto {radios, config}) */
                var customStations = Array.isArray(payload) ? payload : (payload.radios || []);
                var radioConfig    = (!Array.isArray(payload) && payload.config) ? payload.config : {};
                var regrasExternas = (radioConfig.externas_habilitadas && Array.isArray(radioConfig.regras))
                    ? radioConfig.regras
                    : [];

                /* Fallback: se não há regras configuradas, usa busca padrão */
                if (regrasExternas.length === 0) {
                    regrasExternas = [{ label: 'Rádios católicas', tag: 'catholic', pais: 'BR', limite: 30 }];
                }

                /* ── Filtros por categoria e estado ─────────────────────── */
                var filtroAtivo = { categoria: 'todos', estado: 'todos' };
                var todasEstacoes = customStations || [];

                if (todasEstacoes.length > 1) {
                    /* Coletar categorias e estados disponíveis */
                    var categoriasDisp = ['todos'];
                    var estadosDisp   = ['todos'];
                    todasEstacoes.forEach(function (r) {
                        if (r.categoria && !categoriasDisp.includes(r.categoria)) { categoriasDisp.push(r.categoria); }
                        if (r.estado   && !estadosDisp.includes(r.estado))        { estadosDisp.push(r.estado); }
                    });

                    var labelsCat = { todos: 'Todas', catolica: 'Católica', gospel: 'Gospel', religiosa: 'Religiosa', regional: 'Regional', outra: 'Outra' };

                    /* Barra de filtros */
                    var filtroBar = document.createElement('div');
                    filtroBar.className = 'radio-filtro-bar';
                    filtroBar.style.cssText = 'display:flex;gap:6px;flex-wrap:wrap;padding:8px 12px;border-bottom:1px solid rgba(255,255,255,.08);';

                    if (categoriasDisp.length > 2) {
                        var selCat = document.createElement('select');
                        selCat.className = 'radio-filtro-select';
                        selCat.setAttribute('aria-label', 'Filtrar por categoria');
                        selCat.style.cssText = 'flex:1;min-width:100px;padding:4px 6px;font-size:12px;border-radius:4px;border:1px solid rgba(255,255,255,.2);background:#1a1a1a;color:#fff;cursor:pointer;';
                        categoriasDisp.forEach(function (c) {
                            var o = document.createElement('option');
                            o.value       = c;
                            o.textContent = labelsCat[c] || c;
                            selCat.appendChild(o);
                        });
                        selCat.addEventListener('change', function () {
                            filtroAtivo.categoria = this.value;
                            renderizarLista();
                        });
                        filtroBar.appendChild(selCat);
                    }

                    if (estadosDisp.length > 2) {
                        var selEstado = document.createElement('select');
                        selEstado.className = 'radio-filtro-select';
                        selEstado.setAttribute('aria-label', 'Filtrar por estado');
                        selEstado.style.cssText = 'flex:1;min-width:80px;padding:4px 6px;font-size:12px;border-radius:4px;border:1px solid rgba(255,255,255,.2);background:#1a1a1a;color:#fff;cursor:pointer;';
                        estadosDisp.forEach(function (e) {
                            var o = document.createElement('option');
                            o.value       = e;
                            o.textContent = e === 'todos' ? 'Todos UFs' : e;
                            selEstado.appendChild(o);
                        });
                        selEstado.addEventListener('change', function () {
                            filtroAtivo.estado = this.value;
                            renderizarLista();
                        });
                        filtroBar.appendChild(selEstado);
                    }

                    if (filtroBar.children.length > 0) {
                        stationList.parentElement.insertBefore(filtroBar, stationList);
                    }
                }

                /* ── Renderiza lista filtrada ─────────────────────────────── */
                function renderizarLista() {
                    stationList.innerHTML = '';

                    var filtradas = todasEstacoes.filter(function (r) {
                        var passaCat    = filtroAtivo.categoria === 'todos' || (r.categoria || 'catolica') === filtroAtivo.categoria;
                        var passaEstado = filtroAtivo.estado === 'todos' || r.estado === filtroAtivo.estado;
                        return passaCat && passaEstado;
                    });

                    if (filtradas.length === 0) {
                        var vazio = document.createElement('div');
                        vazio.className = 'radio-station-loading';
                        vazio.style.cssText = 'padding:16px;text-align:center;opacity:.6;';
                        vazio.textContent = 'Nenhuma rádio encontrada para este filtro.';
                        stationList.appendChild(vazio);
                        return;
                    }

                    filtradas.forEach(function (r) {
                        var s = {
                            name:             r.nome,
                            url_resolved:     r.url,
                            favicon:          r.favicon || '',
                            tags:             r.descricao || '',
                            programacao:      r.programacao || '',
                            programacao_url:  r.programacao_url || '',
                            state:            (r.cidade ? r.cidade + ' — ' : '') + (r.estado || ''),
                        };
                        var badge = r.destaque ? (currentUrl === r.url ? 'AO VIVO' : 'Destaque') : null;
                        stationList.appendChild(buildItem(s, r.destaque, badge));
                    });
                }

                if (todasEstacoes.length) {
                    renderizarLista();
                } else {
                    /* Fallback: sempre exibe Itacambarí se radios.json vazio */
                    var featured = { name: ITACAMBARI_NAME, url_resolved: ITACAMBARI_STREAM, favicon: '', tags: '', state: 'Jericó/PB' };
                    stationList.appendChild(buildItem(featured, true, isItacambariLive() ? 'AO VIVO' : 'Paróquial'));
                }

                /* ── Busca rádios externas por regras configuradas no CMS ─── */
                function carregarRegra(regra, onDone) {
                    var params = new URLSearchParams({
                        order:       'votes',
                        reverse:     'true',
                        hidebroken:  'true',
                        limit:       String(regra.limite || 10),
                    });
                    if (regra.tag)   { params.set('tag', regra.tag); }
                    if (regra.pais)  { params.set('countrycode', regra.pais); }
                    if (regra.estado) { params.set('state', regra.estado); }

                    fetch('https://de1.api.radio-browser.info/json/stations/search?' + params.toString())
                        .then(function (res) { return res.json(); })
                        .then(onDone)
                        .catch(function () { onDone([]); });
                }

                var regraIdx = 0;
                function carregarProximaRegra() {
                    if (regraIdx >= regrasExternas.length) { return; }
                    var regra = regrasExternas[regraIdx++];

                    var sepEl = document.createElement('div');
                    sepEl.className = 'radio-station-loading';
                    sepEl.style.cssText = 'font-size:10px;text-transform:uppercase;letter-spacing:1px;opacity:.6;padding:8px 12px 4px;';
                    sepEl.textContent = regra.label || 'Outras rádios';
                    stationList.appendChild(sepEl);

                    carregarRegra(regra, function (stations) {
                        /* Remove o separador de loading; readiciona apenas se tiver resultados */
                        if (stationList.contains(sepEl)) { stationList.removeChild(sepEl); }
                        if (stations && stations.length) {
                            var divSep = document.createElement('div');
                            divSep.className = 'radio-station-loading';
                            divSep.style.cssText = 'font-size:10px;text-transform:uppercase;letter-spacing:1px;opacity:.6;padding:8px 12px 4px;';
                            divSep.textContent = regra.label || 'Outras rádios';
                            stationList.appendChild(divSep);
                            stations.forEach(function (s) { stationList.appendChild(buildItem(s, false, null)); });
                        }
                        /* Carrega próxima regra em sequência para não sobrecarregar a API */
                        carregarProximaRegra();
                    });
                }

                carregarProximaRegra();
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

    /* Guard contra dupla inicialização quando radio-player.js é carregado
     * mais de uma vez (ex.: script duplicado na página ou após troca de body) */
    if (window.__nsr_pjax_init) { return; }
    window.__nsr_pjax_init = true;

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

                /* Re-executa scripts necessários para a nova página */
                var scriptsToRun = ['js/function.js', 'js/active-nav.js'];

                /* Scripts página-específicos: detecta pelos <script src> do novo body */
                var pageScripts = Array.from(newDoc.querySelectorAll('script[src]'))
                    .map(function (s) { return s.getAttribute('src').split('?')[0]; })
                    .filter(function (src) {
                        /* Só scripts específicos de página (não os do partial comum) */
                        var commons = ['jquery', 'bootstrap', 'validator', 'slicknav',
                            'swiper', 'waypoints', 'counterup', 'magnific', 'SmoothScroll',
                            'parallaxie', 'gsap', 'magiccursor', 'SplitText', 'ScrollTrigger',
                            'YTPlayer', 'plyr', 'wow', 'function.js', 'active-nav.js',
                            'radio-player.js', 'horarios-missa', 'agenda-pastoral'];
                        return !commons.some(function (c) { return src.indexOf(c) !== -1; });
                    });
                pageScripts.forEach(function (src) { scriptsToRun.push(src); });

                var scriptIdx = 0;
                function runNextScript() {
                    if (scriptIdx >= scriptsToRun.length) {
                        /* Notifica demais módulos que o DOM foi trocado */
                        document.dispatchEvent(new CustomEvent('pjax:ready'));
                        return;
                    }
                    var s = document.createElement('script');
                    s.src = scriptsToRun[scriptIdx++] + '?' + Date.now();
                    s.onload = runNextScript;
                    s.onerror = runNextScript;
                    document.body.appendChild(s);
                }
                runNextScript();
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
