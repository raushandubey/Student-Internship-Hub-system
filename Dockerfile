# ============================================================================
# Student Internship Hub - Production Dockerfile for Render
# ============================================================================
# 
# WHY Docker for Render?
# - Render's PHP environment is limited (no direct Apache control)
# - Docker gives us full control over PHP version, extensions, and web server
# - Ensures consistent environment between local dev and production
# - Allows custom Apache configuration for Laravel
#
# Architecture: Apache → mod_php → Laravel → Database (external)
# ============================================================================

# ============================================================================
# Stage 1: Base Image with PHP 8.2 and Apache
# ============================================================================
# WHY php:8.2-apache?
# - Official PHP image with Apache pre-installed
# - Matches our Laravel 12 requirement (PHP 8.2+)
# - Apache is production-grade (better than artisan serve)
# - mod_php is faster than PHP-FPM for single-server deployments
FROM php:8.2-apache

# ============================================================================
# Stage 2: Install System Dependencies
# ============================================================================
# WHY these packages?
# - git: Required by Composer for package installation
# - curl: For health checks and external API calls
# - libpng-dev, libjpeg-dev: For image processing (GD extension)
# - libonig-dev: For mbstring extension (multibyte string handling)
# - libxml2-dev: For XML parsing
# - zip/unzip: For Composer package management
# - libzip-dev: For PHP zip extension
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*
# WHY rm -rf /var/lib/apt/lists/*?
# - Reduces Docker image size by removing package cache
# - Production best practice (smaller images = faster deployments)

# ============================================================================
# Stage 3: Install PHP Extensions
# ============================================================================
# WHY these extensions?
# - pdo_mysql: Database connection (required for Laravel)
# - mbstring: Multibyte string handling (required for Laravel)
# - exif: Image metadata reading
# - pcntl: Process control (for queue workers)
# - bcmath: Arbitrary precision math (for financial calculations)
# - gd: Image processing (for avatars, thumbnails)
# - zip: Archive handling (for exports)
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# ============================================================================
# Stage 4: Install Composer
# ============================================================================
# WHY Composer in Docker?
# - Need to install Laravel dependencies (vendor/)
# - Composer is not included in base PHP image
# - Using official Composer image ensures latest stable version
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
# WHY --from=composer:latest?
# - Multi-stage build pattern (copies only the binary, not the whole image)
# - Keeps our final image small

# ============================================================================
# Stage 5: Configure Apache for Laravel
# ============================================================================
# WHY enable mod_rewrite?
# - Laravel uses .htaccess for URL rewriting (pretty URLs)
# - Without mod_rewrite, routes won't work (404 errors)
RUN a2enmod rewrite

# WHY change DocumentRoot to /var/www/html/public?
# - Laravel's entry point is public/index.php, not root
# - Security: Prevents direct access to app/, config/, etc.
# - Standard Laravel deployment practice
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# WHY AllowOverride All?
# - Allows .htaccess to work (Laravel's URL rewriting)
# - Without this, .htaccess rules are ignored
RUN echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/laravel.conf
RUN a2enconf laravel

# ============================================================================
# Stage 6: Set Working Directory
# ============================================================================
# WHY /var/www/html?
# - Standard Apache document root location
# - Matches Apache configuration above
# - All subsequent commands run from this directory
WORKDIR /var/www/html

# ============================================================================
# Stage 7: Copy Application Files
# ============================================================================
# WHY copy in this order?
# - Docker caches layers (speeds up rebuilds)
# - composer.json changes less frequently than app code
# - If composer.json unchanged, Docker reuses cached vendor/ layer

# Copy composer files first (for layer caching)
COPY composer.json composer.lock ./

# WHY composer install before copying app code?
# - Leverages Docker layer caching
# - If composer.json unchanged, vendor/ is cached
# - Speeds up subsequent builds significantly
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader
# WHY these flags?
# - --no-dev: Skip dev dependencies (phpunit, etc.) - smaller image
# - --no-interaction: Non-interactive mode (required for Docker)
# - --no-progress: No progress bar (cleaner logs)
# - --no-scripts: Skip post-install scripts (run manually later)
# - --prefer-dist: Download zip instead of git clone (faster)
# - --optimize-autoloader: Generate optimized autoloader (faster app)

