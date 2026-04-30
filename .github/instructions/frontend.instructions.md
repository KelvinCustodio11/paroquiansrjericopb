---
applyTo: "**/*.html,**/*.css,!_template-avenix/**,!_template-paroquia/**"
description: "Padrões frontend (HTML/CSS) — template Avenix Church"
---

# Frontend UI/UX — Avenix Church

Aplique este guia em **todo HTML/CSS** do projeto (exceto pastas de referência `_template-avenix/` e `_template-paroquia/`).

## Princípios
1. Reusar componentes do template antes de criar novos. Consultar `_template-avenix/` (original) e `_template-paroquia/` (com customizações da paróquia).
2. Tokens de design em [`PADRONIZACAO_LAYOUT.md`](../../PADRONIZACAO_LAYOUT.md) — sempre via `var(--*)`.
3. Mobile-first; testar em 375 / 768 / 1024 / 1440px.

## Sempre validar
- [ ] HTML5 semântico (`<main id="main-content">`, `<nav>`, `<article>`, `<section>`).
- [ ] `lang="pt-br"` no `<html>`.
- [ ] Um único `<h1>` por página; hierarquia H1 > H2 > H3.
- [ ] Imagens com `alt` (vazio se decorativa), `width`, `height`, `loading="lazy"` exceto hero.
- [ ] Ícones-only com `aria-label`.
- [ ] Skip link no topo do `<body>` (`<a href="#main-content" class="skip-link">Pular para o conteúdo</a>`).
- [ ] Contraste WCAG AA (mínimo 4.5:1 texto normal).
- [ ] `prefers-reduced-motion` respeitado em animações WOW/GSAP.

## Antes de criar componente novo
1. Procure equivalente em `_template-avenix/` e `_template-paroquia/`.
2. Se existir, copie HTML/CSS exatos (mantendo classes Bootstrap e Avenix).
3. Se não existir, siga o padrão de seções da §6 de `PADRONIZACAO_LAYOUT.md`.

## Botões
- CTA primário: `class="btn-default"` (dourado Avenix `#acaa59`).
- Secundário: `class="btn-default btn-outline"`.
- "Leia mais": `class="readmore-btn"`.

## NÃO fazer
- Não inventar paleta de cores nova.
- Não criar arquivo CSS novo (usar `css/custom.css`).
- Não usar `style=""` inline a menos que justificado.
- Não duplicar header/footer pensando que vai ser refatorado depois — abrir issue antes.

## Templates de referência
| Pasta | Quando usar |
|---|---|
| `_template-avenix/` | Componentes nativos do template, ainda não-implementados no site |
| `_template-paroquia/` | Componentes próprios da paróquia (radio, liturgia, santo do dia) |
