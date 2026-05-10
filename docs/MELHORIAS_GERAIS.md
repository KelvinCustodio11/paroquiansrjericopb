# MELHORIAS GERAIS — Site Paróquia NSR Jericó/PB

> Documento de auditoria técnica e plano de evolução do site institucional da Paróquia Nossa Senhora dos Remédios — Jericó/PB.
>
> Base visual: **Avenix Church HTML Template (ThemeForest)** — toda nova feature deve respeitar este design system.

Última revisão: 2026-05-02

---

## 1. Visão Geral

| Item | Estado atual |
|---|---|
| Tipo | Site HTML estático (~21 páginas) + 1 script PHP de contato + CMS admin |
| Build system | ✅ `node build.js` (partials header/footer) + `php artisan content:export --build` (CMS) |
| Includes (header/footer) | ✅ Centralizados em `partials/` via build.js |
| Backend / CMS | ✅ Laravel 11 + Filament 3 — admin em admin.pascomjerico.com.br |
| HTTPS | ✅ Ativo em pascomjerico.com.br + admin.pascomjerico.com.br |
| Deploy | ✅ GitHub Actions → FTP Plesk (branches: developer/production) |
| SEO técnico | 🟡 Parcial (sitemap/robots ✅, meta tags ~50% preenchidas) |
| Acessibilidade | 🟡 ~70% (lang OK, faltam aria-labels em ícones-only) |
| Performance imagens | 🟡 Sem WebP/srcset ainda |
| Segurança formulário | ✅ CSRF + sanitização + header-injection bloqueado |
| Radio player | ✅ Player + lista de rádios em `data/radios.json` gerenciável via CMS |
| Santo do dia | ✅ Integração Wikipedia (com DOMPurify) |
| Evangelho do dia | ✅ Integração liturgia.up.railway.app |
| Agenda litúrgica | ✅ Calendário romano via `calendario-romano.js` |

---

## 2. Problemas Estruturais (críticos)

### 2.1. Duplicação massiva de header/footer
**✅ RESOLVIDO** — Header, footer e scripts comuns centralizados em `partials/`. O script `build.js` (Node.js) injeta os partials em todas as páginas via marcadores `<!-- @include-start -->` / `<!-- @include-end -->`. Rodar `node build.js` para propagar qualquer alteração.

> ⚠️ **Nunca** editar manualmente o bloco entre os marcadores nas páginas individuais — será sobrescrito no próximo build.

### 2.2. URLs em inglês (legado do template)
Hoje: `/about.html`, `/blog.html`, `/contact.html`, `/eventos.html`, `/ministries.html`, `/sermons.html`, `/campaign.html`, `/gallery.html`, `/service.html`...

Plano de padronização PT-BR (ver §6).

### 2.3. Falta de configuração de repositório
Inexistentes: `README.md`, `.gitignore`, `robots.txt`, `sitemap.xml`, `.htaccess`, `package.json`. Foram criados nesta auditoria (exceto `package.json`, que depende de adoção de build).

### 2.4. Assets não otimizados
- `all.css` carrega Font Awesome inteiro (~250KB). Site usa frações; pode ser substituído por *subset*.
- Imagens em `images/uploads/events/` em PNG/JPEG sem WebP/AVIF e sem `srcset`.
- 14 arquivos CSS carregados separadamente (sem bundling) → muitos *requests*.

---

## 3. Bugs e Inconsistências

