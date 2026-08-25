# Dev Guide — Site Paróquia Nossa Senhora dos Remédios (Jericó/PB)

Guia técnico completo do projeto. Toda informação foi extraída da análise direta do código-fonte.

---

## 1. Visão Geral

Site institucional da Paróquia NSR de Jericó (Paraíba), hospedado em `pascomjerico.com.br`. Composto por dois artefatos:

| Artefato | URL | Stack | Pasta |
|---|---|---|---|
| Site estático | `pascomjerico.com.br` | HTML5 + Bootstrap 5 + jQuery + plugins Avenix | raiz (`/`) |
| CMS Admin | `admin.pascomjerico.com.br` | Laravel 11 + Filament 3 + SQLite | `cms/` |

O CMS exporta dados para JSON (`data/*.json`), que são processados por scripts Node.js para gerar HTML estático. O site não usa banco de dados — todo conteúdo dinâmico vem de arquivos JSON servidos via `fetch()` no browser.

---

## 2. Stack Completa

### Frontend (site estático)
- **HTML5** semântico, 21 páginas em PT-BR + stubs de redirect
- **Bootstrap 5** (grid + components)
- **jQuery 3.7.1** + plugins do template Avenix Church
- **Font Awesome 6** (ícones)
- **Google Fonts**: Fira Sans Condensed
- **Plugins de terceiros**: Swiper, Magnific Popup, GSAP + ScrollTrigger, WOW.js, Plyr (áudio/vídeo), SmoothScroll, parallaxie, magiccursor, SplitText, jQuery CounterUp, SlickNav, YTPlayer

### Scripts próprios (js/)
| Arquivo | Função |
|---|---|
| `function.js` | Inicialização do tema: preloader, sticky header, sliders, carousels, animações, formulário de contato |
| `active-nav.js` | Marca link ativo no menu baseado em `<body data-page="...">` |
| `radio-player.js` | Player flutuante de rádio (FAB). Busca Radio Browser API + `data/radios.json`. Suporte a PJAX |
| `liturgia.js` | Liturgia diária via `liturgia.up.railway.app`. Cache localStorage, TTS, compartilhamento |
| `terco.js` | Terço interativo completo com 4 conjuntos de mistérios, SVG, orações |
| `terco-dia.js` | ES module que determina o mistério do dia por dia da semana |
| `santo-dia.js` | Santo do dia via Evangelizo.org + Wikipedia PT. Cache, busca, scraping |
| `calendario-romano.js` | Tabela estática de Santos do Calendário Romano (lookup `M-D`) |
| `horarios-missa.js` | Renderiza horários de missa a partir de `data/horarios-missa.json` |
| `agenda-pastoral.js` | Renderiza compromissos públicos a partir de `data/agenda-pastoral.json` |
| `proximos-eventos.js` | Fallback para seção "próximo evento" quando build não rodou |
| `devocoes-diarias.js` | Controlador de abas (Santo/Terço/Liturgia) na página de devoções |
| `page-views.js` | Contador de visualizações via API do CMS (`/api/page-view`) |
| `contact-form.js` | Handler do formulário: CSRF token via `form-process.php?action=token`, envio via `fetch()` |

### Backend
- **PHP 8.2** (1 endpoint: `form-process.php`)
- **Laravel 11 + Filament 3** (CMS em `cms/`)
- **SQLite** (somente CMS)
- **Composer 2.7**

### Build & Tooling
- **Node.js >= 18** (scripts puros, sem bundler)
- **GitHub Actions** (CI/CD)
- **FTP Deploy** (Plesk)
- **Docker** (simulação local do Plesk)

---

## 3. Estrutura de Diretórios

