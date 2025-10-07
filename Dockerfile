# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    pkg-config \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libxpm-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    libicu-dev \
    libmemcached-dev \
    libmagickwand-dev \
    ghostscript \
    unzip \
    curl \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
# Configure and install GD extension with all image format support
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm

# Install core PHP extensions first
RUN docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    pgsql \
    zip \
    mbstring \
    xml \
    curl \
    intl \
    opcache \
    soap \
    exif \
    fileinfo \
    hash \
    sodium

# Install GD extension separately to avoid build conflicts
RUN docker-php-ext-install -j$(nproc) gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /opt/render/project/src/moodledata \
    && chown -R www-data:www-data /opt/render/project/src/moodledata \
    && chmod -R 777 /opt/render/project/src/moodledata

# Configure Apache
RUN a2enmod rewrite headers ssl
COPY docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

# Configure PHP
COPY docker/php.ini /usr/local/etc/php/conf.d/moodle.ini

# Install Moodle dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create installation script
COPY docker/install-moodle.sh /usr/local/bin/install-moodle.sh
RUN chmod +x /usr/local/bin/install-moodle.sh

# Expose port
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/public/health.php || exit 1

# Start script that handles installation and starts Apache
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
