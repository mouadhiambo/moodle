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

# Create config.php for deployment (using environment variables)
RUN cat > config.php << 'EOF'
<?php
///////////////////////////////////////////////////////////////////////////
//                                                                       //
// Moodle configuration file for Render deployment                      //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

unset($CFG);
global $CFG;
$CFG = new stdClass();

//=========================================================================
// 1. DATABASE SETUP
//=========================================================================
$CFG->dbtype    = getenv('MOODLE_DB_TYPE') ?: 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost    = getenv('MOODLE_DB_HOST') ?: 'localhost';
$CFG->dbname    = getenv('MOODLE_DB_NAME') ?: 'moodle';
$CFG->dbuser    = getenv('MOODLE_DB_USER') ?: 'moodle';
$CFG->dbpass    = getenv('MOODLE_DB_PASSWORD') ?: 'password';
$CFG->prefix    = 'mdl_';

$CFG->dboptions = [
    'dbpersist' => false,
    'dbsocket'  => false,
    'dbport'    => '',
    'dbhandlesoptions' => false,
    'dbcollation' => 'utf8mb4_unicode_ci',
];

//=========================================================================
// 2. WEB SITE LOCATION
//=========================================================================
$CFG->wwwroot = getenv('MOODLE_WWWROOT') ?: 'https://moodle-web.onrender.com';

//=========================================================================
// 3. DATA FILES LOCATION
//=========================================================================
$CFG->dataroot = getenv('MOODLE_DATAROOT') ?: '/opt/render/project/src/moodledata';

//=========================================================================
// 4. DATA FILES PERMISSIONS
//=========================================================================
$CFG->directorypermissions = 02777;

//=========================================================================
// 5. ADMIN DIRECTORY LOCATION
//=========================================================================
$CFG->admin = 'admin';

//=========================================================================
// 6. RENDER-SPECIFIC SETTINGS
//=========================================================================

// Enable maintenance mode during deployment
$CFG->maintenance_enabled = false;

// Set timezone
date_default_timezone_set('UTC');

// Memory limit for large operations
$CFG->extramemorylimit = '512M';

// File serving optimization
$CFG->filelifetime = 60*60*24; // 24 hours

// Cache settings for better performance
$CFG->cachedir = $CFG->dataroot . '/cache';
$CFG->tempdir = $CFG->dataroot . '/temp';
$CFG->localcachedir = $CFG->dataroot . '/localcache';

// Session handling for cloud environment
$CFG->session_handler_class = '\core\session\database';
$CFG->session_database_acquire_lock_timeout = 120;

// Security settings
$CFG->cookiehttponly = true;
$CFG->preventfilelocking = false; // Disable file locking for cloud storage

// Email settings (configure with your SMTP provider)
$CFG->smtphosts = getenv('SMTP_HOST') ?: '';
$CFG->smtpuser = getenv('SMTP_USER') ?: '';
$CFG->smtppass = getenv('SMTP_PASS') ?: '';
$CFG->smtpsecure = getenv('SMTP_SECURE') ?: 'tls';
$CFG->smtpauthtype = 'LOGIN';

// Disable some features that might not work well in cloud environment
$CFG->disablestatsprocessing = true;
$CFG->disableupdatenotifications = true;

// Performance optimizations
$CFG->yuislasharguments = 1;
$CFG->cachejs = true;
$CFG->cachetemplates = true;

// Cron settings
$CFG->cronclionly = true; // Only allow CLI cron

//=========================================================================
// 7. FORCED SETTINGS
//=========================================================================
$CFG->forced_plugin_settings = [
    'tool_mobile' => [
        'enabled' => 1,
    ],
];

//=========================================================================
// 8. SECURITY SETTINGS
//=========================================================================
// Password pepper for enhanced security
$CFG->passwordpeppers = [
    1 => getenv('MOODLE_PASSWORD_PEPPER') ?: 'default_pepper_change_in_production'
];

//=========================================================================
// 9. FINAL SETUP
//=========================================================================
require_once(__DIR__ . '/lib/setup.php');
EOF

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
