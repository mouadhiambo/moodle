# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    pkg-config \
    autoconf \
    libtool \
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
# Configure GD extension with all image format support
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm

# Install PHP extensions individually to identify and isolate any issues
RUN docker-php-ext-install -j$(nproc) pdo
RUN docker-php-ext-install -j$(nproc) pdo_pgsql
RUN docker-php-ext-install -j$(nproc) pgsql
RUN docker-php-ext-install -j$(nproc) zip
RUN docker-php-ext-install -j$(nproc) mbstring
RUN docker-php-ext-install -j$(nproc) xml
RUN docker-php-ext-install -j$(nproc) curl
RUN docker-php-ext-install -j$(nproc) intl
RUN docker-php-ext-install -j$(nproc) opcache
RUN docker-php-ext-install -j$(nproc) soap
RUN docker-php-ext-install -j$(nproc) exif

# Install GD extension last to avoid conflicts
RUN docker-php-ext-install -j$(nproc) gd

# Verify PHP extensions are installed correctly
RUN php -m | grep -E "(pdo|pdo_pgsql|pgsql|zip|mbstring|xml|curl|intl|opcache|soap|exif|sodium|gd|fileinfo|hash)"

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Debug: Check file structure after copy
RUN echo "=== File structure after COPY ===" && \
    ls -la /var/www/html/ && \
    echo "=== Public directory contents ===" && \
    ls -la /var/www/html/public/ | head -10 && \
    echo "=== Admin CLI directory ===" && \
    ls -la /var/www/html/public/admin/cli/ | head -5 || echo "admin/cli not found"

