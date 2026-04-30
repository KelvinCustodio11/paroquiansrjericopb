/**
 * contact-form.js
 * Manipula o formulário de contato (#contactForm):
 *  1. Busca CSRF token do form-process.php?action=token ao carregar.
 *  2. Submete via fetch (AJAX) para form-process.php.
 *  3. Exibe a resposta dentro de #msgSubmit (sem alert/redirect).
 *
 * Backend espera: csrf_token, hp_field, fname, lname, email, phone, message.
 * Resposta JSON: { ok: bool, mensagem: string, erros?: string[] }
 */
(function () {
    'use strict';

    var form = document.getElementById('contactForm');
    if (!form) return;

    var tokenInput = document.getElementById('csrf_token');
    var msgBox = document.getElementById('msgSubmit');
    var btn = document.getElementById('contactSubmitBtn');
    var endpoint = 'form-process.php';

    function setMessage(text, isError) {
        if (!msgBox) return;
        msgBox.textContent = text;
        msgBox.classList.remove('hidden');
        msgBox.style.color = isError ? '#b00020' : 'var(--accent-color, #2e7d32)';
        msgBox.style.fontSize = '1rem';
        msgBox.style.marginTop = '12px';
    }

    function loadToken() {
        return fetch(endpoint + '?action=token', {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
            .then(function (data) {
                if (data && data.csrf_token && tokenInput) {
                    tokenInput.value = data.csrf_token;
                }
            })
            .catch(function () { /* silencioso; submit revalida */ });
    }

    loadToken();

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        if (!tokenInput || !tokenInput.value) {
            setMessage('Recarregue a página e tente novamente.', true);
            return;
        }
        if (btn) {
            btn.disabled = true;
            btn.dataset.originalText = btn.textContent;
            btn.textContent = 'Enviando…';
        }
        setMessage('', false);

        var fd = new FormData(form);

        fetch(endpoint, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
            .then(function (r) {
                return r.json().then(function (data) { return { status: r.status, data: data }; });
            })
            .then(function (res) {
                var ok = res.data && res.data.ok;
                var msg = (res.data && res.data.mensagem) || (ok ? 'Mensagem enviada.' : 'Não foi possível enviar.');
                setMessage(msg, !ok);
                if (ok) {
                    form.reset();
                    loadToken(); // novo token para próximo envio
                }
            })
            .catch(function () {
                setMessage('Erro de conexão. Tente novamente em instantes.', true);
            })
            .finally(function () {
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = btn.dataset.originalText || 'enviar mensagem';
                }
            });
    });
})();
