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

echo "==> Usando PHP: $($PHP -r 'echo PHP_VERSION;')"
echo "==> CMS dir: $CMS_DIR"
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
