# Stage 1: Build Vite assets
FROM node:20-alpine AS asset-builder
WORKDIR /app
COPY . .
RUN npm install
RUN npm run build

# Stage 2: Production PHP/Apache environment
FROM php:8.4-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring zip exif pcntl bcmath gd intl \
    && pecl install redis \
    && docker-php-ext-enable redis

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set Apache DocumentRoot to Laravel's public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Copy built frontend assets from asset-builder stage
COPY --from=asset-builder /app/public/build ./public/build

# Install Composer dependencies
RUN composer install --no-interaction --no-dev --optimize-autoloader --verbose

# Set correct permissions for storage and bootstrap/cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80
EXPOSE 80

# Make entrypoint script executable
RUN chmod +x /var/www/html/docker/entrypoint.sh

# Set entrypoint
ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
