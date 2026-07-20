#!/bin/sh
set -eu

project_name=centralpanel-agent-integration
compose_file=tests/docker/compose.centralcloud.yml
secrets_dir=$(mktemp -d)
export CENTRALPANEL_TEST_SECRETS_DIR=$secrets_dir
test_image=${CENTRALPANEL_TEST_IMAGE:-centralpanel-cloud:agent-test}

cleanup() {
    docker compose -p "$project_name" -f "$compose_file" down --volumes --remove-orphans >/dev/null 2>&1 || true
    find "$secrets_dir" -type f -delete
    rmdir "$secrets_dir"
}
trap cleanup EXIT INT TERM

umask 077
printf '%s' 'integration-postgres-password' > "$secrets_dir/postgres_password"
printf '%s' 'base64:aW50ZWdyYXRpb24tdGVzdC1rZXktMzItYnl0ZXMhISE=' > "$secrets_dir/app_key"
printf '%s' 'integration-internal-secret' > "$secrets_dir/internal_secret"
printf '%s' '{"name":"CentralCloud Admin","email":"admin@example.com","password":"Integration-password-123!"}' > "$secrets_dir/panel_bootstrap.json"
chmod 0444 \
    "$secrets_dir/postgres_password" \
    "$secrets_dir/app_key" \
    "$secrets_dir/internal_secret" \
    "$secrets_dir/panel_bootstrap.json"

docker compose -p "$project_name" -f "$compose_file" config >/dev/null

image_contract=$(docker image inspect --format '{{.Config.User}} {{json .Config.ExposedPorts}} {{json .Config.Healthcheck.Test}}' "$test_image")
case "$image_contract" in
    '10001:10001 '*8080/tcp*'http://127.0.0.1:8080/up'*)
        ;;
    *)
        printf 'Invalid image metadata: %s\n' "$image_contract" >&2
        exit 1
        ;;
esac

docker compose -p "$project_name" -f "$compose_file" up -d --wait

panel_id=$(docker compose -p "$project_name" -f "$compose_file" ps -q panel)

runtime_contract=$(docker inspect --format '{{.HostConfig.ReadonlyRootfs}} {{.Config.User}} {{json .HostConfig.CapDrop}} {{json .HostConfig.SecurityOpt}}' "$panel_id")
printf 'Runtime contract: %s\n' "$runtime_contract"
case "$runtime_contract" in
    'true 10001:10001 ["ALL"] ["no-new-privileges"]'|'true 10001:10001 ["ALL"] ["no-new-privileges:true"]')
        ;;
    *)
        exit 1
        ;;
esac
docker exec "$panel_id" php -r 'exit(extension_loaded("pdo_pgsql") ? 0 : 1);'
docker exec "$panel_id" curl --fail --silent --show-error http://127.0.0.1:8080/up >/dev/null
docker exec "$panel_id" sh -c '[ -w /app/storage ] && [ ! -w /var/www/html ] && [ "$(readlink /var/www/html/storage)" = /app/storage/laravel-storage ] && [ "$(readlink /var/www/html/bootstrap/cache)" = /app/storage/bootstrap-cache ] && [ "$(readlink /var/www/html/.env)" = /app/storage/runtime/.env ] && [ "$(readlink /var/www/html/public/storage)" = /app/storage/laravel-storage/app/public ]'
[ "$(docker inspect --format '{{json .HostConfig.PortBindings}}' "$panel_id")" = '{}' ]
if docker exec "$panel_id" php artisan config:cache --no-interaction >"$secrets_dir/config-cache.out" 2>&1; then
    printf '%s\n' 'config:cache must be rejected in managed mode.' >&2
    exit 1
fi
docker exec "$panel_id" test ! -e /app/storage/bootstrap-cache/config.php
printf '%s\n' 'Runtime checks passed.'

first_output="$secrets_dir/install-first.out"
second_output="$secrets_dir/install-second.out"
docker exec "$panel_id" php artisan auto:install --bootstrap-file=/run/secrets/panel_bootstrap.json --no-interaction >"$first_output" 2>&1 &
first_pid=$!
docker exec "$panel_id" php artisan auto:install --bootstrap-file=/run/secrets/panel_bootstrap.json --no-interaction >"$second_output" 2>&1 &
second_pid=$!
first_status=0
second_status=0
wait "$first_pid" || first_status=$?
wait "$second_pid" || second_status=$?

php -r '$secret = json_decode(file_get_contents($argv[1]), true, 16, JSON_THROW_ON_ERROR)["password"]; foreach (array_slice($argv, 2) as $output) { if (str_contains(file_get_contents($output), $secret)) { exit(1); } }' "$secrets_dir/panel_bootstrap.json" "$first_output" "$second_output"

if [ "$first_status" -ne 0 ] || [ "$second_status" -ne 0 ]; then
    printf 'Concurrent install exit codes: %s, %s\n' "$first_status" "$second_status" >&2
    sed -n '1,40p' "$first_output" >&2
    sed -n '1,40p' "$second_output" >&2
    exit 1
fi

printf '%s\n' 'Concurrent installation passed.'
docker exec "$panel_id" php -r 'require "/var/www/html/vendor/autoload.php"; $app = require "/var/www/html/bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); exit(App\Models\User::query()->count() === 1 ? 0 : 1);'
docker exec "$panel_id" php -r '$env = file_get_contents("/app/storage/runtime/.env"); foreach (["postgres_password", "app_key", "internal_secret", "panel_bootstrap.json"] as $name) { $value = $name === "panel_bootstrap.json" ? json_decode(file_get_contents("/run/secrets/".$name), true)["password"] : trim(file_get_contents("/run/secrets/".$name)); if ($value !== "" && str_contains($env, $value)) { exit(1); } }'
container_logs="$secrets_dir/container.out"
docker logs "$panel_id" >"$container_logs" 2>&1
php -r '$values = [trim(file_get_contents($argv[1])), trim(file_get_contents($argv[2])), trim(file_get_contents($argv[3])), json_decode(file_get_contents($argv[4]), true, 16, JSON_THROW_ON_ERROR)["password"]]; $logs = file_get_contents($argv[5]); foreach ($values as $value) { if ($value !== "" && str_contains($logs, $value)) { exit(1); } }' "$secrets_dir/postgres_password" "$secrets_dir/app_key" "$secrets_dir/internal_secret" "$secrets_dir/panel_bootstrap.json" "$container_logs"
printf '%s\n' 'Persistence leak checks passed.'

docker compose -p "$project_name" -f "$compose_file" restart panel >/dev/null
docker compose -p "$project_name" -f "$compose_file" up -d --wait panel >/dev/null
docker compose -p "$project_name" -f "$compose_file" exec -T panel php artisan auto:install --bootstrap-file=/run/secrets/panel_bootstrap.json --no-interaction
printf '%s\n' 'Replay after restart passed.'
docker compose -p "$project_name" -f "$compose_file" exec -T panel php -r 'require "/var/www/html/vendor/autoload.php"; $app = require "/var/www/html/bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); exit(App\Models\User::query()->count() === 1 ? 0 : 1);'
printf '%s\n' 'User persistence passed.'
docker compose -p "$project_name" -f "$compose_file" exec -T panel test -s /app/storage/laravel-storage/installed

printf '%s\n' 'CentralCloud Docker integration test passed.'
