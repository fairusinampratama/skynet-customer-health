#!/bin/sh

# Skynet Customer Health - Coolify Deployment Script
# This script runs on container startup

set -e

echo "🚀 Starting Skynet Customer Health deployment..."

# 0. Wait for database to be ready
echo "⏳ Waiting for database connection..."
until php artisan db:show > /dev/null 2>&1; do
  echo "  (Still waiting for database...)"
  sleep 2
done
echo "📡 Database is ready!"

# 1. Create storage symlink (ignore if exists)
echo "🔗 Creating storage symlink..."
php artisan storage:link --force || true

# 2. Run migrations
echo "📦 Running database migrations..."
php artisan migrate --force --isolated

# 3. Cache optimization
echo "⚡ Optimizing application cache..."
php artisan optimize

echo "✅ Pre-deployment tasks complete. Starting Supervisor..."