| # | Local | Problema | Severidade |
|---|---|---|---|
| # | Local | Problema | Severidade | Status |
|---|---|---|---|---|
| B01 | `form-process.php` | Subject / destinatário do template original | 🔴 Alta | ✅ Corrigido |
| B02 | `form-process.php` | E-mail `awaikentechnology@gmail.com` hardcoded | 🔴 Alta | ✅ Corrigido |
| B03 | `form-process.php` | Header injection via campo `From:` | 🔴 Crítica | ✅ Corrigido |
| B04 | `form-process.php` | `$_POST` sem `filter_input` / `htmlspecialchars` | 🔴 Alta | ✅ Corrigido |
| B05 | `form-process.php` | Mensagens de erro em inglês | 🟡 Média | ✅ Corrigido |
| B06 | Todas as páginas | `<meta description>` e `keywords` vazias | 🔴 Alta (SEO) | 🔄 Em progresso |
| B07 | `index.html`, `blog.html`... | `<title>` genérico | 🟡 Média (SEO) | 🔄 Em progresso |
| B08 | `js/santo-dia.js` | innerHTML sem sanitização DOMPurify | 🟡 Média (XSS) | ✅ Corrigido |
| B09 | `js/liturgia.js` | API externa sem fallback robusto | 🟡 Média | 🔄 Em progresso |
| B10 | Footer | Newsletter form sem proteção | 🟡 Média | ⏳ Pendente |
| B11 | Ícones-only no footer | `<i>` sem `aria-label` | 🟡 Média (a11y) | ⏳ Pendente |
| B12 | `images/uploads/` | Imagens sem compressão/WebP | 🟡 Média | ⏳ Pendente |
| B13 | Página 404 | Servidor servindo 404 customizado | 🟢 Baixa | ✅ Configurado via .htaccess |
| B14 | `data/configuracoes.json` | `logo_*_img` nulos causavam SVG inline nas partials | 🔴 Alta | ✅ Corrigido — 2026-05-09 |
| B15 | `schemas/artigo.schema.json`, `homilia.schema.json` | `imagem_capa` declarada como `type: object`; CMS exporta string | 🟡 Média | ✅ Corrigido — 2026-05-09 |
| B16 | `cms/tests/Feature/ContentBuildPhpHistoriaTest.php` | Testes `pipelineCompleto*` apontavam para site root real, zerando `data/artigos.json` | 🔴 Alta | ✅ Corrigido — 2026-05-09 |

> **B14 detalhes**: `build-content.js` usa SVG fallback quando `logo_header_img`, `logo_footer_img` ou `logo_loader_img` são `null` em `data/configuracoes.json`. Corrigido preenchendo com paths reais (`images/uploads/logos/*.png`). Requer 2 passadas de `npm run all` ao mudar logos — ver `PADRONIZACAO_LAYOUT.md §3.6`.
>
> **B15 detalhes**: `ContentBuildPhp::enrichArtigo()` exporta `imagem_capa` como string (path relativo). Schemas agora aceitam `"type": ["string", "object"]`. Ambos os formatos normalizados pelos scripts Node e PHP.
>
> **B16 detalhes**: Dois testes explicitamente faziam `config(['site.root' => realpath(base_path('..'))])` para apontar ao site real. Corrigidos para usar `$this->tmpRoot` isolado com cópia do template real via `copiaTemplateReal()`.

---

## 4. Segurança — Plano de Correção

### 4.1. `form-process.php` (já hardenizado nesta auditoria)
Reescrito com:
- Tokens **CSRF** (sessão PHP).
- **Honeypot field** para bots (`hp_field`).
- **Rate limiting** simples por IP (sessão + arquivo temporário).
- **Sanitização** com `filter_var(..., FILTER_SANITIZE_*)` e `filter_var($email, FILTER_VALIDATE_EMAIL)`.
- **Bloqueio de header injection**: rejeita `\r`, `\n`, `bcc:`, `cc:`, `content-type:` no campo email.
- **Headers** `From:` configurado com endereço **da própria paróquia** (não do remetente) e `Reply-To:` com o e-mail do usuário.
- Mensagens de erro **em português**.
- Resposta JSON estruturada (em vez de texto solto).
- Recomendação: integrar **reCAPTCHA v3** ou **hCaptcha** em etapa seguinte (requer chave).

