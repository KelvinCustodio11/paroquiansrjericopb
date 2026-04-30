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

## Estrutura
```
/
├── *.html              # 21 páginas (a migrar para PT-BR — ver MELHORIAS_GERAIS.md §6)
├── form-process.php    # endpoint do formulário de contato (hardenizado)
├── css/                # estilos (Bootstrap, Avenix custom, plugins)
├── js/                 # scripts (jQuery, plugins, scripts próprios da paróquia)
├── images/             # imagens estáticas e uploads/
├── webfonts/           # ícones Font Awesome
├── robots.txt
├── sitemap.xml
└── *.md                # documentação técnica e padrões
```

## Configuração do `form-process.php`
Antes de usar em produção:
1. Edite `EMAIL_TO` no início do arquivo com o e-mail oficial da paróquia.
2. Edite `EMAIL_FROM` com um endereço **do domínio da paróquia** (ex: `nao-responda@paroquiansr.com.br`) — exigido por SPF/DKIM da maioria dos provedores.
3. Garanta que a sessão PHP esteja habilitada no servidor.
4. (Opcional, recomendado) Cadastre chaves do **reCAPTCHA v3** em variáveis de ambiente.

## Identidade visual
**Não criar** layouts paralelos. Toda nova página/feature **deve** seguir o template Avenix Church e os tokens de [PADRONIZACAO_LAYOUT.md](PADRONIZACAO_LAYOUT.md).

## Licença
Template Avenix Church: licenciado via ThemeForest (verificar termos).
Conteúdo (textos, imagens da paróquia): © Paróquia Nossa Senhora dos Remédios — Jericó/PB.
