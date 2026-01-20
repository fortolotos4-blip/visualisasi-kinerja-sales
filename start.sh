#!/bin/sh

composer install --no-dev --optimize-autoloader --no-scripts

# INI KUNCI TERAKHIR
composer dump-autoload

php artisan config:clear
php artisan cache:clear
php artisan config:cache

php artisan package:discover --ansi

echo "Running migrations via PHP bootstrap..."
php run_migration.php

php artisan serve --host=0.0.0.0 --port=10000