### 4.2. Headers HTTP (configurar no servidor / `.htaccess`)
```apache
Header set X-Frame-Options "SAMEORIGIN"
Header set X-Content-Type-Options "nosniff"
Header set Referrer-Policy "strict-origin-when-cross-origin"
Header set Permissions-Policy "geolocation=(), microphone=(), camera=()"
Header set Strict-Transport-Security "max-age=31536000; includeSubDomains"
Header set Content-Security-Policy "default-src 'self' https:; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com; connect-src 'self' https://liturgia.up.railway.app https://pt.wikipedia.org https://stm10.srvvox.com.br;"
```

### 4.3. Sanitização de output dinâmico (JS)
Em `santo-dia.js` e `liturgia.js`, **nunca** usar `innerHTML` com dado vindo de fetch. Trocar por:
```js
el.textContent = data.titulo;        // texto puro
// ou usar DOMPurify se HTML for necessário:
el.innerHTML = DOMPurify.sanitize(data.html);
```

### 4.4. Outros
- Forçar HTTPS no servidor (redirect 301 de HTTP → HTTPS).
- Remover `awaikentechnology@gmail.com` e qualquer outro vestígio do desenvolvedor original do template.
- Não versionar credenciais (já coberto pelo novo `.gitignore`).

---

## 5. SEO — Plano de Correção

### 5.1. Meta tags por página (preencher todas)
Cada página deve ter `<title>` único, `description` (150-160 caracteres) e `keywords` relevantes em PT-BR.

Exemplos:
| Página | `<title>` sugerido | `description` sugerida |
|---|---|---|
| index.html | Paróquia Nossa Senhora dos Remédios — Jericó/PB | Paróquia centenária no Sertão Paraibano. Missas, sacramentos, eventos, ministérios e história da fé católica em Jericó/PB. |
| about.html | História da Paróquia — Nossa Senhora dos Remédios Jericó/PB | Mais de 150 anos de fé e devoção a Nossa Senhora dos Remédios em Jericó, Paraíba. Conheça nossa história. |
| paroco.html | Pároco — Paróquia NSR Jericó/PB | Conheça o Padre [Nome], pároco da Paróquia Nossa Senhora dos Remédios em Jericó/PB. |
| eventos.html | Eventos e Festas — Paróquia NSR Jericó/PB | Calendário de eventos, festas religiosas, novenas e celebrações da Paróquia NSR Jericó/PB. |
| blog.html | Artigos e Notícias — Paróquia NSR Jericó/PB | Reflexões, notícias e comunicados da Paróquia Nossa Senhora dos Remédios. |
| contact.html | Contato — Paróquia NSR Jericó/PB | Telefone, WhatsApp, endereço e formulário de contato da Paróquia NSR Jericó/PB. |

### 5.2. Open Graph + Twitter Cards (em todas as páginas)
```html
<meta property="og:type" content="website">
<meta property="og:title" content="...">
<meta property="og:description" content="...">
<meta property="og:image" content="https://pascomjerico.com.br/images/og-default.jpg">
<meta property="og:url" content="https://pascomjerico.com.br/...">
<meta property="og:locale" content="pt_BR">
<meta property="og:site_name" content="Paróquia NSR Jericó/PB">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="...">
<meta name="twitter:description" content="...">
<meta name="twitter:image" content="https://pascomjerico.com.br/images/og-default.jpg">

<link rel="canonical" href="https://pascomjerico.com.br/...">
```

### 5.3. Structured Data (JSON-LD)
Adicionar no `<head>` de `index.html`:
```html
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Church",
  "name": "Paróquia Nossa Senhora dos Remédios",
  "url": "https://pascomjerico.com.br",
  "logo": "https://pascomjerico.com.br/images/logo.png",
  "image": "https://pascomjerico.com.br/images/og-default.jpg",
  "telephone": "+55-83-3435-1020",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "[Rua, Nº]",
    "addressLocality": "Jericó",
    "addressRegion": "PB",
    "postalCode": "[CEP]",
    "addressCountry": "BR"
  },
  "sameAs": [
    "https://www.facebook.com/...",
    "https://www.instagram.com/pascomremedios.jerico"
  ]
}
</script>
```
Para eventos use `@type: "Event"`, para artigos `@type: "Article"`.

