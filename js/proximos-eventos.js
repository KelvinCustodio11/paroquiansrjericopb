/**
 * proximos-eventos.js — FALLBACK somente
 *
 * Após rodar `npm run build:content` o HTML da seção our-event
 * já está gerado estaticamente e este script não encontra os
 * elementos (id="next-event-*" foram removidos), ficando inativo.
 *
 * Este fallback só é ativado enquanto o build ainda não rodou,
 * mantendo algum conteúdo visível com dados hardcoded.
 * Para adicionar ou atualizar eventos: use o Filament CMS e
 * rode: php artisan content:export && npm run build:content
 */
(function () {

    /* ── fallback hardcoded (usado apenas antes do primeiro build) ── */
    var EVENTOS = [
        {
            dataInicio:  new Date(2026, 4, 10),
            dataFim:     new Date(2026, 4, 10),
            label:       'assembleia forânea',
            titulo:      'Assembleia Diocesana',
            tituloSpan:  'da PASCOM 2026',
            dataTexto:   '10 de Maio, 2026 — 8h',
            local:       'Salão Paroquial — Jericó/PB',
            descricao:   'Com o tema "Preservar Vozes e Rostos Humanos", membros da PASCOM da Diocese se reúnem para fortalecer a missão na comunicação pastoral.',
            imagem:      'images/uploads/events/assembleia-pascom-2026-capa.png',
            imagemAlt:   'Assembleia Diocesana da PASCOM 2026',
            fallback:    'images/event-image.jpg',
            link:        'eventos.html',
            btnTexto:    'ver todos os eventos'
        },
        {
            dataInicio:  new Date(2026, 8, 8),
            dataFim:     new Date(2026, 8, 8),
            label:       'próximo evento',
            titulo:      'Festa de Nossa Senhora',
            tituloSpan:  'dos Remédios 2026',
            dataTexto:   '08 Set, 2026 — 6:00 às 21:00',
            local:       'Paróquia Nossa Senhora dos Remédios, Praça Pe. Sebastião, Jericó - PB',
            descricao:   'A Festa da Padroeira é o maior evento da vida paroquial de Jericó. Durante os festejos são realizadas missas solenes, novena, procissão e momentos de confraternização.',
            imagem:      'images/event-image.jpg',
            imagemAlt:   'Festa de Nossa Senhora dos Remédios 2026',
            fallback:    null,
            link:        'eventos.html',
            btnTexto:    'ver todos os eventos'
        }
    ];

    /* ── Só atua se os IDs de fallback existirem no DOM ── */
    var imgEl   = document.getElementById('next-event-img');
    if (!imgEl) return; /* build já rodou — HTML correto no lugar */

    var labelEl = document.getElementById('next-event-label');
    var titleEl = document.getElementById('next-event-title');
    var dateEl  = document.getElementById('next-event-date');
    var localEl = document.getElementById('next-event-location');
    var descEl  = document.getElementById('next-event-desc');
    var btnEl   = document.getElementById('next-event-btn');

    var hoje = new Date(); hoje.setHours(0, 0, 0, 0);
    var proximo = null;
    for (var i = 0; i < EVENTOS.length; i++) {
        if (EVENTOS[i].dataFim >= hoje) { proximo = EVENTOS[i]; break; }
    }
    if (!proximo) proximo = EVENTOS[EVENTOS.length - 1];

    imgEl.src = proximo.imagem;
    imgEl.alt = proximo.imagemAlt;
    if (proximo.fallback) {
        imgEl.onerror = function () { this.src = proximo.fallback; this.onerror = null; };
    }
    if (labelEl) labelEl.textContent = proximo.label;
    if (titleEl) titleEl.innerHTML = proximo.titulo + ' <span>' + proximo.tituloSpan + '</span>';
    if (dateEl)  dateEl.textContent  = proximo.dataTexto;
    if (localEl) localEl.textContent = proximo.local;
    if (descEl)  descEl.textContent  = proximo.descricao;
    if (btnEl)  { btnEl.textContent  = proximo.btnTexto; btnEl.href = proximo.link; }

})();

