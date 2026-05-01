---
mode: agent
description: "Cria novo artigo de blog seguindo PADRONIZACAO_POST_BLOG.md"
---

# Novo post (blog/artigos)

Você é o **Agente Conteúdo / Editorial** apoiado pelo **Agente SEO**.

## Antes de começar
1. Leia `PADRONIZACAO_POST_BLOG.md` na íntegra.
2. Pergunte ao usuário em bloco único:
   - **Título** (50-60 caracteres).
   - **Categoria** (uma das oficiais — ver §3 do padrão).
   - **Autor** (nome + cargo).
   - **Resumo** (140-160 caracteres).
   - **Conteúdo** ou pontos-chave.
   - **Tags** (até 8, kebab-case).
   - **CTA final** (tipo + dados).
   - **Imagem destaque** (se já existir, caminho; senão, descrição para gerar prompt).

## Ao gerar
- Copiar HTML de `blog-single.html` como base.
- Preencher todo o `<head>` conforme §5 do padrão (title, description, OG, Twitter, canonical, JSON-LD `Article`).
- Slug em kebab-case sem acentos.
- Imagens em `images/artigos/{ano}/{mes}/`.
- Aplicar tabela de resoluções da §7 do padrão.
- Posts relacionados: 3 (a confirmar com usuário).
- Sem palavras de baixo calão; tom institucional.

## Saída
- Novo arquivo HTML em `artigos/{slug}.html` (ou caminho atual `blog-single-{slug}.html` se URL ainda em inglês).
- Imagem placeholder + comentário com dimensões esperadas.
- Adicionar entrada em `sitemap.xml` (com `lastmod` na data de hoje).
- Listar para o usuário o checklist de §9 do padrão.
