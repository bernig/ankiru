#!/bin/bash

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
source "$SCRIPT_DIR/shared-utils.sh"

check_execution_directory

set -e

if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo "Usage: scripts/deploy.sh [--dev]"
    echo "  --dev : Install development dependencies"
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

log "Starting deployment process..."

enable_maintenance
install_composer_deps $DEV_MODE
install_npm_deps
build_assets
build_icons
fetch_google_fonts
clear_laravel_caches
cache_laravel_config

if [ "$DEV_MODE" = true ]; then
    clear_debugbar
fi

cache_views

if [ "$DEV_MODE" = false ]; then
    cache_filament_components
fi

ensure_directories "lang-custom"
bash "$SCRIPT_DIR/set_permissions.sh"
disable_maintenance

log "Deployment completed successfully!"

if [ "$DEV_MODE" = true ]; then
    log "Development mode was enabled - remember to run 'php artisan optimize' for production"
fi

log "You can now access your application"
