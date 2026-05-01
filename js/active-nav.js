/**
 * active-nav.js
 * Marca o item de menu ativo baseado em <body data-page="...">.
 * Substitui a necessidade de duplicar o markup do header com `class="active"`
 * em cada página HTML.
 *
 * Uso: cada página declara <body data-page="eventos"> e o link do menu
 * com data-page="eventos" recebe a classe `active` + aria-current="page".
 */
(function () {
    'use strict';
    var page = document.body && document.body.getAttribute('data-page');
    if (!page) return;
    var links = document.querySelectorAll('#menu a.nav-link[data-page="' + CSS.escape(page) + '"]');
    Array.prototype.forEach.call(links, function (link) {
        link.classList.add('active');
        link.setAttribute('aria-current', 'page');
    });
})();
