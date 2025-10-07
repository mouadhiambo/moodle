#!/bin/bash

# Moodle Cron Start Script for Docker
# This script starts the cron worker after ensuring Moodle is properly installed

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

# Run installation if needed
/usr/local/bin/install-moodle.sh

# Run cron jobs in a loop
echo "Starting cron job loop..."
while true; do
    echo "$(date): Running Moodle cron jobs..."
    php admin/cli/cron.php
    
    # Wait 5 minutes before next run
    echo "$(date): Waiting 5 minutes before next cron run..."
    sleep 300
done
