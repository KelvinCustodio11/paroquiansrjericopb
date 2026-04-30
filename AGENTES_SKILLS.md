# AGENTES E SKILLS PARA MANUTENÇÃO FUTURA — Paróquia NSR Jericó/PB

> Definição de **agentes especializados** (papéis automatizáveis com IA / responsabilidades humanas) e **skills** recomendadas para evolução contínua do projeto.
>
> **Regra mestra**: todos os agentes **devem respeitar o design original do template Avenix Church** e manter consistência visual em todo o projeto.

---

## 1. Visão Geral dos Agentes

| Agente | Responsabilidade | Aciona quando... |
|---|---|---|
| **Frontend UI/UX** | Mantém padrão visual Avenix Church | Nova página, ajuste de layout, componente novo |
| **Backend / CMS** | Painel admin, permissões, dados dinâmicos | Nova entidade no CMS, integração, autenticação |
| **SEO + Performance** | Indexação, meta tags, velocidade | Antes de publicar conteúdo / release |
| **Segurança + Code Review** | Auditoria contínua, prevenção | Toda PR; revisão trimestral profunda |
| **Conteúdo / Editorial** | Padronização de posts e eventos | Cada novo post/evento |
| **Acessibilidade (a11y)** | WCAG 2.1 AA mínimo | Toda PR de frontend |
| **DevOps / Infra** | Deploy, backup, monitoramento | Configuração inicial e manutenção |

---

## 2. Agente Frontend UI/UX

### Responsabilidades
- Aplicar e preservar o **layout do template Avenix Church**.
- Garantir consistência de paleta, tipografia, espaçamentos (tokens em [PADRONIZACAO_LAYOUT.md](PADRONIZACAO_LAYOUT.md)).
- Construir novas páginas reutilizando componentes existentes.
- Validar **responsividade** em 375 / 768 / 1024 / 1440 px.
- Garantir que toda nova feature visual passe pelo **checklist de aceite** (§11 de [MELHORIAS_GERAIS.md](MELHORIAS_GERAIS.md)).

### Skills recomendadas
- **HTML5 semântico** (landmarks, microformatos).
- **CSS moderno**: custom properties, Grid, Flexbox, container queries.
- **Bootstrap 5** (grid, utilitários, breakpoints).
- **Sass/PostCSS** (quando adotar build).
- **GSAP / WOW.js / Swiper** (já usados no template).
- **Figma** para mockups antes de codar.
- **Lighthouse / DevTools** para auditoria visual.
- **Storybook** (futuro) para catalogar componentes.

### Critérios de aprovação de PR
- [ ] Usa partials/componentes existentes (não duplica HTML).
- [ ] Usa apenas tokens de [PADRONIZACAO_LAYOUT.md](PADRONIZACAO_LAYOUT.md).
- [ ] Responsivo nos 4 breakpoints.
- [ ] Sem regressão visual em outras páginas.
- [ ] Imagens em WebP com `loading="lazy"` e `width`/`height`.

---

## 3. Agente Backend / CMS

### Responsabilidades
- Painel administrativo (Laravel + Filament — ver [SUGESTAO_CMS.md](SUGESTAO_CMS.md)).
- Modelagem de dados (entities, relacionamentos).
- **Autenticação** segura (Sanctum, 2FA opcional).
- **Autorização** granular (Spatie Permission).
- **API REST/GraphQL** para frontend.
- **Workflow editorial** (rascunho → revisão → publicação).
- **Auditoria** (Spatie Activitylog).

### Skills recomendadas
- **PHP 8.3+** moderno (typed properties, enums, readonly).
- **Laravel 11**: Eloquent, Queues, Jobs, Events, Policies.
- **Filament 3**: Resources, Forms, Tables, Actions, Widgets.
- **MySQL/PostgreSQL** + migrations + seeds.
- **Redis** para cache e queues.
- **PHPUnit / Pest** para testes.
- **OpenAPI** para documentar API.
- **Spatie ecosystem** (Permission, MediaLibrary, Activitylog, Sitemap, Backup).

### Critérios de aprovação
- [ ] Migrations reversíveis.
- [ ] Validação no Form Request (não no Controller).
- [ ] Authorization via Policy.
- [ ] Logs de operações sensíveis.
- [ ] Testes para regras de negócio críticas.
- [ ] Sem `dd()`, `dump()`, `var_dump()` em código produção.

---

## 4. Agente SEO + Performance

