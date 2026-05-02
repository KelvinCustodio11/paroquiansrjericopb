# SUGESTÃO DE CMS — Paróquia NSR Jericó/PB

> Análise comparativa para escolher a melhor plataforma de gerenciamento de conteúdo do site, eliminando edição manual de HTML.

> **🟢 DECISÃO IMPLEMENTADA (2026-04-30):** Laravel 11 + Filament 3 escolhido e em produção.
> Admin: https://admin.pascomjerico.com.br | CMS em `cms/` | Pipeline: `php artisan content:export --build`

---

## 0. Estado Atual do CMS (implementado)

| Recurso | Status |
|---|---|
| Artigos (Blog) | ✅ CRUD + exportação HTML/JSON |
| Eventos | ✅ CRUD + exportação HTML/JSON |
| Homilias | ✅ CRUD + exportação HTML/JSON |
| Ministérios | ✅ CRUD + exportação JSON |
| Pároco | ✅ CRUD + exportação JSON |
| Rádios | ✅ CRUD + exportação JSON |
| Galeria de Álbuns | ✅ CRUD + exportação JSON |
| Igreja (config. geral) | ✅ CRUD parcial (sem imagem ainda) |
| Compromissos/Agenda | ✅ CRUD + exportação JSON |
| Configurações do site | ✅ `data/configuracoes.json` |
| Deploy CI/CD | ✅ GitHub Actions → FTP Plesk |
| Autenticação | ✅ Filament Shield (usuário admin criado) |

### Pendências do CMS (próximas iterações)
- Ver backlog em `MELHORIAS_GERAIS.md §12` (itens 0-11).
- `IgrejaResource`: FileUpload de imagem pendente.
- Webhook de rebuild automático ao salvar.
- Gestão de múltiplos usuários admin.

---

## 1. Necessidades funcionais identificadas

A administração precisa gerenciar, sem tocar em código:

| Recurso | Conteúdo |
|---|---|
| **Blog/Artigos** | Posts, categorias, autores, tags, agendamento |
| **Eventos** | Único e recorrente, inscrição, galeria pós-evento |
| **Banners** | Hero da home, banners promocionais |
| **Páginas institucionais** | História, Pároco, Sacramentos, Ministérios, Objetos sagrados, Contato |
| **Imagens** | Upload, otimização automática (WebP), biblioteca |
| **Agenda da igreja** | Missas, terço, adoração, confissões — recorrência |
| **Líderes/Ministérios** | Cadastro com foto, bio, contato |
| **Galerias** | Álbuns de fotos por evento/temática |
| **Newsletter** | Captura de e-mails (LGPD-compliant) |
| **Doações** | Integração PIX/Stripe (futuro) |

### Requisitos transversais
- **Login** com perfis (admin, editor, PASCOM, pároco).
- **Workflow editorial** (rascunho → revisão → publicação).
- **Auditoria** (quem alterou o quê e quando).
- **LGPD**: consentimento, política de privacidade, exportação de dados.
- **Backup** automático.
- **Custo baixo** (orçamento de paróquia).
- **Manutenção fácil** por desenvolvedor freelancer/voluntário.

---

## 2. Comparação das opções

