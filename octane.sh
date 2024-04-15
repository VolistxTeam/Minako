#!/bin/bash

# Define the lockfile and website URL
LOCKFILE="/tmp/minako_octane.lock"
URL="https://api.cryental.dev/minako/ohys"
GIT_REPO_PATH="/home/u721179272/domains/cryental.dev/public_html/api/production/minako"

# Change to the directory where your Laravel application is located
cd "$GIT_REPO_PATH" || exit

# Use mkdir to attempt to create the lock directory
if ! mkdir "$LOCKFILE" 2>/dev/null; then
    echo "Another instance of the script is already running." >&2
    exit 1
fi

# Setup a trap to remove the lock directory when the script exits
trap 'rmdir "$LOCKFILE"' INT TERM EXIT

# Function to ensure the Octane server is running
ensure_octane_running() {
    echo "Checking if Octane server is running..."
    if php artisan octane:status | grep -q 'Octane server is running'; then
        echo "Octane server is running smoothly."
    else
        echo "Checking if port 27195 is already in use..."
        # Convert port number to hexadecimal with leading zeros
        HEX_PORT=$(printf '%04X' 27195)
        # Hexadecimal IP address for 127.0.0.1
        HEX_IP="0100007F"
        # Check if the port is in use by searching /proc/net/tcp
        if grep -i -q "$HEX_IP:$HEX_PORT" /proc/net/tcp; then
            echo "Port 27195 on 127.0.0.1 is in use. Attempting to free the port..."
            # Extract lines from /proc/net/tcp that include the port
            PID_LINES=$(grep -i "$HEX_IP:$HEX_PORT" /proc/net/tcp)
            # Process each line to find a valid inode and map it to a PID
            echo "$PID_LINES" | while IFS= read -r line; do
                INODE=$(echo "$line" | awk '{print $10}')
                if [[ "$INODE" != "0" && "$INODE" != "" ]]; then
                    # Iterate through all /proc/*/fd to find the matching inode
                    for FD in /proc/[0-9]*/fd/*; do
                        if [[ "$(readlink $FD)" == "socket:[$INODE]" ]]; then
                            PID=$(echo "$FD" | cut -d'/' -f3)
                            if [[ ! -z "$PID" ]]; then
                                kill -9 "$PID"
                                echo "Process with PID $PID using port 27195 has been killed."
                                # Continue to next inode rather than breaking, to kill all matching processes
                            fi
                        fi
                    done
                    echo "Could not map inode to PID or kill process."
                else
                    echo "Invalid inode."
                fi
            done
        else
            echo "Port 27195 on 127.0.0.1 is not in use."
        fi

        echo "Attempting to start Octane server..."
        nohup php artisan octane:start --server=swoole --port=27195 > octane.log 2>&1 &
        sleep 2  # Wait for a few seconds to allow the server to start

        if php artisan octane:status | grep -q 'Octane server is running'; then
            echo "Octane server started successfully."
        else
            echo "Failed to start Octane server, attempting to restart..."
            nohup php artisan octane:start --server=swoole --port=27195 > octane.log 2>&1 &
            if php artisan octane:status | grep -q 'Octane server is running'; then
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
        return 1
    fi

    echo "Fetching latest changes from remote..."
    if ! git fetch; then
        echo "Failed to fetch changes."
        return 1
    fi

    LOCAL=$(git rev-parse HEAD)
    REMOTE=$(git rev-parse @{u})
    BASE=$(git merge-base HEAD @{u})

    if [ "$LOCAL" = "$REMOTE" ]; then
        echo "Git repository is up-to-date."
    elif [ "$LOCAL" = "$BASE" ]; then
        echo "Local is behind; need to pull changes from remote."
        if git pull --ff-only; then
            echo "Pulled successfully. Updating composer dependencies..."
            composer_update
            echo "Attempting to restart Octane server to apply changes..."
            restart_octane_server
        else
            echo "Failed to pull changes."
            return 1
        fi
    elif [ "$REMOTE" = "$BASE" ]; then
        echo "Local is ahead of remote; need to push changes."
    else
        echo "Local and remote have diverged; manual intervention required."
    fi
}

composer_update() {
    if ! composer2 update; then
        echo "Failed to update Composer dependencies."
    else
        echo "Composer dependencies updated successfully. Reloading Octane..."
    fi
}

restart_octane_server() {
    if php artisan octane:stop && nohup php artisan octane:start --server=swoole --port=27195 > octane.log 2>&1 &; then
        echo "Octane server restarted successfully."
    else
        echo "Failed to restart Octane server."
        return 1
    fi
}

# Function to check the website's health
check_website_health() {
    # Use curl to check if the website is accessible
    HTTP_STATUS=$(curl -o /dev/null -s -w "%{http_code}\n" "$URL")

    # Check for HTTP success status codes
    if [[ "$HTTP_STATUS" -ne 200 ]]; then
        echo "Website is down or not functioning correctly, HTTP status: $HTTP_STATUS, restarting Octane..."
        php artisan octane:stop
        ensure_octane_running
    else
        echo "Website is up, HTTP status: $HTTP_STATUS."
    fi
}

# Function to update Composer dependencies
composer_update() {
    if ! composer2 update; then
        echo "Failed to update Composer dependencies."
    else
        echo "Composer dependencies updated successfully."
    fi
}

# Main execution block
# Check Git repository updates
check_git_updates

# Ensure Octane is running
ensure_octane_running

# Check the website's health
check_website_health

# The lock directory will be removed by the trap
