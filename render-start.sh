#!/bin/bash
set -e

# Create directories
mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache

# Fix permissions early
chown -R www-data:www-data /var/www/html
chmod -R 775 storage bootstrap/cache

# Auto-detect PostgreSQL from Render DATABASE_URL
if [ ! -z "$DATABASE_URL" ]; then
    echo "Detected DATABASE_URL, using PostgreSQL..."
    export DB_CONNECTION=pgsql
else
    # Default ke PostgreSQL kalau tidak ada DATABASE_URL explicit
    export DB_CONNECTION=${DB_CONNECTION:-pgsql}
fi

# Create .env if not exists (Render provides env vars, but Laravel needs .env file)
if [ ! -f .env ]; then
    echo "Creating .env file with DB_CONNECTION=${DB_CONNECTION}..."
    cat > .env << EOF
APP_NAME="Si LAGO"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=${APP_URL:-http://localhost}

DB_CONNECTION=${DB_CONNECTION}
DB_URL=${DATABASE_URL:-}
DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-laravel}
DB_USERNAME=${DB_USERNAME:-root}
DB_PASSWORD=${DB_PASSWORD:-}

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
EOF
    chown www-data:www-data .env
fi

# Generate APP_KEY hanya jika Render belum menyediakannya.
echo "Ensuring APP_KEY exists..."
if [ -z "${APP_KEY:-}" ]; then
    php artisan key:generate --force --no-interaction
else
    echo "APP_KEY sudah tersedia dari environment, melewati key:generate."
fi

# Pastikan symlink public/storage ada. Tanpa ini file di storage/app/public
# tidak bisa diakses lewat URL /storage/... (menyebabkan 404 pada gambar).
echo "Creating storage symlink..."
php artisan storage:link --force --no-interaction

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
