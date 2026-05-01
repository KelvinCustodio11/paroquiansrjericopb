# Pull Request

## Resumo

<!-- 1-3 frases descrevendo a mudança -->

## Tipo de mudança

- [ ] `feat`: nova funcionalidade
- [ ] `fix`: correção de bug
- [ ] `docs`: apenas documentação
- [ ] `style`: formatação, sem alteração de código
- [ ] `refactor`: refatoração sem mudança funcional
- [ ] `perf`: melhoria de performance
- [ ] `test`: adição/correção de testes
- [ ] `chore`: build, ferramentas, dependências
- [ ] `content`: novo conteúdo editorial (artigo, evento, homilia)

## Checklist obrigatório

- [ ] Rodei `npm run validate-data` localmente (se mexi em `data/*.json` ou `schemas/*.json`)
- [ ] Rodei `npm run build:content` (se mexi em `data/{eventos,artigos,homilias}.json` ou `templates/*.html`)
- [ ] Rodei `npm run build` (se mexi em `partials/*.html`)
- [ ] Sem credenciais hardcoded
- [ ] Inputs validados/sanitizados (se PHP/JS lê dados externos)
- [ ] Links externos com `rel="noopener noreferrer"` quando `target="_blank"`
- [ ] Sem `console.log`/`var_dump` esquecidos

## Checklist de SEO (se nova página)

- [ ] `<title>` único e descritivo (50-60 caracteres)
- [ ] `<meta name="description">` 140-160 caracteres em PT-BR
- [ ] `<link rel="canonical">`
- [ ] Open Graph + Twitter Card
- [ ] JSON-LD apropriado
- [ ] Adicionada ao `sitemap.xml`

## Checklist de acessibilidade

- [ ] Imagens com `alt` (vazio só se decorativa)
- [ ] Ícones-only com `aria-label` no elemento pai + `aria-hidden="true"` no `<i>`
- [ ] Hierarquia de headings correta (1 `<h1>` por página)
- [ ] Contraste de texto adequado

## Como testar

<!-- Passos para revisor reproduzir/validar -->

## Issue relacionada

<!-- Closes #N / Refs #N -->
