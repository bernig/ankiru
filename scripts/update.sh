#!/bin/bash

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
source "$SCRIPT_DIR/shared-utils.sh"

check_execution_directory

set -e

# Display help message
if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo "Usage: scripts/update.sh [--dev]"
    echo "  --dev : Clear development caches (debugbar)"
    exit 0
fi

DEV_MODE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --dev)
            DEV_MODE=true
            shift
            ;;
        *)
            error "Unknown option: $1"
            exit 1
            ;;
    esac
done

check_tools "npm" "composer" "php"

if [ ! -f "scripts/deploy.sh" ]; then
    error "deploy.sh is required but not present. Aborting."
    exit 1
fi

log "Starting update process..."

enable_maintenance

log "Updating NPM dependencies..."
npm update || {
    error "Failed to update NPM dependencies"
    exit 1
}

log "Updating Composer dependencies..."
composer update || {
    error "Failed to update Composer dependencies"
    exit 1
}

build_assets
build_icons
fetch_google_fonts
clear_laravel_caches
cache_laravel_config
cache_filament_components

if [ "$DEV_MODE" = true ]; then
    clear_debugbar
fi

cache_views
bash "$SCRIPT_DIR/set_permissions.sh"
disable_maintenance

log "Update completed successfully!"

if [ "$DEV_MODE" = true ]; then
    log "Development mode was enabled - debugbar cache was cleared"
fi

log "You can now access your application"
