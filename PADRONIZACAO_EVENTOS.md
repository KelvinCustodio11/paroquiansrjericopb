# PADRONIZAÇÃO DE EVENTOS — Paróquia NSR Jericó/PB

> Padrão obrigatório para cadastro e publicação de eventos em `/eventos/`.
> Visual segue o template **Avenix Church**.

---

## 1. Estrutura de metadados (frontmatter)

```yaml
---
titulo: "Assembleia Diocesana da PASCOM 2026"
slug: "assembleia-pascom-2026"
url: "/eventos/assembleia-pascom-2026/"
categoria: "pastoral"             # ver §3
status: "agendado"                # agendado | em-andamento | encerrado | cancelado
destaque: true                    # aparece na home

# Quando
data_inicio: "2026-05-10"         # ISO 8601
data_fim: "2026-05-10"            # ISO (igual a inicio se evento de 1 dia)
horario_inicio: "08:00"           # HH:MM
horario_fim: "17:00"
fuso_horario: "America/Recife"
recorrencia: null                 # null | "semanal" | "mensal" | "anual"

# Onde
local:
  nome: "Salão Paroquial"
  endereco: "Praça da Matriz, s/n"
  bairro: "Centro"
  cidade: "Jericó"
  estado: "PB"
  cep: "58835-000"
  pais: "BR"
  mapa:
    latitude: -6.5500
    longitude: -38.0000
    google_maps_url: "https://maps.google.com/?q=Paróquia+NSR+Jericó+PB"
    waze_url: "https://waze.com/ul?ll=-6.5500,-38.0000"
    embed_iframe: "<iframe src='https://www.google.com/maps/embed?pb=...' width='100%' height='400' style='border:0;' allowfullscreen='' loading='lazy'></iframe>"

# Imagens
imagem_capa:
  url: "/images/eventos/2026/05/assembleia-pascom-2026-capa.webp"
  alt: "Cartaz da Assembleia da PASCOM 2026"
  largura: 1200
  altura: 630
imagem_banner:
  url: "/images/eventos/2026/05/assembleia-pascom-2026-banner.webp"
  alt: "Banner interno da Assembleia da PASCOM 2026"
  largura: 1920
  altura: 800
galeria:                          # opcional, fotos pós-evento
  - { url: "/images/eventos/2026/05/foto-01.webp", alt: "..." }

# Conteúdo
descricao_curta: "Encontro anual de comunicadores católicos da Diocese de Cajazeiras."
descricao_completa: |
  Texto longo em Markdown ou HTML descrevendo o evento, programação,
  palestrantes, público-alvo, requisitos etc.
programacao:
  - { hora: "08:00", titulo: "Credenciamento e café" }
  - { hora: "09:00", titulo: "Missa de abertura" }
  - { hora: "10:30", titulo: "Palestra: Comunicação e Evangelização" }

# Inscrição / Participação
inscricao:
  obrigatoria: true
  link: "https://forms.google.com/..."   # ou interno: "/eventos/assembleia-pascom-2026/inscricao/"
  vagas_total: 150
  vagas_restantes: 80
  encerramento_inscricao: "2026-05-08T23:59:00-03:00"
  valor: 0                            # em reais; 0 = gratuito
  formas_pagamento: []                # ["pix", "dinheiro", "cartao"]
  pix_chave: null

# Contato / organização
organizador:
  nome: "PASCOM Paróquia NSR Jericó/PB"
  responsavel: "Maria da Silva"
  telefone: "+55-83-99999-9999"
  whatsapp_url: "https://wa.me/5583999999999?text=Quero%20participar%20da%20Assembleia%20PASCOM%202026"
  email: "pascom@pascomjerico.com.br"

# CTA
cta_inscricao:
  label: "Inscreva-se gratuitamente"
  href: "https://forms.google.com/..."
  estilo: "primary"                  # primary | secondary | whatsapp

# Redes
compartilhamento:
  whatsapp_texto: "Participe da Assembleia da PASCOM 2026!"
  hashtags: ["#PASCOM2026", "#JericoPB"]

# SEO
seo:
  titulo_seo: "Assembleia Diocesana PASCOM 2026 — Paróquia NSR Jericó/PB"
  descricao_seo: "10 de maio de 2026, das 8h às 17h, no Salão Paroquial. Inscrições gratuitas. Confira a programação completa."
  imagem_og: "/images/eventos/2026/05/assembleia-pascom-2026-og.webp"

publicado: true
---
```

---

## 2. Estrutura visual da página de evento

Padrão Avenix Church para `/eventos/{slug}/`:

1. **Page-header** com título + breadcrumb (Início > Eventos > {Título})
2. **Imagem banner** (1920×800)
3. **Bloco principal (2 colunas)**:
   - Esquerda (8/12):
     - Descrição completa
     - Programação (lista com horários)
     - Galeria (se houver)
   - Direita (4/12) — **sidebar fixa em desktop**:
     - Card de inscrição (CTA primário)
     - Data, horário, local resumidos
     - Botão **WhatsApp** verde
     - Botão **adicionar ao Google Calendar / iCal**
     - Compartilhamento social