```
/
├── *.html                      # 21 páginas PT-BR + stubs de redirect EN
├── index.html                  # Página inicial
├── 404.html                    # Página de erro customizada
│
├── partials/                   # Fragmentos reutilizáveis (build in-place)
│   ├── head-css.html           # <head> completo (meta, fonts, CSS)
│   ├── header.html             # Preloader + skip link + navbar
│   ├── footer.html             # Footer completo (4 colunas)
│   └── scripts-common.html     # Todos os scripts JS comuns
│
├── templates/                  # Templates Mustache para geração estática
│   ├── evento.html             # Template de página de evento
│   ├── artigo.html             # Template de página de artigo
│   ├── homilia.html            # Template de página de homilia
│   └── historia.html           # Template da página história (data-driven)
│
├── data/                       # Dados JSON (fonte da verdade)
│   ├── eventos.json            # Eventos (gera eventos/*.html)
│   ├── artigos.json            # Artigos do blog (gera artigos/*.html)
│   ├── homilias.json           # Homilias (gera homilias/*.html)
│   ├── horarios-missa.json     # Horários de missa (fetch runtime)
│   ├── agenda-pastoral.json    # Compromissos pastorais (fetch runtime)
│   ├── ministerios.json        # Ministérios
│   ├── paroco.json             # Dados do pároco
│   ├── configuracoes.json      # Configurações do site (logos, cores, etc.)
│   ├── galeria.json            # Álbuns e fotos
│   ├── historia.json           # Dados da página história
│   ├── menu.json               # Estrutura do menu
│   ├── pastoral.json           # Dados da pastoral
│   ├── radios.json             # Estações de rádio
│   └── testemunhos.json        # Testemunhos
│
├── schemas/                    # JSON Schemas para validação
│   ├── evento.schema.json
│   ├── artigo.schema.json
│   ├── homilia.schema.json
│   ├── horarios-missa.schema.json
│   ├── agenda-pastoral.schema.json
│   ├── ministerio.schema.json
│   └── paroco.schema.json
│
├── scripts/                    # Scripts Node.js de build
│   ├── build-content.js        # Gerador estático (data → HTML via templates)
│   ├── seo-fill.js             # Injetor de SEO (seo-data.json → HTML)
│   ├── validate-data.js        # Validador JSON → Schema
│   ├── migrate-to-partials.js  # Migração one-time para partials
│   └── migrate-urls-pt-br.js   # Migração one-time de URLs EN → PT-BR
│
├── build.js                    # Resolvedor de @include (partials → HTML in-place)
│
├── seo-data.json               # Metadados SEO por página
├── sitemap.xml                 # Sitemap XML
├── robots.txt                  # Robots.txt
├── .htaccess                   # Apache: segurança, cache, redirects
├── form-process.php            # Endpoint PHP de contato
├── package.json                # Configuração Node.js
│
├── css/                        # Estilos
│   ├── bootstrap.min.css       # Bootstrap 5
│   ├── custom.css              # CSS principal (7375 linhas, tema Avenix)
│   ├── theme-cms.css           # Variáveis CSS geradas pelo CMS
│   └── *.css                   # CSS de plugins
│
├── js/                         # Scripts JavaScript
│   ├── jquery-3.7.1.min.js     # jQuery
│   ├── bootstrap.min.js        # Bootstrap 5
│   ├── custom/                 # (ver seção 2 acima)
│   └── *.js                    # Scripts de plugins
│
├── images/                     # Imagens estáticas
│   ├── uploads/                # Uploads (gitignored, exceto logos/)
│   └── *.jpg/png/svg           # Imagens do template
│
├── webfonts/                   # Font Awesome fonts
│
├── eventos/                    # HTMLs gerados de eventos (build-content)
├── artigos/                    # HTMLs gerados de artigos (build-content)
├── homilias/                   # HTMLs gerados de homilias (build-content)
│
├── cms/                        # Laravel + Filament (FORA de httpdocs no Plesk)
│   ├── app/
│   │   ├── Models/             # 18 models Eloquent
│   │   ├── Filament/
│   │   │   ├── Resources/      # 12 resources Filament
│   │   │   ├── Pages/          # 5 páginas custom (PublicarSite, Configuracoes, etc.)
│   │   │   ├── Widgets/        # 5 widgets (analytics, publish, radios)
│   │   │   ├── Clusters/       # Cluster Radio
│   │   │   └── Forms/          # Componente custom (IconPickerField)
│   │   └── Jobs/               # Jobs Laravel
│   ├── database/migrations/    # 30 migrations
│   ├── routes/web.php          # Rotas (welcome, site-media, page-view API)
│   ├── config/site.php         # Config custom (SITE_ROOT)
│   ├── public/                 # Assets Filament (CSS/JS)
│   └── composer.json           # Dependências PHP
│
├── _template-avenix/           # 📚 READ-ONLY — template Avenix original
├── _template-paroquia/         # 📚 READ-ONLY — snapshot pré-sync
│
├── docker-plesk/               # Ambiente Docker local
│   ├── docker-compose.yml      # Containers site + CMS
│   ├── Dockerfile              # PHP 8.2 + Apache (replica Plesk)
│   ├── .env.docker             # Variáveis para o container
│   └── apache/                 # Configs Apache (httpdocs.conf, cms.conf)
│
├── .github/
│   ├── copilot-instructions.md # Instruções gerais para AI
│   ├── instructions/           # Instruções por contexto (7 arquivos)
│   ├── prompts/                # Comandos /comando (6 prompts)
│   ├── workflows/ci.yml        # CI/CD GitHub Actions
│   └── PULL_REQUEST_TEMPLATE.md
│
├── docs/                       # Documentação técnica (7 arquivos)
│   ├── AMBIENTE.md
│   ├── MELHORIAS_GERAIS.md
│   ├── PADRONIZACAO_LAYOUT.md
│   ├── PADRONIZACAO_POST_BLOG.md
│   ├── PADRONIZACAO_EVENTOS.md
│   ├── SUGESTAO_CMS.md
│   └── AGENTES_SKILLS.md
│
└── .vscode/settings.json       # Configurações do editor
```

---

## 4. Pipeline de Build

A pipeline tem 4 etapas, executadas em ordem fixa:

```bash
npm run all   # executa tudo na ordem
```

### 4.1 Validação de Dados
```bash
node scripts/validate-data.js   # ou: npm run validate-data
```
- Valida `data/*.json` contra `schemas/*.schema.json`
- Implementa subset do JSON Schema sem dependências externas
- Mapeamento: `eventos.json → evento.schema.json`, `artigos.json → artigo.schema.json`, etc.
- Exit code 1 se houver erro

