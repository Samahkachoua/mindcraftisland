#!/usr/bin/env bash
set -e

composer install --no-dev --optimize-autoloader

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force || echo "Migration failed — continuing build anyway (this DB isn't required for the app to run)."
