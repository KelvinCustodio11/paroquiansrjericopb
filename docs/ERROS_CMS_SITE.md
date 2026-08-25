# Diagnóstico Completo de Erros — Projeto Paróquia NSR Jericó/PB

> **Revisão técnica:** a auditoria realizada em agosto de 2026 confirmou a maior parte dos apontamentos, mas alguns itens estavam desatualizados. A galeria de eventos já é exportada como objetos; `santo-dia.js` já protege `summary` nulo; o schema atual não possui mais o pattern `08:00`; e o problema de `Mc` duplicado existe em `liturgia.js`, não foi encontrado em `calendario-romano.js`. Também foram identificados problemas adicionais no exportador de Evento, no pipeline Node, nos landmarks HTML, no sitemap e no fluxo de deploy. Correções devem ser aplicadas nas fontes e os HTMLs gerados devem ser regenerados.

Revisão total do projeto: CMS, site estático, JavaScript, HTML, schemas, dados, infraestrutura.

---

## SEÇÃO 1: CMS ↔ Site (Exportação de Dados)

### 1.1 `/storage/` — Imagens da página História nunca são resolvidas (GRAVE)

**Causa raiz:** Duas falhas combinadas.

**Falha 1 — Model exporta com `/storage/`:**
`cms/app/Models/HistoriaPagina.php:157-158,168,193-194`
```php
'about_imagem1' => '/storage/' . ltrim((string) $this->about_imagem1, '/')
'missao_imagem' => '/storage/' . ltrim((string) $this->missao_imagem, '/')
'paroco_imagem' => '/storage/' . ltrim((string) $this->paroco_imagem, '/')
'paroco_assinatura' => '/storage/' . ltrim((string) $this->paroco_assinatura, '/')
```

**Falha 2 — `buildSinglePageTemplates()` NÃO chama `resolveStorageAsset()`:**
`cms/app/Console/Commands/ContentBuildPhp.php:965-991`
```php
foreach ($plan as $p) {
    $data = json_decode(file_get_contents($dataPath), true) ?? [];
    $tpl  = file_get_contents($tplPath);
    $rendered = $this->render($tpl, $data);  // ← render direto, sem resolver paths
    // ...
}
```

Enquanto eventos/artigos/homilias passam por `enrichEvento()`/`enrichArtigo()`/`enrichHomilia()` que chamam `resolveStorageAsset()`, **o historia.json vai direto do JSON para o template** sem nenhuma resolução de caminho.

**Efeito:** `data/historia.json` contém `/storage/uploads/historia/imagem1.jpg`. O template renderiza `src="/storage/uploads/historia/imagem1.jpg"`. O browser busca essa URL, mas não existe rota `/storage/` no site estático → **5 imagens quebradas**.

**Afeta ambos os pipelines:** Node.js (`scripts/build-content.js:933`) e PHP (`ContentBuildPhp.php:976`) — ambos passam o JSON direto para o template com `enrich: function(data) { return data; }` (identity function, sem resolução).

**Correção (2 opções):**

Opção A — No model, exportar sem `/storage/`:
```php
'about_imagem1' => $this->about_imagem1 ? 'storage/' . ltrim((string) $this->about_imagem1, '/') : '',
```

Opção B — No `buildSinglePageTemplates()`, resolver os paths (afeta ambos pipelines):
```php
// PHP (ContentBuildPhp.php):
$storageFields = ['about_imagem1','about_imagem2','missao_imagem','paroco_imagem','paroco_assinatura'];
foreach ($storageFields as $f) {
    if (!empty($data[$f]) && str_starts_with($data[$f], '/storage/')) {
        $data[$f] = $this->resolveStorageAsset($data[$f], 'historia');
    }
}

// Node (build-content.js):
const STORAGE_FIELDS = ['about_imagem1','about_imagem2','missao_imagem','paroco_imagem','paroco_assinatura'];
for (const f of STORAGE_FIELDS) {
    if (data[f] && data[f].startsWith('/storage/')) {
        data[f] = resolveStorageAsset(data[f], 'historia');
    }
}
```

A Opção B é mais segura porque resolve o caminho e **copia o arquivo** para `images/uploads/historia/`.

---

### 1.2 Galeria de eventos: formato incompatível (MÉDIO)

**Local:** `cms/app/Models/Evento.php:75` vs `cms/app/Console/Commands/ContentBuildPhp.php:353-358`

O model exporta `galeria_imagens` como array de **strings**, mas o `ContentBuildPhp` espera array de **objetos** `{url, alt}`. `$img['url']` é null quando `$img` é string → galeria quebrada.

**Correção:** Normalizar `galeria_imagens` para objetos antes de exportar, ou ajustar o `ContentBuildPhp`.

---