### 4.2 Build de Conteúdo (data-driven)
```bash
node scripts/build-content.js   # ou: npm run build:content
```
- Lê `data/{eventos,artigos,homilias}.json`
- Renderiza via mini template engine Mustache (sem dependências)
- Gera `eventos/{slug}.html`, `artigos/{slug}.html`, `homilias/{slug}.html`
- Expande partials inline e reescreve paths relativos com `../`
- Remove arquivos órfãos (slugs que não existem mais)
- Injeta seções dinâmicas em `index.html` e `eventos.html` (marcadores `<!-- @section-start -->`)
- Modo preview: `--preview-stdin --type evento` (lê JSON do stdin)

**Mini template engine Mustache:**
```
{{var}}          → escape HTML
{{{var}}}        → sem escape (HTML raw)
{{#chave}}...{{/}}  → itera array / renderiza se truthy
{{^chave}}...{{/}}  → renderiza se falsy
{{.}}            → item corrente
```

**Enrichers** normalizam dados antes do render:
- `enrichEvento()`: normaliza `imagem_capa` (string→objeto), `local`, horários, `descricao_completa`, `stats_bar`, `galeria`, `programacao`, `sidebar`
- `enrichArtigo()`: normaliza `imagem_capa`, formata datas, prepara tags
- `enrichHomilia()`: normaliza `imagem_capa_url`, formata data, prepara transcção/resumo

### 4.3 Build de Partials
```bash
node build.js   # ou: npm run build
```
- Resolve marcadores `<!-- @include partials/X.html -->` em todos os HTMLs da raiz
- Modo idempotente: expande para `<!-- @include-start X --> ... <!-- @include-end X -->`
- Se já tem os marcadores -start/-end, regenera o miolo
- Edita HTMLs in-place (sem pasta `dist/`)
- Exclui: `_template-avenix/`, `_template-paroquia/`, `partials/`, `templates/`, `node_modules/`

**Workflow para editar header/footer:**
1. Edite o arquivo em `partials/`
2. Rode `node build.js`
3. Comite as mudanças (HTMLs alterados + partial)

### 4.4 SEO Data-Driven
```bash
node scripts/seo-fill.js   # ou: npm run seo
```
- Lê `seo-data.json` e injeta em cada página HTML:
  - `<title>`, `<meta name="description">`, `<meta name="keywords">`
  - `<link rel="canonical">`
  - Open Graph (og:type, og:title, og:description, og:url, og:image, og:locale, og:site_name)
  - Twitter Card
  - JSON-LD: `Church` + `BreadcrumbList` (home) ou `Organization` + `BreadcrumbList` (demais)
- Idempotente: usa marcadores `<!-- @seo-start -->` / `<!-- @seo-end -->`
- Para adicionar nova página: crie entrada em `seo-data.json` antes de rodar

---

## 5. Sistema de Partials e Build In-Place

### Marcadores nas páginas
Cada página HTML contém:
```html
<!-- @include partials/head-css.html -->
<!-- @include partials/header.html -->
<!-- @include partials/footer.html -->
<!-- @include partials/scripts-common.html -->
```

### Após o build
```html
<!-- @include-start partials/header.html -->
[conteúdo do partial]
<!-- @include-end partials/header.html -->
```

### Regra de ouro
**NUNCA** edite o miolo entre `<!-- @include-start -->` e `<!-- @include-end -->`. Edite o partial em `partials/` e rode `node build.js`.

### Seções editáveis (injetadas pelo build-content)
Alguns arquivos HTML têm marcadores de seção que são preenchidos pelo `build-content.js`:

| Arquivo | Marcador | Conteúdo |
|---|---|---|
| `index.html` | `<!-- @section-start index:evento-destaque -->` | Evento destaque |
| `index.html` | `<!-- @section-start index:eventos-grade -->` | Grade de até 3 eventos |
| `eventos.html` | `<!-- @section-start eventos:lista-destaques -->` | Até 2 eventos em destaque |
| `eventos.html` | `<!-- @section-start eventos:lista-grid -->` | Grid de todos os eventos |

### Item de menu ativo
Cada página declara `<body data-page="...">`. O script `js/active-nav.js` lê esse atributo e aplica `class="active"` + `aria-current="page"` no link correspondente do menu.

Valores aceitos: `home`, `historia`, `pastoral`, `eventos`, `agenda`, `liturgia`, `contato`

---

## 6. CMS (Laravel + Filament)

### 6.1 Arquitetura

O CMS é um painel administrativo Laravel 11 + Filament 3 que gerencia o conteúdo do site. A estratégia é **híbrida**: o Filament administra os dados, e um comando `content:export --build` gera os JSONs que o site estático consome.

```
Filament Admin → content:export → data/*.json → build-content.js → HTML estático
```

### 6.2 Models (18)

