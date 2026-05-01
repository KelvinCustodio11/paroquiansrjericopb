---
description: Padrão de criação/edição de artigos (blog). Aplica-se a data/artigos.json e arquivos artigos/*.html gerados.
applyTo: "data/artigos.json,artigos/**/*.html,artigo-detalhe.html"
---

# Conteúdo — Artigos

Toda alteração em [`data/artigos.json`](../../data/artigos.json) deve respeitar [`schemas/artigo.schema.json`](../../schemas/artigo.schema.json).

## Regras obrigatórias

1. **Slug**: `kebab-case`, único.
2. **`data_publicacao`**: ISO `YYYY-MM-DD`.
3. **`autor.nome`** sempre preenchido.
4. **`categoria`** entre: `noticias`, `espiritualidade`, `pastoral`, `comunidade`, `formacao`, `evangelho`, `outro`.
5. **`resumo`**: 30–320 caracteres em PT-BR (vira meta description e card).
6. **`conteudo`**: HTML simples (`<p>`, `<h2>`, `<h3>`, `<ul>`, `<blockquote>`, `<a>`). Mínimo 100 caracteres.
7. **`imagem_capa`**: 1200×800 recomendado, com `alt` descritivo.
8. **`publicado: true`** para aparecer no site (rascunhos ficam `false`).

## Workflow

1. Editar [`data/artigos.json`](../../data/artigos.json).
2. `npm run validate-data && npm run build:content && npm run seo`.
3. Commit.

## Boas práticas editoriais

- Frases curtas, voz ativa, linguagem acessível.
- Citações litúrgicas com referência: `(Jo 3,16)`.
- Evitar primeira pessoa do singular (preferir "nossa comunidade").
- Sempre fechar o texto com convite à comunidade.