### 1.3 Observers faltando — Configuracao e RadioBuscaExterna (MÉDIO)

**Local:** `cms/app/Providers/AppServiceProvider.php:30-43`

`Configuracao` e `RadioBuscaExterna` não têm observer → mudanças no CMS não disparam rebuild automático.

**Correção:** Adicionar `Configuracao::observe($observer)` e `RadioBuscaExterna::observe($observer)`.

---

### 1.4 Evento — `$fillable` incompleto (GRAVE)

**Local:** `cms/app/Models/Evento.php:15-46`

A migration `2026_05_01_100000_add_missing_fields_to_eventos_table.php` adiciona 4 colunas que **não estão em `$fillable`**:
- `titulo_destaque`
- `local_maps`
- `descricao_curta`
- `descricao_completa`

**Resultado:** Esses campos são ignorados em qualquer mass-assignment (Filament forms, `create()`, `update()`).

---

### 1.5 Evento — `array_filter` inconsistente (MÉDIO)

**Local:** `cms/app/Models/Evento.php:106`

Usa `fn ($v) => $v !== null` (mantém strings vazias), enquanto todos os outros models usam `fn ($v) => $v !== null && $v !== ''`. Campos como `hora_inicio`, `local`, `resumo` exportam como `""` quando vazios.

---

### 1.6 URLs hardcoded `localhost` no Filament (GRAVE)

**Local:** `HomiliaResource.php:102`, `EventoResource.php:402`, `ArtigoResource.php:184`

Botões de preview apontam para `http://localhost:3000/...` — **quebra em produção**.

**Correção:** Usar `config('app.url')` ou variável de ambiente.

---

### 1.7 Credenciais de login hardcoded (MÉDIO)

**Local:** `cms/app/Filament/Pages/Auth/Login.php:20-21`

Credenciais padrão `admin@paroquia.local` / `admin123` expostas no código.

**Correção:** Usar variáveis de ambiente ou criar usuário via seed.

---

### 1.8 Resources sem `$navigationGroup` (BAIXO)

6 resources não têm grupo de navegação: `ParocoResource`, `CompromissoResource`, `HomiliaResource`, `MinisterioResource`, `EventoResource`, `ArtigoResource`, `IgrejaResource`. Aparecem soltos na sidebar.

---

### 1.9 GaleriaAlbum — sem guard `relationLoaded` (BAIXO)

**Local:** `cms/app/Models/GaleriaAlbum.php:42`

Acessa `$this->fotos` sem verificar `relationLoaded('fotos')`. Pode causar N+1 queries se chamado sem eager loading.

---

### 1.10 `ContentBuildPhp.php` — `imgPath()` recebe array após enriquecimento (GRAVE)

**Local:** `cms/app/Console/Commands/ContentBuildPhp.php:517,522,624`

`enrichEvento()` converte `imagem_capa` de string para `{url, alt}`. Depois `ourEventHtml()` e `buildIndexGrade()` passam esse array para `imgPath()`, que faz `(string) $img` — produzindo `"Array"` em vez de um path válido. **2 imagens de eventos quebradas**.

### 1.11 `ContentBuildPhp.php` — `formatDateBR()` sem bounds check no mês (MÉDIO)

**Local:** `cms/app/Console/Commands/ContentBuildPhp.php:1135`

Se `$mo` for `0` (data malformada como `2026-00-15`) ou `>12`, `self::MESES[$mo]` lança `E_WARNING: Undefined array key`.

### 1.12 `ContentBuildPhp.php` — `json_decode` null não verificado (MÉDIO)

**Local:** `cms/app/Console/Commands/ContentBuildPhp.php:78,441,766`

Se o JSON estiver corrompido, `json_decode()` retorna `null`. Acessar `$data[$p['collection']]` ou `['eventos']` em `null` lança warning. Deveria usar `json_decode(...) ?? []`.

### 1.13 `ContentExport.php` — sem null check em `current()` (BAIXO)

**Local:** `cms/app/Console/Commands/ContentExport.php:128,209`

`Configuracao::current()` e `HistoriaPagina::current()` usam `firstOrCreate` que deveria sempre retornar instância, mas se a tabela não existir (deploy limpo), lança exceção não tratada.

---

## SEÇÃO 2: Dados e Schemas

### 2.1 Dados de teste em produção (GRAVE)

**Local:** `data/eventos.json`

- Linha 37: `"testando123"` em `topicos_destaque`
- Linha 38: `"treewrterterwt43214"` em `topicos_destaque`
- Linha 115: `slug: "plenaria2134"` com título `"Plenária e Deliberações qew"`
- Linha 118: descrição terminando com `".eqr"`
- Linha 220: `"teste123"` no segundo evento

