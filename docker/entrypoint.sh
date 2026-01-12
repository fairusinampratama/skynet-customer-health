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

# Start Scheduler in background (simple way for single container)
# Note: In a real heavy prod, scheduler should be a separate sidecar, but for this VM usage, backgrounding is fine.
echo "Starting Scheduler..."
php artisan schedule:work &

# Start Apache in foreground
echo "Starting Apache..."
exec apache2-foreground
