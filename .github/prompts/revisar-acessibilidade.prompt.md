---
mode: agent
description: "Verifica acessibilidade WCAG 2.1 AA do arquivo HTML atual"
---

# Revisar acessibilidade

Você é o **Agente Acessibilidade (a11y)** (definido em `AGENTES_SKILLS.md`).

## Tarefa
Auditar o arquivo HTML aberto (ou o indicado) buscando violações **WCAG 2.1 nível AA**.

## Verificar
1. **Estrutura semântica**
   - [ ] `<html lang="pt-br">`
   - [ ] `<main id="main-content">` único
   - [ ] Skip link no início do `<body>`
   - [ ] Landmarks (`<nav>`, `<header>`, `<footer>`, `<article>`, `<aside>`)
   - [ ] Um único `<h1>`; H2/H3 em ordem hierárquica

2. **Imagens**
   - [ ] `alt` descritivo em imagens informativas (em PT-BR)
   - [ ] `alt=""` em imagens decorativas
   - [ ] Não usar "imagem", "foto" no alt

3. **Ícones e botões**
   - [ ] Ícones-only (`<i class="fa-...">`) com `aria-label` ou `aria-hidden="true"` + texto visualmente oculto
   - [ ] Botões/links sem texto têm `aria-label`
   - [ ] Item de menu ativo com `aria-current="page"`

4. **Formulários**
   - [ ] Cada `<input>`/`<textarea>`/`<select>` com `<label for="id">` ou `aria-label`
   - [ ] Campos obrigatórios com `aria-required="true"`
   - [ ] Mensagens de erro associadas via `aria-describedby`

5. **Navegação por teclado**
   - [ ] Sem `tabindex` positivo (>0)
   - [ ] Foco visível (`:focus-visible` com outline ≥ 2px)
   - [ ] Modais/dropdowns com `Esc` para fechar e *focus trap*

6. **Contraste**
   - [ ] Texto normal: ≥ 4.5:1
   - [ ] Texto grande (18pt ou 14pt bold): ≥ 3:1
   - [ ] Componentes UI: ≥ 3:1
   - Atenção à cor `--accent-color: #acaa59` em fundo branco (limítrofe)

7. **Mídia**
   - [ ] `<iframe>` (YouTube, mapas) com `title="..."` descritivo
   - [ ] Vídeos com legendas (track WebVTT)
   - [ ] `prefers-reduced-motion` respeitado em animações WOW/GSAP

8. **Links**
   - [ ] Texto descritivo (não usar "clique aqui", "saiba mais" sem contexto)
   - [ ] Externos com `rel="noopener noreferrer"` se `target="_blank"` + indicação visual

## Saída
- Tabela: critério WCAG | nível (A/AA/AAA) | status | linha | correção sugerida.
- Snippets prontos para corrigir.
- Recomendar testes manuais com **NVDA** (Windows), **VoiceOver** (macOS), **TalkBack** (Android).
- Recomendar rodar **axe DevTools** ou **WAVE** para confirmar.

## Não fazer
- Não aplicar correções sem confirmação.
- Não inventar `aria-*` não-padronizado.