# Create config.php for deployment (using environment variables)
RUN echo '<?php' > config.php && \
    echo '///////////////////////////////////////////////////////////////////////////' >> config.php && \
    echo '//                                                                       //' >> config.php && \
    echo '// Moodle configuration file for Render deployment                      //' >> config.php && \
    echo '//                                                                       //' >> config.php && \
    echo '///////////////////////////////////////////////////////////////////////////' >> config.php && \
    echo '' >> config.php && \
    echo 'unset($CFG);' >> config.php && \
    echo 'global $CFG;' >> config.php && \
    echo '$CFG = new stdClass();' >> config.php && \
    echo '' >> config.php && \
    echo '// Database Setup' >> config.php && \
    echo '$CFG->dbtype    = getenv("MOODLE_DB_TYPE") ?: "pgsql";' >> config.php && \
    echo '$CFG->dblibrary = "native";' >> config.php && \
    echo '$CFG->dbhost    = getenv("MOODLE_DB_HOST") ?: "localhost";' >> config.php && \
    echo '$CFG->dbname    = getenv("MOODLE_DB_NAME") ?: "moodle";' >> config.php && \
    echo '$CFG->dbuser    = getenv("MOODLE_DB_USER") ?: "moodle";' >> config.php && \
    echo '$CFG->dbpass    = getenv("MOODLE_DB_PASSWORD") ?: "password";' >> config.php && \
    echo '$CFG->prefix    = "mdl_";' >> config.php && \
    echo '' >> config.php && \
    echo '$CFG->dboptions = [' >> config.php && \
    echo '    "dbpersist" => false,' >> config.php && \
    echo '    "dbsocket"  => false,' >> config.php && \
    echo '    "dbport"    => "",' >> config.php && \
    echo '    "dbhandlesoptions" => false,' >> config.php && \
    echo '    "dbcollation" => "utf8mb4_unicode_ci",' >> config.php && \
    echo '];' >> config.php && \
    echo '' >> config.php && \
    echo '// Web Site Location' >> config.php && \
    echo '$CFG->wwwroot = getenv("MOODLE_WWWROOT") ?: "http://localhost";' >> config.php && \
    echo '' >> config.php && \
    echo '// Data Files Location' >> config.php && \
    echo '$CFG->dataroot = getenv("MOODLE_DATAROOT") ?: "/opt/render/project/src/moodledata";' >> config.php && \
    echo '' >> config.php && \
    echo '// Data Files Permissions' >> config.php && \
    echo '$CFG->directorypermissions = 02777;' >> config.php && \
    echo '' >> config.php && \
    echo '// Admin Directory Location' >> config.php && \
    echo '$CFG->admin = "admin";' >> config.php && \
    echo '' >> config.php && \
    echo '// Render-specific settings' >> config.php && \
    echo '$CFG->maintenance_enabled = false;' >> config.php && \
    echo 'date_default_timezone_set("UTC");' >> config.php && \
    echo '$CFG->extramemorylimit = "512M";' >> config.php && \
    echo '$CFG->filelifetime = 60*60*24;' >> config.php && \
    echo '' >> config.php && \
    echo '// Proxy/SSL offloading' >> config.php && \
    echo '$CFG->reverseproxy = true;' >> config.php && \
    echo '$CFG->sslproxy = true;' >> config.php && \
    echo '' >> config.php && \
    echo '// Cache settings' >> config.php && \
    echo '$CFG->cachedir = $CFG->dataroot . "/cache";' >> config.php && \
    echo '$CFG->tempdir = $CFG->dataroot . "/temp";' >> config.php && \
    echo '$CFG->localcachedir = $CFG->dataroot . "/localcache";' >> config.php && \
    echo '' >> config.php && \
    echo '// Session handling' >> config.php && \
    echo '$CFG->session_handler_class = "\\core\\session\\database";' >> config.php && \
    echo '$CFG->session_database_acquire_lock_timeout = 120;' >> config.php && \
    echo '' >> config.php && \
    echo '// Security settings' >> config.php && \
    echo '$CFG->cookiehttponly = true;' >> config.php && \
    echo '$CFG->preventfilelocking = false;' >> config.php && \
    echo '' >> config.php && \
    echo '// Email settings' >> config.php && \
    echo '$CFG->smtphosts = getenv("SMTP_HOST") ?: "";' >> config.php && \
    echo '$CFG->smtpuser = getenv("SMTP_USER") ?: "";' >> config.php && \
    echo '$CFG->smtppass = getenv("SMTP_PASS") ?: "";' >> config.php && \
    echo '$CFG->smtpsecure = getenv("SMTP_SECURE") ?: "tls";' >> config.php && \
    echo '$CFG->smtpauthtype = "LOGIN";' >> config.php && \
    echo '' >> config.php && \
    echo '// Performance optimizations' >> config.php && \
    echo '$CFG->disablestatsprocessing = true;' >> config.php && \
    echo '$CFG->disableupdatenotifications = true;' >> config.php && \
    echo '$CFG->yuislasharguments = 1;' >> config.php && \
    echo '$CFG->cachejs = true;' >> config.php && \
    echo '$CFG->cachetemplates = true;' >> config.php && \
    echo '$CFG->cronclionly = true;' >> config.php && \
    echo '' >> config.php && \
    echo '// Forced settings' >> config.php && \
    echo '$CFG->forced_plugin_settings = [' >> config.php && \
    echo '    "tool_mobile" => [' >> config.php && \
    echo '        "enabled" => 1,' >> config.php && \
    echo '    ],' >> config.php && \
    echo '];' >> config.php && \
    echo '' >> config.php && \
    echo '// Security settings' >> config.php && \
    echo '$CFG->passwordpeppers = [' >> config.php && \
    echo '    1 => getenv("MOODLE_PASSWORD_PEPPER") ?: "default_pepper_change_in_production"' >> config.php && \
    echo '];' >> config.php && \
    echo '' >> config.php && \
    echo '// Directory setup - migration helper will append /lib to libdir' >> config.php && \
    echo '$CFG->dirroot = __DIR__;' >> config.php && \
    echo '$CFG->libdir = __DIR__;' >> config.php && \
    echo '' >> config.php && \
    echo '// Final setup' >> config.php && \
    echo 'require_once(__DIR__ . "/lib/setup.php");' >> config.php

# Verify essential files exist
RUN ls -la config.php && echo "config.php created successfully"

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /opt/render/project/src/moodledata \
    && chown -R www-data:www-data /opt/render/project/src/moodledata \
    && chmod -R 777 /opt/render/project/src/moodledata

# Configure Apache
RUN a2enmod rewrite headers ssl
COPY docker/apache-config.conf /etc/apache2/sites-available/000-default.conf
# Ensure our site config is active and set a global ServerName to silence warnings
RUN printf "ServerName localhost\n" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername \
    && rm -f /etc/apache2/sites-enabled/000-default.conf \
    && ln -s /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-enabled/000-default.conf

# Configure PHP
COPY docker/php.ini /usr/local/etc/php/conf.d/moodle.ini

# Install Moodle dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create installation script
COPY docker/install-moodle.sh /usr/local/bin/install-moodle.sh
RUN chmod +x /usr/local/bin/install-moodle.sh

# Expose port
EXPOSE 80

# Health check - try simple health check first, then full health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health-simple.php || curl -f http://localhost/public/health-simple.php || curl -f http://localhost/health.php || curl -f http://localhost/public/health.php || exit 1

# Start script that handles installation and starts Apache
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]
