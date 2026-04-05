FROM php:8.4-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by Laravel + Hackazon
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    xml

# Enable Apache mod_rewrite (required for Laravel routing)
RUN a2enmod rewrite

# Configure Apache document root to Laravel's public/
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' \
    /etc/apache2/apache2.conf

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for layer caching
COPY composer.json composer.lock* ./

# Download packages without generating autoload (app dirs not present yet)
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --no-autoloader

# Copy the two custom non-Packagist vendor libraries (AMF + GWT)
# These are vendored in the repo because they're not on Packagist.
# vendor/ is excluded from .dockerignore so we COPY them explicitly here.
COPY vendor/hackazon vendor/hackazon
COPY vendor/gwtphp   vendor/gwtphp

# Copy application source (vendor/ is excluded via .dockerignore)
COPY . .

# Generate optimized autoload now that all classmap directories exist
# --no-scripts skips artisan package:discover (needs .env / DB, not available at build time)
RUN composer dump-autoload --no-dev --optimize --no-scripts && \
    rm -f bootstrap/cache/packages.php bootstrap/cache/services.php

# Set permissions for Laravel storage and cache
RUN chown -R www-data:www-data /var/www/html/storage \
    /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage \
    /var/www/html/bootstrap/cache

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
