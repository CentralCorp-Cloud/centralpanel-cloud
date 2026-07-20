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

LABEL org.opencontainers.image.source="https://github.com/CentralCorp-Cloud/centralpanel-cloud" \
      org.opencontainers.image.description="CentralCorp Cloud panel" \
      org.opencontainers.image.licenses="CC0-1.0"

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        pdo_mysql \
        zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

WORKDIR /var/www/html

COPY docker/apache.conf /etc/apache2/sites-available/centralpanel.conf
COPY --from=vendor /app /var/www/html
COPY docker/entrypoint.sh /usr/local/bin/centralpanel-entrypoint

RUN a2dissite 000-default \
    && a2ensite centralpanel \
    && chmod +x /usr/local/bin/centralpanel-entrypoint \
    && mkdir -p \
        /var/www/runtime \
        /var/www/html/database \
        /var/www/html/storage/app/public \
        /var/www/html/storage/framework/cache/data \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views \
        /var/www/html/storage/logs \
        /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data \
        /var/www/runtime \
        /var/www/html/database \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["centralpanel-entrypoint"]
CMD ["apache2-foreground"]
