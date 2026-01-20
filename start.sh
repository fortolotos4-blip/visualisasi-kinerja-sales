#!/bin/sh

echo "Installing dependencies (no scripts)..."
composer install --no-dev --optimize-autoloader --no-scripts

echo "Preparing Laravel..."
php artisan key:generate --force
php artisan config:clear
php artisan cache:clear

echo "=== CHECKING DATABASE CONNECTION ==="
php artisan migrate:status

echo "=== RUNNING MIGRATIONS ==="
php artisan migrate --force

echo "=== MIGRATION DONE ==="

echo "Starting Laravel server..."
php artisan serve --host=0.0.0.0 --port=10000
