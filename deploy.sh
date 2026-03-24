#!/bin/sh

# Skynet Customer Health - Coolify Deployment Script
# This script runs on container startup

set -e

echo "🚀 Starting Skynet Customer Health deployment..."

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
