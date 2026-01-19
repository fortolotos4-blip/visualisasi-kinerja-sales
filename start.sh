#!/bin/sh

echo "Installing dependencies (no scripts)..."
composer install --no-dev --optimize-autoloader --no-scripts

echo "Preparing Laravel..."
php artisan key:generate --force || true
php artisan config:clear
php artisan cache:clear

echo "Running package discovery..."
php artisan package:discover --ansi || true

echo "Running migrations & seeders..."
php artisan migrate --force || true
php artisan db:seed --force || true

echo "Starting Laravel server..."
php artisan serve --host=0.0.0.0 --port=10000
