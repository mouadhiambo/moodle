#!/bin/bash

# Moodle Start Script for Render Web Service
# This script starts the Moodle web service

set -e

echo "Starting Moodle web service..."

# Ensure data directory exists and has proper permissions
mkdir -p /opt/render/project/src/moodledata
chmod -R 777 /opt/render/project/src/moodledata

# Check if config.php exists
if [ ! -f "config.php" ]; then
    echo "Error: config.php not found in root directory"
    echo "Please ensure the build process completed successfully"
    exit 1
fi

# Verify database connection
echo "Verifying database connection..."
php -r "
\$host = getenv('MOODLE_DB_HOST') ?: 'localhost';
\$dbname = getenv('MOODLE_DB_NAME') ?: 'moodle';
\$user = getenv('MOODLE_DB_USER') ?: 'moodle';
\$pass = getenv('MOODLE_DB_PASSWORD') ?: 'password';

try {
    \$pdo = new PDO(\"pgsql:host=\$host;dbname=\$dbname\", \$user, \$pass);
    echo 'Database connection verified\n';
} catch (Exception \$e) {
    echo 'Database connection failed: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

# Check if Moodle is installed
if ! php -r "
    define('CLI_SCRIPT', true);
    require_once('config.php');
    try {
        \$tables = \$DB->get_tables();
        if (empty(\$tables)) {
            echo 'Moodle is not installed\n';
            exit(1);
        } else {
            echo 'Moodle is installed\n';
            exit(0);
        }
    } catch (Exception \$e) {
        echo 'Database error: ' . \$e->getMessage() . '\n';
        exit(1);
    }
"; then
    echo "Moodle is not installed. Please run the build script first."
    exit 1
fi

# Start the PHP built-in server with router script
echo "Starting PHP server on port ${PORT:-8000}..."
exec php -S 0.0.0.0:${PORT:-8000} -t public router.php
