#!/bin/bash

# Define the lockfile and website URL
LOCKFILE="/tmp/minako_octane.lock"
URL="https://api.cryental.dev/minako/ohys"

# Change to the directory where your Laravel application is located
cd /home/u721179272/domains/cryental.dev/public_html/api/production/minako

# Function to ensure the Octane server is running
ensure_octane_running() {
    # First, check if Octane is running
    if php artisan octane:status | grep -q 'Server is running'; then
        echo "Octane server is running smoothly."
    else
        echo "Octane server is not running, attempting to start..."
        # Try to start Octane
        php artisan octane:start --server=swoole
        sleep 2  # Wait for a few seconds to allow the server to start

        # Check again if Octane has started successfully
        if php artisan octane:status | grep -q 'Server is running'; then
            echo "Octane server started successfully."
        else
            echo "Failed to start Octane server, attempting to restart..."
            # Attempt to restart Octane
            if php artisan octane:restart --server=swoole; then
                echo "Octane server restarted successfully."
            else
                echo "Failed to restart Octane server."
            fi
        fi
    fi
}

# Function to check the website's health
check_website_health() {
    # Use curl to check if the website is accessible
    HTTP_STATUS=$(curl -o /dev/null -s -w "%{http_code}\n" "$URL")

    # Check for HTTP success status codes
    if [[ "$HTTP_STATUS" -ne 200 ]]; then
        echo "Website is down or not functioning correctly, HTTP status: $HTTP_STATUS, restarting Octane..."
        php artisan octane:restart --server=swoole
    else
        echo "Website is up, HTTP status: $HTTP_STATUS."
    fi
}

# Main execution block
# Check if another instance of the script is running
if [ -f "$LOCKFILE" ]; then
    echo "Another instance is running, exiting..."
    exit
else
    # Create a lock file
    touch "$LOCKFILE"

    # Ensure Octane is running
    ensure_octane_running

    # Check the website's health
    check_website_health

    # Remove lock file
    rm "$LOCKFILE"
fi
