#!/bin/bash

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
source "$SCRIPT_DIR/shared-utils.sh"

check_execution_directory

set -e

if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo "Usage: scripts/get_ready_for_dev.sh"
    echo "Prepares the development environment by updating dependencies,"
    echo "formatting code, clearing caches, and generating IDE helpers."
    exit 0
fi

check_tools "npm" "composer" "vendor/bin/duster" "node" "node_modules/.bin/blade-formatter"

log "Starting development environment setup..."

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

build_icons
format_blade_views
run_duster
clear_laravel_caches
clear_debugbar

log "Caching icons..."
php artisan icons:cache || {
    warning "Failed to cache icons"
}

# Generate IDE helper files
generate_ide_helpers

# Print the Git status
log "Current Git status:"
git status || {
    warning "Failed to get Git status"
}

# Building assets for development
log "Building assets for development..."
npm run dev || {
    error "Failed to build development assets"
    exit 1
}

log "Development environment setup completed successfully!"
log "Your environment is now ready for development work."
