#!/bin/sh

echo "Installing dependencies (no scripts)..."
composer install --no-dev --optimize-autoloader --no-scripts

echo "Preparing Laravel configuration..."
php artisan config:clear
php artisan cache:clear
php artisan config:cache

echo "Running package discovery..."
php artisan package:discover --ansi

echo "Running migrations..."
php artisan migrate --force

echo "Running seeders..."
php artisan db:seed --force || true

echo "Starting Laravel server..."
php artisan serve --host=0.0.0.0 --port=10000
