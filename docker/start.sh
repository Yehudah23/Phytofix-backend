#!/bin/sh
set -e

php artisan migrate --force
php artisan db:seed --class=CatalogDefaultsSeeder --force
php-fpm -D
exec nginx -g 'daemon off;'
