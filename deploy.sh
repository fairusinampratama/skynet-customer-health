#!/bin/sh

# Exit immediately if a command exits with a non-zero status
set -e

echo "🚀 Starting Deployment Script..."

echo "📂 Fixing permissions..."
chmod -R 777 storage bootstrap/cache

echo "🔗 Linking storage..."
php artisan storage:link

echo "⚡ Optimizing application..."
php artisan optimize

echo "📦 Running migrations..."
php artisan migrate --force

echo "Db Seeding..."
# Allow seeding to fail without stopping deployment if needed, or keep strict?
# Given the login issue, we want it strict, but if it fails due to timeout, maybe we warn?
# Sticking to strict for now as requested by user to fix login.
php artisan db:seed --force

echo "✅ Deployment tasks completed."

echo "🚀 Starting services..."

# Find concurrently executable
if [ -f "./node_modules/.bin/concurrently" ]; then
    CONCURRENTLY="./node_modules/.bin/concurrently"
else
    CONCURRENTLY="npx concurrently"
fi

$CONCURRENTLY -c "#93c5fd,#c4b5fd,#fb7185" \
    "php artisan serve --host=0.0.0.0 --port=3000" \
    "php artisan schedule:work" \
    "php artisan queue:work --tries=3"
