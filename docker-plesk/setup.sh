#!/usr/bin/env bash
# =============================================================================
# setup.sh — Inicialização do CMS dentro do container Docker
#
# Replica exatamente o procedimento de setup-server.sh usado no Plesk real.
# Execute UMA VEZ após subir o container pela primeira vez:
#
#   docker compose -f docker-plesk/docker-compose.yml exec plesk bash /setup.sh
# =============================================================================
set -euo pipefail

PHP="/opt/plesk/php/8.2/bin/php"    # caminho Plesk-like (symlink no Dockerfile)
CMS="/var/www/vhosts/paroquia.local/cms"
HTTPDOCS="/var/www/vhosts/paroquia.local/httpdocs"

echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║  Setup — Simulação Plesk (paroquia.local)           ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""

# ── 1. Verificar PHP -----------------------------------------------------------
echo "► PHP disponível:"
"$PHP" --version
echo ""

# ── 2. Verificar ausência de Node (reproduz o problema!) ---------------------
echo "► Node.js disponível? (esperado: NÃO)"
if command -v node &>/dev/null; then
    echo "  [AVISO] Node.js ENCONTRADO: $(node --version)"
    echo "  Isso NÃO replica o servidor de produção (Plesk não tem Node)."
else
    echo "  [OK] node: command not found  ← comportamento correto do Plesk"
fi
echo ""

# ── 3. Copiar .env do Docker para o CMS -------------------------------------
echo "► Configurando .env do CMS..."
if [ ! -f "$CMS/.env" ]; then
    cp /docker-plesk-env "$CMS/.env"
    echo "  .env copiado de .env.docker"
else
    echo "  .env já existe — pulando (remova manualmente se quiser resetar)"
fi
echo ""

# ── 4. Gerar APP_KEY se ainda não foi gerado --------------------------------
if [ -z "${APP_KEY:-}" ] && grep -q '^APP_KEY=$' "$CMS/.env"; then
    echo "► Gerando APP_KEY..."
    cd "$CMS" && "$PHP" artisan key:generate --ansi
    echo ""
fi

# ── 5. Criar banco SQLite se não existe -------------------------------------
echo "► Banco de dados SQLite..."
DB_FILE="$CMS/database/database.sqlite"
if [ ! -f "$DB_FILE" ]; then
    touch "$DB_FILE"
    echo "  Criado: $DB_FILE"
else
    echo "  Já existe: $DB_FILE"
fi
echo ""

# ── 6. Permissões (como o Plesk ajusta para www-data) -----------------------
echo "► Ajustando permissões..."
chown -R www-data:www-data "$CMS/storage" "$CMS/bootstrap/cache" "$CMS/database"
chmod -R 775 "$CMS/storage" "$CMS/bootstrap/cache" "$CMS/database"
chmod 664 "$CMS/database/database.sqlite" 2>/dev/null || true
# Pastas e arquivos do site estático que o CMS precisa gravar.
# O bind mount pode trazer arquivos 644/root do host, mesmo quando o diretório
# está gravável; ajuste os dois níveis para evitar falha no file_put_contents().
for dir in data eventos artigos homilias images/uploads partials css; do
    if [ -d "$HTTPDOCS/$dir" ]; then
        chmod -R u+rwX,g+rwX,o+rX "$HTTPDOCS/$dir" 2>/dev/null || true
    fi
done
echo "  OK"
echo ""

# ── 7. Instalar/atualizar dependências Composer (se vendor/ não existe) ----
echo "► Dependências Composer..."
if [ ! -d "$CMS/vendor" ]; then
    cd "$CMS" && "$PHP" /usr/bin/composer install \
        --no-dev --optimize-autoloader --no-interaction
    echo "  vendor/ instalado"
else
    echo "  vendor/ já existe — pulando (rode: composer install manualmente se necessário)"
fi
echo ""

# ── 8. Migrations + seed ----------------------------------------------------
echo "► Rodando migrations..."
cd "$CMS" && "$PHP" artisan migrate --force
echo ""

# ── 9. Cache de configuração (igual ao Plesk pós-deploy) -------------------
# NOTA: route:cache NÃO é usado — é incompatível com Livewire (gera 404 no /livewire/update)
echo "► Cache de config..."
cd "$CMS" && "$PHP" artisan config:cache
echo ""

# ── 10. Criar usuário admin (opcional — só se tabela vazia) ----------------
echo "► Verificar usuário admin..."
USER_COUNT=$("$PHP" "$CMS/artisan" tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tr -d '[:space:]' || echo "0")
if [ "$USER_COUNT" = "0" ]; then
    echo "  Nenhum usuário encontrado. Crie via:"
    echo "  $PHP $CMS/artisan make:filament-user"
else
    echo "  $USER_COUNT usuário(s) já cadastrado(s)"
fi
echo ""

echo "✔ Setup concluído."
echo ""
echo "  Site estático : http://localhost:8080"
echo "  CMS Admin     : http://localhost:8081"
echo ""
