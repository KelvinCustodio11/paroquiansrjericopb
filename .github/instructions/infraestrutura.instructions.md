---
applyTo: "docker-plesk/**,docker-plesk/**/*,.github/workflows/**,build.js,scripts/**,package.json,docs/AMBIENTE.md"
description: "Arquitetura de ambiente, Docker, CI/CD e pipeline de build"
---

Leia o arquivo [docs/AMBIENTE.md](../../docs/AMBIENTE.md) antes de propor qualquer mudança neste contexto.

## Resumo dos pontos críticos

### Docker local
- Container: `paroquia-plesk-sim`, PHP 8.2 + Apache (sem Node.js — igual ao Plesk)
- Site: `:8080` → bind-mount do repo raiz como `httpdocs/`
- CMS: `:8081` → bind-mount de `/root/dev/paroquia-cms/` (symlink → `cms/`) como irmão de `httpdocs/`
- `docker-entrypoint.sh` faz `chown -R www-data` nas pastas de conteúdo — **não usar `chmod 777`**
- Após alterar `Dockerfile` ou `docker-compose.yml`: `docker compose -f docker-plesk/docker-compose.yml up -d --build --force-recreate`

### Pipeline de build (ordem obrigatória)
```
validate-data.js → build-content.js → build.js → seo-fill.js
```
`npm run all` executa tudo na ordem correta. **Sempre rodar antes de commitar.**

### CI/CD
- Push em `production` → deploy FTP automático (site → `httpdocs/`, CMS → `cms/` fora de httpdocs)
- O check `git status --porcelain` no CI falha se arquivos gerados estiverem fora de sincronia
- PHP no servidor: `/opt/plesk/php/8.2/bin/php` (caminho fixo do Plesk Napoleon)

### Fluxo de branches
```
developer → (npm run all + commit) → push developer → merge production → push production
```
