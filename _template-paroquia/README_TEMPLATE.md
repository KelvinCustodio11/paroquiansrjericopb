# Template de referência: Paróquia NSR — versão estável (developer)

> ⚠️ **NÃO MODIFICAR ARQUIVOS DESTA PASTA.**
> ⚠️ **NÃO USAR EM PRODUÇÃO.** Pasta bloqueada via `.htaccess` e `robots.txt`.

## Origem
- Snapshot da branch **`developer`** no commit `d3eecaa` (último estado antes da sincronização com `production`).
- Corresponde ao **Avenix Church + customizações da Paróquia NSR Jericó/PB**, mas **antes** dos ajustes finais aplicados em `production`.

## Para que serve
- **Referência visual e estrutural** das customizações que a paróquia adicionou ao template Avenix.
- **Copiar componentes próprios** já implementados (radio player, liturgia, santo do dia, calendário romano, próximos eventos) ao criar novas seções/páginas.
- **Comparar** com o estado atual de produção para entender o que mudou nos últimos ajustes (texto de botões, "Nossa Missão" para Sobre, renomeação `pastor.html` → `paroco.html`, etc.).
- Servir como **playground**: testar variações de blocos sem mexer no site real.

## Componentes próprios disponíveis aqui
- `js/radio-player.js` — player de rádio FAB com controles
- `js/liturgia.js` — liturgia diária (API externa)
- `js/santo-dia.js` — santo/beato do dia (Wikipedia PT)
- `js/calendario-romano.js` — calendário litúrgico
- `js/proximos-eventos.js` — atualização dinâmica de eventos
- `index-slider.html` — variante com slider (removida em produção)
- `index-video.html` — variante com vídeo de fundo (removida em produção)
- `pastor.html` — versão antiga da página do pároco

## Diferenças para a produção atual
O `production` (HEAD `eee0674`) tem 8 commits a mais que este snapshot:
- `pastor.html` renomeado para `paroco.html`
- `index-slider.html` e `index-video.html` removidos
- Logo, footer-logo e favicon trocados (mais leves)
- Seção "Nossa Missão" movida do index para `about.html`
- Correção de visibilidade GSAP em Santo do Dia e agenda litúrgica
- Texto de botões refinado
- Seção "Páginas" temporariamente oculta no menu

## Como usar
1. Abra os arquivos aqui para ver implementações já testadas dos componentes próprios.
2. Copie estruturas HTML/CSS/JS para o site real (mantendo IDs, classes e nomes de variáveis).
3. Quando precisar de algo NÃO customizado (ainda do Avenix puro), consulte `_template-avenix/`.

## Restrições legais
- Customizações feitas pela equipe da paróquia: uso interno autorizado.
- Imagens demo do Avenix: ainda sob licença ThemeForest restrita.
- Imagens próprias da paróquia: © Paróquia NSR Jericó/PB.
