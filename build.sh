#!/bin/bash

# Moodle Build Script for Render Deployment
# This script prepares Moodle for deployment on Render

set -e

echo "Starting Moodle build process..."

# Install PHP dependencies
echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Create necessary directories
echo "Creating necessary directories..."
mkdir -p /opt/render/project/src/moodledata/{cache,temp,localcache,sessions,trashdir}
mkdir -p /opt/render/project/src/moodledata/lang
mkdir -p /opt/render/project/src/moodledata/backup
mkdir -p /opt/render/project/src/moodledata/filedir
mkdir -p /opt/render/project/src/moodledata/secret

# Set proper permissions
echo "Setting directory permissions..."
chmod -R 777 /opt/render/project/src/moodledata

# Copy config file if it doesn't exist
if [ ! -f "public/config.php" ]; then
    echo "Copying config.php to public directory..."
    cp config.php public/config.php
fi

# Wait for database to be ready
echo "Waiting for database connection..."
max_attempts=30
attempt=1

while [ $attempt -le $max_attempts ]; do
    if php -r "
        \$host = getenv('MOODLE_DB_HOST') ?: 'localhost';
        \$dbname = getenv('MOODLE_DB_NAME') ?: 'moodle';
        \$user = getenv('MOODLE_DB_USER') ?: 'moodle';
        \$pass = getenv('MOODLE_DB_PASSWORD') ?: 'password';
        
        try {
            \$pdo = new PDO(\"pgsql:host=\$host;dbname=\$dbname\", \$user, \$pass);
            echo 'Database connection successful\n';
            exit(0);
        } catch (Exception \$e) {
            echo 'Database connection failed: ' . \$e->getMessage() . '\n';
            exit(1);
        }
    "; then
        echo "Database is ready!"
        break
    else
        echo "Attempt $attempt/$max_attempts: Database not ready, waiting..."
        sleep 5
        attempt=$((attempt + 1))
    fi
done

if [ $attempt -gt $max_attempts ]; then
    echo "Database connection timeout. Proceeding with installation anyway..."
fi

# Check if Moodle is already installed
if php admin/cli/isinstalled.php; then
    echo "Moodle is already installed. Running upgrade..."
    php admin/cli/upgrade.php --non-interactive --allow-unstable
else
    echo "Installing Moodle..."
    php admin/cli/install_database.php \
        --agree-license \
        --fullname="Moodle LMS" \
        --shortname="Moodle" \
        --summary="Moodle Learning Management System deployed on Render" \
        --adminuser=admin \
        --adminpass="${MOODLE_ADMIN_PASSWORD:-admin123}" \
        --adminemail="${MOODLE_ADMIN_EMAIL:-admin@example.com}" \
        --non-interactive
fi

# Purge caches
echo "Purging caches..."
php admin/cli/purge_caches.php

# Generate password pepper if not set
if [ -z "$MOODLE_PASSWORD_PEPPER" ]; then
    echo "Generating password pepper..."
    export MOODLE_PASSWORD_PEPPER=$(openssl rand -base64 32)
    echo "Generated pepper: $MOODLE_PASSWORD_PEPPER"
fi

echo "Build process completed successfully!"
