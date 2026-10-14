# ============================================
# Dockerfile Silago-Web di Render
# Laravel 12 + PHP 8.3 + Apache + PostgreSQL
# ============================================

# Stage 1: build asset frontend (Vite)
FROM node:22-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: aplikasi
FROM php:8.3-apache

ENV APP_ENV=production \
    APP_DEBUG=false

RUN apt-get update && apt-get install -y \
    libpq-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libicu-dev \
    unzip git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql pgsql zip gd intl bcmath exif opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Document root ke /public + izinkan .htaccess Laravel
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Dependency dulu (cache layer), tanpa script karena artisan belum ada
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader

# Salin seluruh aplikasi + hasil build asset
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Start script (sekaligus buang CRLF kalau file dibuat di Windows)
COPY render-start.sh /usr/local/bin/render-start.sh
RUN sed -i 's/\r$//' /usr/local/bin/render-start.sh \
    && chmod +x /usr/local/bin/render-start.sh

CMD ["render-start.sh"]