### 2.2 Schema do Evento com nomes divergentes (MÉDIO)

| Schema espera | Modelo/JSON usam |
|---|---|
| `horario_inicio` | `hora_inicio` |
| `descricao_completa` | `conteudo` |

Schema não cobre: `subtitulo`, `stats_bar`, `galeria`, `sidebar_*`, `programacao_*`, `publicado`. Schema tem campos ausentes no model: `horario_fim`, `fuso_horario`, `recorrencia`, `organizador`, `descricao_curta`.

### 2.3 `programacao[].hora` viola pattern do schema (MÉDIO)

**Local:** `data/eventos.json:56-135`

Todos os valores usam formato `"08h00"` mas o schema espera `"^\\d{2}:\\d{2}$"` (ex: `08:00`).

### 2.4 `imagem_capa` tipo inconsistente (BAIXO)

Artigo e Homilia exportam como **objeto** `{url, alt}`, mas JSONs podem ter como **string**. Schema aceita ambos.

### 2.5 `data/test.tmp` — arquivo temporário no repositório (MÉDIO)

**Local:** `data/test.tmp`

Conteúdo: `test`. Arquivo temporário que deveria ser removido.

### 2.6 `data/historia.json` — dados de teste extensivos em produção (GRAVE)

**Local:** `data/historia.json`

| Campo | Valor |
|---|---|
| `meta_titulo` | Filler latino |
| `page_titulo` | `"TITULO_UNICO_DE_TESTE_ABC123"` |
| `breadcrumb_atual` | `"BREADCRUMB_UNICO_TESTE_ABC123"` |
| `about_intro1/2` | Filler latino |
| `missao_texto` | Filler latino |
| `paroco_texto` | Filler latino |

### 2.7 `data/paroco.json` — dados placeholder em produção (MÉDIO)

**Local:** `data/paroco.json`

| Campo | Valor |
|---|---|
| `nome` | `"A definir"` |
| `data_ordenacao` | `"1900-01-01"` (data sentinela) |
| `data_inicio_paroquia` | `"1900-01-01"` |
| `biografia` | `"Biografia do pároco a ser preenchida..."` |
| `foto` | `images/uploads/demo/paroco.jpg` (pasta demo) |

### 2.8 `data/artigos.json` — 4 artigos são lorem ipsum fake (MÉDIO)

**Local:** `data/artigos.json`

| Slug | Autor fake | Status |
|---|---|---|
| `artigo-recente` | "Leonora Lynch Sr." | Lorem ipsum |
| `artigo-antigo` | "Guido Hudson" | Lorem ipsum |
| `artigo-de-teste` | "Carmine Hamill IV" | Lorem ipsum |
| `artigo-campos-test` | "Kali Kuhlman PhD" | Lorem ipsum |

Apenas `boas-vindas-novo-site` é conteúdo real.

### 2.9 `data/homilias.json` — primeira homilia é teste (MÉDIO)

**Local:** `data/homilias.json`

`homilia-do-domingo-test` — autor fictício "Nicholas Feeney", conteúdo em latim. Apenas `homilia-pascoa-2026` é real.

### 2.10 `data/` — imagens demo referenciadas (BAIXO)

**Local:** `data/artigos.json`, `data/homilias.json`, `data/paroco.json`

6 referências a `images/uploads/demo/` — pasta de demonstração do template Avenix. Imagens provavelmente não existem no deploy.

---

## SEÇÃO 3: HTML e Templates

### 3.1 Footer — links de sacramentos quebrados (GRAVE)

**Local:** `partials/footer.html:46-50`

Todos os links de sacramentos apontam para `href="#"` (batismo, eucaristia, crisma, matrimônio, unção dos enfermos).

### 3.2 Footer — política de privacidade quebrada (MÉDIO)

**Local:** `partials/footer.html:90`

Link aponta para `href="#"`.

### 3.3 Template Historia — ID duplicado `main-content` (GRAVE)

**Local:** `templates/historia.html:27,49`

Dois elementos compartilham `id="main-content"` — HTML inválido.

### 3.4 Template Artigo — `data-page="historia"` errado (MÉDIO)

**Local:** `templates/artigo.html:46`

Artigos deveriam ter `data-page` dedicado, não `"historia"`.

### 3.5 Template Artigo — breadcrumb sem `<nav aria-label>` (MÉDIO)

**Local:** `templates/artigo.html:61-65`

Inconsistente com `evento.html` que usa `<nav aria-label="breadcrumb">`.

### 3.6 `index.html` — sem `<main>` landmark (GRAVE)

**Local:** `index.html`

Não há elemento `<main>` envolvendo o conteúdo. Skip link aponta para `#main-content` mas não existe landmark.

### 3.7 `index.html` — texto placeholder "Resumo" (MÉDIO)

