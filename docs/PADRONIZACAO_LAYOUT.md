# PADRONIZAÇÃO DE LAYOUT — Paróquia NSR Jericó/PB

> Guia de design system baseado no template **Avenix Church (ThemeForest)**.
> Toda nova página, componente ou feature visual **deve** seguir este documento.

---

## 1. Filosofia

- **Identidade visual única**: paleta, tipografia e espaçamentos do Avenix Church.
- **Reutilização antes de duplicação**: criar componente antes de copiar bloco HTML.
- **Mobile-first**: todo layout começa em 375px.
- **Acessibilidade não é opcional**: WCAG 2.1 AA mínimo.

---

## 2. Tokens de Design

### 2.1. Paleta de cores
Definidas em [css/custom.css](css/custom.css) `:root`.

| Token | Valor | Uso |
|---|---|---|
| `--primary-color` | `#000000` | Texto principal, botões secundários |
| `--secondary-color` | `#FFF4F1` | Fundos suaves, seções alternadas |
| `--accent-color` | `#acaa59` | Destaques, links, ícones, CTA primário (dourado) |
| `--white-color` | `#FFFFFF` | Fundos, textos sobre fundo escuro |
| `--text-color` | (a documentar) | Texto corrido |

**Regra:** nunca usar cores hexadecimais soltas em CSS — sempre referenciar via `var(--token)`.

### 2.2. Tipografia
- Família principal: **"Fira Sans Condensed", sans-serif** (corpo).
- Família de display: confirmar no template (geralmente "Merriweather" ou similar para títulos).
- Tamanho base: `16px` / `line-height: 1.6`.
- Hierarquia:
  - `h1`: 48-56px (mobile: 32-36px)
  - `h2`: 36-40px (mobile: 28px)
  - `h3`: 28px (mobile: 22px)
  - `h4`: 22px (mobile: 18px)
  - `body`: 16px

### 2.3. Espaçamento
Usar múltiplos de `8px`: `8, 16, 24, 32, 48, 64, 96, 128`.

### 2.4. Breakpoints (Bootstrap 5)
| Nome | Largura |
|---|---|
| xs | <576px |
| sm | ≥576px |
| md | ≥768px |
| lg | ≥992px |
| xl | ≥1200px |
| xxl | ≥1400px |

---

## 3. Componentes Centralizáveis

> Todas as páginas hoje **duplicam** estes blocos. Devem virar **partials** assim que adotarmos PHP includes / SSG.

### 3.1. `<head>` comum
Arquivo proposto: `partials/head.html` (ou `_head.njk` em 11ty).

Contém:
- meta charset, viewport
- meta description / keywords (variáveis por página)
- title (variável)
- 14 links de CSS
- favicon
- Open Graph / Twitter Cards (variáveis)
- canonical (variável)
- JSON-LD base (Organization)

### 3.2. Header / Menu
Arquivo proposto: `partials/header.html`.

Estrutura padrão:
```html
<header class="main-header">
  <nav class="navbar navbar-expand-lg">
    <div class="container">
      <a class="navbar-brand" href="/">
        <img src="/images/logo.png" alt="Paróquia NSR Jericó/PB" style="max-height:55px;">
      </a>
      <button class="navbar-toggler" ...>...</button>
      <div class="collapse navbar-collapse main-menu">
        <ul class="navbar-nav mr-auto" id="menu">
          <!-- itens via loop / dados -->
        </ul>
        <div class="header-cta">
          <a href="/contato/" class="btn-default">Fale Conosco</a>
        </div>
      </div>
    </div>
  </nav>
</header>
```

**Itens de menu (centralizados em `data/menu.json`):**
```json
[
  { "label": "Página Inicial", "href": "/", "id": "home" },
  { "label": "História", "href": "/historia/", "id": "historia" },
  { "label": "Pároco", "href": "/paroco/", "id": "paroco" },
  { "label": "Sacramentos", "href": "/sacramentos/", "id": "sacramentos" },
  { "label": "Ministérios", "href": "/ministerios/", "id": "ministerios" },
  { "label": "Eventos", "href": "/eventos/", "id": "eventos" },
  { "label": "Agenda Litúrgica", "href": "/agenda-liturgica/", "id": "agenda" },
  { "label": "Galeria", "href": "/galeria/", "id": "galeria" },
  { "label": "Artigos", "href": "/artigos/", "id": "artigos" },
  { "label": "Contato", "href": "/contato/", "id": "contato" }
]
```

