#!/bin/bash

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
source "$SCRIPT_DIR/shared-utils.sh"

check_execution_directory

set -e

check_tools "php"

clear_laravel_caches

log "Starting the tests..."
php artisan test --coverage || {
    error "Tests failed"
    exit 1
}

cache_laravel_config
cache_filament_components
cache_views
