#!/bin/sh
set -eu

umask 077

persistent_root=/app/storage
runtime_dir="$persistent_root/runtime"
runtime_env="$runtime_dir/.env"

mkdir -p \
    "$persistent_root/laravel-storage/app/public" \
    "$persistent_root/laravel-storage/framework/cache/data" \
    "$persistent_root/laravel-storage/framework/sessions" \
    "$persistent_root/laravel-storage/framework/views" \
    "$persistent_root/laravel-storage/logs" \
    "$persistent_root/bootstrap-cache" \
    "$runtime_dir" \
    "$persistent_root/database" \
    /run/apache2 \
    /run/lock/apache2 \
    /tmp/apache2-logs

if [ ! -e "$runtime_env" ]; then
    cp /var/www/html/.env.example "$runtime_env"
fi

case "${PANEL_MANAGED:-false}" in
    1|true|TRUE|yes|YES)
        rm -f "$persistent_root/bootstrap-cache/config.php"
        ;;
    *)
        case "${AUTO_INSTALL:-false}" in
            1|true|TRUE|yes|YES)
                bootstrap_file=${AUTO_INSTALL_BOOTSTRAP_FILE:-}

                if [ -n "$bootstrap_file" ] && [ -f "$bootstrap_file" ]; then
                    php artisan auto:install --bootstrap-file="$bootstrap_file" --no-interaction
                elif [ -n "${AUTO_INSTALL_PSEUDO:-}" ] && [ -n "${AUTO_INSTALL_MAIL:-}" ] && [ -n "${AUTO_INSTALL_PASSWORD:-}" ]; then
                    legacy_bootstrap=/tmp/centralpanel-auto-install.json
                    php -r '$data = ["name" => getenv("AUTO_INSTALL_PSEUDO"), "email" => getenv("AUTO_INSTALL_MAIL"), "password" => getenv("AUTO_INSTALL_PASSWORD")]; if (file_put_contents($argv[1], json_encode($data, JSON_THROW_ON_ERROR), LOCK_EX) === false) { exit(1); }' "$legacy_bootstrap"
                    php artisan auto:install --bootstrap-file="$legacy_bootstrap" --no-interaction
                    rm -f "$legacy_bootstrap"
                elif [ -n "$bootstrap_file" ]; then
                    php artisan auto:install --bootstrap-file="$bootstrap_file" --no-interaction
                else
                    echo "AUTO_INSTALL nécessite AUTO_INSTALL_BOOTSTRAP_FILE ou les trois variables historiques." >&2
                    exit 1
                fi
                ;;
        esac
        ;;
esac

exec "$@"
