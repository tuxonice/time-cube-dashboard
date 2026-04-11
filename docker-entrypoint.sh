#!/usr/bin/env bash

# Laravel Sail-style entrypoint
# Ensures proper permissions and runs as non-root user

set -e

# If running as root, fix permissions and switch to sail user
if [ "$(id -u)" = "0" ]; then
    # Fix ownership of mounted volumes
    chown -R sail:sail /var/www/html/database /var/www/html/public/uploads 2>/dev/null || true
    
    # If command is apache2-foreground, run it directly (Apache handles user switching)
    if [ "$1" = "apache2-foreground" ]; then
        exec "$@"
    else
        # For other commands (like bash), switch to sail user
        exec gosu sail "$@"
    fi
else
    # Already running as non-root, execute command directly
    exec "$@"
fi
