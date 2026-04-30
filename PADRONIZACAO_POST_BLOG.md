# PADRONIZAÇÃO DE POSTS DO BLOG — Paróquia NSR Jericó/PB

> Padrão obrigatório para toda nova publicação em `/artigos/` (atual `/blog.html`).
> Visual segue o template **Avenix Church** — não criar layouts paralelos.

---

## 1. Estrutura de metadados (frontmatter)

Quando adotarmos um SSG ou CMS, cada post terá os campos abaixo. Em HTML estático, esses dados devem ser preenchidos no `<head>` e no corpo da página.

```yaml
---
titulo: "Título do artigo (50-60 caracteres)"
slug: "titulo-do-artigo"          # URL amigável, kebab-case, sem acentos
url: "/artigos/titulo-do-artigo/"
categoria: "Espiritualidade"      # ver §3
autor:
  nome: "Padre João da Silva"
  cargo: "Pároco"
  foto: "/images/autores/padre-joao.webp"
data_publicacao: "2026-04-30"     # ISO 8601
data_atualizacao: "2026-04-30"
imagem_destaque:
  url: "/images/artigos/2026/04/titulo-do-artigo-destaque.webp"
  alt: "Descrição da imagem em PT-BR"
  largura: 1200
  altura: 630
resumo: "Resumo de 150-160 caracteres em PT-BR para SEO e listagens."
tempo_leitura: 5                  # minutos, calculado automaticamente
tags: ["maria", "novena", "outubro"]
seo:
  titulo_seo: "Título SEO específico — Paróquia NSR Jericó/PB"
  descricao_seo: "Descrição alternativa para SERP (até 160 caracteres)."
  imagem_og: "/images/artigos/2026/04/titulo-do-artigo-og.webp"
  noindex: false
cta_final:
  tipo: "evento" | "doacao" | "newsletter" | "contato"
  titulo: "Participe da Novena de Nossa Senhora"
  texto: "De 21 a 29 de outubro, todas as noites às 19h."
  botao:
    label: "Ver no calendário"
    href: "/eventos/novena-2026/"
relacionados: ["slug-1", "slug-2", "slug-3"]
publicado: true
---
```

---

## 2. Estrutura de conteúdo

Todo artigo segue a mesma sequência:

1. **Imagem destaque** (1200×630)
2. **Cabeçalho do post**: categoria + data + autor + tempo de leitura
3. **Título H1** (= `titulo` do frontmatter)
4. **Resumo / lead** (parágrafo introdutório em destaque)
5. **Sumário automático** (se artigo > 800 palavras)
6. **Corpo do artigo**:
   - Sub-headings hierárquicos (H2 > H3 > H4)
   - Parágrafos curtos (3-5 linhas)
   - Citações com `<blockquote>` (estilo Avenix)
   - Imagens internas com `<figure>` + `<figcaption>`
   - Listas `<ul>`/`<ol>` quando houver enumeração
7. **Caixa do autor** (foto + bio curta)
8. **Tags clicáveis**
9. **Botões de compartilhamento** (WhatsApp, Facebook, X, copiar link)
10. **CTA final** (configurável por post)
11. **Posts relacionados** (3 cards usando `partials/post-card.html`)
12. **Comentários** (opcional — Disqus, GitHub Discussions ou nativo do CMS)

---

## 3. Categorias oficiais

Categorias permitidas (slug → label):
- `espiritualidade` → Espiritualidade
- `liturgia` → Liturgia
- `eventos` → Eventos
- `pastorais` → Pastorais e Ministérios
- `historia-da-paroquia` → História da Paróquia
- `comunicados` → Comunicados Oficiais
- `formacao` → Formação Cristã
- `juventude` → Juventude
- `familia` → Família
- `caridade` → Caridade e Ação Social

Cada artigo pertence a **uma única categoria principal** (mais tags livres).

---

## 4. Tags

