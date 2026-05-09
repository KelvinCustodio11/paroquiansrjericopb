#!/bin/bash
# =============================================================================
# docker-entrypoint.sh — Entrypoint do container Plesk-sim
#
# Roda ANTES do Apache iniciar. Garante permissões corretas para www-data
# em bind-mounts que chegam com dono root (arquivos do host).
#
# Simula o comportamento do Plesk: www-data deve conseguir gravar nos
# diretórios de conteúdo (data/, artigos/, eventos/, homilias/, uploads/).
# =============================================================================
set -e

HTTPDOCS="/var/www/vhosts/paroquia.local/httpdocs"
CMS="/var/www/vhosts/paroquia.local/cms"

echo "[entrypoint] Ajustando permissões para www-data..."

# Pastas do site estático onde o CMS precisa gravar JSONs e HTMLs
for dir in data artigos eventos homilias images/uploads partials css; do
    if [ -d "$HTTPDOCS/$dir" ]; then
        chmod -R 777 "$HTTPDOCS/$dir" 2>/dev/null || true
    fi
done

# Arquivos HTML raiz que o CMS pode reescrever
chmod 777 "$HTTPDOCS"/*.html 2>/dev/null || true

# Pastas do Laravel que precisam de escrita (logs, cache, sessions, views)
for dir in storage bootstrap/cache database; do
    if [ -d "$CMS/$dir" ]; then
        chown -R www-data:www-data "$CMS/$dir" 2>/dev/null || true
        chmod -R 775 "$CMS/$dir" 2>/dev/null || true
    fi
done

# SQLite precisa de permissão de escrita no arquivo E no diretório
if [ -f "$CMS/database/database.sqlite" ]; then
    chmod 664 "$CMS/database/database.sqlite" 2>/dev/null || true
fi

echo "[entrypoint] Permissões OK. Iniciando Apache..."

# Passa o controle para o entrypoint original do php:8.2-apache
exec docker-php-entrypoint "$@"