| Critério (peso) | WordPress (tradicional) | WordPress Headless | Strapi | Directus | **Laravel + Filament** | Bagisto | Painel custom |
|---|---|---|---|---|---|---|---|
| **Facilidade de uso (admin)** ⭐⭐⭐⭐⭐ | 5 | 4 | 4 | 4 | **5** | 3 | 3-4 |
| **Curva de aprendizado equipe** | 5 | 3 | 3 | 4 | **4** | 2 | 2 |
| **Personalização visual** ⭐⭐⭐⭐ | 4 | 5 | 5 | 5 | **5** | 3 | 5 |
| **Performance** ⭐⭐⭐⭐ | 3 | 5 | 5 | 4 | **4-5** | 3 | 5 |
| **Segurança (out-of-the-box)** ⭐⭐⭐⭐⭐ | 2 (alvo nº1 de bots) | 3 | 4 | 4 | **5** | 3 | depende |
| **Escalabilidade** ⭐⭐⭐ | 3 | 4 | 4 | 4 | **5** | 4 | 4 |
| **Manutenção futura** ⭐⭐⭐⭐ | 4 | 3 | 4 | 4 | **5** | 3 | 2 |
| **Custo (hosting + licenças)** ⭐⭐⭐⭐ | 5 (~R$15/mês) | 3 | 4 | 4 | **4** (~R$30-60/mês) | 3 | 5 |
| **Comunidade BR** ⭐⭐⭐ | 5 | 4 | 3 | 3 | **5** | 4 | — |
| **LGPD / compliance** | 3 | 3 | 4 | 4 | **5** (controle total) | 3 | 5 |
| **Integrações (PIX, WhatsApp)** | 4 | 4 | 4 | 4 | **5** | 4 | 5 |
| **Mídia / biblioteca de imagens** | 5 | 5 | 4 | 4 | **5** (Spatie Media Library) | 4 | depende |
| **Agendamento / publicação programada** | 5 | 5 | 4 | 4 | **5** | 4 | 3 |
| **Auditoria / log de alterações** | 3 (com plugin) | 3 | 4 | 5 | **5** (Spatie Activitylog) | 3 | depende |
| **Total ponderado** | **3,9** | **3,8** | **4,0** | **4,1** | **🏆 4,7** | **3,2** | **3,5** |

---

## 3. Recomendação principal: **Laravel + Filament**

### Por que?
1. **Filament v3** é um painel admin Laravel de altíssima qualidade, com componentes prontos para CRUD, mídia, login, permissões (Spatie Permission), auditoria.
2. **Domínio total do código** — nenhum lock-in com plugin/marketplace.
3. **Segurança Laravel**: CSRF, XSS, SQL injection prevention nativos.
4. **Filament + plugins gratuitos**: media library, SEO, agendamento, exportação CSV, dashboard.
5. **Comunidade brasileira ativa** (RootsTech, Codecasts, Filament Brasil no Discord).
6. **Custo** moderado: hospedagem PHP (Hostinger/Hetzner/DigitalOcean) ~R$30-60/mês.
7. **Frontend desacoplado**: o site público pode continuar HTML estático (Avenix Church) consumindo a API/Blade, ou virar SPA Inertia + Vue/React no futuro.
8. **Migração suave**: dá pra começar usando **Blade** (server-side) reaproveitando o HTML do template; depois evoluir.
9. **Multi-perfil**: `Pároco`, `PASCOM Editor`, `PASCOM Revisor`, `Voluntário Galeria` — tudo com permissões granulares.

### Stack proposta
```
Laravel 11 (PHP 8.3)
├── Filament 3        (painel admin)
├── Spatie Permission (papéis e permissões)
├── Spatie MediaLibrary (upload e conversão WebP automática)
├── Spatie Activitylog (auditoria)
├── Spatie Sitemap   (geração automática)
├── Spatie SEO       (meta tags, OG, JSON-LD)
├── Filament Spatie Media Library plugin
├── Filament SEO plugin
├── Filament Tiptap Editor (editor rich text + Markdown)
└── Frontend: Blade (com HTML do Avenix) ou API + SSG (11ty)
```

