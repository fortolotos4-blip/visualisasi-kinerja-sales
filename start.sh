#!/bin/sh

composer install --no-dev --optimize-autoloader --no-scripts
composer dump-autoload

php artisan config:clear
php artisan cache:clear
php artisan optimize

php artisan serve --host=0.0.0.0 --port=10000
