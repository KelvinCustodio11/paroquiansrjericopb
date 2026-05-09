# Instruções gerais — Site Paróquia NSR Jericó/PB

> Estas instruções são lidas automaticamente pelo Copilot Chat em toda interação neste repositório.

## Contexto do projeto

Site institucional da **Paróquia Nossa Senhora dos Remédios — Jericó/PB**, construído sobre o template ThemeForest **Avenix Church**. Stack atual: HTML5 estático (21 páginas) + Bootstrap 5 + jQuery + plugins do template + scripts próprios + 1 endpoint PHP de contato.

## Regras mestras

1. **Identidade visual única**: toda nova página, componente ou feature **deve** seguir o design do template Avenix Church. Nunca criar layouts paralelos.
2. **Idioma**: responder e gerar conteúdo em **português do Brasil**.
3. **Antes de duplicar HTML**, verificar se já existe componente equivalente no projeto ou nos templates de referência (`_template-avenix/`, `_template-paroquia/`).
4. **Nunca modificar** arquivos dentro de `_template-avenix/` ou `_template-paroquia/` — são pastas de **referência somente leitura**.
5. **Nunca usar** imagens de `_template-avenix/images/` em produção — são demo licenciadas pelo ThemeForest.
6. **Header/footer/scripts comuns** ficam em `partials/`. Para alterar qualquer um deles, edite o arquivo em `partials/` e rode `node build.js` (idempotente). **Nunca** edite manualmente o miolo entre `<!-- @include-start -->` e `<!-- @include-end -->` em uma página — será sobrescrito no próximo build.
7. **Item de menu ativo**: definir via `<body data-page="...">`. Valores aceitos: `home`, `historia`, `pastoral`, `eventos`, `agenda`, `liturgia`, `contato`. O JS `active-nav.js` aplica a classe `.active`.

## Documentação obrigatória — leia antes de propor mudanças

| Arquivo | Quando consultar |
|---|---|
| [`AMBIENTE.md`](../AMBIENTE.md) | **Sempre** que envolver Docker, build, CI/CD, deploy, permissões ou branches |
| [`MELHORIAS_GERAIS.md`](../MELHORIAS_GERAIS.md) | Visão geral, bugs catalogados, plano de URLs PT-BR, roadmap |
| [`PADRONIZACAO_LAYOUT.md`](../PADRONIZACAO_LAYOUT.md) | Tokens de design, componentes reutilizáveis, regras visuais |
| [`PADRONIZACAO_POST_BLOG.md`](../PADRONIZACAO_POST_BLOG.md) | Antes de criar/editar artigo |
| [`PADRONIZACAO_EVENTOS.md`](../PADRONIZACAO_EVENTOS.md) | Antes de criar/editar evento |
| [`SUGESTAO_CMS.md`](../SUGESTAO_CMS.md) | Discussões sobre futuro CMS (Laravel + Filament recomendado) |
| [`AGENTES_SKILLS.md`](../AGENTES_SKILLS.md) | Definição dos papéis especializados |

## Templates de referência (somente leitura)

- **`_template-avenix/`** — Avenix Church original (snapshot de `main`). Use para identificar componentes nativos do template ainda não-utilizados.
- **`_template-paroquia/`** — Site da paróquia em estado intermediário (snapshot da `developer` pré-sync). Use para ver implementações dos componentes próprios (radio player, liturgia, santo do dia, etc.).

Quando precisar copiar HTML/CSS de um bloco existente do template, **prefira copiar literalmente** as classes e estruturas dessas pastas.

## Padrões de código

### HTML
- HTML5 semântico com landmarks (`<main>`, `<nav>`, `<article>`, `<section>`).
- `lang="pt-br"` no `<html>`.
- Hierarquia de headings correta: um único `<h1>` por página.
- Imagens com `alt` descritivo (vazio só se decorativa) + `width`/`height` + `loading="lazy"` (exceto imagem hero).
- Ícones-only (`<i>`) sempre acompanhados de `aria-label` no elemento pai.
- Links externos com `rel="noopener noreferrer"` quando `target="_blank"`.

### CSS
- Sempre usar tokens de `:root` (`var(--primary-color)`, `var(--accent-color)` etc.) — nunca hex solto.
- Mobile-first; respeitar breakpoints Bootstrap 5.
- Não criar arquivo CSS novo — adicionar em `css/custom.css`.

### JavaScript
- Não usar `eval`, `document.write`, `innerHTML` com dados de fontes externas.
- Quando manipular HTML vindo de fetch externo, sanitizar (DOMPurify) ou usar `textContent`.
- Cuidado com chaves de API expostas — nunca hardcoded em JS público.

### PHP
- Sempre `declare(strict_types=1);`.
- Validar com `filter_var`, sanitizar com `htmlspecialchars`/`strip_tags`.
- CSRF token em todo formulário.
- Nunca concatenar dados de usuário em headers de e-mail.

## Segurança — checklist mínimo em qualquer PR

- [ ] Sem credenciais hardcoded.
- [ ] Inputs validados e sanitizados.
- [ ] Output escapado conforme contexto.
- [ ] Links externos com `rel="noopener noreferrer"`.
- [ ] Sem `console.log` ou `var_dump` esquecidos.

## SEO — checklist mínimo em nova página

- [ ] `<title>` único e descritivo (50-60 caracteres).
- [ ] `<meta name="description">` 140-160 caracteres em PT-BR.
- [ ] `<link rel="canonical">`.
- [ ] Open Graph (`og:title`, `og:description`, `og:image`, `og:url`, `og:locale`).
- [ ] Twitter Card.
- [ ] JSON-LD apropriado (`Organization`, `Article`, `Event`, `BreadcrumbList`).
- [ ] Página adicionada ao `sitemap.xml`.

## URLs

- Plano de migração para PT-BR documentado em [`MELHORIAS_GERAIS.md §6`](../MELHORIAS_GERAIS.md).
- **Não renomear** arquivos `.html` sem ativar redirect 301 no `.htaccess`.
- Padrão de URL nova: kebab-case, sem acentos, em português.

## Branches

- `main` — template Avenix original (não tocar).
- `developer` — branch de integração.
- `production` — branch de deploy.
- Trabalho novo sai de `developer`, vai pra PR → `developer`, depois `developer` → `production`.

## Como invocar agentes especializados

Use os comandos `/` no Copilot Chat (definidos em `.github/prompts/`):

- `/nova-pagina` — gera nova página seguindo padrão Avenix
- `/novo-post` — gera novo artigo seguindo `PADRONIZACAO_POST_BLOG.md`
- `/novo-evento` — gera novo evento seguindo `PADRONIZACAO_EVENTOS.md`
- `/auditar-seo` — audita SEO da página atual
- `/revisar-seguranca` — revisa segurança do arquivo atual
- `/revisar-acessibilidade` — checa WCAG 2.1 AA