### Custo estimado mensal
| Item | Valor |
|---|---|
| Hospedagem PHP/MySQL (Hostinger Premium ou Cloudways) | R$ 30-50 |
| Domínio `.com.br` | R$ 40/ano (~R$ 4/mês) |
| E-mail profissional (Zoho Free / Workspace ~R$30/mês) | R$ 0-30 |
| Backup automático (incluso ou Backblaze ~R$5) | R$ 0-5 |
| Certificado SSL | grátis (Let's Encrypt) |
| **Total mensal estimado** | **R$ 40-90** |

---

## 4. Alternativas (segunda escolha)

### 4.1. Directus (segundo lugar — 4,1/5)
- Headless CMS open-source em Node.js/PostgreSQL.
- Painel admin lindo e moderno, fora-da-caixa.
- Boa escolha se quiserem **API-first** desde o início (frontend desacoplado).
- Auto-hosting ou cloud (US$ 15-25/mês).
- **Contra**: comunidade BR menor; equipe precisa entender API REST/GraphQL.

### 4.2. Strapi (4,0/5)
- Similar ao Directus, mais maduro em alguns aspectos.
- **Contra**: licença muda de tier mais agressivamente; cloud caro.

### 4.3. WordPress (3,9/5) — **opção pragmática se a equipe é leiga em tecnologia**
- Popularidade absoluta; qualquer um sabe usar.
- Plugins (Elementor / Bricks Builder) replicam o template Avenix.
- **Sérios contras**:
  - Vetor #1 de ataques na internet — exige WAF (Wordfence Pro ~US$99/ano) + atualizações religiosas.
  - Performance ruim sem cache agressivo (LiteSpeed/W3 Total Cache).
  - Risco de plugins quebrarem o layout do template.
- **Quando escolher**: se ninguém na equipe técnica vai dar manutenção contínua e a paróquia tem orçamento para um WP Manager terceirizado (~R$200-400/mês).

### 4.4. WordPress Headless (3,8/5)
- WP só como backend, frontend em Next.js/Astro.
- Combina pior dos dois mundos para uma paróquia: complexidade alta, custo de duas hospedagens.
- **Não recomendado** neste caso.

### 4.5. Painel admin custom (3,5/5)
- Reinvenção da roda. Só compensa se for projeto muito específico.
- **Não recomendado**.

### 4.6. Bagisto (3,2/5)
- E-commerce sobre Laravel. **Inadequado** — site da paróquia não é loja. Tem doações pontuais que se resolvem com Stripe/PIX direto. **Descartado**.

---

## 5. Roadmap de adoção (Laravel + Filament)

### Fase 1 — MVP (4-6 semanas)
- [ ] Provisionar servidor + domínio + SSL.
- [ ] Instalar Laravel 11 + Filament 3 + pacotes Spatie.
- [ ] Modelar entidades: `Post`, `Categoria`, `Tag`, `Autor`, `Evento`, `Ministerio`, `Lider`, `Galeria`, `Foto`, `Pagina`, `Banner`, `Inscricao`, `Newsletter`, `User`.
- [ ] Painel Filament com CRUD completo de cada entidade.
- [ ] Permissões: `pároco`, `editor`, `pascom`, `voluntario`.
- [ ] Migração de conteúdo atual (script de import dos eventos JS hardcoded → DB).
- [ ] Frontend Blade reaproveitando 100% do HTML/CSS/JS do Avenix.

### Fase 2 — Públicação (3-4 semanas)
- [ ] Geração automática de `sitemap.xml`.
- [ ] Meta tags + Open Graph + JSON-LD via plugin SEO.
- [ ] Conversão automática de imagens para WebP (Spatie Media).
- [ ] Cache (Redis ou file cache) para páginas públicas.
- [ ] Newsletter com captura LGPD (Mailcoach ou Mailgun).
- [ ] Agenda litúrgica como entidade própria (recorrência semanal/diária).

### Fase 3 — Engajamento (2-3 semanas)
- [ ] Inscrição em eventos pelo próprio site (formulários + e-mail confirmação).
- [ ] Integração WhatsApp Business API (avisos automáticos).
- [ ] Doações PIX dinâmico + Stripe.
- [ ] Login do paroquiano (área do membro — opcional).
- [ ] Dashboard analítico no Filament (eventos com mais inscrições, posts mais lidos).

### Fase 4 — Otimização contínua
- [ ] PWA (offline para horários e agenda).
- [ ] Notificações push (OneSignal).
- [ ] Multi-paróquia (multi-tenant) se a Diocese aderir.

---

## 6. Riscos e mitigação

| Risco | Mitigação |
|---|---|
| Equipe não sabe Laravel | Contratar dev freelance ~R$2-5k para MVP; Filament tem curva curta; documentar tudo. |
| Hospedagem cair | Backup diário + monitoramento UptimeRobot (grátis). |
| Vazamento de e-mails (LGPD) | Mailcoach self-hosted ou serviço LGPD-compliant; consentimento explícito. |
| Performance de imagens | Spatie Media Library + Filament image optimizer + CDN (Cloudflare grátis). |
| Pároco/PASCOM "quebrarem" o site | Workflow de aprovação no Filament + permissões; sandbox de pré-visualização. |

---

## 7. Decisão final sugerida

> **Adotar Laravel 11 + Filament 3** como CMS oficial da Paróquia NSR Jericó/PB, mantendo o template Avenix Church como base visual via Blade.
>
> **Plano B (se restrição de equipe técnica)**: WordPress + tema premium + Wordfence + Elementor, com contrato anual de manutenção.

A escolha final depende de **quem manterá o sistema nos próximos 3+ anos**:
- **Voluntário/freelancer dev PHP-Laravel disponível** → Filament.
- **Apenas pessoas leigas + orçamento mensal de manutenção terceirizada** → WordPress.

---

## 11. Status atual (sessão de auditoria — branch `chore/auditoria-tecnica-pt-br`)

**Decisão registrada:** **Estratégia híbrida B** — Filament administra
banco; comando artisan `content:export` regenera `data/*.json`; gerador
estático Node (`scripts/build-content.js`) regenera HTMLs; site público
permanece estático.

### Já entregue nesta sessão (PRs 1–5)

| PR | Conteúdo | Status |
|---|---|---|
| 1 | Camada de dados: `data/*.json` (7 entidades) + `schemas/*.schema.json` (draft-07) + `scripts/validate-data.js` + 3 instructions de conteúdo | ✅ commitado |
| 2-3-4 | Gerador estático: `templates/{evento,artigo,homilia}.html` + `scripts/build-content.js` (mini-engine Mustache-like + path-rewrite + idempotência) + 3 páginas de exemplo geradas e validadas (HTTP 200) | ✅ commitado |
| 5 | Componentes dinâmicos: `js/horarios-missa.js` + `js/agenda-pastoral.js` (lêem JSON em runtime, sem rebuild) + integração em `contato.html` e `eventos.html` via `data-component=...` | ✅ commitado |
| 6 | Spike Laravel/Filament em `cms/` (composer.json, migration `Evento`, model com `toJsonExport()`, comando `content:export --build --validate`) | 🟡 esqueleto (sem `composer install`) |

### Workflow editorial atual (sem CMS ainda)

```bash
# 1. Editar JSON
$EDITOR data/eventos.json

# 2. Validar contra schemas
npm run validate-data

# 3. Regerar HTMLs
npm run build:content

# 4. Commit
git add data/ eventos/ artigos/ homilias/
git commit -m "content: ..."
```

Quando o CMS Filament entrar no ar, esses 4 passos viram **um clique**
em `Publicar` no painel admin (que dispara internamente
`php artisan content:export --build --validate`).

### Próximos PRs sugeridos

7. `composer create-project` real em `cms/` (subrepo separado).
8. Migrations + Models para Artigo, Homilia, HorarioMissa+Igreja, Compromisso, Ministerio, Paroco.
9. Filament Resources (Forms espelhando schemas; uploads → `images/uploads/`).
10. Seeders que importam o estado atual de `data/*.json` (migração inicial).
11. RBAC via `spatie/laravel-permission` (perfis: pároco, secretaria, PASCOM).
12. Deploy em `admin.pascomjerico.com.br` (Plesk/Napoleon, PHP 8.2+).
