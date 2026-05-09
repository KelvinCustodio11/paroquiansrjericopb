# =============================================================================
# Dockerfile — Simulação Plesk Napoleon (hospedagem compartilhada)
#
# Replica a stack do servidor de produção:
#   - PHP 8.2 + Apache (sem Node.js — igual ao Plesk real)
#   - Extensões PHP idênticas às disponíveis no Plesk
#   - Composer instalado (para instalar dependências Laravel)
#   - Symlink /opt/plesk/php/8.2/bin/php (mesmo caminho que o Plesk usa)
#
# Estrutura que replica produção:
#   /var/www/vhosts/paroquia.local/
#     httpdocs/   ← site estático (bind-mount do repo raiz)
#     cms/        ← CMS Laravel   (bind-mount de cms/)
#
# No servidor real (Plesk):
#   /var/www/vhosts/pedisys.com.br/
#     httpdocs/   ← site estático
#     cms/        ← CMS Laravel (FORA de httpdocs — igual ao Docker)
# =============================================================================
FROM php:8.2-apache

# ── Extensões PHP (replicando o Plesk) ────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libsqlite3-dev \
        sqlite3 \
        curl \
        git \
        unzip \
        libicu-dev \
    && docker-php-ext-install \
        pdo_sqlite \
        zip \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ── Composer ──────────────────────────────────────────────────────────────────
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# ── Symlink PHP no caminho Plesk (/opt/plesk/php/8.2/bin/php) ─────────────────
RUN mkdir -p /opt/plesk/php/8.2/bin \
    && ln -s /usr/local/bin/php /opt/plesk/php/8.2/bin/php

# ── Módulos Apache ────────────────────────────────────────────────────────────
RUN a2enmod rewrite headers expires

# ── Configuração PHP (replica php.ini do Plesk) ───────────────────────────────
RUN { \
    echo "expose_php = Off"; \
    echo "display_errors = Off"; \
    echo "log_errors = On"; \
    echo "error_log = /var/log/php_errors.log"; \
    echo "upload_max_filesize = 10M"; \
    echo "post_max_size = 12M"; \
    echo "memory_limit = 256M"; \
    echo "max_execution_time = 60"; \
    echo "date.timezone = America/Fortaleza"; \
} > /usr/local/etc/php/conf.d/plesk-like.ini

# ── Estrutura de pastas (replicando vhosts do Plesk) ─────────────────────────
RUN mkdir -p /var/www/vhosts/paroquia.local/httpdocs \
    && mkdir -p /var/www/vhosts/paroquia.local/cms \
    && chown -R www-data:www-data /var/www/vhosts

# ServerName global (suprime warning do Apache) ───────────────────────────────
RUN echo "ServerName paroquia.local" >> /etc/apache2/apache2.conf

# ── Configuração Apache ───────────────────────────────────────────────────────
COPY apache/httpdocs.conf /etc/apache2/sites-available/000-httpdocs.conf
COPY apache/cms.conf      /etc/apache2/sites-available/001-cms.conf

RUN a2dissite 000-default \
    && a2ensite 000-httpdocs \
    && a2ensite 001-cms

# ── Entrypoint customizado (corrige permissões antes do Apache) ───────────────
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint-plesk.sh
RUN chmod +x /usr/local/bin/docker-entrypoint-plesk.sh

# ── Porta extra para o CMS (81) ───────────────────────────────────────────────
EXPOSE 80 81

ENTRYPOINT ["docker-entrypoint-plesk.sh"]
CMD ["apache2-foreground"]
