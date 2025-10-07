#!/bin/bash

# Moodle Cron Script for Render Worker Service
# This script runs Moodle cron jobs

set -e

echo "Starting Moodle cron worker..."

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

# Run cron jobs in a loop
echo "Starting cron job loop..."
while true; do
    echo "$(date): Running Moodle cron jobs..."
    php admin/cli/cron.php
    
    # Wait 5 minutes before next run
    echo "$(date): Waiting 5 minutes before next cron run..."
    sleep 300
done
