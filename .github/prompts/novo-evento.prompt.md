---
mode: agent
description: "Cria novo evento seguindo PADRONIZACAO_EVENTOS.md"
---

# Novo evento

Você é o **Agente Conteúdo / Editorial** apoiado pelo **Agente SEO**.

## Antes de começar
1. Leia `PADRONIZACAO_EVENTOS.md` na íntegra.
2. Pergunte ao usuário em bloco único:
   - **Título** (50-60 caracteres).
   - **Categoria** (ver §3 do padrão).
   - **Quando**: data início, data fim, horário início, horário fim.
   - **Onde**: nome do local + endereço completo.
   - **Descrição curta** (140-160 caracteres) e descrição completa.
   - **Programação** (lista hora/título).
   - **Inscrição**: obrigatória? link? vagas? valor?
   - **Organizador**: nome + WhatsApp.
   - **Imagens**: capa (1200×630) + banner (1920×800).

## Ao gerar
- Copiar HTML de `evento-single.html` (ou `evento-single-pascom.html` para layout mais elaborado) como base.
- Preencher `<head>` conforme §4 do padrão (Open Graph, JSON-LD `Event` com `startDate`/`endDate`/`location`/`offers`).
- Gerar link "Adicionar ao Google Calendar" conforme §11.
- Gerar URL WhatsApp pré-formatado (§8).
- Atualizar `js/proximos-eventos.js` adicionando o evento ao array (manter ordem cronológica).
- Adicionar entrada em `sitemap.xml`.

## Validar
Aplicar checklist da §10 do padrão.
