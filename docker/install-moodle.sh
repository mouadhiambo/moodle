#!/bin/bash

# Moodle Installation Script for Docker
# This script handles the Moodle installation process

set -e

echo "Starting Moodle installation process..."

# Change to the root directory where Moodle files are located
cd /var/www/html

# Debug: Show current directory and file structure
echo "Current working directory: $(pwd)"
echo "Checking for admin/cli directory..."
ls -la admin/cli/ | head -5 || echo "admin/cli directory not found"

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

# Check if Moodle is already installed by checking if database tables exist
echo "Checking if Moodle is already installed..."
if php -r "
    define('CLI_SCRIPT', true);
    require_once('config.php');
    try {
        \$tables = \$DB->get_tables();
        if (empty(\$tables)) {
            echo 'Moodle is not installed (no tables found)\n';
            exit(1);
        } else {
            echo 'Moodle is already installed (' . count(\$tables) . ' tables found)\n';
            exit(0);
        }
    } catch (Exception \$e) {
        echo 'Database error: ' . \$e->getMessage() . '\n';
        exit(1);
    }
"; then
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
        --adminemail="${MOODLE_ADMIN_EMAIL:-admin@example.com}"
fi

# Purge caches
echo "Purging caches..."
php admin/cli/purge_caches.php

echo "Moodle installation completed successfully!"