- Sempre em **minúsculo**, sem acentos, separadas por hífen.
- Máximo de **8 tags** por post.
- Reusar tags existentes antes de criar novas (evita fragmentação).
- Exemplos: `nossa-senhora`, `eucaristia`, `quaresma`, `pascoa`, `mes-mariano`, `crisma`, `batismo`.

---

## 5. SEO obrigatório por post

```html
<title>{{titulo_seo || titulo}} — Paróquia NSR Jericó/PB</title>
<meta name="description" content="{{descricao_seo || resumo}}">
<meta name="author" content="{{autor.nome}}">
<meta name="article:published_time" content="{{data_publicacao}}">
<meta name="article:modified_time" content="{{data_atualizacao}}">
<meta name="article:section" content="{{categoria}}">

<!-- Open Graph -->
<meta property="og:type" content="article">
<meta property="og:title" content="{{titulo}}">
<meta property="og:description" content="{{resumo}}">
<meta property="og:url" content="https://paroquiansr.com.br{{url}}">
<meta property="og:image" content="https://paroquiansr.com.br{{imagem_og}}">
<meta property="og:locale" content="pt_BR">
<meta property="article:author" content="{{autor.nome}}">
<meta property="article:published_time" content="{{data_publicacao}}">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{titulo}}">
<meta name="twitter:description" content="{{resumo}}">
<meta name="twitter:image" content="https://paroquiansr.com.br{{imagem_og}}">

<link rel="canonical" href="https://paroquiansr.com.br{{url}}">

<!-- JSON-LD Article -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "{{titulo}}",
  "description": "{{resumo}}",
  "image": "https://paroquiansr.com.br{{imagem_destaque.url}}",
  "datePublished": "{{data_publicacao}}",
  "dateModified": "{{data_atualizacao}}",
  "author": {
    "@type": "Person",
    "name": "{{autor.nome}}",
    "jobTitle": "{{autor.cargo}}"
  },
  "publisher": {
    "@type": "Organization",
    "name": "Paróquia Nossa Senhora dos Remédios",
    "logo": {
      "@type": "ImageObject",
      "url": "https://paroquiansr.com.br/images/logo.png"
    }
  },
  "mainEntityOfPage": "https://paroquiansr.com.br{{url}}"
}
</script>
```

---

## 6. CTA final (obrigatório)

Todo post **deve** terminar com um CTA. Tipos disponíveis:

| Tipo | Quando usar | Componente |
|---|---|---|
| `evento` | Post relacionado a evento próximo | `partials/cta-evento.html` |
| `doacao` | Campanhas, obras, restauração | `partials/cta-doacao.html` |
| `newsletter` | Tema geral de espiritualidade | `partials/cta-newsletter.html` |
| `contato` | Inscrição em sacramentos, atendimento | `partials/cta-contato.html` (com botão WhatsApp) |

---

## 7. Padrão de imagens

Todas as imagens **devem**:
- Ser em **WebP** (com fallback `<picture><source><img></picture>`).
- Ter `alt` descritivo em PT-BR (não usar "imagem", "foto").
- Ter `width` e `height` declarados (evita layout shift).
- Usar `loading="lazy"` exceto a imagem destaque.
- Estar em `/images/artigos/{ano}/{mes}/{slug}-{tipo}.webp`.

### 7.1. Resoluções recomendadas

