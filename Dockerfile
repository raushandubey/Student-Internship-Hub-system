# ============================================================================
# Student Internship Hub - Production Dockerfile for Render
# ============================================================================
# 
# Architecture: Nginx → PHP-FPM → Laravel → Database (external)
# 
# WHY Nginx + PHP-FPM instead of Apache?
# - Nginx: Lightweight, high-performance, better for static files
# - PHP-FPM: Process manager, better resource handling, production-grade
# - Separation of concerns: Nginx serves static, PHP-FPM handles dynamic
# - Industry standard for modern Laravel deployments
# ============================================================================

FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions (PostgreSQL only, MySQL removed)
RUN docker-php-ext-install \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure Nginx
COPY nginx.conf /etc/nginx/nginx.conf
RUN rm -f /etc/nginx/sites-enabled/default

# Configure PHP-FPM to listen on TCP port 9000
COPY php-fpm-www.conf /usr/local/etc/php-fpm.d/www.conf

# Configure Supervisor
RUN mkdir -p /var/log/supervisor
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies (without scripts, without autoloader optimization)
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist

# Copy application code
COPY . .

# Generate optimized autoloader AFTER copying application code
RUN composer dump-autoload --optimize --no-dev

# DO NOT run package:discover here - no database connection available
# Will run in start.sh after environment variables are loaded

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
RUN find /var/www/html -type f -exec chmod 644 {} \;
RUN find /var/www/html -type d -exec chmod 755 {} \;
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# DO NOT cache Laravel here - environment variables not available yet
# Caching will be done in start.sh after env vars are loaded

# Copy and set startup script
COPY start.sh /start.sh
RUN chmod +x /start.sh

# Expose port
EXPOSE ${PORT:-10000}

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:${PORT:-10000}/health || exit 1

# Start services
CMD ["/start.sh"]
