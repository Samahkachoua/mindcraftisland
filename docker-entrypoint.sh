#!/bin/bash
set -e

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force || echo "Migration failed — continuing startup anyway (this DB isn't required for the app to run)."

exec "$@"
