# Use PHP 8.4 FPM as base image (Debian for better compatibility)
FROM php:8.4-fpm AS base

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    curl \
    wget \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libxml2-dev \
    libsqlite3-dev \
    libonig-dev \
    libicu-dev \
    nodejs \
    npm \
    gnupg \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        gd \
        pdo_sqlite \
        zip \
        bcmath \
        ctype \
        fileinfo \
        intl \
        mbstring \
        opcache \
        pdo \
        tokenizer \
        xml \
    && docker-php-ext-enable opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create non-root user
RUN groupadd -r laravel -g 1000 \
    && useradd -r -g laravel -u 1000 -d /var/www/html laravel

# Set permissions
RUN chown -R laravel:laravel /var/www/html \
    && chmod -R 775 /var/www/html

# Development stage
FROM base AS development

# Switch to laravel user
USER laravel

# Copy composer files
COPY --chown=laravel:laravel composer.json composer.lock ./

# Install Composer dependencies
RUN composer install --prefer-dist --no-interaction --optimize-autoloader

# Copy package files
COPY --chown=laravel:laravel package.json package-lock.json ./

# Install Node.js dependencies
RUN npm ci

# Copy application files
COPY --chown=laravel:laravel . .

# Create environment file
RUN cp .env.example .env

# Generate application key
RUN php artisan key:generate

# Build frontend assets
RUN npm run build

# Create necessary directories and set permissions
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Production stage
FROM base AS production

# Switch to laravel user
USER laravel

# Copy composer files
COPY --chown=laravel:laravel composer.json composer.lock ./

# Install Composer dependencies (production only)
RUN composer install --prefer-dist --no-interaction --optimize-autoloader --no-dev

# Copy package files
COPY --chown=laravel:laravel package.json package-lock.json ./

# Install Node.js dependencies and build
RUN npm ci --only=production \
    && npm run build

# Copy application files
COPY --chown=laravel:laravel . .

# Create environment file
RUN cp .env.example .env

# Generate application key
RUN php artisan key:generate

# Laravel optimizations for production
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan event:cache \
    && php artisan optimize

# Create necessary directories and set permissions
RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Final stage
FROM production

# Copy configuration files from host
COPY --chown=laravel:laravel docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY --chown=laravel:laravel docker/nginx.conf /etc/nginx/nginx.conf
COPY --chown=laravel:laravel docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY --chown=laravel:laravel docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Switch back to root for final setup
USER root

# Create log directories
RUN mkdir -p /var/log/supervisor /var/log/nginx \
    && chown -R laravel:laravel /var/log/supervisor /var/log/nginx

# Expose ports
EXPOSE 8080

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]