A página atual marca seu item com `class="active"` automaticamente via lógica do template/SSG.

### 3.3. Footer
Arquivo proposto: `partials/footer.html`.

Blocos do footer Avenix:
- Coluna 1: logo + descrição da paróquia + redes sociais
- Coluna 2: links rápidos (subset do menu)
- Coluna 3: contato (telefone, WhatsApp, e-mail, endereço)
- Coluna 4: newsletter ou últimas publicações
- Linha de copyright

### 3.4. Componentes reutilizáveis identificados

| Componente | Onde aparece hoje | Sugestão de partial |
|---|---|---|
| **Page header / breadcrumb** | Todas as páginas internas | `partials/page-header.html(titulo, breadcrumb[])` |
| **Card de evento** | `index.html`, `eventos.html` | `partials/event-card.html(evento)` |
| **Card de blog** | `index.html`, `blog.html` | `partials/post-card.html(post)` |
| **Card de ministério** | `index.html`, `ministries.html` | `partials/ministry-card.html(ministerio)` |
| **Card de campanha** | `index.html`, `campaign.html` | `partials/campaign-card.html(campanha)` |
| **Bloco CTA** ("Junte-se à nossa comunidade") | Várias páginas | `partials/cta.html(titulo, texto, botao)` |
| **Galeria masonry** | `gallery.html`, `index.html` | `partials/gallery-grid.html(imagens[])` |
| **Player de rádio FAB** | Todas (via `radio-player.js`) | Já centralizado ✓ |
| **Bloco de redes sociais** | Header e footer | `partials/social-links.html` |
| **WhatsApp FAB** | Várias | Centralizar em script único |
| **Preloader** | Todas | `partials/preloader.html` |

### 3.5. Estrutura de pastas proposta
```
/
├── partials/
│   ├── head.html
│   ├── header.html
│   ├── footer.html
│   ├── page-header.html
│   ├── event-card.html
│   ├── post-card.html
│   ├── ministry-card.html
│   ├── campaign-card.html
│   ├── cta.html
│   ├── gallery-grid.html
│   ├── social-links.html
│   └── preloader.html
├── data/
│   ├── menu.json
│   ├── eventos.json
│   ├── ministerios.json
│   ├── posts.json
│   └── paroquia.json   (telefones, endereço, redes — fonte única)
├── pages/
│   ├── home.html
│   ├── historia.html
│   └── ...
└── ...
```

### 3.6. Logos do Site — Gestão via `data/configuracoes.json`

Os logos da paróquia são configurados em `data/configuracoes.json` pelos campos:

| Campo | Onde é usado | Fallback (quando `null`) |
|---|---|---|
| `logo_header_img` | Navbar / header | SVG inline de `images/logo.svg` |
| `logo_footer_img` | Footer (coluna 1) | SVG inline de `images/footer-logo.svg` |
| `logo_loader_img` | Preloader / splash | SVG inline de `images/loader.svg` |

Valor esperado: **caminho relativo à raiz do site**, prefixado por `images/` (obrigatório para que páginas em subdiretórios tenham o caminho reescrito corretamente pelo `build-content.js`).

```json
"logo_header_img": "images/uploads/logos/nome-do-arquivo.png",
"logo_footer_img": "images/uploads/logos/nome-do-arquivo.png",
"logo_loader_img": "images/uploads/logos/nome-do-arquivo.png"
```

**⚠️ Atenção — Dois passes necessários**: o `build-content.js` injeta os logos nos arquivos de `partials/` **após** gerar as páginas de subdiretório (`artigos/`, `eventos/`, `homilias/`). Por isso, ao alterar `configuracoes.json`, execute `npm run all` **duas vezes** para que as páginas em subdiretórios recebam os logos atualizados:

```bash
npm run all  # 1ª passada: atualiza partials/header.html e partials/footer.html
npm run all  # 2ª passada: regera artigos/, eventos/ e homilias/ com as partials corretas
npm run all  # 3ª passada (verificação): deve mostrar apenas "(sem alteracoes)"
```

---

## 4. Regras para Novas Páginas

Toda nova página **deve**:

