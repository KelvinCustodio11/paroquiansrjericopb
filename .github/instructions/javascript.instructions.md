---
applyTo: "js/**/*.js,!_template-avenix/**,!_template-paroquia/**"
description: "Padrões para JavaScript próprio (pasta js/)"
---

# JavaScript — Padrões de segurança e qualidade

Aplica-se aos scripts próprios em `js/` (não toca em libs de terceiros como jQuery, Bootstrap, Swiper).

## Sempre
- [ ] Use `'use strict';` no topo de IIFE/módulos.
- [ ] Prefira `textContent` a `innerHTML` quando o conteúdo for dinâmico/externo.
- [ ] Se precisar inserir HTML de fonte externa, sanitize com **DOMPurify** ou construa via `document.createElement`.
- [ ] Cache em `localStorage`/`sessionStorage` deve ter TTL e tratamento de invalidação.
- [ ] Toda chamada `fetch` externa precisa de `try/catch`, timeout (`AbortController`) e fallback.
- [ ] Sem chaves de API hardcoded — usar configuração via `<meta>` ou endpoint próprio.

## Proibido
- ❌ `eval`, `Function()` com strings dinâmicas.
- ❌ `document.write`.
- ❌ `innerHTML = userControlledData`.
- ❌ `console.log` em código de produção (manter só `console.error` para erros críticos).
- ❌ Modificar protótipos de objetos nativos (`Array.prototype.X`, etc.).

## Scripts próprios atuais
| Arquivo | Função |
|---|---|
| `js/radio-player.js` | FAB de player de rádio (stream externo) |
| `js/liturgia.js` | Liturgia diária (API `liturgia.up.railway.app`) |
| `js/santo-dia.js` | Santo do dia (Wikipedia PT) |
| `js/calendario-romano.js` | Calendário litúrgico romano |
| `js/proximos-eventos.js` | Atualização dinâmica de eventos |
| `js/function.js` | Inicializações gerais (sliders, sticky header) |

## Ao adicionar novo script
1. Documentar dependências externas no topo (comentário JSDoc).
2. Encapsular em IIFE ou objeto namespaced (`window.MeuModulo = {...}`).
3. Disparar evento `paroquia:module-ready` quando inicializar.
4. Ouvir `paroquia:dom-replaced` (para reagir a substituições do `radio-player.js`).
