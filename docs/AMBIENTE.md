# Arquitetura de Ambiente — Paróquia NSR Jericó/PB

> **Leia antes de fazer qualquer mudança que envolva build, deploy, permissões ou Docker.**

## 1. Visão geral

O projeto tem **dois artefatos** que convivem no mesmo repositório mas são servidos por domínios diferentes:

| Artefato | Local | Produção |
|---|---|---|
| Site estático | `http://localhost:8080` | `https://pascomjerico.com.br` |
| CMS Admin (Laravel/Filament) | `http://localhost:8081` | `https://admin.pascomjerico.com.br` |

```
Repositório git (raiz = httpdocs)
├── *.html, css/, js/, images/, data/ ...  ← site estático
└── cms/                                   ← Laravel 11 + Filament 3
```

O site é **100% estático** — o CMS não é servido ao público. O CMS grava arquivos
nos diretórios do site (`data/*.json`, `artigos/*.html`, `partials/*.html` etc.)
e o site os lê diretamente. Não há requisição PHP no frontend.

---

## 2. Estrutura de pastas (local = produção)

A estrutura dentro do container Docker **replica exatamente** o Plesk Napoleon:

```
/var/www/vhosts/paroquia.local/      (Plesk: /var/www/vhosts/pedisys.com.br/)
  httpdocs/                           ← repo raiz (site estático)
  cms/                                ← Laravel — IRMÃO de httpdocs, fora dele
```

**Por que `cms/` fora de `httpdocs/`?**  
O Plesk cria um diretório pai por domínio. `httpdocs/` é a document root do site
público. O CMS ficando fora impede exposição de `.env`, SQLite, vendor/ etc.

### No servidor (Plesk)

```
/var/www/vhosts/pedisys.com.br/
  httpdocs/    ← pascomjerico.com.br (Apache DocumentRoot :80)
  cms/         ← admin.pascomjerico.com.br aponta para cms/public/ (:443)
```

### Localmente (Docker)

```
/root/dev/paroquiansrjericopb/        ← repo git (bind-mount como httpdocs/)
/root/dev/paroquia-cms/               ← symlink → /root/dev/paroquiansrjericopb/cms/
                                         (bind-mount como cms/)
```

> **Atenção:** O symlink `/root/dev/paroquia-cms` **não está no git** — precisa
> ser criado manualmente em cada máquina de dev:
> ```bash
> ln -sfn /root/dev/paroquiansrjericopb/cms /root/dev/paroquia-cms
> ```

---

## 3. Ambiente local — Docker

### Pré-requisitos

- Docker Engine + Docker Compose v2
- O symlink `/root/dev/paroquia-cms` criado (veja acima)

### Arquivos em `docker-plesk/`

| Arquivo | Função |
|---|---|
| `Dockerfile` | PHP 8.2 + Apache, sem Node.js (igual Plesk), Composer 2.7 |
| `docker-compose.yml` | Bind-mounts, portas, variáveis de ambiente |
| `.env.docker` | `.env` do CMS para o container (APP_URL=localhost:8081, SQLite) |
| `apache/httpdocs.conf` | VirtualHost :80 — site estático |
| `apache/cms.conf` | VirtualHost :81 — CMS Admin |
| `docker-entrypoint.sh` | Roda antes do Apache: aplica `chown www-data` nas pastas de conteúdo |
| `setup.sh` | Inicialização do CMS (uma única vez) |
| `debug-node.sh` | Diagnóstico de ausência de Node.js |

### Primeiro uso (máquina nova)

```bash
# 1. Criar symlink (se não existir)
ln -sfn /root/dev/paroquiansrjericopb/cms /root/dev/paroquia-cms

# 2. Subir e construir a imagem
docker compose -f docker-plesk/docker-compose.yml up -d --build

# 3. Inicializar o CMS (migrations, app key, permissões)
docker compose -f docker-plesk/docker-compose.yml exec plesk bash /setup.sh

# 4. Criar usuário admin do Filament
docker compose -f docker-plesk/docker-compose.yml exec plesk \
  /opt/plesk/php/8.2/bin/php /var/www/vhosts/paroquia.local/cms/artisan \
  make:filament-user
```

### Uso cotidiano

```bash
# Subir (sem rebuild)
docker compose -f docker-plesk/docker-compose.yml up -d

# Parar
docker compose -f docker-plesk/docker-compose.yml down

# Logs Apache em tempo real
docker compose -f docker-plesk/docker-compose.yml logs -f

# Rodar comando Artisan
docker compose -f docker-plesk/docker-compose.yml exec plesk \
  /opt/plesk/php/8.2/bin/php /var/www/vhosts/paroquia.local/cms/artisan <comando>
```

### Rebuild (após alterar Dockerfile ou docker-compose.yml)

```bash
docker compose -f docker-plesk/docker-compose.yml up -d --build --force-recreate
```

