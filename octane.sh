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
echo "Version 1.3.10" >&2
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

# Check website health and manage server accordingly
check_website_health() {
    HTTP_STATUS=$(curl -o /dev/null -s -w "%{http_code}\n" "$URL")
    if [[ "$HTTP_STATUS" -ne 200 ]]; then
        echo "Website is down or not functioning correctly, HTTP status: $HTTP_STATUS."
        manage_octane_server
    else
        echo "Website is up, HTTP status: $HTTP_STATUS."
    fi
}

is_port_free() {
    local in_use=false
    while IFS=' ' read -a line; do
        local local_address="${line[1]}"  # Field 2 in /proc/net/tcp is local address and port
        local inode="${line[9]}"  # Field 10 is inode

        # Split the local address into IP and port
        local ip="${local_address%%:*}"
        local port="${local_address##*:}"

        if [[ "$ip" == "$HEX_IP" && "$port" == "$HEX_PORT" ]]; then
            echo "Port $PORT is currently in use."
            in_use=true
            break  # Exit the loop as soon as we find the port is in use
        fi
    done < /proc/net/tcp

    if [ "$in_use" = false ]; then
        echo "Port $PORT is free."
        return 0
    else
        return 1
    fi
}

# Release the port by killing processes using it
release_port() {
    if is_port_free; then
        return 0  # Exit if the port is not in use
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

                    # Use SIGKILL if necessary
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
}

# Manage Octane server
manage_octane_server() {
    # Stop any currently running Octane server
    if php artisan octane:status | grep -q 'Octane server is running'; then
        echo "Stopping current Octane server..."
        php artisan octane:stop
    fi

    # Release the port
    release_port

    # Start the Octane server
    echo "Starting Octane server..."
    php artisan octane:start --server=swoole --port=$PORT > octane.log 2>&1 &
    sleep 2

    # Verify that the server has started
    if php artisan octane:status | grep -q 'Octane server is running'; then
        echo "Octane server started successfully."
        check_website_health
    else
        echo "Failed to start Octane server."
    fi
}

# Check and update Git repository, then manage server
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
        if git pull --ff-only; then
            echo "Pulled successfully."
            composer_update
            manage_octane_server
        else
            echo "Failed to pull changes."
        fi
    elif [ "$REMOTE" = "$BASE" ]; then
        echo "Local is ahead of remote; need to push changes."
    else
        echo "Local and remote have diverged; manual intervention required."
    fi
}

# Update Composer dependencies
composer_update() {
    echo "Updating Composer dependencies..."
    if composer2 update; then
        echo "Composer dependencies updated successfully."
    else
        echo "Failed to update Composer dependencies."
    fi
}

# Main execution block
check_git_updates
check_website_health
