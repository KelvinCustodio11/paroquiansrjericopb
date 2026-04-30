---
description: Padrão para horários de missa e agenda pastoral. Aplica-se a data/horarios-missa.json e data/agenda-pastoral.json.
applyTo: "data/horarios-missa.json,data/agenda-pastoral.json"
---

# Conteúdo — Agenda e Horários

## Horários de missa (`data/horarios-missa.json`)

Schema: [`schemas/horarios-missa.schema.json`](../../schemas/horarios-missa.schema.json).

- Estrutura: `igrejas[]` → cada igreja tem `horarios[]`.
- `dia_semana`: minúscula, sem acento (`segunda`, `terca`, ..., `domingo`).
- `hora`: `HH:MM` 24h.
- `tipo_celebracao`: `missa` (padrão), `novena`, `adoracao`, `terco`, `outro`.
- `tipo` da igreja: `matriz`, `capela` ou `comunidade`.
- Use `observacao` para detalhes (ex.: "Missa com bênção das crianças").

> ⚠️ **Sempre confirmar com o pároco antes de publicar alterações de horário.**

## Agenda pastoral (`data/agenda-pastoral.json`)

Schema: [`schemas/agenda-pastoral.schema.json`](../../schemas/agenda-pastoral.schema.json).

- `compromissos[]` com `titulo`, `data` (ISO), `tipo` e `publico` (boolean).
- **`publico: true`** → aparece no site público.
- **`publico: false`** → uso interno (pároco/coordenadores).
- Eventos públicos relevantes devem ser cadastrados também em `data/eventos.json` com mais detalhes.

## Workflow

1. Editar JSON.
2. `npm run validate-data`.
3. `npm run build:content` (regenera componentes que listam horários e agenda).
4. Commit.