### Como as permissões funcionam

O Apache dentro do container roda como **`www-data`**. Os arquivos do bind-mount
chegam com dono `root` (do host). Para evitar `Permission denied` quando o CMS
grava arquivos (ex.: `data/artigos.json`), o `docker-entrypoint.sh` faz
`chown -R www-data:www-data` nas pastas de conteúdo **antes de iniciar o Apache**:

```
data/   artigos/   eventos/   homilias/   images/uploads/   partials/   css/
+ todos os *.html da raiz de httpdocs/
```

Isso garante que `www-data` seja **dono** dessas pastas. Quando o CMS recria um
arquivo (unlink + create), o novo arquivo é criado por `www-data` e continua
acessível em operações subsequentes — sem depender de chmod abertos.

> `chown` não é rastreado pelo git (apenas o bit `x` é relevante para fileMode),
> então isso não contamina o repositório.

---

## 4. Pipeline de build (`npm run all`)

**Sempre rode isso antes de commitar** qualquer alteração em `partials/`, `data/`,
`templates/` ou `seo-data.json`.

```bash
npm run all
# equivale a:
node scripts/validate-data.js   # 1. valida JSONs contra schemas
node scripts/build-content.js   # 2. gera artigos/*.html, eventos/*.html, homilias/*.html, historia.html
node build.js                   # 3. injeta partials (header/footer) em todas as páginas
node scripts/seo-fill.js        # 4. injeta blocos SEO em todas as páginas
```

### Por que a ordem importa

| Passo | O que faz | Depende de |
|---|---|---|
| `validate-data.js` | Valida `data/*.json` contra `schemas/*.schema.json` | JSONs em `data/` |
| `build-content.js` | Gera HTMLs a partir de `templates/` + `data/` | Templates em `templates/`, JSONs |
| `build.js` | Expande `<!-- @include partials/X.html -->` nos HTMLs | `partials/` atualizados |
| `seo-fill.js` | Injeta/atualiza bloco `<!-- @seo-start -->` nos HTMLs | `seo-data.json` |

Se rodar `build.js` **antes** de `build-content.js`, os HTMLs gerados não terão
os partials expandidos. Se rodar `seo-fill.js` antes de `build-content.js`, o
SEO de `historia.html` (gerado pelo build-content) ficará desatualizado.

### Marcadores de template

- Partials: `<!-- @include partials/X.html -->` → expandido para par start/end (idempotente)
- SEO: `<!-- @seo-start -->` ... `<!-- @seo-end -->` (idempotente)
- Sections do CMS: `<!-- @section-start site:loader-logo -->` ... `<!-- @section-end ... -->` (gerenciado pelo CMS via "Publicar Site")

---

## 5. CI/CD — GitHub Actions

### Quando dispara

- Push em qualquer branch: `main`, `developer`, `production`, `chore/**`, `feat/**`, `fix/**`
- Pull request para: `main`, `developer`, `production`

### Jobs

#### `validate` (roda sempre)

```
validate-data.js   → build-content.js   → build.js   → seo-fill.js
→ git status --porcelain  (zero diff obrigatório)
→ smoke test (curl nas páginas principais)
```

> O check de drift (`git status --porcelain`) falha se **qualquer arquivo gerado
> estiver fora de sincronia** com os fontes. É a proteção mais importante —
> garante que o repositório é sempre o estado final correto.

#### `audit` (roda sempre, em paralelo com validate)

- `target="_blank"` sem `rel="noopener"` → erro
- `lang="pt-br"` ausente → warning
- `<title>` vazio → erro

#### `deploy` (só em push para `production`, após validate + audit passarem)

1. **Site estático → `httpdocs/`** via FTP (exclui `cms/`, `node_modules/`, templates, docs)
2. **CMS → pasta `cms/`** (fora de httpdocs) via FTP (exclui `vendor/`, `.env`, SQLite, storage)

### Secrets necessários no GitHub

| Secret | Valor |
|---|---|
| `FTP_SERVER` | IP/hostname do servidor Plesk |
| `FTP_USERNAME` | Usuário FTP do Plesk |
| `FTP_PASSWORD` | Senha FTP |
| `FTP_HTTPDOCS_DIR` | Caminho remoto do httpdocs (ex.: `/httpdocs/`) |
| `FTP_CMS_DIR` | Caminho remoto do cms (ex.: `/../cms/`) |

---

## 6. Produção — Plesk Napoleon

### Servidor

- **Painel**: Plesk Napoleon em `pedisys.com.br`
- **PHP**: `/opt/plesk/php/8.2/bin/php` (caminho fixo — use este em todos os comandos `artisan`)
- **Site público**: `pascomjerico.com.br` → `/var/www/vhosts/pedisys.com.br/httpdocs/`
- **CMS Admin**: `admin.pascomjerico.com.br` → `/var/www/vhosts/pedisys.com.br/cms/public/`

