#!/usr/bin/env bash
# =============================================================================
# debug-node.sh — Diagnóstico do problema "node: command not found" no Plesk
#
# Execute dentro do container após o setup:
#   docker compose -f docker-plesk/docker-compose.yml exec plesk bash /debug-node.sh
# =============================================================================
set -uo pipefail

PHP="/opt/plesk/php/8.2/bin/php"
CMS="/var/www/vhosts/paroquia.local/cms"
HTTPDOCS="/var/www/vhosts/paroquia.local/httpdocs"

echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║  Diagnóstico — Problema Node.js no Plesk                    ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""

# ── 1. PATH disponível para o PHP (passthru) ----------------------------------
echo "► PATH visto pelo Apache/PHP (como passthru() enxerga):"
"$PHP" -r 'echo shell_exec("echo PATH=\$PATH") . "\n";'
echo ""

# ── 2. Verificar node no PATH --------------------------------------------------
echo "► Verificando node no PATH do sistema:"
"$PHP" -r 'echo shell_exec("which node 2>&1 || echo NOT_FOUND") . "\n";'
echo ""

# ── 3. Analisar o ContentBuild.php -------------------------------------------
echo "► Conteúdo relevante de ContentBuild.php:"
grep -n "passthru\|node\|PATH\|shell_exec" "$CMS/app/Console/Commands/ContentBuild.php" || echo "  (nenhum match)"
echo ""

echo "✔ Diagnóstico concluído."
echo ""