| Model | Tabela | Campos Principais |
|---|---|---|
| `User` | users | name, email, password |
| `Igreja` | igrejas | slug, nome, endereco, bairro, tipo, ativa |
| `HorarioMissa` | horarios_missa | igreja_id, dia_semana, hora, tipo_celebracao |
| `Ministerio` | ministerios | slug, nome, categoria, descricao, coordenador, imagem, icone, ativo |
| `Paroco` | parocos | nome, saudacao, data_ordenacao, biografia, foto, contato, redes, ativo |
| `Homilia` | homilias | slug, titulo, data, celebrante, ocasiao, leitura_evangelho, resumo, transcricao, audio_url, imagem_capa, publicado |
| `Evento` | eventos | slug, titulo, data_inicio/fim, hora_inicio, local, categoria, status, resumo, conteudo, imagem_capa, stats_bar, galeria, programacao, inscricao, publicado, destaque |
| `Artigo` | artigos | slug, titulo, data_publicacao, autor, categoria, tags, resumo, imagem_capa, conteudo, destaque, publicado |
| `Compromisso` | compromissos | titulo, data, hora, tipo, local, responsavel, publico |
| `Configuracao` | configuracoes | Singleton (id=1). Cores, logos, header_cta, hero, footer, contato, habilitar, radio |
| `GaleriaAlbum` | galeria_albuns | titulo, slug, descricao, capa_imagem, categoria, ordem, publico |
| `GaleriaFoto` | galeria_fotos | album_id, arquivo, legenda, alt, ordem, ativa |
| `Radio` | radios | nome, url, descricao, programacao, favicon, destaque, ativa, ordem, categoria, estado, cidade |
| `RadioBuscaExterna` | radio_buscas_externas | label, tag, pais, estado, regiao, limite, ativo, ordem |
| `MenuItem` | menu_items | titulo, link, icone, page_key, pai_id, ordem, visivel, externo (self-referencing) |
| `Testemunho` | testemunhos | nome, email, cidade, texto, status, consentimento_lgpd |
| `HistoriaPagina` | historia_pagina | Singleton (id=1). SEO, header, about, missao, visao/missao, contadores, servicos, equipe, paroco, valores |
| `PageView` | page_views | pagina, titulo, ip_hash, viewed_at |

### 6.3 Filament Resources (12)

| Resource | Modelo | Grupo |
|---|---|---|
| `EventoResource` | Evento | — |
| `ArtigoResource` | Artigo | — |
| `HomiliaResource` | Homilia | — |
| `CompromissoResource` | Compromisso | — |
| `MinisterioResource` | Ministerio | — |
| `ParocoResource` | Paroco | — |
| `IgrejaResource` | Igreja | — |
| `HistoriaPaginaResource` | HistoriaPagina | Conteúdo |
| `GaleriaAlbumResource` | GaleriaAlbum | Conteúdo |
| `TestemunhoResource` | Testemunho | Conteúdo |
| `MenuItemResource` | MenuItem | Configurações |
| `RadioResource` | Radio | Cluster Radio |
| `RadioBuscaExternaResource` | RadioBuscaExterna | Cluster Radio |

### 6.4 Filament Pages Custom (5)

| Página | Função |
|---|---|
| `PublicarSite` | Executa `content:export --build` para publicar o site estático |
| `Configuracoes` | Form singleton para `Configuracao` (identidade visual, logos, hero, footer, contato) |
| `Visualizacoes` | Analytics de page views (migrado para widget) |
| `Radios` | Página de gerenciamento de rádios |
| `Auth/Login` | Login custom com credenciais padrão: `admin@paroquia.local` / `admin123` |

### 6.5 Filament Widgets (5)

| Widget | Função |
|---|---|
| `VisualizacoesWidget` | Dashboard com gráficos, sorting, filtering, visitantes únicos |
| `PublicarSiteWidget` | Botão rápido de publicação no dashboard |
| `RadiosTabela` | CRUD inline de rádios |
| `RadioBuscaExternaTabela` | Regras de busca externa de rádio |
| `RadioTabs` | Widget combinado com abas para ambas tabelas de rádio |

### 6.6 Rotas (routes/web.php)

```php
Route::get('/', fn () => view('welcome'));
Route::get('/site-media/{path}', /* serve arquivos do site estático via Storage disk */);
Route::post('/api/page-view', [PageViewController::class, 'store']); // API pública
```

### 6.7 Variáveis de Ambiente do CMS

```env
DB_CONNECTION=sqlite
SITE_ROOT=/var/www/vhosts/pedisys.com.br/httpdocs
CORS_ALLOWED_ORIGINS=https://pascomjerico.com.br
```

---

## 7. Dados JSON e Schemas

### 7.1 Arquivos de dados (`data/`)

| Arquivo | Tipo | Consumido por |
|---|---|---|
| `eventos.json` | Array `eventos[]` | `build-content.js` → `eventos/*.html` |
| `artigos.json` | Array `artigos[]` | `build-content.js` → `artigos/*.html` |
| `homilias.json` | Array `homilias[]` | `build-content.js` → `homilias/*.html` |
| `horarios-missa.json` | Objeto `igrejas[].horarios[]` | `js/horarios-missa.js` (runtime fetch) |
| `agenda-pastoral.json` | Objeto `compromissos[]` | `js/agenda-pastoral.js` (runtime fetch) |
| `ministerios.json` | Array `ministerios[]` | Página estática |
| `paroco.json` | Objeto | Página estática |
| `configuracoes.json` | Objeto singleton | `js/radio-player.js`, logos, cores |
| `galeria.json` | Array `albuns[]` | Página galeria |
| `historia.json` | Objeto | `build-content.js` → `historia.html` |
| `menu.json` | Array | Estrutura do menu |
| `radios.json` | Array `radios[]` | `js/radio-player.js` |
| `testemunhos.json` | Array `testemunhos[]` | Página testemunhos |

### 7.2 Schemas de validação

