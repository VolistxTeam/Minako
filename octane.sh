#!/bin/bash

# Define constants
LOCKFILE="/tmp/minako_octane.lock"
URL="https://api.cryental.dev/minako/ohys"
GIT_REPO_PATH="/home/u721179272/domains/cryental.dev/public_html/api/production/minako"
PORT=27195
HEX_PORT=$(printf '%04X' $PORT)
HEX_IP="0100007F"

echo "Laravel Octane Manager For Shared Hosting" >&2
echo "Version 1.2" >&2
echo "" >&2
# Change to the directory where your Laravel application is located
cd "$GIT_REPO_PATH" || exit

# Open lock file for reading and writing (fd 200), create if not exists
exec 200>"$LOCKFILE"

# Acquire an exclusive non-blocking lock (fd 200)
if ! flock -n 200; then
    echo "Another instance of the script is already running." >&2
    exit 1
fi

# Trap that will execute on any script exit, successful or not
trap 'cleanup' EXIT INT TERM

cleanup() {
    echo "Cleaning up..."
    flock -u 200
    rm -f "$LOCKFILE"
    # Additional cleanup commands can be added here
}

# Function to ensure the Octane server is running
ensure_octane_running() {
    echo "Checking if Octane server is running..."
    if ! php artisan octane:status | grep -q 'Octane server is running'; then
        echo "Octane server is not running, attempting to start..."
        start_octane
    else
        echo "Octane server is running smoothly."
    fi
}

# Start or restart the Octane server
start_octane() {
    # Check if port is in use
    if grep -i -q "$HEX_IP:$HEX_PORT" /proc/net/tcp; then
        echo "Port $PORT on 127.0.0.1 is in use. Attempting to free the port..."
        release_port
    fi
    echo "Starting Octane server..."
    php artisan octane:start --server=swoole --port=$PORT > octane.log 2>&1 &
    sleep 2
    if php artisan octane:status | grep -q 'Octane server is running'; then
        echo "Octane server started successfully."
    else
        echo "Failed to start Octane server."
    fi
}

# Release the port by killing processes using it
release_port() {
    PID_LINES=$(grep -i "$HEX_IP:$HEX_PORT" /proc/net/tcp)
    echo "$PID_LINES" | while IFS= read -r line; do
        INODE=$(echo "$line" | awk '{print $10}')
        if [[ "$INODE" != "0" && "$INODE" != "" ]]; then
            for FD in /proc/[0-9]*/fd/*; do
                if [[ "$(readlink $FD)" == "socket:[$INODE]" ]]; then
                    PID=$(echo "$FD" | cut -d'/' -f3)
                    echo "Attempting to kill process $PID using port $PORT..."
                    kill -9 "$PID"
                    echo "Process with PID $PID has been killed."
                    sleep 2  # Give some time for the port to be released
                fi
            done
        fi
    done
    # Check again if the port is free
    if grep -i -q "$HEX_IP:$HEX_PORT" /proc/net/tcp; then
        echo "Port is still in use. Manual intervention may be required."
    else
        echo "Port is now free."
    fi
}

# Function to check if Git repository is up-to-date and manage updates
check_git_updates() {
    echo "Fetching latest changes from remote..."
    git fetch
    LOCAL=$(git rev-parse HEAD)
    REMOTE=$(git rev-parse @{u})
    BASE=$(git merge-base HEAD @{u})

    if [ "$LOCAL" = "$REMOTE" ]; then
        echo "Git repository is up-to-date."
    elif [ "$LOCAL" = "$BASE" ]; then
        echo "Local is behind; pulling changes from remote..."
        git pull --ff-only && echo "Pulled successfully."
        composer_update
        start_octane
    elif [ "$REMOTE" = "$BASE" ]; then
        echo "Local is ahead of remote; need to push changes."
    else
        echo "Local and remote have diverged; manual intervention required."
    fi
}

# Update Composer dependencies
composer_update() {
    echo "Updating Composer dependencies..."
    composer2 update && echo "Composer dependencies updated successfully."
}

# Function to check the website's health
check_website_health() {
    HTTP_STATUS=$(curl -o /dev/null -s -w "%{http_code}\n" "$URL")
    if [[ "$HTTP_STATUS" -ne 200 ]]; then
        echo "Website is down or not functioning correctly, HTTP status: $HTTP_STATUS."
        start_octane
    else
        echo "Website is up, HTTP status: $HTTP_STATUS."
    fi
}

# Main execution block
check_git_updates
ensure_octane_running
check_website_health
