#!/usr/bin/env bash
set -euo pipefail

# Railpack runs this instead of its default PHP start script.
php artisan migrate --force
php artisan storage:link --force >/dev/null 2>&1 || true
php artisan optimize:clear
php artisan optimize

# Background clock: Pendiente → En proceso → Esperando recogida (every 5s).
# Finalizado only happens when the student confirms "Recogido".
php artisan quickwash:sync --watch &
worker=$!

docker-php-entrypoint --config /Caddyfile --adapter caddyfile &
web=$!

trap 'kill "$worker" "$web" 2>/dev/null || true' EXIT
trap 'exit 0' TERM INT

# If either process dies, exit so Railway restarts the service.
wait -n "$worker" "$web"
exit 1
