# MELHORIAS GERAIS — Site Paróquia NSR Jericó/PB

> Documento de auditoria técnica e plano de evolução do site institucional da Paróquia Nossa Senhora dos Remédios — Jericó/PB.
>
> Base visual: **Avenix Church HTML Template (ThemeForest)** — toda nova feature deve respeitar este design system.

Última revisão: 2026-04-30

---

## 1. Visão Geral

| Item | Estado atual |
|---|---|
| Tipo | Site HTML estático (21 páginas) + 1 script PHP de contato |
| Build system | ❌ Inexistente (sem npm, gulp, vite, Jekyll, Hugo etc.) |
| Includes (header/footer) | ❌ Duplicados em todas as 21 páginas |
| Backend / CMS | ❌ Inexistente |
| HTTPS | ⚠️ A confirmar no servidor de produção |
| SEO técnico | 🔴 Insuficiente (meta tags vazias, sem sitemap/robots) |
| Acessibilidade | 🟡 ~70% (lang OK, faltam aria-labels) |
| Performance imagens | 🟡 Sem WebP/lazy-load/srcset |
| Segurança formulário | 🔴 Crítico (header injection, sem CSRF) |

---

## 2. Problemas Estruturais (críticos)

### 2.1. Duplicação massiva de header/footer
Todas as 21 páginas `.html` repetem **byte-por-byte** o `<head>`, `<header>`, `<footer>`, links de CSS e scripts. Mudar o menu obriga editar 21 arquivos.

**Refatoração sugerida (curto prazo, sem CMS):**
- Opção A — **PHP includes**: renomear `.html` → `.php` e usar `<?php include 'partials/header.php'; ?>`. Requer servidor PHP (já temos para `form-process.php`).
- Opção B — **JS fetch**: criar `partials/header.html` e injetar via `fetch()` no `<header data-include="partials/header.html">`. Penaliza SEO e gera flash de conteúdo.
- Opção C — **Static Site Generator** (recomendado): migrar para **Eleventy (11ty)** ou **Astro** mantendo o HTML do Avenix como templates. Gera HTML puro no build, mantendo SEO 100% e simplificando manutenção.

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
| B01 | `form-process.php` linha 41 | Subject = `"Contact Inquiry from Physiocare Website"` — copy-paste de outro template | 🔴 Alta |
| B02 | `form-process.php` linha 43 | E-mail destinatário `awaikentechnology@gmail.com` (e-mail do desenvolvedor original do template) | 🔴 Alta |
| B03 | `form-process.php` linha 64 | `mail(..., "From:".$email)` — **header injection**: usuário pode injetar `Bcc:`, `Cc:` via `\r\n` | 🔴 Crítica |
| B04 | `form-process.php` linhas 5-39 | Campos `$_POST` lidos sem `filter_input` / `htmlspecialchars` | 🔴 Alta |
| B05 | `form-process.php` linhas 70-72 | Mensagens em inglês (`"Full Name is required"`, `"Something went wrong :("`) | 🟡 Média |
| B06 | Todas as páginas | `<meta name="description" content="">` e `keywords` vazias (exceto `eventos.html`) | 🔴 Alta (SEO) |
| B07 | `index.html`, `blog.html`, `contact.html` | `<title>` genérico = `"Paróquia NSR - Jericó/PB"` (sem contexto da página) | 🟡 Média (SEO) |
| B08 | `js/santo-dia.js` | Fetch da Wikipedia sem sanitização explícita do HTML retornado antes de injetar no DOM | 🟡 Média (XSS) |
| B09 | `js/liturgia.js` | Dependência de API externa não-oficial `https://liturgia.up.railway.app/` sem fallback robusto | 🟡 Média |
| B10 | Footer (todas as páginas) | Newsletter form sem `action`, sem proteção, sem validação | 🟡 Média |
| B11 | Ícones-only no footer | `<a><i class="fa-solid fa-..."></i></a>` sem `aria-label` | 🟡 Média (a11y) |
| B12 | `images/uploads/` | Imagens grandes sem compressão (provavelmente >500KB cada) | 🟡 Média |
| B13 | Página 404 | Confirmar se servidor está configurado para servir `404.html` em rotas inexistentes | 🟢 Baixa |

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

1. **Centralizar `<head>`** em include único (assim que adotar PHP/SSG).
2. **Centralizar `<header>` e `<footer>`** (idem).
3. **Extrair componentes reutilizáveis**: cards de evento, cards de blog, blocos de CTA, breadcrumb (ver `PADRONIZACAO_LAYOUT.md`).
4. **Padronizar paleta** via CSS variables (já parcialmente feito em `:root` de `custom.css`).
5. **Documentar tokens de design** (cores, tipografia, espaçamentos) em `PADRONIZACAO_LAYOUT.md`.
6. **Mover dados estáticos do JS** (`proximos-eventos.js`, `calendario-romano.js`) para JSON em `/data/` — facilita futura migração para CMS.
7. **Internacionalizar** mensagens do `form-process.php` se desejar suporte multi-idioma.
8. **Substituir validator.min.js** (jQuery plugin obscuro) por validação HTML5 nativa + script próprio leve.

---

## 10. Sugestões Futuras (Roadmap)

1. **CMS** — ver [SUGESTAO_CMS.md](SUGESTAO_CMS.md). Curto: WordPress Headless ou Strapi; ideal: Laravel + Filament.
2. **PWA** — manifest + service worker para acesso offline a horários de missa e agenda.
3. **Notificações WhatsApp/Push** — newsletter de eventos via API WhatsApp Business ou OneSignal.
4. **Área do paroquiano** — login para inscrição em eventos, certificados de batismo/crisma, doações online (PIX, Stripe).
5. **Transmissão ao vivo** — integração com YouTube Live API; player embedado com agenda automática.
6. **Doações online** — PIX dinâmico + Stripe/MercadoPago.
7. **Multi-paróquia** — se a Diocese quiser unificar várias paróquias, adotar Laravel multi-tenant.
8. **Analytics** — instalar Google Analytics 4 + Search Console + Microsoft Clarity (heatmap gratuito).

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
