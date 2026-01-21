#!/bin/sh

# Pastikan folder storage ada
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/framework/cache
mkdir -p storage/logs

# Permission agar Laravel bisa nulis
chmod -R 775 storage bootstrap/cache

php artisan config:clear
php artisan cache:clear

php artisan serve --host=0.0.0.0 --port=10000