1. ✅ Seguir o `<head>` padrão do partial (sem CSS extra inline a menos que justificado).
2. ✅ Incluir `header.html` e `footer.html` via partial.
3. ✅ Iniciar com componente `page-header.html` mostrando título + breadcrumb.
4. ✅ Usar grid Bootstrap 5 (`container > row > col-*`).
5. ✅ Respeitar paleta via `var(--*)`.
6. ✅ Imagens em WebP/AVIF com fallback `<picture>`.
7. ✅ Lazy-load em imagens abaixo do *fold*.
8. ✅ Hierarquia de headings correta (`h1` único por página).
9. ✅ Links internos com URL definitiva em PT-BR (ver `MELHORIAS_GERAIS.md` §6).
10. ✅ Botão CTA primário com classe `.btn-default` (estilo dourado do Avenix).
11. ✅ Atributos `alt` em todas as imagens (vazio só se decorativa).
12. ✅ `aria-label` em ícones-only.
13. ✅ Validar no W3C Validator + Lighthouse antes de subir.

### 4.1. Template mínimo de página nova
```html
<!DOCTYPE html>
<html lang="pt-br">
<head>
  {{> head titulo="Nome da Página — Paróquia NSR Jericó/PB"
            descricao="Descrição de 150-160 caracteres em PT-BR."
            url="https://pascomjerico.com.br/nome-da-pagina/"
            imagem="https://pascomjerico.com.br/images/og-nome-da-pagina.jpg" }}
</head>
<body>
  {{> preloader }}
  {{> header pagina="id-do-item-de-menu" }}

  {{> page-header titulo="Título da Página" breadcrumb=[{"label":"Início","href":"/"},{"label":"Título da Página"}] }}

  <main id="main-content">
    <section class="section-padding">
      <div class="container">
        <!-- conteúdo da página -->
      </div>
    </section>

    {{> cta titulo="..." texto="..." botao={"label":"...","href":"..."} }}
  </main>

  {{> footer }}
  {{> scripts }}
</body>
</html>
```

---

## 5. Padrões de Botões

| Classe | Uso | Exemplo |
|---|---|---|
| `.btn-default` | CTA primário (dourado Avenix) | "Doar agora", "Inscreva-se" |
| `.btn-default.btn-outline` | CTA secundário | "Saiba mais" |
| `.readmore-btn` | Link "Leia mais" em cards | Cards de blog/evento |
| `.btn-whatsapp` (a criar) | Atalho WhatsApp | Botão verde flutuante |

---

## 6. Padrões de Seção

Estrutura recomendada para qualquer seção da home/landing:
```html
<section class="section-padding [bg-secondary]">
  <div class="container">
    <div class="section-title text-center" data-aos="fade-up">
      <span class="sub-title">SUBTÍTULO EM CAIXA ALTA</span>
      <h2>Título da Seção</h2>
      <p>Texto introdutório opcional.</p>
    </div>
    <div class="row">
      <!-- cards / conteúdo -->
    </div>
  </div>
</section>
```

---

## 7. Acessibilidade — Padrões Obrigatórios

- Skip link no topo do `<body>`.
- `<main id="main-content">` em todas as páginas.
- `aria-current="page"` no item de menu ativo.
- `aria-label` em todo `<a>`/`<button>` que tenha apenas ícone.
- Contraste mínimo 4.5:1 para texto normal, 3:1 para texto grande.
- Foco visível com `outline: 2px solid var(--accent-color); outline-offset: 2px;`.
- `prefers-reduced-motion`: desativar animações WOW.js / GSAP.

---

## 8. Versionamento de Assets

Quando adotar build:
- CSS final: `/css/main.[hash].css`
- JS final: `/js/main.[hash].js`
- Cache HTTP: `Cache-Control: public, max-age=31536000, immutable` para `.css`/`.js` versionados.

Sem build: usar `?v=YYYYMMDD` em `<link href="css/custom.css?v=20260430">`.

---

## 9. Como evoluir este documento

- Toda alteração visual significativa **deve** atualizar este `.md`.
- Quando adicionar novo componente, criar entrada na §3.4.
- Quando adicionar novo token (cor, espaçamento), criar entrada na §2.
- PRs que quebrem este padrão devem ser rejeitados pelo Agente Frontend UI/UX (ver `AGENTES_SKILLS.md`).
