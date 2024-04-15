#!/bin/bash

# Define the lockfile and website URL
LOCKFILE="/tmp/minako_octane.lock"
URL="https://api.cryental.dev/minako/ohys"
GIT_REPO_PATH="/home/u721179272/domains/cryental.dev/public_html/api/production/minako"

# Change to the directory where your Laravel application is located
# shellcheck disable=SC2164
cd "$GIT_REPO_PATH"

# Function to ensure the Octane server is running
ensure_octane_running() {
    # First, check if Octane is running
    if php artisan octane:status | grep -q 'Server is running'; then
        echo "Octane server is running smoothly."
    else
        echo "Octane server is not running, attempting to start..."
        # Try to start Octane
        php artisan octane:start --server=swoole --port=27195
        sleep 2  # Wait for a few seconds to allow the server to start

        # Check again if Octane has started successfully
        if php artisan octane:status | grep -q 'Server is running'; then
            echo "Octane server started successfully."
        else
            echo "Failed to start Octane server, attempting to restart..."
            # Attempt to restart Octane
            if php artisan octane:restart --server=swoole --port=27195; then
                echo "Octane server restarted successfully."
            else
                echo "Failed to restart Octane server."
            fi
        fi
    fi
}

# Function to check if Git repository is up-to-date
check_git_updates() {
    echo "Checking for local uncommitted changes..."
    if ! git diff-index --quiet HEAD --; then
        echo "There are uncommitted changes in the repository."
        return
    fi

    echo "Fetching latest changes from remote..."
    git fetch

    LOCAL=$(git rev-parse @)
    REMOTE=$(git rev-parse @{u})
    BASE=$(git merge-base @ @{u})

    if [ $LOCAL = $REMOTE ]; then
        echo "Git repository is up-to-date."
    elif [ $LOCAL = $BASE ]; then
        echo "Need to pull, the repository is not up-to-date."
        git pull
        echo "Pulled successfully. Updating composer dependencies..."
        composer_update
    elif [ $REMOTE = $BASE ]; then
        echo "Need to push local changes."
    else
        echo "Repository has diverged from remote, manual intervention required."
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

# Function to update Composer dependencies
composer_update() {
    if composer2 update; then
        echo "Composer dependencies updated successfully."
    else
        echo "Failed to update Composer dependencies."
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

    # Check Git repository updates
    check_git_updates

    # Ensure Octane is running
    ensure_octane_running

    # Check the website's health
    check_website_health

    # Remove lock file
    rm "$LOCKFILE"
fi
