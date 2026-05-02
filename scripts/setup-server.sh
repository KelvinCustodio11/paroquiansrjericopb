#!/usr/bin/env bash
# =============================================================================
# setup-server.sh — Configuração inicial do CMS no servidor Plesk
#
# Execute via SSH UMA VEZ após o primeiro deploy pelo GitHub Actions:
#
#   ssh usuario@pascomjerico.com.br 'bash -s' < scripts/setup-server.sh
#
# Requisitos no servidor:
#   - PHP 8.1+  com extensões: pdo, pdo_sqlite, mbstring, xml, curl, zip, gd
#   - Composer instalado (ou acessível via /usr/bin/composer)
#   - Node.js 18+ (para build-content.js)
#   - cms/ em /var/www/vhosts/pascomjerico.com.br/cms/
#   - httpdocs/ em /var/www/vhosts/pascomjerico.com.br/httpdocs/
# =============================================================================
set -euo pipefail

CMS_DIR="/var/www/vhosts/pascomjerico.com.br/cms"
HTTPDOCS_DIR="/var/www/vhosts/pascomjerico.com.br/httpdocs"
REPO_ROOT="/var/www/vhosts/pascomjerico.com.br"

echo "==> [1/7] Instalando dependências PHP (sem pacotes dev)..."
cd "$CMS_DIR"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> [2/7] Configurando .env de produção..."
if [ ! -f "$CMS_DIR/.env" ]; then
    cp "$CMS_DIR/.env.production.example" "$CMS_DIR/.env"
    echo "     ATENÇÃO: edite $CMS_DIR/.env com as credenciais reais antes de continuar."
    echo "     Em especial: APP_KEY, MAIL_PASSWORD"
    echo "     Pressione ENTER após editar o .env..."
    read -r
fi

echo "==> [3/7] Gerando chave da aplicação..."
php artisan key:generate --force

echo "==> [4/7] Criando banco de dados e rodando migrations..."
touch "$CMS_DIR/database/database.sqlite"
php artisan migrate --force

echo "==> [5/7] Rodando seeders (configuração inicial)..."
php artisan db:seed --force

echo "==> [6/7] Ajustando permissões..."
chmod -R 755 "$CMS_DIR/storage"
chmod -R 755 "$CMS_DIR/bootstrap/cache"
# Garante que o CMS pode escrever nos HTMLs do site
chmod -R 755 "$HTTPDOCS_DIR"

echo "==> [7/7] Otimizando para produção..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "✅  Setup concluído!"
echo ""
echo "Próximos passos MANUAIS no Plesk:"
echo "  1. Subdomínio admin.pascomjerico.com.br"
echo "     Document root: $CMS_DIR/public"
echo ""
echo "  2. Criar usuário admin no CMS:"
echo "     cd $CMS_DIR && php artisan make:filament-user"
echo ""
echo "  3. Verificar acesso: https://admin.pascomjerico.com.br/admin"
