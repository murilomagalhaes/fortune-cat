#!/bin/sh
set -e

php artisan migrate --force
php artisan optimize
php artisan filament:optimize

exec frankenphp run --config /etc/caddy/Caddyfile