### 5.4. Arquivos auxiliares (criados nesta auditoria)
- ✅ `robots.txt`
- ✅ `sitemap.xml`

---

## 6. Plano de Migração de URLs para PT-BR

> ⚠️ **Operação sensível para SEO.** Não execute renomeações sem antes ativar redirecionamentos `301` para preservar autoridade dos URLs antigos.

### 6.1. Mapeamento proposto

| URL atual (EN) | URL nova (PT-BR) | Observação |
|---|---|---|
| `/about.html` | `/historia/` | Mantém alias `/sobre/` |
| `/paroco.html` | `/paroco/` | Já PT |
| `/service.html` | `/sacramentos/` | "Service" no template = sacramentos/pastorais |
| `/service-single.html` | `/sacramentos/{slug}/` | Detalhe |
| `/ministries.html` | `/ministerios/` | |
| `/ministry-single.html` | `/ministerios/{slug}/` | |
| `/eventos.html` | `/eventos/` | Já PT |
| `/evento-single.html` | `/eventos/{slug}/` | |
| `/evento-single-pascom.html` | `/eventos/assembleia-pascom-2026/` | Renomear pelo slug real |
| `/agenda-liturgica.html` | `/agenda-liturgica/` | Já PT |
| `/blog.html` | `/artigos/` | "Notícias" também é aceitável |
| `/blog-single.html` | `/artigos/{slug}/` | |
| `/sermons.html` | `/homilias/` | |
| `/sermons-single.html` | `/homilias/{slug}/` | |
| `/campaign.html` | `/campanhas/` | |
| `/campaign-single.html` | `/campanhas/{slug}/` | |
| `/gallery.html` | `/galeria/` | |
| `/objetos-sagrados.html` | `/objetos-sagrados/` | Já PT |
| `/contact.html` | `/contato/` | |
| `/404.html` | `/404.html` | Mantém (não indexável) |
| `/index.html` | `/` | Raiz |

### 6.2. Estratégias técnicas

**Opção 1 — Redirecionamentos via `.htaccess` (Apache)** (criar arquivo `.htaccess` na raiz):
```apache
RewriteEngine On
Redirect 301 /about.html        /historia/
Redirect 301 /service.html      /sacramentos/
Redirect 301 /ministries.html   /ministerios/
Redirect 301 /blog.html         /artigos/
Redirect 301 /sermons.html      /homilias/
Redirect 301 /campaign.html     /campanhas/
Redirect 301 /gallery.html      /galeria/
Redirect 301 /contact.html      /contato/
Redirect 301 /eventos.html      /eventos/
# ... etc para single pages
```

**Opção 2 — Static Site Generator** (recomendado a médio prazo):
- Cada página vira um Markdown ou template; o gerador produz `/historia/index.html`.
- Configura redirects no host (Netlify `_redirects`, Vercel `vercel.json`, GitHub Pages `_redirects` via plugin).

**Cronograma sugerido:**
1. **Fase 1 (não destrutiva)**: criar **cópias** com nome novo + adicionar `<link rel="canonical">` apontando para a URL nova; adicionar redirecionamentos 301 dos antigos.
2. **Fase 2**: atualizar todos os `<a href>` internos para os novos URLs.
3. **Fase 3**: monitorar Google Search Console por 30 dias; remover URLs antigos.

---

## 7. Performance

