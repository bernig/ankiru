#!/bin/bash

# shared-utils.sh - Common utilities for Laravel scripts

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging functions
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ERROR: $1${NC}"
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] WARNING: $1${NC}"
}

# Check if script is executed from parent directory
check_execution_directory() {
    local script_dir=$(cd "$(dirname "$0")" && pwd)
    if [ "$(pwd)" != "$(dirname "$script_dir")" ]; then
        error "Please execute the script from the parent folder like this: 'scripts/$(basename "$0")'"
        exit 1
    fi
}

# Check if required tools are installed
check_tool() {
    local tool=$1
    local message=${2:-"$tool is required but not installed. Aborting."}

    if ! command -v "$tool" >/dev/null 2>&1; then
        error "$message"
        exit 1
    fi
}

# Check multiple tools at once
check_tools() {
    local tools=("$@")
    for tool in "${tools[@]}"; do
        check_tool "$tool"
    done
}

# Clear Laravel caches
clear_laravel_caches() {
    log "Clearing application caches..."
    php artisan optimize:clear || warning "Failed to clear some caches"
#    php artisan filament:clear-cached-components || warning "Failed to clear Filament components"
}

# Cache Laravel configuration and routes
cache_laravel_config() {
    log "Caching configuration and routes..."

    php artisan optimize || {
        error "Failed to optimize"
        exit 1
    }
}

# Build NPM assets
build_assets() {
    log "Building assets..."
    npm run build || {
        error "Failed to build assets"
        exit 1
    }
}

# Install composer dependencies
install_composer_deps() {
    local dev_mode=${1:-false}

    log "Installing Composer dependencies..."
    if [ "$dev_mode" = true ]; then
        composer install --prefer-dist || {
            error "Failed to install Composer dependencies"
            exit 1
        }
    else
        composer install --optimize-autoloader --no-dev --prefer-dist || {
            error "Failed to install Composer dependencies"
            exit 1
        }
    fi
}

# Install NPM dependencies
install_npm_deps() {
    log "Installing NPM dependencies..."
    npm ci || {
        error "Failed to install NPM dependencies"
        exit 1
    }
}

# Put application in maintenance mode
enable_maintenance() {
    log "Putting application in maintenance mode..."
    php artisan down --refresh=5 --secret="$(openssl rand -hex 16)" || {
        error "Failed to put application in maintenance mode"
        exit 1
    }
}

# Bring application back up
disable_maintenance() {
    log "Bringing application back up..."
    php artisan up || {
        error "Failed to bring application back up"
        exit 1
    }
}

# Generate IDE helper files
generate_ide_helpers() {
    log "Generating IDE helper files..."
    php artisan ide-helper:generate || warning "Failed to generate IDE helpers"
    php artisan ide-helper:models --nowrite --phpstorm-noinspections || warning "Failed to generate model helpers"
    php artisan ide-helper:meta || warning "Failed to generate meta helpers"
}

# Format Blade views
format_blade_views() {
    log "Formatting Blade views..."
    check_tool "node_modules/.bin/blade-formatter" "Blade formatter is required but not installed. Aborting."

    node_modules/.bin/blade-formatter "resources/views/**/*.blade.php" --write --wrap-line-length 9999 --sort-tailwindcss-classes --sort-html-attributes "code-guide" --no-multiple-empty-lines || {
        warning "Failed to format Blade views"
    }
}

# Run Duster for PHP linting and formatting
run_duster() {
    log "Running Duster..."
    check_tool "vendor/bin/duster" "Duster is required but not installed. Aborting."

    vendor/bin/duster fix || {
        warning "Duster found issues that need attention"
    }
}

# Clear debugbar (development)
clear_debugbar() {
	log ""
}

# Cache views
cache_views() {
    log "Caching views..."
    php artisan view:cache || {
        warning "Failed to cache views"
    }
}

# Cache Filament components
cache_filament_components() {
	log ""
}

# Fetch Google Fonts
fetch_google_fonts() {
	log ""
}

# Create directories if they don't exist
ensure_directories() {
    local dirs=("$@")
    for dir in "${dirs[@]}"; do
        mkdir -p "$dir" || {
            warning "Failed to create directory: $dir"
        }
    done
}


# Build icons
build_icons() {
	log ""
}