**Local:** `index.html:1070`

Texto `"Resumo"` de teste visível na página.

### 3.8 `index.html` — typo "A Parquía" (MÉDIO)

**Local:** `index.html:269`

Deveria ser "A Paróquia".

### 3.9 `contato.html` e `404.html` — `radio-player.js` duplicado (GRAVE)

**Local:** `contato.html:456`, `404.html:324`

O script já está em `scripts-common.html` e é incluído novamente → inicialização dupla.

**Expandido:** Este erro afeta **18 páginas** — todas as que incluem `scripts-common.html` + script adicional: `paroco.html:478`, `sacramentos.html:702`, `sacramento-detalhe.html:516`, `ministerios.html:334`, `ministerio-detalhe.html:588`, `eventos.html:507`, `evento-detalhe.html:639`, `evento-single-pascom.html:592`, `artigos.html:526`, `artigo-detalhe.html:389`, `homilias.html:606`, `homilia-detalhe.html:415`, `campanhas.html:624`, `campanha-detalhe.html:644`, `galeria.html:390`, `objetos-sagrados.html:376`.

### 3.10 Seções ocultas com dead links (BAIXO)

**Local:** `index.html:336-1358`

Múltiplas seções com `display:none` contêm `href="#"` e conteúdo placeholder.

### 3.11 `contato.html` — iframe Google Maps sem `title` (MÉDIO)

**Local:** `contato.html:316`

Violação de acessibilidade para iframes.

### 3.12 Templates — prefixo `..{{campo}}` frágil (MÉDIO)

**Local:** `templates/evento.html:93,158,161`, `artigo.html:89`, `homilia.html:78,88`

Padrão `src="..{{url}}"` depende da estrutura de diretórios de saída.

### 3.13 `historia.html` — testo de teste no h1 e breadcrumb (GRAVE)

**Local:** `historia.html:163,167`

- `TITULO_UNICO_DE_TESTE_ABC123` visível no `<h1>`
- `BREADCRUMB_UNICO_TESTE_ABC123` visível no breadcrumb

### 3.14 `historia.html` — CSS class usado como `<img src>` (GRAVE)

**Local:** `historia.html:478,490,502`

```html
<img src="fas fa-church" alt="...">
<img src="fas fa-hands" alt="...">
<img src="fas fa-book" alt="...">
```

Classes CSS passadas como caminho de imagem → 3 imagens quebradas.

### 3.15 `historia.html` — lorem ipsum na seção "Sobre Nós" (MÉDIO)

**Local:** `historia.html:207-208`

Parágrafos com texto em latim visíveis na página.

### 3.16 `eventos.html` — dados de teste "4321" (GRAVE)

**Local:** `eventos.html:182,190,203,293,300-301`

Eventos com título `"Assembleia Forânea 4321"` e `alt="Assembleia Forânea 4321"`. Texto `"Resumo"` como placeholder.

### 3.17 `campanha-detalhe.html` — sinais de dólar em vez de R$ (MÉDIO)

**Local:** `campanha-detalhe.html:209,428-454`

Valores doados exibem `$` em vez de `R$`. Botão de doação diz "Ouça agora" em vez de algo como "Doar agora".

### 3.18 `campanhas.html` — texto "Our Campaigns" em inglês (MÉDIO)

**Local:** `campanhas.html:162`

Tabela `<h1>Our Campaigns</h1>` em inglês em site em PT-BR.

### 3.19 `campanhas.html` — texto corrompido "Doa00e700f5es" (GRAVE)

**Local:** `campanhas.html:215,264,313,362,411,460`

Provável erro de encoding — texto deveria ser "Doações" mas aparece como `Doa00e700f5es`.

### 3.20 `homilias.html` — placeholder "John Due" e dead links (MÉDIO)

**Local:** `homilias.html:187-445`

6 sermões com `href="#"`, autor fictício "John Due", categoria "Pray" — tudo em inglês.

### 3.21 `homilia-detalhe.html` — audio src é URL externa demo (GRAVE)

**Local:** `homilia-detalhe.html:188`

```html
<source src="https://demo.awaikenthemes.com/assets/videos/avenix-audio.mp3">
```

URL de demonstração do template — áudio não funcionará.

### 3.22 `galeria.html` — todas as imagens sem `alt` (BAIXO)

**Local:** `galeria.html:187-247`

6 imagens de galeria com `alt=""` — acessibilidade ruim.

### 3.23 `artigos.html` — blog items com dead links (MÉDIO)

**Local:** `artigos.html:215,243,271,299,327,355`

5 itens de blog com `href="#"`.

### 3.24 `ministerio-detalhe.html` — lorem ipsum + texto em inglês (MÉDIO)

**Local:** `ministerio-detalhe.html:435,443`

