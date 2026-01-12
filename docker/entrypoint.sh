#!/bin/bash
set -e

# Run migrations (force for production)
echo "Running migrations..."
php artisan migrate --force

# optimize
echo "Caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start Supervisor (which starts Nginx + PHP-FPM + Scheduler)
echo "Starting Supervisor..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
