#!/bin/bash
set -e

# Create directories
mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache

# Fix permissions early
chown -R www-data:www-data /var/www/html
chmod -R 775 storage bootstrap/cache

# Generate APP_KEY if missing (Render should set this via env vars)
echo "Ensuring APP_KEY exists..."
php artisan key:generate --force --no-interaction 2>/dev/null || true

# Configure Apache to use Render's PORT
sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf

echo "Running migrations..."
php artisan migrate --force --no-interaction

echo "Caching config, routes, and views..."
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

echo "Starting Apache on port ${PORT:-80}..."
exec apache2-foreground