O script `validate-data.js` valida cada JSON contra seu schema correspondente. Implementa um subset do JSON Schema sem dependências externas, suportando:
- `type` (string, number, integer, boolean, array, object, null)
- `required` (campos obrigatórios)
- `properties` (propriedades do objeto)
- `items` (schema dos elementos do array)
- `enum` (valores permitidos)
- `minLength`, `maxLength`, `pattern` (restrições de string)

### 7.3 Regras de dados

**Eventos (`data/eventos.json`):**
- Slug: `kebab-case`, único
- Datas: ISO `YYYY-MM-DD`. Horários: `HH:MM` 24h
- Status: `agendado`, `em-andamento`, `encerrado`, `cancelado`
- Categorias: `liturgico`, `pastoral`, `social`, `formativo`, `festivo`, `outro`
- `descricao_curta`: 20-320 caracteres
- `imagem_capa`: 1200×630 (Open Graph)
- `publicado: false` = rascunho (não gera HTML)

**Artigos (`data/artigos.json`):**
- Slug: `kebab-case`, único
- `data_publicacao`: ISO `YYYY-MM-DD`
- Categorias: `noticias`, `espiritualidade`, `pastoral`, `comunidade`, `formacao`, `evangelho`, `outro`
- `resumo`: 30-320 caracteres
- `conteudo`: HTML simples (p, h2, h3, ul, blockquote, a). Mínimo 100 caracteres

**Homilias (`data/homilias.json`):**
- Slug: `kebab-case`, único
- `data`: ISO `YYYY-MM-DD`
- `celebrante`, `ocasiao`, `leitura_evangelho` (referência + texto)
- `transcricao` ou `resumo` (pelo menos um)
- `audio_url`: opcional

---

## 8. SEO

### 8.1 Arquivo `seo-data.json`

Contém metadados por página: `title`, `description`, `url`, `type`, `image`, `keywords`, `noindex`. Configurações globais em `_global`:
- `site_name`, `site_url`, `default_image`, `locale`, `twitter_card`

### 8.2 JSON-LD gerado

- **Home**: Schema `Church` + `BreadcrumbList`
- **Demais**: Schema `Organization` + `BreadcrumbList`
- **Artigos**: `Article` (via template `templates/artigo.html`)
- **Eventos**: `Event` (via template `templates/evento.html`)
- **Homilias**: `Article` (via template `templates/homilia.html`)

### 8.3 Imagens Open Graph

Padrão: `/images/og-default.jpg` (1200×630). Cada página pode ter `og-image` específica.

### 8.4 Checklist SEO por página

1. `<title>` único, 50-60 caracteres
2. `<meta name="description">` 140-160 caracteres em PT-BR
3. `<link rel="canonical">`
4. Open Graph completo
5. Twitter Card
6. JSON-LD apropriado
7. Adicionada ao `sitemap.xml`

---

## 9. Segurança

### 9.1 Formulário de Contato (`form-process.php`)

- `declare(strict_types=1)`
- CSRF token (sessão + `hash_equals`)
- Honeypot anti-bot
- Rate limiting (3 req/10 min por IP)
- Validação com `filter_var`, sanitização com `htmlspecialchars`/`strip_tags`
- Bloqueio de header injection (rejeita `\r`, `\n`, `bcc:`, `cc:`, `content-type:`)
- `From:` do domínio próprio + `Reply-To:` do remetente
- Resposta JSON estruturada
- Suporte opcional a reCAPTCHA v3

### 9.2 `.htaccess`

- Bloqueio de pastas internas (`_template-avenix/`, `_template-paroquia/`, `.github/`, `partials/`, `templates/`, `schemas/`, `scripts/`, `cms/`)
- Bloqueio de arquivos `.md`, `.yml`, `.env`, `.gitignore`, `.htaccess`, `.log`, `.bak`
- Bloqueio de `.json` fora de `data/`
- Headers de segurança: X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, CSP
- Compressão (mod_deflate)
- Cache HTTP (mod_expires)
- Página 404 customizada
- Redirects 301 (EN → PT-BR)

### 9.3 Headers CSP

```
default-src 'self' https:;
script-src 'self' 'unsafe-inline' 'unsafe-eval' https:;
style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
img-src 'self' data: blob: https:;
font-src 'self' data: https://fonts.gstatic.com;
connect-src 'self' https://liturgia.up.railway.app https://pt.wikipedia.org https://commons.wikimedia.org https://stm10.srvvox.com.br;
media-src 'self' https://stm10.srvvox.com.br;
frame-src https://www.youtube.com https://www.google.com;
```

### 9.4 Checklist de segurança em qualquer PR

- [ ] Sem credenciais hardcoded
- [ ] Inputs validados e sanitizados
- [ ] Output escapado conforme contexto
- [ ] Links externos com `rel="noopener noreferrer"`
- [ ] Sem `console.log` ou `var_dump` esquecidos

---

## 10. Docker (Ambiente Local)

### 10.1 Arquitetura

Simula o Plesk Napoleon (hospedagem compartilhada):

```
http://localhost:8080 → Site estático (= pascomjerico.com.br)
http://localhost:8081 → CMS Admin (= admin.pascomjerico.com.br)
```

Estrutura dentro do container:
```
/var/www/vhosts/paroquia.local/
  httpdocs/   ← repo raiz (bind-mount)
  cms/        ← CMS Laravel (FORA de httpdocs, bind-mount separado)
```

