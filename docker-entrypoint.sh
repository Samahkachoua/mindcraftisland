#!/bin/bash
set -e

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force || echo "Migration failed — continuing startup anyway (this DB isn't required for the app to run)."

# The commands above run as root and can create files (e.g. storage/logs/laravel.log)
# owned by root. Apache serves the app as www-data, so re-fix ownership before it starts,
# otherwise www-data gets "Permission denied" appending to a root-owned log file.
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

exec "$@"
