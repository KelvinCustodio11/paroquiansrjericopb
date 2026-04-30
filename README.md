# Site Paróquia Nossa Senhora dos Remédios — Jericó/PB

Site institucional da Paróquia NSR de Jericó (Paraíba), construído sobre o template **Avenix Church (ThemeForest)**.

## Stack atual
- HTML5 estático (21 páginas)
- CSS (Bootstrap 5 + custom)
- JavaScript (jQuery + plugins do template + scripts próprios)
- 1 endpoint PHP de contato (`form-process.php`)

## Documentação técnica

| Arquivo | Conteúdo |
|---|---|
| [MELHORIAS_GERAIS.md](MELHORIAS_GERAIS.md) | Auditoria técnica completa, plano de correções, roadmap |
| [PADRONIZACAO_LAYOUT.md](PADRONIZACAO_LAYOUT.md) | Design system, componentes reutilizáveis, regras visuais |
| [PADRONIZACAO_POST_BLOG.md](PADRONIZACAO_POST_BLOG.md) | Padrão de novos posts, SEO, imagens, checklist editorial |
| [PADRONIZACAO_EVENTOS.md](PADRONIZACAO_EVENTOS.md) | Padrão de novos eventos, mapa, CTAs, Schema.org Event |
| [SUGESTAO_CMS.md](SUGESTAO_CMS.md) | Comparativo de CMS e recomendação (Laravel + Filament) |
| [AGENTES_SKILLS.md](AGENTES_SKILLS.md) | Papéis especializados e skills para evolução do projeto |

## Antes de subir qualquer mudança
Leia o **checklist de aceite** em [MELHORIAS_GERAIS.md §11](MELHORIAS_GERAIS.md).

## Build (partials → HTML estático)

Para evitar duplicação de header/footer/scripts entre as 21 páginas, o projeto usa um sistema simples de partials processados por um script Node sem dependências.

```bash
node build.js          # ou: npm run build
```

- **Origem**: `partials/head-css.html`, `partials/header.html`, `partials/footer.html`, `partials/scripts-common.html`.
- **Marcadores**: cada página HTML contém `<!-- @include partials/X.html -->`. O build expande para um par `<!-- @include-start X --> ... <!-- @include-end X -->` (idempotente — pode rodar quantas vezes quiser).
- **Saída**: o build edita os HTMLs **in-place** (sem pasta `dist/`). Funciona em GitHub Pages e Plesk sem configuração extra.
- **Item de menu ativo**: cada página declara `<body data-page="eventos">` e `js/active-nav.js` aplica `class="active"` no link correspondente.

**Workflow para editar header/footer**: edite o partial em `partials/`, rode `node build.js`, comite as mudanças (HTMLs alterados + partial). 

**Migração inicial**: feita uma única vez via `node scripts/migrate-to-partials.js` (não precisa rodar de novo, mas o script fica versionado para referência).


## Estrutura
```
/
├── *.html                  # 21 páginas (a migrar para PT-BR — ver MELHORIAS_GERAIS.md §6)
├── form-process.php        # endpoint do formulário de contato (hardenizado)
├── partials/               # head-css, header, footer, scripts-common (fonte para build)
├── build.js                # script Node que expande @include
├── scripts/
│   └── migrate-to-partials.js   # migração one-time (já executado)
├── css/                    # estilos (Bootstrap, Avenix custom, plugins)
├── js/                     # scripts (jQuery, plugins, scripts próprios da paróquia)
├── images/                 # imagens estáticas e uploads/
├── webfonts/               # ícones Font Awesome
├── _template-avenix/       # 📚 referência — Avenix Church original (NÃO MODIFICAR)
├── _template-paroquia/     # 📚 referência — versão pré-sync com customizações (NÃO MODIFICAR)
├── .github/                # instructions e prompts do Copilot Chat
│   ├── copilot-instructions.md
│   ├── instructions/       # regras por tipo de arquivo (frontend/backend/js)
│   └── prompts/            # comandos /nova-pagina, /novo-post, /auditar-seo etc.
├── .htaccess               # segurança, cache, redirects
├── robots.txt
├── sitemap.xml
└── *.md                    # documentação técnica e padrões
```

## Templates de referência

Duas pastas read-only servem como **fonte visual e estrutural** para novos componentes:

- **`_template-avenix/`** — snapshot da branch `main` (Avenix Church puro, sem customizações).
- **`_template-paroquia/`** — snapshot da branch `developer` antes da sincronização com `production` (Avenix + radio player, liturgia, santo do dia, calendário, etc.).

Nunca modifique nem faça deploy dessas pastas. Use-as como base para copiar HTML/CSS de blocos ainda não-aproveitados. Ambas estão bloqueadas no `.htaccess` e `robots.txt`.

## Branches

- `main` — template Avenix original (não tocar; serve de referência de diff).
- `developer` — branch de integração; abrir PRs daqui.
- `production` — branch de deploy.

## Configuração do `form-process.php`
Antes de usar em produção:
1. Edite `EMAIL_TO` no início do arquivo com o e-mail oficial da paróquia.
2. Edite `EMAIL_FROM` com um endereço **do domínio da paróquia** (ex: `nao-responda@pascomjerico.com.br`) — exigido por SPF/DKIM da maioria dos provedores.
3. Garanta que a sessão PHP esteja habilitada no servidor.
4. (Opcional, recomendado) Cadastre chaves do **reCAPTCHA v3** em variáveis de ambiente.

## Identidade visual
**Não criar** layouts paralelos. Toda nova página/feature **deve** seguir o template Avenix Church e os tokens de [PADRONIZACAO_LAYOUT.md](PADRONIZACAO_LAYOUT.md).

## Licença
Template Avenix Church: licenciado via ThemeForest (verificar termos).
Conteúdo (textos, imagens da paróquia): © Paróquia Nossa Senhora dos Remédios — Jericó/PB.
