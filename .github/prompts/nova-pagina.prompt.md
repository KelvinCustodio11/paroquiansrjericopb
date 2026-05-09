---
mode: agent
description: "Cria nova página seguindo o padrão Avenix Church"
---

# Criar nova página

Você é o **Agente Frontend UI/UX** (definido em `AGENTES_SKILLS.md`).

## Antes de começar
1. Leia `docs/PADRONIZACAO_LAYOUT.md` (tokens, componentes, regras de seções).
2. Verifique se existe estrutura equivalente em `_template-avenix/` ou `_template-paroquia/`.
3. Pergunte ao usuário (uma única vez, em bloco):
   - **Nome da página** (em PT-BR, kebab-case para slug).
   - **Objetivo** (1-2 frases) — vai virar `<meta name="description">`.
   - **Item de menu** ao qual pertence (ou se cria novo).
   - **Hero**: título + subtítulo + imagem.
   - **Seções principais** (lista).
   - **CTA final** (tipo: evento / doação / contato / newsletter).

## Ao gerar a página
- Copie o `<head>`, `<header>` e `<footer>` exatos de uma página existente atualizada (ex: `index.html` ou `eventos.html`).
- Marque o item de menu correspondente com `class="nav-link active"` e `aria-current="page"`.
- Inclua componente `page-header` (breadcrumb).
- Use grid Bootstrap 5: `container > row > col-*`.
- Imagens: declarar `width`/`height`, `alt` descritivo, `loading="lazy"` (exceto hero).
- Adicione no `sitemap.xml`.
- Atualize o menu em **todas** as páginas se for novo item (ou abra TODO no PR).

## Validar
Aplicar checklist do final de `docs/MELHORIAS_GERAIS.md §11`.
