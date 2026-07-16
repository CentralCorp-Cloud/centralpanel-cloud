# syntax=docker/dockerfile:1.7
FROM composer:2 AS vendor
WORKDIR /src
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/cache composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction

FROM node:24-bookworm-slim AS assets
WORKDIR /src
COPY package.json package-lock.json ./
RUN --mount=type=cache,target=/root/.npm npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM dunglas/frankenphp:1-php8.4-bookworm AS runtime
RUN install-php-extensions bcmath gd intl pdo_pgsql zip
ENV XDG_CONFIG_HOME=/tmp/caddy-config \
    XDG_DATA_HOME=/tmp/caddy-data
WORKDIR /app
COPY . .
COPY --from=vendor /src/vendor ./vendor
COPY --from=vendor /src/bootstrap/cache ./bootstrap/cache
COPY --from=assets /src/public/build ./public/build
COPY Caddyfile /etc/frankenphp/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/centralpanel-entrypoint
RUN setcap -r /usr/local/bin/frankenphp \
    && chmod 0755 /usr/local/bin/centralpanel-entrypoint \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && ln -s /app/storage/app/public /app/public/storage \
    && chown -R 10001:10001 storage bootstrap/cache
USER 10001:10001
EXPOSE 8080
HEALTHCHECK --interval=15s --timeout=5s --retries=12 CMD ["php", "-r", "$c=@file_get_contents('http://127.0.0.1:8080/healthz'); exit($c===false?1:0);"]
ENTRYPOINT ["centralpanel-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