| Otimização | Impacto | Esforço |
|---|---|---|
| Converter imagens para **WebP** + `<picture>` com fallback | 🚀 Alto | Médio |
| Adicionar `loading="lazy"` em `<img>` abaixo do *fold* | 🚀 Alto | Baixo |
| Usar `srcset`/`sizes` para imagens responsivas | 🚀 Alto | Médio |
| Bundling/minificação de CSS (14 arquivos → 1) | Médio | Médio |
| Substituir Font Awesome completo por subset (só ícones usados) | Alto | Médio |
| Habilitar compressão **gzip/brotli** no servidor | 🚀 Alto | Baixo |
| Cache HTTP (`Cache-Control: max-age=31536000` para assets versionados) | Alto | Baixo |
| Remover jQuery quando viável (gradual; muito plugin depende) | Médio | Alto |
| Defer/Async em `<script>` de terceiros (não-críticos) | Médio | Baixo |
| Pré-conexão a domínios externos (`<link rel="preconnect">` para Google Fonts, Wikipedia, Railway) | Baixo | Baixo |

---

## 8. Acessibilidade (WCAG 2.1 AA)

| Item | Ação |
|---|---|
| Ícones-only sem texto | Adicionar `aria-label="Facebook"`, `aria-label="Instagram"` etc. |
| Botões `<a>` com apenas `<i>` | Mesma ação acima |
| Inputs sem `<label>` associado | Adicionar `<label for="id">` ou `aria-label` em inputs |
| Foco visível | Garantir `:focus-visible` com outline suficiente em todos os elementos interativos |
| Contraste | Validar com ferramenta (axe, Lighthouse) — cores `#acaa59` em fundo branco têm contraste limítrofe |
| Skip link | Adicionar `<a href="#main-content" class="skip-link">Pular para o conteúdo</a>` no início do `<body>` |
| Landmarks | Garantir `<main id="main-content">` em todas as páginas |
| Forms | `aria-required="true"` + `aria-describedby` para mensagens de erro |
| Vídeos do YouTube embedados | Adicionar `title="Descrição do vídeo"` no `<iframe>` |

---

## 9. Pontos de Refatoração (próximos sprints)

1. ~~**Centralizar `<head>`** em include único (assim que adotar PHP/SSG).~~ ✅ Feito via `partials/` + `build.js`
2. ~~**Centralizar `<header>` e `<footer>`**~~ ✅ Feito via `partials/`
3. **Extrair componentes reutilizáveis** remanescentes: cards de sacramento, cards de ministério, breadcrumb.
4. **Padronizar paleta** via CSS variables (já parcialmente feito em `:root` de `custom.css`).
5. ~~**Mover dados estáticos do JS** para JSON em `/data/`~~ ✅ Feito — `data/` gerenciado pelo CMS.
6. **Substituir validator.min.js** (jQuery plugin obscuro) por validação HTML5 nativa + script próprio leve.
7. **Testes de integração** CMS → exportação → HTML (ver novo backlog §12, item 1).
8. **Webhook/trigger automático** de rebuild ao salvar no CMS (atualmente requer `artisan content:export --build` manual).

---

## 10. Sugestões Futuras (Roadmap)

1. ~~**CMS** — Laravel + Filament~~ ✅ **Implementado** — admin em admin.pascomjerico.com.br
2. **PWA** — manifest + service worker para acesso offline a horários de missa e agenda.
3. **Notificações WhatsApp/Push** — newsletter de eventos via API WhatsApp Business ou OneSignal.
4. **Área do paroquiano** — login para inscrição em eventos, certificados de batismo/crisma, doações online (PIX, Stripe).
5. **Transmissão ao vivo** — integração com YouTube Live API; player embedado com agenda automática.
6. **Doações online** — PIX dinâmico + Stripe/MercadoPago.
7. **Multi-paróquia** — se a Diocese quiser unificar várias paróquias, adotar Laravel multi-tenant.
8. **Analytics** — instalar Google Analytics 4 + Search Console + Microsoft Clarity (heatmap gratuito).
9. **Webhook de rebuild** — trigger automático ao salvar conteúdo no CMS.
10. **Testimonhos de fiéis** — espaço para envio de testemunhos + aprovação via CMS (ver §12, item 7).

---

---

## 12. Backlog de Novas Features (2026)

