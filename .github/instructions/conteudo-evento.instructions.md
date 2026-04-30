---
description: Padrão de criação/edição de eventos. Aplica-se a data/eventos.json e arquivos eventos/*.html gerados.
applyTo: "data/eventos.json,eventos/**/*.html,evento-detalhe.html"
---

# Conteúdo — Eventos

Toda alteração em [`data/eventos.json`](../../data/eventos.json) deve respeitar [`schemas/evento.schema.json`](../../schemas/evento.schema.json) e o padrão visual do template Avenix Church.

## Regras obrigatórias

1. **Slug**: `kebab-case`, sem acentos, sem espaços, único no array `eventos[]`.
2. **Datas**: ISO `YYYY-MM-DD`. Horários `HH:MM` em 24h. `fuso_horario` = `America/Recife`.
3. **`status`** entre: `agendado`, `em-andamento`, `encerrado`, `cancelado`.
4. **`categoria`** entre: `liturgico`, `pastoral`, `social`, `formativo`, `festivo`, `outro`.
5. **`descricao_curta`**: 20–320 caracteres, frase completa em PT-BR (usada em meta description e cards).
6. **`imagem_capa`**: 1200×630 (Open Graph). Sempre informar `alt` descritivo.
7. **Local**: pelo menos `nome`, `cidade`, `estado` (sigla 2 letras).
8. **Programação**: array opcional `[{hora, titulo}]` ordenado cronologicamente.

## Workflow

1. Editar [`data/eventos.json`](../../data/eventos.json).
2. Validar: `npm run validate-data`.
3. Regenerar HTMLs: `npm run build:content` (gera `eventos/{slug}.html` e atualiza `eventos.html`).
4. Atualizar SEO: `npm run seo`.
5. Commit dos arquivos `data/eventos.json` + HTMLs gerados.

## Não faça

- Não edite `eventos/{slug}.html` manualmente — será sobrescrito.
- Não invente campos fora do schema (rejeitados na validação).
- Não use HTML inline pesado em `descricao_completa`; prefira parágrafos `<p>` simples e listas `<ul>`.
- Não use imagens da pasta `_template-avenix/images/` (licença ThemeForest).
