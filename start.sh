#!/bin/sh

echo "Running Laravel setup..."

php artisan key:generate --force || true
php artisan migrate --force || true
php artisan db:seed --force || true
php artisan config:clear
php artisan config:cache

echo "Starting Laravel server..."
php artisan serve --host=0.0.0.0 --port=10000
