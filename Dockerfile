# Use PHP 8.4 CLI as base image for development
FROM php:8.4-cli AS base

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
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
# Extensions that need configuration
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd

# Extensions that don't need configuration
RUN docker-php-ext-install -j$(nproc) \
        pdo_sqlite \
        zip \
        bcmath \
        ctype \
        fileinfo \
        intl \
        mbstring \
        opcache \
        pdo \
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

# Copy application files first
COPY --chown=laravel:laravel . .

# Copy composer files
COPY --chown=laravel:laravel composer.json composer.lock ./

# Install Composer dependencies
RUN composer install --prefer-dist --no-interaction --optimize-autoloader

# Copy package files
COPY --chown=laravel:laravel package.json package-lock.json ./

# Install Node.js dependencies
RUN npm ci

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

# Copy application files first
COPY --chown=laravel:laravel . .

# Copy composer files
COPY --chown=laravel:laravel composer.json composer.lock ./

# Install Composer dependencies (production only)
RUN composer install --prefer-dist --no-interaction --optimize-autoloader --no-dev

# Copy package files
COPY --chown=laravel:laravel package.json package-lock.json ./

# Install Node.js dependencies and build
RUN npm ci --only=production \
    && npm run build

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
