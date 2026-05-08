/**
 * js/horarios-missa.js
 *
 * Componente que renderiza a tabela de horarios de missa lendo data/horarios-missa.json.
 *
 * Uso: adicionar em qualquer pagina:
 *   <div data-component="horarios-missa"></div>
 *
 * Auto-detecta se esta na raiz ou em subpasta (1 nivel) e ajusta o fetch.
 */
(function () {
    'use strict';

    const DIAS = {
        segunda: 'Segunda-feira',
        terca:   'Terça-feira',
        quarta:  'Quarta-feira',
        quinta:  'Quinta-feira',
        sexta:   'Sexta-feira',
        sabado:  'Sábado',
        domingo: 'Domingo'
    };

    const TIPOS = {
        missa:    'Missa',
        novena:   'Novena',
        adoracao: 'Adoração',
        terco:    'Terço',
        outro:    ''
    };

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function renderIgreja(igreja) {
        const blocos = {};
        (igreja.horarios || []).forEach(h => {
            (blocos[h.dia_semana] = blocos[h.dia_semana] || []).push(h);
        });
        const ordem = ['domingo','segunda','terca','quarta','quinta','sexta','sabado'];
        const linhas = ordem
            .filter(d => blocos[d])
            .map(d => {
                const items = blocos[d].map(h => {
                    const tipo = TIPOS[h.tipo_celebracao] || '';
                    const obs = h.observacao ? ` <small class="text-muted">(${escapeHtml(h.observacao)})</small>` : '';
                    return `<li><strong>${escapeHtml(h.hora)}</strong> ${escapeHtml(tipo)}${obs}</li>`;
                }).join('');
                return `<tr>
                    <th scope="row" class="text-nowrap">${DIAS[d]}</th>
                    <td><ul class="list-unstyled mb-0">${items}</ul></td>
                </tr>`;
            }).join('');

        return `
            <article class="igreja-horarios mb-4" data-igreja="${escapeHtml(igreja.slug || '')}">
                <header class="mb-2">
                    <h3 class="h5 mb-1">${escapeHtml(igreja.nome)}</h3>
                    ${igreja.endereco ? `<p class="small text-muted mb-0">${escapeHtml(igreja.endereco)}</p>` : ''}
                </header>
                <table class="table table-sm align-middle">
                    <caption class="visually-hidden">Horários de celebrações em ${escapeHtml(igreja.nome)}</caption>
                    <tbody>${linhas}</tbody>
                </table>
            </article>
        `;
    }

    function render(container, data) {
        const igrejas = (data && data.igrejas) || [];
        if (!igrejas.length) {
            container.innerHTML = '<p class="text-muted">Horários ainda não cadastrados.</p>';
            return;
        }
        container.innerHTML = igrejas.map(renderIgreja).join('');
    }

    function dataPath() {
        // Detecta profundidade: se URL tem /eventos/, /artigos/, /homilias/ no path -> usa ../
        const p = window.location.pathname;
        const subDirs = ['/eventos/', '/artigos/', '/homilias/'];
        return subDirs.some(d => p.includes(d)) ? '../data/horarios-missa.json' : 'data/horarios-missa.json';
    }

    function init() {
        const containers = document.querySelectorAll('[data-component="horarios-missa"]');
        if (!containers.length) return;
        fetch(dataPath(), { cache: 'no-cache' })
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(data => containers.forEach(c => render(c, data)))
            .catch(err => {
                console.warn('[horarios-missa] Erro ao carregar:', err);
                containers.forEach(c => { c.innerHTML = '<p class="text-muted small">Não foi possível carregar os horários no momento.</p>'; });
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    document.addEventListener('pjax:ready', init);
})();