4. **Mapa** (Google Maps embed, full-width)
5. **Como chegar** (links Google Maps + Waze)
6. **Organizador** (foto/logo + contato)
7. **Eventos relacionados** (3 cards)
8. **CTA final** (newsletter ou outro evento)

---

## 3. Categorias de evento

| Slug | Label | Cor (badge) |
|---|---|---|
| `liturgico` | Litúrgico | `#7B2D26` |
| `sacramental` | Sacramental | `#1F5673` |
| `pastoral` | Pastoral | `#acaa59` (cor accent) |
| `formacao` | Formação | `#3D5A4D` |
| `caridade` | Caridade | `#9C3848` |
| `juventude` | Juventude | `#E76F51` |
| `infantil` | Infantil | `#F4A261` |
| `cultural` | Cultural | `#264653` |
| `festivo` | Festa Padroeira / Festividades | `#B8860B` |
| `retiro` | Retiro / Encontro | `#6B4226` |

---

## 4. SEO obrigatório por evento

```html
<title>{{titulo_seo || titulo}} — Paróquia NSR Jericó/PB</title>
<meta name="description" content="{{descricao_seo || descricao_curta}}">

<meta property="og:type" content="event">
<meta property="og:title" content="{{titulo}}">
<meta property="og:description" content="{{descricao_curta}}">
<meta property="og:url" content="https://pascomjerico.com.br{{url}}">
<meta property="og:image" content="https://pascomjerico.com.br{{imagem_og}}">
<meta property="og:locale" content="pt_BR">

<meta name="twitter:card" content="summary_large_image">
<link rel="canonical" href="https://pascomjerico.com.br{{url}}">

<!-- JSON-LD Event (CRÍTICO para Google Events) -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Event",
  "name": "{{titulo}}",
  "description": "{{descricao_curta}}",
  "startDate": "{{data_inicio}}T{{horario_inicio}}:00-03:00",
  "endDate": "{{data_fim}}T{{horario_fim}}:00-03:00",
  "eventAttendanceMode": "https://schema.org/OfflineEventAttendanceMode",
  "eventStatus": "https://schema.org/EventScheduled",
  "location": {
    "@type": "Place",
    "name": "{{local.nome}}",
    "address": {
      "@type": "PostalAddress",
      "streetAddress": "{{local.endereco}}",
      "addressLocality": "{{local.cidade}}",
      "addressRegion": "{{local.estado}}",
      "postalCode": "{{local.cep}}",
      "addressCountry": "{{local.pais}}"
    }
  },
  "image": ["https://pascomjerico.com.br{{imagem_capa.url}}"],
  "organizer": {
    "@type": "Organization",
    "name": "{{organizador.nome}}",
    "url": "https://pascomjerico.com.br/"
  },
  "offers": {
    "@type": "Offer",
    "url": "https://pascomjerico.com.br{{url}}",
    "price": "{{inscricao.valor || 0}}",
    "priceCurrency": "BRL",
    "availability": "https://schema.org/InStock",
    "validFrom": "{{data_publicacao}}"
  }
}
</script>
```

**Atenção:** `eventStatus` deve refletir `status` do frontmatter:
- `agendado` → `EventScheduled`
- `em-andamento` → `EventScheduled`
- `cancelado` → `EventCancelled`
- `adiado` → `EventPostponed`
- `online` (futuro) → `OnlineEventAttendanceMode`

---

## 5. CTAs obrigatórios

Todo evento **deve** ter:
1. **Botão primário de inscrição** (ou "Confirmar presença" se gratuito sem inscrição).
2. **Botão WhatsApp** com mensagem pré-formatada (`https://wa.me/5583XXXXXXXX?text=...`).
3. **Botão "Adicionar ao calendário"** (Google Calendar / iCal `.ics`).
4. **Compartilhamento social** (WhatsApp, Facebook, copiar link).

### Exemplo HTML:
```html
<div class="event-cta-box">
  <a href="{{cta_inscricao.href}}" class="btn-default btn-block">{{cta_inscricao.label}}</a>
  <a href="{{organizador.whatsapp_url}}" class="btn-whatsapp btn-block" target="_blank" rel="noopener">
    <i class="fa-brands fa-whatsapp" aria-hidden="true"></i> Falar no WhatsApp
  </a>
  <a href="{{google_calendar_url}}" class="btn-default btn-outline btn-block" target="_blank" rel="noopener">
    <i class="fa-regular fa-calendar-plus" aria-hidden="true"></i> Adicionar ao Google Calendar
  </a>
</div>
```

---

## 6. Padrão de imagens para eventos