- `<h3>location</h3>` — texto em inglês
- Lorem ipsum no campo de localização

### 3.25 `sacramento-detalhe.html` — breadcrumb link incorreto (BAIXO)

**Local:** `sacramento-detalhe.html:166`

Breadcrumb "Pastoral" aponta para `./` em vez de `sacramentos.html`.

### 3.26 `data-page` errado em 11 páginas (MÉDIO)

| Página | `data-page` atual | `data-page` correto |
|---|---|---|
| `paroco.html:96` | `historia` | `paroco` |
| `artigos.html:96` | `eventos` | `artigos` |
| `artigo-detalhe.html:96` | `eventos` | `artigos` |
| `homilias.html:96` | `eventos` | `homilias` |
| `homilia-detalhe.html:96` | `eventos` | `homilias` |
| `campanhas.html:96` | `eventos` | `campanhas` |
| `campanha-detalhe.html:96` | `eventos` | `campanhas` |
| `galeria.html:96` | `eventos` | `galeria` |
| `objetos-sagrados.html:96` | `historia` | `eventos` |
| `testemunhos.html:72` | `home` | `testemunhos` |
| `404.html:91` | *(ausente)* | `404` |

### 3.27 `artigo-detalhe.html` — conteúdo incompatível com título (MÉDIO)

**Local:** `artigo-detalhe.html:194-215`

Título: "Abraçando o Perdão" mas conteúdo é sobre "Missa de Natal".

---

## SEÇÃO 4: JavaScript

### 4.1 `santo-dia.js` — TypeError em null summary (GRAVE)

**Local:** `js/santo-dia.js:370,396,732,771`

Múltiplos caminhos chamam `validator(summary)` ou acessam `summary.thumbnail` quando `summary` é `null` (fetch falhou). **Vai crashar** quando a API da Wikipédia estiver lenta ou fora.

### 4.2 `function.js` — form data sem encoding (GRAVE)

**Local:** `js/function.js:293`

Dados do formulário montados por concatenação crua: `"fname=" + fname`. Caracteres especiais (`&`, `=`, `+`) quebram a requisição.

**Correção:** Usar `encodeURIComponent()` ou `$.param()`.

### 4.3 `function.js` — Swiper e Plyr sem DOM check (MÉDIO)

**Local:** `js/function.js:45-57,129`

`new Swiper(...)` e `new Plyr('#player')` chamados incondicionalmente. Se o elemento não existir, lançam erro.

### 4.4 `function.js` — $.ajax sem error handler (MÉDIO)

**Local:** `js/function.js:290`

Submissão do formulário de contato não tem `.fail()` — falhas de rede são silenciosas.

### 4.5 `liturgia.js` — loadWidget sem validação (MÉDIO)

**Local:** `js/liturgia.js:1044-1055`

`loadWidget()` não chama `validateData()` antes de salvar em cache. Respostas malformadas da API ficam cacheadas permanentemente.

### 4.6 `radio-player.js` — fetch duplicado de `radios.json` (BAIXO)

**Local:** `js/radio-player.js:691+456`

`verificarPlayerAtivo()` e `loadStations()` fazem fetch do mesmo arquivo.

### 4.7 `proximos-eventos.js` — closure frágil no loop (BAIXO)

**Local:** `js/proximos-eventos.js:70`

`onerror` captura `proximo` por closure — funciona mas é frágil.

### 4.8 `liturgia.js` e `calendario-romano.js` — chave `Mc` duplicada (GRAVE)

**Local:** `js/liturgia.js:184,195`

```js
'Mc': 'Marcos',      // linha 184
...
'Mc': 'Macabeus',    // linha 195 — sobrescreve Marcos
```

A segunda chave sobrescreve a primeira. Abreviação `Mc` resolve para `'Macabeus'` em vez de `'Marcos'` (Evangelho). **TTS diz "Macabeus" em vez de "Marcos"**. Mesmo bug em `calendario-romano.js`.

### 4.9 `terco-dia.js` — chave `sorridentes` deveria ser `dolorosos` (GRAVE)

**Local:** `js/terco-dia.js:39,92-103`

```js
sorridentes: {
    nome: 'Mistérios Dolorosos',  // conteúdo é Dolorosos
    ...
}
```

Lógica funciona (Terça/Friday recebem Dolorosos), mas `MISTERIOS.dolorosos` é `undefined`. Armadilha de manutenção — `terco.js` usa `dolorosos` corretamente.

### 4.10 `function.js` — Plyr sem check quebra toda a IIFE (GRAVE)

**Local:** `js/function.js:129`

```js
const player = new Plyr('#player');
```

Se `#player` não existe (maioria das páginas), lança erro. **Todo o código posterior na IIFE falha silenciosamente** — counter, GSAP, magnific popup, WOW.

