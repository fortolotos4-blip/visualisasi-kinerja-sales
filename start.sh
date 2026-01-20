#!/bin/sh

set -x

composer install --no-dev --optimize-autoloader --no-scripts

php artisan config:clear
php artisan cache:clear
php artisan config:cache

php artisan package:discover --ansi

echo "=== DATABASE CONNECTION CHECK ==="
php artisan migrate:status

echo "=== RUNNING MIGRATIONS (VERBOSE) ==="
php artisan migrate --force -vvv

php artisan serve --host=0.0.0.0 --port=10000