### Responsabilidades
- **Meta tags** preenchidas (title, description, OG, Twitter, canonical).
- **Structured data** JSON-LD (Organization, Article, Event, BreadcrumbList).
- **Sitemap.xml** atualizado.
- **Robots.txt** correto.
- **Core Web Vitals** (LCP < 2.5s, INP < 200ms, CLS < 0.1).
- **Performance de imagens** (WebP/AVIF, srcset, lazy-load).
- **Bundling/minificação** de CSS/JS.
- **CDN** (Cloudflare) e cache HTTP corretos.
- **Indexação** monitorada via Google Search Console + Bing Webmaster.

### Skills recomendadas
- **Google Search Console** + **Bing Webmaster Tools**.
- **Lighthouse / PageSpeed Insights / WebPageTest**.
- **Schema.org** vocabulary.
- **Cloudflare** (CDN, WAF, Workers).
- **Image optimization**: Squoosh, sharp, ImageMagick.
- **Webpack / Vite / Rollup** (bundlers).
- **GTmetrix / Pingdom** para monitoramento.
- **Core Web Vitals** profundo.
- **HTTP caching** (ETag, Cache-Control, Last-Modified).

### Critérios de aprovação
- [ ] Lighthouse ≥ 80 Performance, ≥ 95 SEO, ≥ 90 A11y, ≥ 95 Best Practices.
- [ ] Preview de compartilhamento testado (Facebook Debugger, Twitter Card Validator).
- [ ] JSON-LD validado em [validator.schema.org](https://validator.schema.org).
- [ ] Sem URLs em inglês após migração para PT-BR.
- [ ] Redirects 301 configurados para URLs antigas.

---

## 5. Agente Segurança + Code Review

### Responsabilidades
- **Auditoria técnica** trimestral.
- **Revisão contínua** de toda PR (security checklist).
- **Prevenção de falhas** OWASP Top 10.
- **Sanitização e validação** de input.
- **Headers HTTP de segurança** (CSP, HSTS, X-Frame-Options).
- **Atualizações de dependências** (composer/npm audit semanal).
- **Backup** verificado mensalmente.
- **Plano de resposta a incidentes**.
- **LGPD compliance** (consentimento, exportação, exclusão).

### Skills recomendadas
- **OWASP Top 10** atualizado (2021/2025).
- **PHPStan / Psalm** (análise estática PHP).
- **ESLint / Stylelint** (frontend).
- **Snyk / Dependabot** (vulnerabilidades em dependências).
- **Burp Suite Community** (pen testing básico).
- **Security headers**: CSP, HSTS, X-Frame-Options, Referrer-Policy, Permissions-Policy.
- **SSL Labs** para validar TLS.
- **Mozilla Observatory** para validar headers.
- **Rate limiting** (Laravel Throttle, fail2ban).
- **WAF** (Cloudflare WAF rules).

### Critérios de aprovação de PR
- [ ] Sem credenciais hardcoded.
- [ ] Toda input validada e sanitizada.
- [ ] Output escapado conforme contexto (HTML, JS, URL).
- [ ] CSRF token em todo formulário.
- [ ] SQL via prepared statements / ORM.
- [ ] Upload de arquivos com validação MIME + tamanho.
- [ ] Permissões granulares (não usar `is_admin` boolean).
- [ ] Logs sem dados sensíveis (PII, tokens, senhas).

### Agendamento
- **Diário**: dependabot/snyk auto-PR.
- **Semanal**: code review backlog + composer/npm audit.
- **Mensal**: backup restore drill, log review.
- **Trimestral**: pen test interno + revisão completa do checklist OWASP.

---

## 6. Agente Conteúdo / Editorial

### Responsabilidades
- Garantir que **todo post** segue [PADRONIZACAO_POST_BLOG.md](PADRONIZACAO_POST_BLOG.md).
- Garantir que **todo evento** segue [PADRONIZACAO_EVENTOS.md](PADRONIZACAO_EVENTOS.md).
- Revisar ortografia, gramática e tom institucional.
- Validar imagens (resolução, peso, alt).
- Aprovação pastoral em conteúdo doutrinal.

### Skills recomendadas
- Português culto (norma padrão).
- **Copywriting** para SEO (sem clickbait).
- **Markdown** / editor rich-text.
- Conhecimento básico de **direito autoral** (imagens livres: Unsplash, Pexels, Pixabay).
- **Canva / Photoshop / GIMP** para cartazes.
- **Hootsuite / Buffer** para agendamento social.

---

## 7. Agente Acessibilidade (a11y)

### Responsabilidades
- Garantir **WCAG 2.1 AA** em todo conteúdo publicado.
- Validar contraste de cores.
- Verificar `alt` em imagens, `label` em forms, `aria-label` em ícones-only.
- Testar com leitores de tela (NVDA / VoiceOver).
- Garantir navegação 100% via teclado.

### Skills recomendadas
- **axe DevTools** (extensão Chrome/Firefox).
- **Lighthouse a11y audit**.
- **WAVE** (Web Accessibility Evaluation Tool).
- **NVDA** (Windows) / **VoiceOver** (macOS) / **TalkBack** (Android).
- **WCAG 2.1 / 2.2** vocabulary.
- **ARIA Authoring Practices**.

---

## 8. Agente DevOps / Infra

### Responsabilidades
- **Deploy** automatizado (CI/CD).
- **Backup** diário + restore mensal validado.
- **Monitoramento** (UptimeRobot, Healthchecks).
- **Logs centralizados** (Papertrail, Loki, ou arquivo).
- **TLS/SSL** sempre válido (Let's Encrypt auto-renovação).
- **Rollback** em < 5 min em caso de falha.
- **Custos** sob controle e revisados trimestralmente.

### Skills recomendadas
- **Linux** (Ubuntu/Debian) básico-intermediário.
- **GitHub Actions** ou **GitLab CI** (pipelines).
- **Docker / Docker Compose** (opcional para dev).
- **Nginx / Apache** + PHP-FPM tuning.
- **MySQL / MariaDB** backup + restore.
- **Cloudflare** (DNS, CDN, WAF).
- **Hostinger / Hetzner / DigitalOcean / Cloudways** (hosting).
- **rsync / restic / Backblaze B2** (backup).
- **Monitoring**: UptimeRobot, Better Stack, Healthchecks.io.

---

## 9. Matriz de responsabilidades (RACI)

| Tarefa | Frontend | Backend | SEO | Security | Conteúdo | A11y | DevOps |
|---|---|---|---|---|---|---|---|
| Nova página institucional | **R** | C | A | C | C | A | I |
| Novo post no blog | C | I | A | I | **R** | A | I |
| Novo evento | C | C | A | I | **R** | A | I |
| Nova feature CMS | C | **R** | I | A | I | I | C |
| Login/permissões | I | **R** | I | A | I | I | C |
| Mudança de header/footer | **R** | C | A | C | I | A | I |
| Otimização de imagens | C | I | **R** | I | C | I | C |
| Deploy em produção | I | C | I | A | I | I | **R** |
| Auditoria OWASP | I | C | I | **R** | I | I | C |
| Backup / restore | I | I | I | A | I | I | **R** |

**Legenda:** R=Responsável | A=Aprovador | C=Consultado | I=Informado

---

## 10. Skills transversais (todo o time)

- **Git** + GitHub Flow (branches, PRs, code review).
- **Markdown** para documentação.
- **Português** claro e objetivo.
- Conhecimento básico do **template Avenix Church** (componentes, paleta).
- Leitura mínima dos 5 documentos `.md` deste projeto:
  - [MELHORIAS_GERAIS.md](MELHORIAS_GERAIS.md)
  - [PADRONIZACAO_LAYOUT.md](PADRONIZACAO_LAYOUT.md)
  - [PADRONIZACAO_POST_BLOG.md](PADRONIZACAO_POST_BLOG.md)
  - [PADRONIZACAO_EVENTOS.md](PADRONIZACAO_EVENTOS.md)
  - [SUGESTAO_CMS.md](SUGESTAO_CMS.md)

---

## 11. Como invocar cada agente (uso prático com IA)

Quando trabalhar com Copilot/IA, use prompts dirigidos ao papel:

```
@Frontend UI/UX: Crie uma nova seção "Doe agora" para a home, seguindo o
template Avenix Church e os tokens de PADRONIZACAO_LAYOUT.md.

@Backend / CMS: Modele a entidade Doação no Filament com PIX e Stripe,
permissão "tesoureiro", auditoria habilitada.

@SEO: Audite os meta tags de todas as páginas e gere um relatório com
pendências.

@Segurança: Revise o form-process.php hardenizado e aponte vulnerabilidades
remanescentes.

@Conteúdo: Revise este post seguindo PADRONIZACAO_POST_BLOG.md e
sugira melhorias.

@A11y: Valide a página /eventos/ e liste violações WCAG 2.1 AA.

@DevOps: Configure deploy automatizado via GitHub Actions para produção.
```

---

## 12. Cadência sugerida

- **Diária**: agentes Frontend, Backend, Conteúdo (work-in-progress).
- **A cada PR**: Security + A11y + SEO (review obrigatório).
- **Semanal**: dependency audit (Security), backup verify (DevOps).
- **Mensal**: relatório de Performance + SEO; revisão editorial.
- **Trimestral**: auditoria OWASP completa; revisão de UX/UI; revisão de custos infra.
- **Anual**: revisão estratégica do roadmap (CMS evolução, integrações, novos canais).
