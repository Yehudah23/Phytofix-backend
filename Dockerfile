FROM php:8.5-fpm

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    mariadb-client \
    && docker-php-ext-install pdo pdo_mysql gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory contents
COPY . /var/www/html

# Create .env from .env.example (since .env is gitignored)
RUN cp .env.example .env

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Set permissions for storage and bootstrap cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Configure Nginx to point to public/
COPY ./docker/nginx.conf /etc/nginx/sites-available/default

# Generate optimized autoloader and cache config
RUN composer dump-autoload --optimize --no-interaction

EXPOSE 80

# Start PHP-FPM and keep Nginx in the foreground
CMD ["sh", "-c", "php-fpm -D && exec nginx -g 'daemon off;'"]