**Correção:** `if ($('#player').length) { new Plyr('#player'); }`

### 4.11 `terco-dia.js` — ES6 `export` em script não-module (MÉDIO)

**Local:** `js/terco-dia.js:189-190`

```js
export { TercoDia, getMisterioParaDia, MISTERIOS, ORACOES };
```

Se carregado via `<script src="js/terco-dia.js">` (sem `type="module"`), lança `SyntaxError: Unexpected token 'export'`.

### 4.12 `liturgia.js` — comentário diz sessionStorage mas código usa localStorage (BAIXO)

**Local:** `js/liturgia.js:4 vs 29`

Comentário: `* Cacheia no sessionStorage pelo dia atual.`
Código: `localStorage.getItem(todayKey());`

Não é bug de runtime mas engana quem mantém o código.

---

## SEÇÃO 5: Segurança

### 5.1 `.htaccess` — HTTPS redirect comentado (GRAVE)

**Local:** `.htaccess:9-11`

Redirect para HTTPS está comentado — sem HTTPS forçado em produção.

### 5.2 `.htaccess` — CSP com `unsafe-inline` e `unsafe-eval` (MÉDIO)

**Local:** `.htaccess:60`

 enfraquece proteção XSS. Deveria usar nonce ou hash-based CSP.

### 5.3 `testemunho-process.php` — sem CSRF (MÉDIO)

**Local:** `testemunho-process.php`

Diferente de `form-process.php`, não valida token CSRF.

### 5.4 `testemunho-process.php` — confia em `X-Forwarded-For` (MÉDIO)

**Local:** `testemunho-process.php:78`

Atacante pode falsificar IP para bypass de rate limiting.

### 5.5 `form-process.php` — PHP `mail()` sem SMTP (BAIXO)

**Local:** `form-process.php:222`

Deveria usar PHPMailer/SMTP autenticado.

### 5.6 `form-process.php` — leak de versão PHP (BAIXO)

**Local:** `form-process.php:217`

Header `X-Mailer: PHP/` expõe versão do PHP.

### 5.7 `docker-plesk/.env.docker` — APP_KEY commitado (MÉDIO)

**Local:** `docker-plesk/.env.docker:3`

Chave APP_KEY visível no repositório.

### 5.8 `docker-plesk/.env.docker` — `APP_DEBUG=true` com `APP_ENV=production` (MÉDIO)

**Local:** `docker-plesk/.env.docker:2,4`

Modo debug ativado em ambiente de produção.

### 5.9 `robots.txt` — `testemunho-process.php` não bloqueado (BAIXO)

**Local:** `robots.txt`

`form-process.php` está bloqueado mas `testemunho-process.php` não.

### 5.10 `diag2.php` — arquivo de diagnóstico em produção (MÉDIO)

**Local:** `diag2.php`

Arquivo de diagnóstico de deploy com comentários "apagar após uso". Expõe caminhos do servidor, estrutura de pastas e metadados de arquivos. Deveria ser removido ou bloqueado no `.htaccess`.

**Correção:** Adicionar `RewriteRule ^diag2\.php$ - [F,L]` no `.htaccess` ou remover o arquivo.

### 5.11 `PageViewController.php` — `APP_KEY` não validado para IP hash (BAIXO)

**Local:** `cms/app/Http/Controllers/PageViewController.php:37`

```php
$ipHash = hash('sha256', $request->ip() . config('app.key'));
```

Se `APP_KEY` não estiver definido ou estiver vazio em produção, todos os IPs produzem o mesmo hash — deduplicação quebrada.

---

## SEÇÃO 6: Sitemap e SEO

### 6.1 Sitemap — páginas dinâmicas ausentes (MÉDIO)

Páginas individuais de eventos (`/eventos/*.html`), artigos (`/artigos/*.html`) e homilias (`/homilias/*.html`) não estão no `sitemap.xml`.

### 6.2 `evento-single-pascom.html` ausente do sitemap (BAIXO)

Página existe em `seo-data.json` mas não no `sitemap.xml`.

### 6.3 `seo-data.json` — `twitter_site` vazio (BAIXO)

**Local:** `seo-data.json:9`

Deveria conter o handle do Twitter/Instagram da paróquia.

### 6.4 `seo-data.json` — todas as 20 imagens OG não existem (GRAVE)

**Local:** `seo-data.json` — todas as entradas

