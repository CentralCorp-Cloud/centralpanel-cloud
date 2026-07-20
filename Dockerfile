FROM composer:2 AS vendor

WORKDIR /app

COPY . .

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

FROM php:8.2-apache

LABEL org.opencontainers.image.source="https://github.com/CentralCorp-Cloud/centralpanel-v2" \
      org.opencontainers.image.description="CentralPanel compatible with CentralCloud Node Agent" \
      org.opencontainers.image.licenses="MIT"

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        libonig-dev \
        libpq-dev \
        libxml2-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        mbstring \
        pdo_mysql \
        pdo_pgsql \
        xml \
        zip \
    && a2enmod rewrite \
    && groupadd --gid 10001 centralpanel \
    && useradd --uid 10001 --gid 10001 --no-create-home --home-dir /var/www/html --shell /usr/sbin/nologin centralpanel \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    APACHE_RUN_USER=centralpanel \
    APACHE_RUN_GROUP=centralpanel \
    APACHE_RUN_DIR=/run/apache2 \
    APACHE_LOCK_DIR=/run/lock/apache2 \
    APACHE_LOG_DIR=/tmp/apache2-logs \
    APACHE_PID_FILE=/run/apache2/apache2.pid \
    HOME=/tmp \
    PANEL_RUNTIME_PATH=/app/storage/runtime \
    LOG_CHANNEL=stderr \
    SESSION_DRIVER=file \
    CACHE_STORE=file \
    QUEUE_CONNECTION=sync

WORKDIR /var/www/html

COPY docker/apache.conf /etc/apache2/sites-available/centralpanel.conf
COPY docker/apache-runtime.conf /etc/apache2/conf-available/centralpanel-runtime.conf
COPY --from=vendor /app /var/www/html
COPY docker/entrypoint.sh /usr/local/bin/centralpanel-entrypoint

RUN a2dissite 000-default \
    && a2ensite centralpanel \
    && a2enconf centralpanel-runtime \
    && sed -ri 's/^Listen 80$/Listen 8080/' /etc/apache2/ports.conf \
    && sed -ri 's/^export APACHE_RUN_USER=.*/export APACHE_RUN_USER=centralpanel/; s/^export APACHE_RUN_GROUP=.*/export APACHE_RUN_GROUP=centralpanel/' /etc/apache2/envvars \
    && chmod 0755 /usr/local/bin/centralpanel-entrypoint \
    && rm -rf \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache \
        /var/www/html/.env \
        /var/www/html/public/storage \
        /var/www/html/database/database.sqlite \
    && mkdir -p \
        /app/storage/laravel-storage/app/public \
        /app/storage/laravel-storage/framework/cache/data \
        /app/storage/laravel-storage/framework/sessions \
        /app/storage/laravel-storage/framework/views \
        /app/storage/laravel-storage/logs \
        /app/storage/bootstrap-cache \
        /app/storage/runtime \
        /app/storage/database \
    && ln -s /app/storage/laravel-storage /var/www/html/storage \
    && ln -s /app/storage/bootstrap-cache /var/www/html/bootstrap/cache \
    && ln -s /app/storage/runtime/.env /var/www/html/.env \
    && ln -s /app/storage/laravel-storage/app/public /var/www/html/public/storage \
    && ln -s /app/storage/database/database.sqlite /var/www/html/database/database.sqlite \
    && chown -R 10001:10001 /app/storage

USER 10001:10001

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD ["curl", "--fail", "--silent", "--show-error", "http://127.0.0.1:8080/up"]

ENTRYPOINT ["centralpanel-entrypoint"]
CMD ["apache2-foreground"]
