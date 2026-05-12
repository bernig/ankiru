#!/bin/bash

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
source "$SCRIPT_DIR/shared-utils.sh"

check_execution_directory

set -e

if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo "Usage: scripts/set_permissions.sh"
    echo "Sets the correct permissions for the project directories and files."
    exit 0
fi

log "Fixing permissions..."

# Set ownership to the web server user and group (adjust 'www-data' as needed)
find storage bootstrap/cache -type d -exec chmod 2775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
chmod g+s storage bootstrap/cache

log "Permissions fixed."