| Página | `og:image` | Existe? |
|---|---|---|
| home | `og-home.jpg` | NÃO |
| historia | `og-historia.jpg` | NÃO |
| paroco | `og-paroco.jpg` | NÃO |
| pastoral | `og-pastoral.jpg` | NÃO |
| ministerios | `og-ministerios.jpg` | NÃO |
| eventos | `og-eventos.jpg` | NÃO |
| pascom | `og-pascom.jpg` | NÃO |
| liturgia | `og-liturgia.jpg` | NÃO |
| blog | `og-blog.jpg` | NÃO |
| homilias | `og-homilias.jpg` | NÃO |
| campanhas | `og-campanhas.jpg` | NÃO |
| galeria | `og-galeria.jpg` | NÃO |
| objetos | `og-objetos.jpg` | NÃO |
| contato | `og-contato.jpg` | NÃO |

Compartilhamento em redes sociais não terá imagem.

### 6.5 `css/custom.css` — typo `--secondry-color` (MÉDIO)

**Local:** `css/custom.css:3484`

```css
.post-social-sharing ul li:hover a i {
    color: var(--secondry-color);
}
```

Variável inexistente — `--secondry` em vez de `--secondary`. Cor não aplicada em hover dos ícones sociais.

---

## SEÇÃO 7: Docker e CI/CD

### 7.1 CI — FTP Deploy com `security: loose` (BAIXO)

**Local:** `.github/workflows/ci.yml:130`

Não verifica certificados SSL durante transferência FTP.

### 7.2 Docker — sem HEALTHCHECK (BAIXO)

**Local:** `docker-plesk/Dockerfile`

Não há instrução `HEALTHCHECK`.

### 7.3 `.github/copilot-instructions.md` — prompts inexistentes (BAIXO)

**Local:** `.github/copilot-instructions.md`

Referencia comandos `/nova-pagina`, `/novo-post`, `/novo-evento`, `/auditar-seo`, `/revisar-seguranca`, `/revisar-acessibilidade` mas os diretórios `.github/instructions/` e `.github/prompts/` **não existem** no repositório.

---

## Resumo Geral

