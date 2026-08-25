#!/usr/bin/env bash
# =============================================================================
# setup-server.sh — Configuração inicial do CMS no servidor Plesk (chroot)
#
# Execute UMA VEZ no terminal SSH do Plesk após o primeiro deploy:
#
#   cd ~/cms && bash ~/cms/scripts/setup-server.sh
#
# Ou copie e cole diretamente no terminal do Plesk.
# =============================================================================
set -euo pipefail

# Detecta PHP 8.2 do Plesk (único disponível no chroot)
PHP="/opt/plesk/php/8.2/bin/php"
CMS_DIR="$HOME/cms"
ENV_SITE_ROOT=''
if [ -f "$CMS_DIR/.env" ]; then
    ENV_SITE_ROOT="$(awk -F= '/^SITE_ROOT=/{sub(/^SITE_ROOT=/, ""); gsub(/^"|"$/, ""); print; exit}' "$CMS_DIR/.env")"
fi
SITE_ROOT="${SITE_ROOT:-${STATIC_DISK_ROOT:-${ENV_SITE_ROOT:-$HOME/httpdocs}}}"

echo "==> Usando PHP: $($PHP -r 'echo PHP_VERSION;')"
echo "==> CMS dir: $CMS_DIR"
echo "==> Site dir: $SITE_ROOT"
cd "$CMS_DIR"

# ── 1. Composer ──────────────────────────────────────────────────────────────
echo ""
echo "==> [1/7] Baixando Composer (se necessário)..."
if [ ! -f "$CMS_DIR/composer.phar" ]; then
    $PHP -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
    $PHP /tmp/composer-setup.php --install-dir="$CMS_DIR" --filename=composer.phar
    rm -f /tmp/composer-setup.php
else
    echo "     composer.phar já existe, pulando."
fi

# ── 2. Dependências ───────────────────────────────────────────────────────────
echo ""
echo "==> [2/7] Instalando dependências PHP..."
mkdir -p "$CMS_DIR/bootstrap/cache"
chmod 775 "$CMS_DIR/bootstrap/cache"
$PHP "$CMS_DIR/composer.phar" install --no-dev --optimize-autoloader --no-interaction

# ── 3. .env ───────────────────────────────────────────────────────────────────
echo ""
echo "==> [3/7] Configurando .env..."
if [ ! -f "$CMS_DIR/.env" ]; then
    cp "$CMS_DIR/.env.production.example" "$CMS_DIR/.env"
    echo "     .env criado a partir do exemplo."
    echo ""
    echo "     ⚠️  EDITE o .env agora com os valores corretos:"
    echo "        APP_URL, STATIC_DISK_ROOT, MAIL_PASSWORD etc."
    echo ""
    echo "     Pressione ENTER após editar o .env para continuar..."
    read -r
fi

# ── 4. App key ────────────────────────────────────────────────────────────────
echo ""
echo "==> [4/7] Gerando chave da aplicação..."
$PHP artisan key:generate --force

# ── 5. Banco de dados ─────────────────────────────────────────────────────────
echo ""
echo "==> [5/7] Criando banco e rodando migrations..."
mkdir -p "$CMS_DIR/database"
touch "$CMS_DIR/database/database.sqlite"
$PHP artisan migrate --force
$PHP artisan db:seed --force

# ── 6. Permissões ─────────────────────────────────────────────────────────────
echo ""
echo "==> [6/7] Ajustando permissões de storage..."
mkdir -p "$CMS_DIR/storage/framework/sessions" \
         "$CMS_DIR/storage/framework/views" \
         "$CMS_DIR/storage/framework/cache" \
         "$CMS_DIR/storage/logs" \
         "$CMS_DIR/storage/app/public"
chmod -R 775 "$CMS_DIR/storage"
chmod -R 775 "$CMS_DIR/bootstrap/cache"

# O CMS exporta e regenera o site estático. No Plesk, o usuário do PHP precisa
# ser dono (ou membro do grupo com escrita) destas pastas e arquivos.
echo "==> Ajustando permissões do site estático..."
if [ ! -d "$SITE_ROOT" ]; then
    echo "ERRO: SITE_ROOT não existe: $SITE_ROOT" >&2
    exit 1
fi

for dir in data eventos artigos homilias images/uploads partials css; do
    if [ -d "$SITE_ROOT/$dir" ]; then
        chmod -R u+rwX,g+rwX "$SITE_ROOT/$dir"
    else
        echo "AVISO: pasta ausente: $SITE_ROOT/$dir"
    fi
done

find "$SITE_ROOT" -maxdepth 1 -name '*.html' -exec chmod u+rw,g+rw {} \;

if [ ! -w "$SITE_ROOT/data" ] || [ ! -w "$SITE_ROOT/data/eventos.json" ]; then
    echo "ERRO: o usuário atual não pode gravar em $SITE_ROOT/data/eventos.json" >&2
    echo "       Ajuste o proprietário/grupo no Plesk e execute este script novamente." >&2
    exit 1
fi

# ── 7. Otimização ─────────────────────────────────────────────────────────────
echo ""
echo "==> [7/7] Otimizando para produção..."
$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache

echo ""
echo "✅  Setup concluído!"
echo ""
echo "Próximo passo — criar usuário admin:"
echo "   cd ~/cms && $PHP artisan make:filament-user"
echo ""
echo "Depois acesse: https://admin.pascomjerico.com.br"
