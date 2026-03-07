#!/bin/sh
set -e

php artisan migrate --force
php artisan optimize

exec frankenphp run --config /etc/caddy/Caddyfile
