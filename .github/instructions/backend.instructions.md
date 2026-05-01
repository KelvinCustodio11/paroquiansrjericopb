---
applyTo: "**/*.php,!_template-avenix/**,!_template-paroquia/**"
description: "Padrões de segurança e qualidade para PHP"
---

# Backend PHP — Segurança em primeiro lugar

Aplicar em **todo arquivo `.php`** do projeto.

## Obrigatório
- [ ] `declare(strict_types=1);` no topo de todo arquivo.
- [ ] Validar input com `filter_input` / `filter_var` (`FILTER_VALIDATE_EMAIL`, `FILTER_VALIDATE_INT`, etc.).
- [ ] Sanitizar com `htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` antes de qualquer output.
- [ ] **CSRF token** em todo formulário (sessão + `hash_equals`).
- [ ] **Rate limiting** em endpoints públicos.
- [ ] **Honeypot** anti-bot em formulários.
- [ ] Verificar Origin/Referer.
- [ ] **Bloquear header injection**: rejeitar `\r`, `\n`, `bcc:`, `cc:`, `content-type:` em campos enviados a `mail()`.
- [ ] `From:` do domínio próprio + `Reply-To:` do remetente (nunca `From:` com email do usuário).
- [ ] Resposta JSON estruturada (`Content-Type: application/json; charset=utf-8`).

## Proibido
- ❌ `eval`, `assert(string)`, `create_function`.
- ❌ Concatenar dados de `$_POST`/`$_GET` em SQL — usar prepared statements.
- ❌ Concatenar dados de usuário em headers de e-mail.
- ❌ `@mail()` sem checar retorno.
- ❌ `var_dump`, `print_r`, `dd()` em produção.
- ❌ Credenciais hardcoded — usar `getenv()`.
- ❌ `error_reporting(E_ALL); ini_set('display_errors', 1);` em produção.

## Em produção
- Migrar de `mail()` para **PHPMailer/SMTP** autenticado.
- Logging persistente (arquivo rotacionado ou banco).
- reCAPTCHA v3 ativado via `RECAPTCHA_SECRET` em variável de ambiente.

## Referência
Ver `form-process.php` na raiz do projeto como exemplo aplicado.