> Levantamento de maio/2026. Priorizar em conjunto com o pároco e equipe PASCOM.

### 12.0 — Devoções Diárias (Terço + página agrupada)
- **O quê**: além do santo do dia e evangelho do dia, exibir os **mistérios do terço** do dia (segunda/quinta=alegres, terça/sexta=dolorosos, quarta/sábado=gloriosos, domingo=luminosos) com a opção de ver o **passo a passo completo** do terço.
- **Referência**: https://formacao.cancaonova.com/espiritualidade/oracao/voce-sabe-como-rezar-o-santo-terco/
- **Agrupamento**: criar página `/devocoes-diarias/` reunindo santo do dia, evangelho e terço em abas ou seções.
- **CMS**: configuração para habilitar/desabilitar cada bloco de devoção (santo, evangelho, terço).
- **Arquivos envolvidos**: `js/terco-dia.js` (novo), `devocoes-diarias.html` (nova página), CMS `ConfiguracaoResource` (nova opção).

### 12.1 — Testes de Integração CMS → Exportação
- **O quê**: cobertura de 100% dos cenários do comando `content:export` — criar, editar, deletar cada entidade (Artigo, Evento, Homilia, Ministerio, Paroco, Radio, GaleriaAlbum, Igreja, Compromisso) e validar que o HTML/JSON exportado está correto.
- **Framework**: Pest PHP (já dependência do CMS).
- **Arquivos envolvidos**: `cms/tests/Feature/ContentExport/` (novo diretório com testes por recurso).

### 12.2 — Melhorias no Rádio
- **O quê**: busca/filtro de rádios além das já cadastradas — filtros por tipo (ex: católicas), região (km de um ponto), estado, melhores, aleatórias. Scroll infinito ou limite de exibição. Suporte a múltiplas categorias.
- **CMS**: campo `categoria` (enum: católica, gospel, religiosa...) + campo `estado` (UF) no RadioResource.
- **Frontend**: UI de filtros no radio player (`js/radio-player.js` + UI em `index.html`).
- **Arquivos envolvidos**: `js/radio-player.js`, `data/radios.json`, `RadioResource.php`.

### 12.3 — Otimização de Imagens por Resolução/Contexto
- **O quê**: ao fazer upload de imagens no CMS, gerar automaticamente as resoluções corretas para cada contexto: thumbnail (300×200), card (600×400), hero (1920×600), OG (1200×630) usando Spatie MediaLibrary conversions.
- **Arquivos envolvidos**: `cms/app/` (MediaLibrary conversions), build pipeline de imagens.

### 12.4 — Múltiplos Textos por Campo com Rotação/Animação
- **O quê**: campos de texto principais (Título, Subtítulo, CTA da home) com suporte a N variações, tempo de transição configurável e efeito (fade, slide, typewriter) e cor por variação.
- **CMS**: editor multi-valor (repetidores) nos campos de configuração da home.
- **Frontend**: `js/text-rotator.js` (novo) usando GSAP já disponível.
- **Arquivos envolvidos**: `js/text-rotator.js` (novo), `ConfiguracaoResource.php`.

### 12.5 — Footer Gerenciável via CMS
- **O quê**: título e nome da paróquia, links rápidos (qualquer texto + URL) e lista de sacramentos com link — todos editáveis no admin sem precisar rodar build.
- **CMS**: seção de rodapé no `IgrejaResource` ou novo `FooterResource`.
- **Build**: `content:export` inclui `data/footer.json` → `build.js` injeta no partial `partials/footer.html`.

### 12.6 — Menu Gerenciável via CMS
- **O quê**: itens de menu (texto + link + agrupamento/dropdown) editáveis no painel sem tocar no HTML.
- **CMS**: novo `MenuResource` com ordem, nível (pai/filho), visível (sim/não).
- **Build**: exporta `data/menu.json` → build.js injeta na partial de header.