### 10.2 Setup

```bash
# Primeiro uso
docker compose -f docker-plesk/docker-compose.yml up -d --build
docker compose -f docker-plesk/docker-compose.yml exec plesk bash /setup.sh

# Após alterar Dockerfile/docker-compose.yml
docker compose -f docker-plesk/docker-compose.yml up -d --build --force-recreate
```

### 10.3 Dockerfile

- Base: `php:8.2-apache`
- Extensões: pdo_sqlite, zip, mbstring, exif, pcntl, bcmath, gd, intl
- Composer 2.7
- Symlink `/opt/plesk/php/8.2/bin/php` (mesmo caminho do Plesk)
- Módulos Apache: rewrite, headers, expires
- PHP config: upload 10M, memory 256M, timezone `America/Fortaleza`

---

## 11. CI/CD (GitHub Actions)

### 11.1 Workflow (`.github/workflows/ci.yml`)

**Trigger:** push em `main`, `developer`, `production`, `chore/**`, `feat/**`, `fix/**` + PRs

**Job 1: validate**
1. Checkout
2. Setup Node.js 20
3. `node scripts/validate-data.js`
4. `node scripts/build-content.js`
5. `node build.js`
6. `node scripts/seo-fill.js`
7. Verificar `git status --porcelain` (falha se arquivos gerados fora de sincronia)
8. Smoke test: serve HTTP + curl em páginas-chave

**Job 2: audit**
1. Verificar `target="_blank"` sem `rel=noopener`
2. Verificar `lang="pt-br"` em todos os HTMLs
3. Verificar `<title>` vazio

**Job 3: deploy** (só em push para `production`)
1. Build content + partials
2. FTP Deploy site estático → `httpdocs/` (exclui `.git*`, `node_modules/`, `_template-*`, `cms/`, `scripts/`, `templates/`, `schemas/`, `partials/`, `docs/`)
3. FTP Deploy CMS → pasta fora de `httpdocs/` (exclui `.git*`, `vendor/`, `node_modules/`, `.env`, `database.sqlite`, `storage/`)

### 11.2 Branches

```
main → template Avenix original (não tocar)
developer → branch de integração (PRs saem daqui)
production → branch de deploy
```

Fluxo: `developer → PR → developer → merge → production → push → deploy automático`

---

## 12. Design System

### 12.1 Tokens CSS (`:root` em `css/custom.css`)

```css
--primary-color:    #000000;
--secondary-color:  #FFF4F1;
--text-color:       #525252;
--accent-color:     #acaa59;   /* dourado Avenix */
--white-color:      #FFFFFF;
--divider-color:    #FFFFFF26;
--dark-divider-color: #E9E9E9;
--error-color:      rgb(230, 87, 87);
--default-font:     "Fira Sans Condensed", sans-serif;
```

### 12.2 Tipografia

- Fonte principal: **Fira Sans Condensed** (Google Fonts)
- Pesos: 100-900 (regular + italic)
- Headings: `font-weight: 700`, `line-height: 1.2em`

### 12.3 Grid

- Bootstrap 5 grid system
- Breakpoints: 576px (sm), 768px (md), 992px (lg), 1200px (xl)
- Mobile-first

### 12.4 Componentes Padrão

| Classe | Uso |
|---|---|
| `.btn-default` | CTA primário (dourado) |
| `.btn-default.btn-outline` | CTA secundário |
| `.readmore-btn` | Link "leia mais" |
| `.page-header` | Cabeçalho de página com breadcrumb |
| `.section-title` | Título de seção (h3 subtítulo + h2 título) |
| `.image-anime reveal` | Container de imagem com animação de entrada |
| `.wow fadeInUp` | Animação de entrada WOW.js |
| `.text-anime-style-2` | Texto com efeito de animação GSAP |

### 12.5 Regras para novas páginas

1. Seguir template Avenix Church
2. Usar tokens CSS via `var(--*)`
3. Mobile-first, testar em 375/768/1024/1440px
4. HTML5 semântico com landmarks
5. Um único `<h1>` por página
6. Imagens com `alt` + `width`/`height` + `loading="lazy"`
7. Skip link no topo do `<body>`
8. Contraste WCAG AA (4.5:1 mínimo)
9. Respeitar `prefers-reduced-motion`

---

## 13. Scripts Node.js (Detalhes)

### 13.1 `build-content.js`

**Gerador estático data-driven.** O script mais complexo do projeto (500+ linhas).

Responsabilidades:
1. Ler JSON de dados
2. Renderizar templates Mustache
3. Expandir partials inline
4. Reescrever paths relativos (`../`)
5. Injetar seções dinâmicas em `index.html` e `eventos.html`
6. Remover arquivos órfãos

**Enrichers** (funções que normalizam dados antes do render):
- `enrichEvento()`: normaliza `imagem_capa`, `local`, horários, `descricao_completa`, `stats_bar`, `topicos_destaque`, `galeria`, `programacao`, `sidebar_items`, `sidebar_milestones`
- `enrichArtigo()`: normaliza `imagem_capa`, formata datas, prepara `tags_list`
- `enrichHomilia()`: normaliza `imagem_capa_url`, formata data, prepara `transcricao_or_resumo`