# Copy application code
COPY . .

# WHY copy everything?
# - Need all Laravel files (app/, config/, routes/, resources/, etc.)
# - .dockerignore excludes unnecessary files (node_modules, .git, etc.)

# ============================================================================
# Stage 8: Run Post-Install Scripts
# ============================================================================
# WHY run these scripts?
# - Composer skipped them earlier (--no-scripts)
# - Laravel needs these for proper setup
RUN composer run-script post-autoload-dump

# ============================================================================
# Stage 9: Set Permissions
# ============================================================================
# WHY set permissions?
# - Apache runs as www-data user (not root)
# - Laravel needs write access to storage/ and bootstrap/cache/
# - Without correct permissions, app will crash (500 errors)

# Change ownership to www-data (Apache user)
RUN chown -R www-data:www-data /var/www/html

# Set correct permissions
# WHY 775 for directories?
# - Owner (www-data): read, write, execute
# - Group (www-data): read, write, execute
# - Others: read, execute
# - Allows Apache to read/write, but not too permissive
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# WHY 644 for files?
# - Owner: read, write
# - Group: read
# - Others: read
# - Standard file permissions (not executable)
RUN find /var/www/html -type f -exec chmod 644 {} \;

# WHY 755 for directories?
# - Owner: read, write, execute
# - Group: read, execute
# - Others: read, execute
# - Allows directory traversal
RUN find /var/www/html -type d -exec chmod 755 {} \;

# Re-apply 775 to storage and cache (override previous 755)
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# ============================================================================
# Stage 10: Optimize Laravel for Production
# ============================================================================
# WHY cache config, routes, and views?
# - Dramatically improves performance (no file parsing on each request)
# - Production best practice
# - Config cache: ~50ms faster per request
# - Route cache: ~30ms faster per request
# - View cache: ~20ms faster per request

# Cache configuration
# WHY config:cache?
# - Combines all config files into single cached file
# - Skips file I/O on every request
RUN php artisan config:cache

# Cache routes
# WHY route:cache?
# - Compiles all routes into single cached file
# - Skips route file parsing on every request
RUN php artisan route:cache

# Cache views
# WHY view:cache?
# - Pre-compiles all Blade templates
# - Skips template compilation on first request
RUN php artisan view:cache

# ============================================================================
# Stage 11: Expose Port
# ============================================================================
# WHY port 10000?
# - Render requires web services to listen on port 10000
# - This is Render's standard (not configurable)
# - Apache listens on 80 by default, we'll remap it
EXPOSE 10000

# ============================================================================
# Stage 12: Configure Apache to Listen on Port 10000
# ============================================================================
# WHY change Apache port?
# - Render expects port 10000, Apache defaults to 80
# - Must match EXPOSE directive above
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf
RUN sed -i 's/:80/:10000/' /etc/apache2/sites-available/000-default.conf

# ============================================================================
# Stage 13: Health Check
# ============================================================================
# WHY health check?
# - Render uses this to verify container is running
# - If health check fails, Render restarts container
# - Checks if Apache is responding on port 10000
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:10000/ || exit 1

# ============================================================================
# Stage 14: Start Apache
# ============================================================================
# WHY apache2-foreground?
# - Keeps Apache running in foreground (required for Docker)
# - If Apache runs in background, container exits immediately
# - This is the official Apache Docker image command
CMD ["apache2-foreground"]

# ============================================================================
# FINAL IMAGE SUMMARY
# ============================================================================
# What this Dockerfile does:
# 1. Installs PHP 8.2 with Apache
# 2. Installs required PHP extensions for Laravel
# 3. Installs Composer and Laravel dependencies
# 4. Configures Apache for Laravel (DocumentRoot, mod_rewrite)
# 5. Sets correct file permissions for Laravel
# 6. Caches Laravel config, routes, and views
# 7. Exposes port 10000 for Render
# 8. Starts Apache in foreground
#
# Request Flow:
# User → Render → Port 10000 → Apache → public/index.php → Laravel → Response
#
# Why this approach?
# - Production-grade (Apache, not artisan serve)
# - Optimized (caching, autoloader optimization)
# - Secure (correct permissions, public/ as DocumentRoot)
# - Render-compatible (port 10000, single container)
# - Explainable (every step documented for viva)
# ============================================================================
