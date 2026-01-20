FROM php:8.2-fpm
FROM php:8.2-fpm-alpine

RUN apt-get update && apt-get install -y \
RUN apk add --no-cache \
    nginx \
    supervisor \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libjpeg-turbo-dev \
    oniguruma-dev \
    libxml2-dev \
    libpq-dev \
    postgresql-dev \
    postgresql-client \
    zip \
    unzip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    libzip-dev \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install -j$(nproc) \
    pdo \
        pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

RUN php -m | grep -E 'pdo_pgsql|pgsql'

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY nginx.conf /etc/nginx/nginx.conf
RUN rm -f /etc/nginx/sites-enabled/default

COPY php-fpm-www.conf /usr/local/etc/php-fpm.d/www.conf

RUN mkdir -p /var/log/supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist
    --prefer-dist \
    --optimize-autoloader

COPY . .

RUN composer dump-autoload --optimize --no-dev

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

COPY nginx.conf /etc/nginx/nginx.conf
COPY php-fpm-www.conf /usr/local/etc/php-fpm.d/www.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY start.sh /start.sh
RUN chmod +x /start.sh

RUN rm -f /etc/nginx/sites-enabled/default \
    && mkdir -p /var/log/supervisor \
    && chmod +x /start.sh \
    && addgroup -g 1000 www-data \
    && adduser -u 1000 -G www-data -s /bin/sh -D www-data

COPY --chown=www-data:www-data . .

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE ${PORT:-10000}

HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:${PORT:-10000}/health || exit 1

CMD ["/start.sh"]