**Reescrita de paths:**
O script detecta URLs relativas (href, src, action, url() em CSS) e adiciona `../` para arquivos gerados em subpastas. Não altera: URLs absolutas (`/`, `http://`, `https://`), `#`, `mailto:`, `tel:`.

### 13.2 `seo-fill.js`

Injeta blocos SEO completos (title, meta, OG, Twitter, JSON-LD) em cada página baseado em `seo-data.json`.

JSON-LD gerado:
- `Church` schema (home): nome, url, logo, endereço, telefone, sameAs
- `Organization` schema (demais)
- `BreadcrumbList` schema (todas)

### 13.3 `validate-data.js`

Validador JSON Schema minimalista. Suporta: type, required, properties, items, enum, minLength, maxLength, pattern. Sem dependências externas.

---

## 14. Componentes Dinâmicos (Runtime)

Alguns dados são carregados via `fetch()` no browser, não no build:

### 14.1 Liturgia Diária (`liturgia.js`)
- API: `liturgia.up.railway.app`
- Cache: localStorage com TTL
- Features: cores litúrgicas, divisão de versículos, TTS com karaoke, compartilhamento

### 14.2 Santo do Dia (`santo-dia.js`)
- Fontes primárias: Evangelizo.org → Calendário Romano + Wikipedia PT
- Cache: localStorage
- Features: scraping de artigo completo, extração de imagem, busca por nome

### 14.3 Horários de Missa (`horarios-missa.js`)
- Dados: `data/horarios-missa.json` (fetch)
- Container: `[data-component="horarios-missa"]`
- Auto-detecta profundidade de subdiretório

### 14.4 Agenda Pastoral (`agenda-pastoral.js`)
- Dados: `data/agenda-pastoral.json` (fetch)
- Container: `[data-component="agenda-pastoral"]`
- Atributos: `data-limit`, `data-type`

### 14.5 Player de Rádio (`radio-player.js`)
- Fontes: `data/radios.json` + Radio Browser API
- FAB flutuante com toggle
- Filtros por categoria/estado
- Volume persistente (localStorage)
- PJAX: intercepta links, troca body, re-executa scripts
- Kill-switch: `player_ativo` em `configuracoes.json`

### 14.6 Page Views (`page-views.js`)
- API: CMS `/api/page-view` (POST)
- Deduplicação: localStorage (1× por browser por dia)
- Servidor: 429 se duplicado

---

## 15. Páginas HTML

### 15.1 Páginas Estáticas (21)

| Arquivo | Título | data-page |
|---|---|---|
| `index.html` | Página Inicial | `home` |
| `historia.html` | História da Paróquia | `historia` |
| `paroco.html` | Pároco | `historia` |
| `sacramentos.html` | Pastoral e Sacramentos | `pastoral` |
| `sacramento-detalhe.html` | Detalhe do Sacramento | `pastoral` |
| `ministerios.html` | Ministérios | `pastoral` |
| `ministerio-detalhe.html` | Detalhe do Ministério | `pastoral` |
| `eventos.html` | Eventos e Novidades | `eventos` |
| `evento-detalhe.html` | Detalhe do Evento | `eventos` |
| `evento-single-pascom.html` | Encontro Pascom | `eventos` |
| `agenda-liturgica.html` | Agenda Litúrgica | `agenda` |
| `devocoes-diarias.html` | Devoções Diárias | `liturgia` |
| `artigos.html` | Blog | `historia` |
| `artigo-detalhe.html` | Detalhe do Artigo | `historia` |
| `homilias.html` | Homilias | `liturgia` |
| `homilia-detalhe.html` | Detalhe da Homilia | `liturgia` |
| `campanhas.html` | Campanhas | `eventos` |
| `campanha-detalhe.html` | Detalhe da Campanha | `eventos` |
| `galeria.html` | Galeria | `eventos` |
| `objetos-sagrados.html` | Objetos Sagrados | `historia` |
| `contato.html` | Contato | `contato` |
| `404.html` | Página Não Encontrada | — |

### 15.2 Stubs de Redirect

Arquivos EN que redirecionam para PT-BR:
`about.html → historia.html`, `service.html → sacramentos.html`, `ministries.html → ministerios.html`, `blog.html → artigos.html`, `sermons.html → homilias.html`, `campaign.html → campanhas.html`, `gallery.html → galeria.html`, `contact.html → contato.html`, etc.

Cada stub contém: `<link rel="canonical">` + `<meta http-equiv="refresh">` + `<script>window.location.replace()`

### 15.3 Páginas Geradas (dinâmicas)

| Pasta | Fonte | Template |
|---|---|---|
| `eventos/*.html` | `data/eventos.json` | `templates/evento.html` |
| `artigos/*.html` | `data/artigos.json` | `templates/artigo.html` |
| `homilias/*.html` | `data/homilias.json` | `templates/homilia.html` |

---

## 16. Git e Branches

### 16.1 `.gitignore`

Ignora: `.DS_Store`, `images/uploads/**` (exceto `.gitkeep` e `logos/`), `cms/vendor/`, `cms/node_modules/`, `cms/storage/`, `cms/.env`, `cms/database/database.sqlite`, `node_modules/`, `vendor/`, `dist/`, `build/`, `.env`, `*.pem`, `*.key`, `*.bak`, `preview.html`

