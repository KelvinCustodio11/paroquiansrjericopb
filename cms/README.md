# CMS Paroquial — Spike Laravel + Filament

> Status: **esqueleto/spike** — não-instalado. Documenta a arquitetura-alvo
> para o futuro CMS administrativo da Paróquia NSR Jericó.

## Estratégia: Híbrida B (recomendada)

```
Filament Admin (PHP)  ─┐
   ↕ CRUD em DB        │
Banco MySQL/SQLite    ─┤── exporta JSON em data/*.json
                       │       (artisan content:export)
                       └── chama Node build-content.js
                               ↓
                       HTMLs estáticos regerados
                               ↓
                       deploy GitHub Pages / FTP
```

**Por que híbrido:**
- Site continua **estático** (rápido, barato, sem PHP em produção pública).
- Admin roda em **subdomínio separado** (`admin.pascomjerico.com.br`)
  com Laravel + Filament para autenticação, RBAC e UX rica.
- A "single source of truth" continua sendo `data/*.json` versionado em Git
  — exportado pelo Filament. O gerador `scripts/build-content.js`
  permanece intocado.

## Modelos planejados (espelham `schemas/*.schema.json`)

| Model | Schema correspondente | Filament Resource |
|---|---|---|
| `Evento` | `evento.schema.json` | `EventoResource` |
| `Artigo` | `artigo.schema.json` | `ArtigoResource` |
| `Homilia` | `homilia.schema.json` | `HomiliaResource` |
| `HorarioMissa` (+ `Igreja`) | `horarios-missa.schema.json` | `IgrejaResource` (relation: horarios) |
| `Compromisso` | `agenda-pastoral.schema.json` | `CompromissoResource` |
| `Ministerio` | `ministerio.schema.json` | `MinisterioResource` |
| `Paroco` (singleton) | `paroco.schema.json` | `ParocoResource` |

## Comandos artisan customizados (a implementar)

```bash
php artisan content:export        # DB -> data/*.json (validado contra schemas)
php artisan content:import        # data/*.json -> DB (seed/migração inicial)
php artisan content:build         # roda 'node scripts/build-content.js'
php artisan content:publish       # export + build + git commit + push
```

## Stack-alvo

- PHP 8.2+
- Laravel 11
- Filament 3.x (admin panel)
- Spatie/laravel-permission (RBAC: pároco, secretaria, pascom, voluntário)
- League/Csv (importação massiva)
- Banco: MySQL (Plesk) ou SQLite (dev)

## Roadmap próximos PRs

1. `composer create-project laravel/laravel cms` + `composer require filament/filament`
2. Migrations espelhando os 7 schemas
3. Seeders que leem `data/*.json` (migração de dados existentes)
4. Resources Filament com Form/Table espelhando schemas
5. Comando `content:export` validando JSON contra `schemas/*.schema.json`
6. Comando `content:build` chamando `node scripts/build-content.js`
7. Deploy admin em subdomínio + CI que executa build após export

## Por que ainda não foi instalado

Este spike não roda `composer install` para evitar:
- Adicionar `vendor/` ao repositório atual (estático)
- Misturar dependências PHP no repo do site público
- Quebrar GitHub Pages (que não executa PHP)

A próxima sessão deve criar `cms/` como **subrepositório Git separado**
ou pasta com seu próprio `.gitignore` excluindo `vendor/`.

## Decisão de arquitetura registrada

Veja [`SUGESTAO_CMS.md`](../SUGESTAO_CMS.md) para análise completa
das alternativas (A: SSR puro, B: híbrido, C: headless externo).
**Decisão: B (híbrido)**, motivada por custo zero de hospedagem do site
público e pela infra já existente (GitHub Pages + Plesk).
