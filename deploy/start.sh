#!/usr/bin/env bash
set -euo pipefail
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan quickwash:sync --watch &
worker=$!
docker-php-entrypoint --config /Caddyfile --adapter caddyfile &
web=$!
trap 'kill "$worker" "$web" 2>/dev/null || true' EXIT
trap 'exit 0' TERM INT
# Restart the service if either the web server or the clock worker stops.
wait -n "$worker" "$web"
exit 1