### 16.2 Branches

- `main`: template Avenix original (referência de diff, não modificar)
- `developer`: branch de integração
- `production`: branch de deploy (push triggera deploy automático)

### 16.3 PR Template

Checklists obrigatórios:
- Build: `validate-data`, `build:content`, `build` (conforme o que foi alterado)
- Segurança: sem credenciais, inputs validados, links safe
- SEO: title, description, canonical, OG, JSON-LD, sitemap
- Acessibilidade: alt, aria-label, headings, contraste

---

## 17. Instruções para AI (`.github/`)

### 17.1 `copilot-instructions.md`

Regras mestras:
1. Identidade visual única (Avenix Church)
2. Responder em PT-BR
3. Reusar componentes antes de criar
4. Nunca modificar `_template-*`
5. Nunca usar imagens de `_template-avenix/images/`
6. Header/footer em `partials/` → build → commit
7. Menu ativo via `data-page`

### 17.2 Instruções por contexto (7 arquivos)

| Arquivo | Aplica-se a |
|---|---|
| `backend.instructions.md` | `**/*.php` — segurança PHP |
| `frontend.instructions.md` | `**/*.html, **/*.css` — padrões Avenix |
| `javascript.instructions.md` | `js/**/*.js` — padrões JS próprios |
| `conteudo-evento.instructions.md` | `data/eventos.json`, `eventos/*.html` |
| `conteudo-artigo.instructions.md` | `data/artigos.json`, `artigos/*.html` |
| `conteudo-agenda.instructions.md` | `data/horarios-missa.json`, `data/agenda-pastoral.json` |
| `infraestrutura.instructions.md` | Docker, CI/CD, build |

### 17.3 Prompts (6 comandos `/`)

| Comando | Função |
|---|---|
| `/nova-pagina` | Gera nova página seguindo padrão Avenix |
| `/novo-post` | Gera novo artigo seguindo PADRONIZACAO_POST_BLOG |
| `/novo-evento` | Gera novo evento seguindo PADRONIZACAO_EVENTOS |
| `/auditar-seo` | Audita SEO da página atual |
| `/revisar-seguranca` | Revisa segurança do arquivo atual |
| `/revisar-acessibilidade` | Checa WCAG 2.1 AA |

---

## 18. Comandos Essenciais

```bash
# Build completo (validar → construir conteúdo → construir partials → SEO)
npm run all

# Ou individualmente:
npm run validate-data      # Valida JSON contra schemas
npm run build:content      # Gera HTMLs dinâmicos (eventos/artigos/homilias)
npm run build              # Expande partials nos HTMLs
npm run seo                # Injeta SEO em todas as páginas

# Docker
docker compose -f docker-plesk/docker-compose.yml up -d --build
docker compose -f docker-plesk/docker-compose.yml exec plesk bash /setup.sh

# CMS (dentro do container ou localmente)
php artisan content:export --build   # Exporta dados → JSON + gera HTML
```

---

## 19. URLs de Externas

### APIs consumidas em runtime (fetch no browser)
- `liturgia.up.railway.app` — Liturgia diária
- `publication.evangelizo.ws` — Santo do dia
- `pt.wikipedia.org` — Fallback santo do dia
- `commons.wikimedia.org` — Imagens de santos
- `stm10.srvvox.com.br` — Streams de rádio

### Recursos estáticos externos
- Google Fonts (Fira Sans Condensed)
- Font Awesome 6 (CSS + webfonts)

---

## 20. Armadilhas Conhecidas

1. **Nunca rodar `npm run build` sem antes rodar `npm run build:content`** — os HTMLs gerados seriam sobrescritos
2. **Arquivos em `eventos/`, `artigos/`, `homilias/` são gerados** — não edite manualmente
3. **`seo-data.json` controla o SEO** — edite lá, não nos HTMLs diretamente
4. **`data/configuracoes.json` é um singleton** — CMS exporta com id=1
5. **O CMS fica FORA de `httpdocs/`** — tanto no Docker quanto no Plesk
6. **Imagens de upload ficam em `images/uploads/`** — gitignored (exceto logos)
7. **`preview.html` é gitignored** — usado para testes locais
8. **O build é in-place** — não cria pasta `dist/`, edita os HTMLs na raiz
9. **Partials têm marcadores `<!-- @section-start -->`** — editáveis pelo `build-content.js`
10. **O `radio-player.js` faz PJAX** — intercepta navegação e re-executa scripts

---

## 21. Roadmap (Backlog Conhecido)

Baseado em `docs/MELHORIAS_GERAIS.md`:
- PWA (manifest + service worker)
- Sistema de doações online
- Live streaming de missas
- Google Analytics / Plausible
- Radio player: melhorias (equalizer visual, favoritos)
- Testemunhos gerenciáveis pelo CMS
- Leitor da Bíblia
- História da paróquia via CMS
- Pastoral via CMS
- Contato via CMS (gerenciamento de mensagens)
- Imagens WebP com fallback
- Lazy-load de imagens
- Bundling de CSS/JS
- CDN para assets estáticos
- Cache HTTP agressivo
- Acessibilidade: skip links, aria-labels, landmarks (parcialmente implementado)
- Formulário: PHPMailer/SMTP em vez de `mail()`
- Logging persistente
