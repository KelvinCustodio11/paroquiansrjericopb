/**
 * js/agenda-pastoral.js
 *
 * Renderiza compromissos pastorais publicos lendo data/agenda-pastoral.json.
 *
 * Uso:
 *   <div data-component="agenda-pastoral" data-limit="10"></div>
 *
 * Atributos opcionais:
 *   data-limit  -> numero maximo de itens (padrao 10)
 *   data-tipo   -> filtra por tipo (reuniao|formacao|visita|celebracao|evento|outro)
 */
(function () {
    'use strict';

    const TIPO_LABEL = {
        reuniao:    'Reunião',
        formacao:   'Formação',
        visita:     'Visita',
        celebracao: 'Celebração',
        evento:     'Evento',
        outro:      ''
    };

    const MESES = ['jan','fev','mar','abr','mai','jun','jul','ago','set','out','nov','dez'];

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function parseISO(d) {
        const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(d);
        return m ? new Date(parseInt(m[1],10), parseInt(m[2],10)-1, parseInt(m[3],10)) : null;
    }

    function fmtData(d) {
        const dt = parseISO(d);
        if (!dt) return d;
        return `${String(dt.getDate()).padStart(2,'0')}/${MESES[dt.getMonth()]}`;
    }

    function dataPath() {
        const p = window.location.pathname;
        const subDirs = ['/eventos/', '/artigos/', '/homilias/'];
        return subDirs.some(d => p.includes(d)) ? '../data/agenda-pastoral.json' : 'data/agenda-pastoral.json';
    }

    function render(container, items) {
        if (!items.length) {
            container.innerHTML = '<p class="text-muted small mb-0">Sem compromissos públicos cadastrados.</p>';
            return;
        }
        container.innerHTML = `
            <ul class="list-unstyled agenda-list mb-0">
                ${items.map(it => `
                    <li class="d-flex align-items-start py-2 border-bottom">
                        <div class="agenda-data text-center me-3" style="min-width:48px;">
                            <strong class="d-block">${escapeHtml(fmtData(it.data))}</strong>
                            ${it.hora ? `<small class="text-muted">${escapeHtml(it.hora)}</small>` : ''}
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${escapeHtml(it.titulo)}</div>
                            <small class="text-muted">
                                ${TIPO_LABEL[it.tipo] ? escapeHtml(TIPO_LABEL[it.tipo]) : ''}
                                ${it.local ? ` · ${escapeHtml(it.local)}` : ''}
                            </small>
                        </div>
                    </li>
                `).join('')}
            </ul>
        `;
    }

    document.addEventListener('DOMContentLoaded', function () {
        const containers = document.querySelectorAll('[data-component="agenda-pastoral"]');
        if (!containers.length) return;
        fetch(dataPath(), { cache: 'no-cache' })
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(data => {
                const today = new Date(); today.setHours(0,0,0,0);
                let items = (data.compromissos || [])
                    .filter(c => c.publico === true)
                    .filter(c => {
                        const dt = parseISO(c.data);
                        return dt && dt >= today;
                    })
                    .sort((a, b) => (a.data + (a.hora || '')).localeCompare(b.data + (b.hora || '')));
                containers.forEach(c => {
                    const limit = parseInt(c.dataset.limit || '10', 10);
                    const tipo  = c.dataset.tipo;
                    let list = items.slice();
                    if (tipo) list = list.filter(i => i.tipo === tipo);
                    render(c, list.slice(0, limit));
                });
            })
            .catch(err => {
                console.warn('[agenda-pastoral] Erro ao carregar:', err);
                containers.forEach(c => { c.innerHTML = '<p class="text-muted small">Não foi possível carregar a agenda no momento.</p>'; });
            });
    });
})();