| Tipo de imagem | Dimensões (px) | Aspect ratio | Peso máx | Uso |
|---|---|---|---|---|
| **Destaque (hero)** | 1200 × 630 | 1.91:1 | 200 KB | Topo do artigo, cards na home |
| **Open Graph / social** | 1200 × 630 | 1.91:1 | 250 KB | Compartilhamento Facebook/WhatsApp |
| **Twitter Card grande** | 1200 × 600 | 2:1 | 250 KB | Twitter/X |
| **Thumbnail (card)** | 600 × 400 | 3:2 | 80 KB | Listagens, posts relacionados |
| **Thumbnail quadrada** | 400 × 400 | 1:1 | 60 KB | Sidebar, widgets |
| **Banner interno** | 1920 × 800 | 2.4:1 | 350 KB | Page-header de seções |
| **Imagem dentro do texto** | 1024 × 576 | 16:9 | 150 KB | Conteúdo do artigo |
| **Foto do autor** | 200 × 200 | 1:1 | 30 KB | Box do autor |
| **Galeria do post** | 1600 × 1066 | 3:2 | 250 KB | Lightbox / galeria |
| **WhatsApp preview** | 400 × 400 | 1:1 | 50 KB | Compartilhamento WhatsApp |
| **Favicon** | 32 × 32 / 192 × 192 / 512 × 512 | 1:1 | 10 KB | Ícone do site |

### 7.2. Regras de nomenclatura
```
{slug-do-artigo}-destaque.webp
{slug-do-artigo}-og.webp
{slug-do-artigo}-thumb.webp
{slug-do-artigo}-corpo-01.webp
{slug-do-artigo}-corpo-02.webp
{slug-do-artigo}-galeria-01.webp
```

### 7.3. Otimização
- Comprimir com **Squoosh**, **ImageOptim** ou **sharp** (CLI) antes de subir.
- Para fotografia, usar quality 80-85.
- Para artes/ícones, preferir **SVG**.
- **Nunca** subir PNG > 500 KB ou JPG > 400 KB.

---

## 8. Slug — regras

- Apenas `a-z`, `0-9` e hífen.
- Sem acentos, sem `ç`, sem espaços.
- Máximo 60 caracteres.
- Refletir o título sem stop-words ("a", "o", "de", "para" → remover).
- **Imutável** após publicação (mudar quebra SEO/links externos). Se precisar mudar, configurar 301.

Exemplos:
| Título | Slug |
|---|---|
| "Novena de Nossa Senhora dos Remédios 2026" | `novena-nossa-senhora-remedios-2026` |
| "Como participar da Pastoral da Juventude" | `como-participar-pastoral-juventude` |
| "Calendário do Mês Mariano" | `calendario-mes-mariano` |

---

## 9. Checklist de publicação

Antes de publicar um post, validar:

- [ ] `titulo` único e descritivo (50-60 caracteres)
- [ ] `slug` único, em kebab-case
- [ ] `categoria` é uma das oficiais (§3)
- [ ] `tags` ≤ 8, reusando existentes quando possível
- [ ] `autor` preenchido com foto
- [ ] `data_publicacao` em ISO 8601
- [ ] `imagem_destaque` 1200×630 em WebP, < 200 KB
- [ ] `resumo` entre 140-160 caracteres
- [ ] Todas as imagens têm `alt` descritivo
- [ ] Hierarquia de headings correta (H1 único, H2 > H3 > H4)
- [ ] Links internos com URL definitiva PT-BR
- [ ] Links externos com `rel="noopener noreferrer"` se `target="_blank"`
- [ ] CTA final configurado
- [ ] Posts relacionados definidos (3)
- [ ] SEO meta tags + JSON-LD validados
- [ ] Preview de compartilhamento testado (Facebook Debugger, Twitter Card Validator)
- [ ] Lighthouse: Performance ≥ 80, SEO ≥ 95, A11y ≥ 90
- [ ] Sem palavras de baixo calão / sensíveis (revisão pastoral)
- [ ] Aprovação do pároco / coordenador da PASCOM (workflow editorial)

---

## 10. Workflow editorial sugerido

1. **Rascunho**: redator cria com `publicado: false`.
2. **Revisão de conteúdo**: PASCOM revisa ortografia, doutrina, tom.
3. **Revisão pastoral**: pároco aprova quando tema for sensível.
4. **Revisão técnica**: SEO, imagens, links.
5. **Publicação**: agendar `data_publicacao` ou publicar imediato.
6. **Divulgação**: gerar imagem para Stories/WhatsApp e postar.
7. **Monitoramento**: ver Search Console em 7/30 dias.
