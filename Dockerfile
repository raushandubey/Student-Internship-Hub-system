FROM php:8.2-fpm

# Install system dependencies for PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN php artisan key:generate --force || true
RUN php artisan config:clear
RUN php artisan config:cache

EXPOSE 10000

CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]
