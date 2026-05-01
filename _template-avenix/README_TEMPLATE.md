# Template de referência: Avenix Church (ThemeForest) — ORIGINAL

> ⚠️ **NÃO MODIFICAR ARQUIVOS DESTA PASTA.**
> ⚠️ **NÃO USAR EM PRODUÇÃO.** Pasta bloqueada via `.htaccess` e `robots.txt`.

## Origem
- Snapshot da branch **`main`** do repositório, commit `9eb3426`.
- Corresponde ao template ThemeForest **Avenix Church HTML Template** em estado **original** (sem customizações da paróquia).

## Para que serve
- **Referência visual e estrutural**: identificar componentes, blocos e seções nativas do template ainda **não-utilizadas** no site real.
- **Copiar HTML/CSS exatos** ao adicionar novas seções (manter classes, variáveis CSS e padrões de marcação).
- **Comparar** com o site real (`/`) para entender o quanto foi customizado.
- Treinar o Copilot Chat: as instructions em `.github/instructions/` apontam para esta pasta como fonte de verdade do design system.

## Como usar
1. Antes de criar componente novo, abra o arquivo equivalente aqui e veja se já existe algo similar.
2. Copie o HTML/CSS para o site real e ajuste apenas o conteúdo (textos, imagens, links).
3. **Mantenha** as mesmas classes Bootstrap/Avenix para preservar o visual e a responsividade.

## Restrições legais
- O template é licenciado via **ThemeForest** (verificar termos da licença adquirida).
- As **imagens demo** dentro de `images/` desta pasta têm direitos restritos ao preview do template — **não usar** em produção.

## Diferenças principais para o site real
O site real (`/`) é uma versão fortemente customizada deste template, com:
- Player de rádio FAB (`js/radio-player.js`)
- Liturgia diária (`js/liturgia.js`)
- Santo do dia (`js/santo-dia.js`)
- Calendário Romano (`js/calendario-romano.js`)
- Próximos eventos dinâmicos (`js/proximos-eventos.js`)
- Páginas próprias: `paroco.html`, `objetos-sagrados.html`, `agenda-liturgica.html`
- Conteúdo institucional (textos, fotos da paróquia)

Para comparar o snapshot intermediário (após customizações da paróquia, antes dos ajustes finais de produção), ver `_template-paroquia/`.