| # | Erro | Severidade | Categoria |
|---|---|---|---|
| 1 | `/storage/` paths nunca resolvidos — imagens história quebradas (Node+PHP) | **GRAVE** | CMS |
| 2 | Galeria eventos: string vs objeto | **MÉDIO** | CMS |
| 3 | Configuracao/RadioBuscaExterna sem observer | **MÉDIO** | CMS |
| 4 | Evento `$fillable` incompleto (4 campos) | **GRAVE** | CMS |
| 5 | Evento `array_filter` inconsistente | **MÉDIO** | CMS |
| 6 | URLs `localhost` hardcoded no Filament | **GRAVE** | CMS |
| 7 | Credenciais login hardcoded | **MÉDIO** | CMS |
| 8 | Resources sem `$navigationGroup` | **BAIXO** | CMS |
| 9 | GaleriaAlbum sem `relationLoaded` guard | **BAIXO** | CMS |
| 10 | `imgPath()` recebe array → produz "Array" | **GRAVE** | CMS |
| 11 | `formatDateBR()` sem bounds check | **MÉDIO** | CMS |
| 12 | `json_decode` null não verificado (3 locais) | **MÉDIO** | CMS |
| 13 | `ContentExport` sem null check em `current()` | **BAIXO** | CMS |
| 14 | Dados de teste em produção (eventos.json) | **GRAVE** | Dados |
| 15 | `data/test.tmp` no repositório | **MÉDIO** | Dados |
| 16 | `historia.json` — dados teste extensivos | **GRAVE** | Dados |
| 17 | `paroco.json` — dados placeholder | **MÉDIO** | Dados |
| 18 | `artigos.json` — 4 artigos lorem ipsum fake | **MÉDIO** | Dados |
| 19 | `homilias.json` — primeira homilia é teste | **MÉDIO** | Dados |
| 20 | Imagens demo `images/uploads/demo/` referenciadas | **BAIXO** | Dados |
| 21 | Schema evento: nomes divergentes | **MÉDIO** | Schemas |
| 22 | `programacao[].hora` viola pattern | **MÉDIO** | Dados |
| 23 | `imagem_capa` tipo inconsistente | **BAIXO** | Dados |
| 24 | Footer: links sacramentos quebrados | **GRAVE** | HTML |
| 25 | Footer: política privacidade quebrada | **MÉDIO** | HTML |
| 26 | Historia template: ID duplicado main-content | **GRAVE** | HTML |
| 27 | Artigo template: data-page errado | **MÉDIO** | HTML |
| 28 | Artigo template: breadcrumb sem aria-label | **MÉDIO** | HTML |
| 29 | index.html: sem `<main>` landmark | **GRAVE** | HTML |
| 30 | index.html: placeholder "Resumo" | **MÉDIO** | HTML |
| 31 | index.html: typo "A Parquía" | **MÉDIO** | HTML |
| 32 | radio-player.js duplicado em **18 páginas** | **GRAVE** | HTML |
| 33 | Seções ocultas com dead links | **BAIXO** | HTML |
| 34 | iframe sem title | **MÉDIO** | HTML |
| 35 | Template prefixo `..{{campo}}` frágil | **MÉDIO** | HTML |
| 36 | historia.html: texto de teste no h1 | **GRAVE** | HTML |
| 37 | historia.html: CSS class como img src (3 imgs) | **GRAVE** | HTML |
| 38 | historia.html: lorem ipsum seção "Sobre Nós" | **MÉDIO** | HTML |
| 39 | eventos.html: dados teste "4321" | **GRAVE** | HTML |
| 40 | campanha-detalhe: $ em vez de R$ | **MÉDIO** | HTML |
| 41 | campanhas.html: "Our Campaigns" em inglês | **MÉDIO** | HTML |
| 42 | campanhas.html: texto corrompido "Doa00e700f5es" | **GRAVE** | HTML |
| 43 | homilias.html: placeholder "John Due" + dead links | **MÉDIO** | HTML |
| 44 | homilia-detalhe: audio src é URL demo externa | **GRAVE** | HTML |
| 45 | galeria.html: 6 imagens sem alt | **BAIXO** | HTML |
| 46 | artigos.html: 5 blog items com href="#" | **MÉDIO** | HTML |
| 47 | ministerio-detalhe: lorem ipsum + inglês | **MÉDIO** | HTML |
| 48 | sacramento-detalhe: breadcrumb link incorreto | **BAIXO** | HTML |
| 49 | `data-page` errado em 11 páginas | **MÉDIO** | HTML |
| 50 | artigo-detalhe: conteúdo incompatível com título | **MÉDIO** | HTML |
| 51 | santo-dia.js: TypeError null summary | **GRAVE** | JS |
| 52 | function.js: form data sem encoding | **GRAVE** | JS |
| 53 | function.js: Swiper/Plyr sem DOM check | **MÉDIO** | JS |
| 54 | function.js: $.ajax sem error handler | **MÉDIO** | JS |
| 55 | liturgia.js: loadWidget sem validação | **MÉDIO** | JS |
| 56 | radio-player.js: fetch duplicado | **BAIXO** | JS |
| 57 | proximos-eventos.js: closure frágil | **BAIXO** | JS |
| 58 | liturgia.js/calendario-romano.js: `Mc` duplicado | **GRAVE** | JS |
| 59 | terco-dia.js: chave `sorridentes` deveria ser `dolorosos` | **GRAVE** | JS |
| 60 | function.js: Plyr sem check quebra IIFE inteira | **GRAVE** | JS |
| 61 | terco-dia.js: ES6 export em script não-module | **MÉDIO** | JS |
| 62 | liturgia.js: comentário/sessionStorage vs código/localStorage | **BAIXO** | JS |
| 63 | .htaccess: HTTPS redirect comentado | **GRAVE** | Segurança |
| 64 | .htaccess: CSP unsafe-inline/eval | **MÉDIO** | Segurança |
| 65 | testemunho-process.php: sem CSRF | **MÉDIO** | Segurança |
| 66 | testemunho-process.php: X-Forwarded-For spoofable | **MÉDIO** | Segurança |
| 67 | form-process.php: sem SMTP | **BAIXO** | Segurança |
| 68 | form-process.php: leak PHP version | **BAIXO** | Segurança |
| 69 | Docker: APP_KEY commitado | **MÉDIO** | Docker |
| 70 | Docker: APP_DEBUG=true em production | **MÉDIO** | Docker |
| 71 | robots.txt: testemunho-process não bloqueado | **BAIXO** | SEO |
| 72 | Sitemap: páginas dinâmicas ausentes | **MÉDIO** | SEO |
| 73 | evento-single-pascom ausente do sitemap | **BAIXO** | SEO |
| 74 | seo-data: twitter_site vazio | **BAIXO** | SEO |
| 75 | seo-data: 20 imagens OG não existem | **GRAVE** | SEO |
| 76 | css/custom.css: typo `--secondry-color` | **MÉDIO** | CSS |
| 77 | CI: FTP security: loose | **BAIXO** | CI/CD |
| 78 | Docker: sem HEALTHCHECK | **BAIXO** | Docker |
| 79 | copilot-instructions: prompts inexistentes | **BAIXO** | CI/CD |
| 80 | PageViewController: APP_KEY não validado | **BAIXO** | Segurança |
| 81 | diag2.php — arquivo diagnóstico em produção | **MÉDIO** | Segurança |

### Por Severidade

| Severidade | Quantidade |
|---|---|
| **GRAVE** | 20 |
| **MÉDIO** | 32 |
| **BAIXO** | 29 |
| **Total** | **81** |
