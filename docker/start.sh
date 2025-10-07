#!/bin/bash

# Moodle Start Script for Docker
# This script starts Apache after ensuring Moodle is properly installed

set -e

echo "Starting Moodle web service..."

# Ensure data directory exists and has proper permissions
mkdir -p /opt/render/project/src/moodledata
chmod -R 777 /opt/render/project/src/moodledata

# Check if config.php exists (in the root directory, not public)
if [ ! -f "config.php" ]; then
    echo "Error: config.php not found in root directory"
    echo "Please ensure the build process completed successfully"
    exit 1
fi

# Run installation if needed
/usr/local/bin/install-moodle.sh

# Start Apache in foreground
echo "Starting Apache web server..."
exec apache2-foreground