### 12.7 — Testemunhos de Fiéis
- **O quê**: formulário no site para o paroquiano enviar testemunho (nome, texto, consentimento LGPD). No CMS, listar os pendentes e aprovar/reprovar antes de exibir no site.
- **CMS**: novo `TestemunhoResource` com status (`pendente`, `aprovado`, `rejeitado`).
- **Frontend**: `testemunhos.html` (listagem dos aprovados) + formulário de envio.
- **Backend**: endpoint PHP de recebimento (mesmo padrão do `form-process.php`).

### 12.8 — Leitor de Texto (Bíblia/Homilia) Aprimorado
- **O quê**: remover o karaoke palavra-a-palavra (dessincronizado). Substituir por **leitura por parágrafo** com destaque leve no parágrafo sendo lido. Melhorias no TTS: não ler numeração de versículos, não ler siglas bíblicas (ex: `Sl` → "Salmos", `Jo` → "João"), não ler intervalos como `1-10` (ler "versículo 1 ao 10").
- **Arquivos envolvidos**: `js/leitor-automatico.js` (refatorar), `homilia-detalhe.html`.

### 12.9 — Sobre / História Gerenciável via CMS
- **O quê**: página `historia.html` com seções editáveis (título, texto, imagem) via CMS — mesma estrutura já existente na página, mas com controle de conteúdo.
- ~~**CMS**: `IgrejaResource` → novas seções de história como repetidor.~~
- **✅ resolvido — 2026-05-09**: Implementação completa com recurso singleton dedicado `HistoriaPaginaResource` (9 abas, ~40 campos). Migração cria tabela `historia_pagina`, campos historia removidos de `igrejas`. Build via `buildSinglePageTemplates()` em `ContentBuildPhp`. Testes unitários e de integração com 100% de cobertura dos cenários: `tests/Unit/HistoriaPaginaModelTest.php` (43 testes) + `tests/Feature/ContentExport/HistoriaPaginaExportTest.php` (14 testes) + `tests/Feature/ContentBuildPhpHistoriaTest.php` (22 testes).
- **Bug extra corrigido**: `injectDynamicSections()` em `ContentBuildPhp` crashava com `TypeError` quando havia exatamente 1 evento publicado (null padding em `array_map` com arrays de tamanhos diferentes).

### 12.10 — Pastoral Gerenciável via CMS (com Catequese/Estudos)
- **O quê**: página `ministerios.html` / nova `pastoral.html` com seções de catequese e estudos bíblicos editáveis (título, descrição, horários, responsável).
- **CMS**: `MinisterioResource` com categoria (`ministerio`, `catequese`, `estudo-biblico`, `grupo-oracao`).
- **Build**: exporta filtrado por categoria.

### 12.11 — Contato e Localização via CMS
- **O quê**: endereço, telefone, WhatsApp, e-mail, link do Google Maps, horários de atendimento na secretaria — tudo editável no admin.
- **CMS**: campos já parcialmente no `IgrejaResource` — expandir e conectar ao build.
- **Build**: exporta `data/configuracoes.json` já exportado → template `templates/contato.html` (novo) gera `contato.html`.

---

## 11. Checklist de Aceite (definição de pronto para cada nova página/feature)

- [ ] Segue layout e paleta do template **Avenix Church**.
- [ ] `<title>`, `description`, `keywords` preenchidos em PT-BR.
- [ ] Open Graph + Twitter Cards + canonical configurados.
- [ ] Imagens em WebP com `loading="lazy"` e dimensões corretas.
- [ ] Validação HTML (W3C Validator) sem erros.
- [ ] Lighthouse: Performance ≥ 80, Acessibilidade ≥ 90, SEO ≥ 95.
- [ ] Sem console errors no navegador.
- [ ] Responsivo testado em 375px, 768px, 1024px, 1440px.
- [ ] Links internos funcionais.
- [ ] Formulários com CSRF, sanitização e validação.
- [ ] Documentação atualizada nos `.md` correspondentes.
