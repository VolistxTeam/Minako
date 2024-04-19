#!/bin/bash

# Define constants
LOCKFILE="/tmp/minako_octane.lock"
URL="https://api.cryental.dev/minako/ohys"
GIT_REPO_PATH="/home/u721179272/domains/cryental.dev/public_html/api/production/minako"
PORT=27195
HEX_PORT=$(printf '%04X' $PORT)
HEX_IP="0100007F"

echo "Laravel Octane Manager For Shared Hosting" >&2
echo "Made By Cryental" >&2
echo "Version 1.3.6" >&2
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


# Release the port by killing processes using it
release_port() {
    PID_LINES=$(grep -i "$HEX_IP:$HEX_PORT" /proc/net/tcp)
    if [ -z "$PID_LINES" ]; then
        echo "Port $PORT is not currently in use."
        return 0
    fi

    echo "$PID_LINES" | while IFS= read -r line; do
        local inode=$(echo "$line" | awk '{print $10}')
        if [[ "$inode" != "0" && "$inode" != "" ]]; then
            for FD in /proc/[0-9]*/fd/*; do
                if [[ "$(readlink $FD)" == "socket:[$inode]" ]]; then
                    local pid=$(echo "$FD" | cut -d'/' -f3)
                    echo "Attempting to gracefully kill process $pid using port $PORT..."
                    kill -15 "$pid"  # Send SIGTERM
                    sleep 5  # Allow some time for the process to terminate

                    # Check if the process is still running and use SIGKILL if necessary
                    if ps -p $pid > /dev/null 2>&1; then
                        echo "Process $pid did not terminate, using SIGKILL..."
                        kill -9 "$pid"
                        echo "Process with PID $pid has been force-killed."
                    else
                        echo "Process with PID $pid has terminated gracefully."
                    fi
                fi
            done
        fi
    done

    # Verify if the port is finally free
    if grep -i -q "$HEX_IP:$HEX_PORT" /proc/net/tcp; then
        echo "Port is still in use. Manual intervention may be required."
    else
        echo "Port is now free."
    fi
}

# Function to ensure the Octane server is running
ensure_octane_running() {
    echo "Checking if Octane server is running..."
    if php artisan octane:status | grep -q 'Octane server is running'; then
        echo "Octane server is running smoothly."
        check_website_health
    else
        echo "Octane server is not running, attempting to start..."
        start_octane
    fi
}

# Start or restart the Octane server
start_octane() {
    # Check if port is in use
    stop_octane
    sleep 3
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

# Stop the Octane server
stop_octane() {
    echo "Stopping Octane server..."
    php artisan octane:stop
    release_port
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

# Main execution block
check_git_updates
ensure_octane_running
check_website_health
