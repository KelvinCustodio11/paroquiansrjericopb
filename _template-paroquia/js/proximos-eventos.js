/**
 * proximos-eventos.js
 * Atualiza dinamicamente a seção "our-event" da index.html
 * com o próximo evento que ainda não terminou.
 *
 * ── COMO ADICIONAR UM EVENTO ──────────────────────────────────────────────
 * 1. Salve a imagem em:  images/uploads/events/
 * 2. Nomeie seguindo o padrão kebab-case (sem espaços, sem acentos, sem
 *    maiúsculas):  {slug-do-evento}-{descricao}.jpeg
 *    Exemplos:
 *      mes-de-maio-2026-capa.jpeg
 *      mes-de-maio-2026-abertura.jpeg
 *      festa-padroeira-2026-capa.jpeg
 *      corpus-christi-2026-capa.jpeg
 * 3. Adicione um objeto ao array EVENTOS abaixo (antes do comentário final)
 *    seguindo o mesmo modelo dos já existentes.
 * 4. Os campos dataInicio/dataFim usam: new Date(ANO, MES-1, DIA)
 *    (meses em JS são base 0: jan=0, fev=1, ... mai=4, set=8, dez=11)
 * ─────────────────────────────────────────────────────────────────────────
 */
(function () {

    /* ============================================================
       LISTA DE EVENTOS
       Ordem: do mais próximo para o mais distante.
       O script exibe o primeiro cuja dataFim >= hoje.
    ============================================================ */
    var EVENTOS = [
        {
            dataInicio:  new Date(2026, 4, 2),   // 02/Mai/2026
            dataFim:     new Date(2026, 4, 2),   // 02/Mai/2026
            label:       'assembleia forânea',
            titulo:      'Assembleia Forânea',
            tituloSpan:  'da PASCOM 2026',
            dataTexto:   '02 de Maio, 2026 — 9h',
            local:       'Paróquia São Francisco de Assis — Riacho dos Cavalos-PB',
            descricao:   'Com o tema "Preservar Vozes e Rostos Humanos", membros da PASCOM da Forania de Catolé do Rocha se reúnem para fortalecer a missão na comunicação pastoral, partilhar experiências e renovar o compromisso com a verdade e a evangelização.',
            imagem:      'images/uploads/events/assembleia-pascom-2026-capa.png',
            imagemAlt:   'Assembleia Forânea da PASCOM — Forania de Católé do Rocha 2026',
            fallback:    'images/event-image.jpg',
            link:        'evento-single-pascom.html',
            btnTexto:    'saiba mais'
        },
        {
            dataInicio:  new Date(2026, 4, 1),   // 01/Mai/2026
            dataFim:     new Date(2026, 4, 31),  // 31/Mai/2026
            label:       'mês mariano 2026',
            titulo:      'Mês de Maio',
            tituloSpan:  'com Maria 2026',
            dataTexto:   '1º a 31 de Maio, 2026 — 19h todas as noites',
            local:       'Igreja Matriz — Praça Pe. Sebastião, Jericó - PB',
            descricao:   'Durante todo o mês de maio a comunidade se reúne todas as noites para celebrações marianas em família. Abertura solene em 1º de maio e encerramento com a Coroação de Nossa Senhora em 31 de maio. Venha com sua família viver este tempo de graça e devoção!',
            imagem:      'images/uploads/events/mes-de-maio-2026-capa.jpeg',
            imagemAlt:   'Mês de Maio com Maria 2026',
            fallback:    'images/event-image.jpg',
            link:        'evento-single.html',
            btnTexto:    'ver programação completa'
        },
        {
            dataInicio:  new Date(2026, 8, 8),   // 08/Set/2026
            dataFim:     new Date(2026, 8, 8),   // 08/Set/2026
            label:       'próximo evento',
            titulo:      'Festa de Nossa Senhora',
            tituloSpan:  'dos Remédios 2026',
            dataTexto:   '08 Set, 2026 — 6:00 às 21:00',
            local:       'Paróquia Nossa Senhora dos Remédios, Praça Pe. Sebastião, Jericó - PB',
            descricao:   'A Festa da Padroeira é o maior evento da vida paroquial de Jericó. Durante os festejos são realizadas missas solenes, novena, procissão, apresentações culturais e momentos de confraternização que reúnem toda a comunidade em louvor à Nossa Senhora dos Remédios.',
            imagem:      'images/event-image.jpg',
            imagemAlt:   'Festa de Nossa Senhora dos Remédios 2026',
            fallback:    null,
            link:        'eventos.html',
            btnTexto:    'participe pessoalmente'
        }
        /* ── Adicione novos eventos aqui seguindo o mesmo padrão ── */
    ];

    /* ============================================================
       LÓGICA: encontra o próximo evento (dataFim >= hoje)
    ============================================================ */
    var hoje = new Date();
    hoje.setHours(0, 0, 0, 0);

    var proximo = null;
    for (var i = 0; i < EVENTOS.length; i++) {
        if (EVENTOS[i].dataFim >= hoje) {
            proximo = EVENTOS[i];
            break;
        }
    }

    // Se todos os eventos já passaram, exibe o último da lista
    if (!proximo) {
        proximo = EVENTOS[EVENTOS.length - 1];
    }

    /* ============================================================
       ATUALIZA O DOM
       (este script roda ANTES do function.js, então o WOW.js
       e o GSAP SplitText já processam o texto correto)
    ============================================================ */
    var imgEl   = document.getElementById('next-event-img');
    var labelEl = document.getElementById('next-event-label');
    var titleEl = document.getElementById('next-event-title');
    var dateEl  = document.getElementById('next-event-date');
    var localEl = document.getElementById('next-event-location');
    var descEl  = document.getElementById('next-event-desc');
    var btnEl   = document.getElementById('next-event-btn');

    if (imgEl) {
        imgEl.src = proximo.imagem;
        imgEl.alt = proximo.imagemAlt;
        if (proximo.fallback) {
            imgEl.onerror = function () {
                this.src     = proximo.fallback;
                this.onerror = null;
            };
        }
    }

    if (labelEl) labelEl.textContent = proximo.label;

    // innerHTML porque o título tem <span> para colorir uma parte do texto
    if (titleEl) titleEl.innerHTML = proximo.titulo + ' <span>' + proximo.tituloSpan + '</span>';

    if (dateEl)  dateEl.textContent = proximo.dataTexto;
    if (localEl) localEl.textContent = proximo.local;
    if (descEl)  descEl.textContent = proximo.descricao;

    if (btnEl) {
        btnEl.textContent = proximo.btnTexto;
        btnEl.href        = proximo.link;
    }

})();