### Comandos pós-deploy no servidor

Após o CI fazer deploy de mudanças no CMS (ex.: novas migrations, novos providers):

```bash
# Acessar via SSH ou terminal do Plesk
cd /var/www/vhosts/pedisys.com.br/cms

/opt/plesk/php/8.2/bin/php artisan config:clear
/opt/plesk/php/8.2/bin/php artisan cache:clear
/opt/plesk/php/8.2/bin/php artisan view:clear

# Se houver novas migrations:
/opt/plesk/php/8.2/bin/php artisan migrate --force
```

### O que o CI **não** faz (requer ação manual no servidor)

- Instalar `vendor/` (Composer) — o servidor já tem; rodar `composer install --no-dev` manualmente se `composer.json` mudar
- Criar/atualizar `.env` de produção (nunca versionado)
- Rodar migrations

---

## 7. CMS ↔ Site estático — como o "Publicar Site" funciona

O Filament Admin tem uma action "Publicar Site" que:

1. Serializa os dados em `data/*.json` (artigos, eventos, homilias, horários, etc.)
2. Regera os HTMLs em `artigos/`, `eventos/`, `homilias/` a partir dos templates
3. Atualiza `partials/header.html` e `partials/footer.html` com logos/configurações salvas no banco
4. Faz `file_put_contents` nesses arquivos diretamente no filesystem de `httpdocs/`

**Não** há `git push` automático — o CMS grava diretamente no disco. Em produção,
as mudanças ficam no servidor sem passar pelo repositório. O repositório deve ser
a fonte de verdade para conteúdo estrutural; o CMS é para conteúdo editorial.

### Armadilha: sync entre CMS e git

Se o CMS alterar `partials/header.html` no servidor e você depois fizer `git pull`
(ou o CI fizer deploy), os partials do git sobrescrevem os do CMS. Para evitar:

- Use `npm run all` antes de qualquer push quando o CMS tiver sido usado no servidor
- Ou copie os arquivos gerados pelo CMS de volta para o repo antes do deploy

---

## 8. Fluxo de trabalho (branches)

```
main        ← template Avenix original (não tocar)
  ↑
developer   ← branch de integração (trabalho novo aqui)
  ↓
production  ← branch de deploy (CI/CD dispara o FTP deploy)
```

### Fluxo padrão

```bash
# 1. Trabalhar em developer
git checkout developer
# ... editar arquivos ...
npm run all           # sempre antes de commitar
git add -A
git commit -m "feat: ..."
git push origin developer

# 2. Promover para production (dispara deploy automático)
git checkout production
git merge developer --no-edit
git push origin production
git checkout developer
```

---

## 9. Armadilhas conhecidas (gotchas)

### `git status` mostra fileMode alterado após entrar no container

O `chown` dentro do container não contamina o git. Mas se você rodar `chmod` nos
arquivos do bind-mount dentro do container, o git no host vai detectar mudança de
modo (644 → 755). Solução:

```bash
# No host, restaurar fileMode
git diff --name-only | xargs git update-index --chmod=-x
git checkout -- .
```

### `npm run all` deve ser rodado antes de qualquer commit

Se você editar `partials/header.html` e não rodar o build, o CI vai falhar no
check de drift (`historia.html`, `eventos/*.html`, etc. ficarão desatualizados).

### O CI não instala dependências do CMS

O `vendor/` do Laravel é excluído do FTP deploy. Se `composer.json` mudar, rodar
manualmente no servidor:

```bash
cd /var/www/vhosts/pedisys.com.br/cms
/opt/plesk/php/8.2/bin/php /usr/local/bin/composer install --no-dev --optimize-autoloader
```

### `seo-fill.js` sobrescreve o bloco `@seo-start`

O `seo-fill.js` **sempre** reescreve o bloco SEO a partir de `seo-data.json`.
Se você editar manualmente o `<title>` de uma página, ele será sobrescrito na
próxima vez que `npm run all` rodar. Edite `seo-data.json` e rode `npm run seo`.

### Páginas geradas por `build-content.js` não devem ser editadas manualmente

`artigos/*.html`, `eventos/*.html`, `homilias/*.html`, `historia.html`:
todos têm `<!-- GENERATED FROM data/... — DO NOT EDIT MANUALLY -->` no topo.
Edite o JSON correspondente e rode `npm run all`.

---

## 10. Checklist antes de fazer push

- [ ] `npm run all` rodou sem erros
- [ ] `git status` mostra apenas arquivos intencionalmente alterados
- [ ] Nenhum `console.log` ou `var_dump` esquecido
- [ ] Imagens novas têm `alt` descritivo, `width`/`height`, `loading="lazy"`
- [ ] Links externos têm `rel="noopener noreferrer"`
- [ ] Nova página adicionada a `seo-data.json` e `sitemap.xml`