| Tipo | Dimensões (px) | Aspect ratio | Peso máx | Uso |
|---|---|---|---|---|
| **Capa / Cartaz** | 1200 × 630 | 1.91:1 | 200 KB | Card listagem, OG |
| **Banner interno** | 1920 × 800 | 2.4:1 | 350 KB | Topo da página do evento |
| **Cartaz vertical (stories)** | 1080 × 1920 | 9:16 | 300 KB | Stories Instagram/WhatsApp |
| **Quadrada (feed Instagram)** | 1080 × 1080 | 1:1 | 250 KB | Feed Instagram |
| **Thumbnail** | 600 × 400 | 3:2 | 80 KB | Cards menores |
| **Galeria pós-evento** | 1600 × 1066 | 3:2 | 250 KB | Lightbox |
| **Foto do organizador/responsável** | 200 × 200 | 1:1 | 30 KB | Box organizador |
| **WhatsApp preview** | 400 × 400 | 1:1 | 50 KB | Compartilhamento |

### Regras
- Sempre **WebP** com fallback `<picture>`.
- Nomes em kebab-case: `{slug}-{tipo}.webp`.
- Pasta: `/images/eventos/{ano}/{mes}/`.
- `alt` descritivo em PT-BR (não usar "imagem do evento").
- Cartazes feitos em ferramentas como Canva devem ser exportados em PNG → otimizados para WebP.

---

## 7. Recorrência (eventos periódicos)

Eventos como **missas semanais**, **terço diário**, **adoração mensal** devem usar `recorrencia`:

```yaml
recorrencia: "semanal"
recorrencia_detalhes:
  dias_semana: ["domingo"]           # ["segunda","terca","quarta","quinta","sexta","sabado","domingo"]
  horarios: ["07:00", "09:00", "19:00"]
  data_termino: null                 # null = indefinido
```

Para **agenda litúrgica** (missas regulares), usar página dedicada `/agenda-liturgica/` em vez de criar evento individual.

---

## 8. Botão WhatsApp — padrão

URL formato:
```
https://wa.me/55{DDD}{NUMERO}?text={MENSAGEM_URL_ENCODED}
```

Mensagem padrão sugerida:
```
Olá! Vi o evento "{titulo}" no site da Paróquia NSR Jericó/PB e gostaria de mais informações.
```

Em código:
```js
const msg = encodeURIComponent(`Olá! Vi o evento "${titulo}" no site da Paróquia NSR Jericó/PB e gostaria de mais informações.`);
const url = `https://wa.me/5583999999999?text=${msg}`;
```

---

## 9. Status de evento — exibição

| Status | Badge | Comportamento |
|---|---|---|
| `agendado` | Verde "Em breve" | CTA inscrição visível |
| `em-andamento` | Amarelo pulsante "Acontecendo agora" | CTA inscrição oculto, WhatsApp visível |
| `encerrado` | Cinza "Encerrado" | CTA inscrição oculto, galeria visível |
| `cancelado` | Vermelho "Cancelado" | Mensagem destacada explicando motivo |
| `adiado` | Laranja "Adiado" | Nova data destacada |

---

## 10. Checklist de publicação

- [ ] `titulo` claro (50-60 caracteres)
- [ ] `slug` único, kebab-case
- [ ] `data_inicio`, `horario_inicio` em formato ISO/HH:MM
- [ ] `local` completo (nome + endereço + cidade + UF)
- [ ] `mapa.embed_iframe` ou `latitude/longitude` preenchidos
- [ ] `imagem_capa` (1200×630) em WebP < 200 KB com `alt`
- [ ] `imagem_banner` (1920×800) se for página própria
- [ ] `descricao_curta` (140-160 caracteres)
- [ ] `programacao` detalhada com horários
- [ ] `inscricao.link` válido (ou marcar `obrigatoria: false`)
- [ ] `organizador` com nome + WhatsApp
- [ ] CTA primário definido
- [ ] Botão WhatsApp com mensagem pré-formatada
- [ ] Link "Adicionar ao Google Calendar" gerado
- [ ] SEO meta tags + JSON-LD `Event` validados
- [ ] Preview de compartilhamento testado
- [ ] Aprovação do organizador / pároco
- [ ] Após o evento: trocar `status` para `encerrado` e adicionar `galeria`

---

## 11. Geração do link "Adicionar ao Google Calendar"

```
https://www.google.com/calendar/render?action=TEMPLATE
  &text={titulo URL-encoded}
  &dates={data_inicio}T{hora_inicio_sem_colon}00/{data_fim}T{hora_fim_sem_colon}00
  &details={descricao URL-encoded}
  &location={endereco URL-encoded}
  &ctz=America/Recife
```

Exemplo:
```
https://www.google.com/calendar/render?action=TEMPLATE&text=Assembleia+PASCOM+2026&dates=20260510T080000/20260510T170000&details=Encontro+anual+de+comunicadores+cat%C3%B3licos&location=Sal%C3%A3o+Paroquial%2C+Pra%C3%A7a+da+Matriz%2C+Jeric%C3%B3+%E2%80%94+PB&ctz=America/Recife
```
