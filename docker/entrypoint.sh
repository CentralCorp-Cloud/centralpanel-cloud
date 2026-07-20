#!/bin/sh
set -eu

runtime_env=/var/www/runtime/.env

if [ ! -f "$runtime_env" ]; then
    cp /var/www/html/.env.example "$runtime_env"
fi

ln -sfn "$runtime_env" /var/www/html/.env

mkdir -p \
    /var/www/html/database \
    /var/www/html/storage/app/public \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache

chown -R www-data:www-data \
    /var/www/runtime \
    /var/www/html/database \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

case "${AUTO_INSTALL:-false}" in
    1|true|TRUE|yes|YES)
        if [ -z "${AUTO_INSTALL_PSEUDO:-}" ] || [ -z "${AUTO_INSTALL_MAIL:-}" ] || [ -z "${AUTO_INSTALL_PASSWORD:-}" ]; then
            echo "AUTO_INSTALL nécessite AUTO_INSTALL_PSEUDO, AUTO_INSTALL_MAIL et AUTO_INSTALL_PASSWORD." >&2
            exit 1
        fi

        php artisan auto:install \
            -p "$AUTO_INSTALL_PSEUDO" \
            -m "$AUTO_INSTALL_MAIL" \
            -pass "$AUTO_INSTALL_PASSWORD"

        chown -R www-data:www-data \
            /var/www/runtime \
            /var/www/html/database \
            /var/www/html/storage \
            /var/www/html/bootstrap/cache
        ;;
esac

exec "$@"
