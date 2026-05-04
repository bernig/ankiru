#!/bin/bash

SCRIPT_DIR=$(cd "$(dirname "$0")" && pwd)
source "$SCRIPT_DIR/shared-utils.sh"

check_execution_directory

set -e

check_tools "vendor/bin/duster" "node" "node_modules/.bin/blade-formatter"

log "Starting development formatting and optimization..."

format_blade_views
run_duster
build_assets
#build_icons
clear_laravel_caches
#clear_debugbar
cache_laravel_config
#cache_filament_components
generate_ide_helpers

log "Development tasks completed successfully!"
