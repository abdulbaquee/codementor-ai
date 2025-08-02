#!/bin/bash

# 🗑️ Code Review System Uninstaller
# Purpose: Remove the review system and restore original state

set -e

# Rollback functionality
ROLLBACK_NEEDED=false
ROLLBACK_STEPS=()

# Function to add rollback step
add_rollback_step() {
    ROLLBACK_STEPS+=("$1")
}

# Function to execute rollback
execute_rollback() {
    if [ "$ROLLBACK_NEEDED" = true ]; then
        echo -e "${RED}🔄 Executing rollback...${NC}"
        for step in "${ROLLBACK_STEPS[@]}"; do
            echo -e "${YELLOW}   Rolling back: $step${NC}"
            eval "$step"
        done
        echo -e "${GREEN}✅ Rollback completed${NC}"
    fi
}

# Trap to handle errors and rollback
trap 'execute_rollback' ERR

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
COMPOSER_FILE="$PROJECT_ROOT/composer.json"
MARKER_FILE="$PROJECT_ROOT/review-system/install-marker.json"

# Parse command line arguments
REMOVE_PACKAGES=false
REMOVE_HOOKS=true
REMOVE_CONFIG=true
RESTORE_BACKUP=false
FORCE=false
RESTORE_HOOKS=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --full)
            REMOVE_PACKAGES=true
            REMOVE_HOOKS=true
            REMOVE_CONFIG=true
            RESTORE_BACKUP=true
            shift
            ;;
        --hooks)
            REMOVE_PACKAGES=false
            REMOVE_HOOKS=true
            REMOVE_CONFIG=false
            RESTORE_BACKUP=false
            shift
            ;;
        --safe)
            REMOVE_PACKAGES=false
            REMOVE_HOOKS=true
            REMOVE_CONFIG=true
            RESTORE_BACKUP=false
            shift
            ;;
        --packages)
            REMOVE_PACKAGES=true
            REMOVE_HOOKS=false
            REMOVE_CONFIG=false
            RESTORE_BACKUP=false
            shift
            ;;
        --force)
            FORCE=true
            shift
            ;;
        --restore-hooks)
            RESTORE_HOOKS=true
            shift
            ;;
        --help)
            echo "🗑️ Code Review System Uninstaller"
            echo ""
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --full         Remove everything (packages + hooks + config + restore backup)"
            echo "  --hooks        Remove only Git hooks"
            echo "  --safe         Remove hooks and config, keep packages (recommended)"
            echo "  --packages     Remove only external packages"
            echo "  --force        Force removal without confirmation"
echo "  --restore-hooks Restore custom hooks from backup"
echo "  --help         Show this help message"
            echo ""
            echo "Examples:"
            echo "  $0 --safe      # Safe removal (recommended)"
            echo "  $0 --full      # Complete removal"
            echo "  $0 --hooks     # Remove only Git hooks"
            echo ""
            echo "⚠️  Warning: --full will remove external packages and restore composer.json backup"
            exit 0
            ;;
        *)
            echo -e "${RED}❌ Unknown option: $1${NC}"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

echo -e "${PURPLE}🗑️ Code Review System Uninstaller${NC}"
echo "====================================="

# Display removal mode
if [ "$REMOVE_PACKAGES" = true ] && [ "$RESTORE_BACKUP" = true ]; then
    echo -e "${RED}📋 Mode: Full Removal${NC}"
elif [ "$REMOVE_HOOKS" = true ] && [ "$REMOVE_CONFIG" = true ]; then
    echo -e "${YELLOW}📋 Mode: Safe Removal${NC}"
elif [ "$REMOVE_HOOKS" = true ]; then
    echo -e "${BLUE}📋 Mode: Hooks Only${NC}"
else
    echo -e "${CYAN}📋 Mode: Packages Only${NC}"
fi
echo ""

# Check if marker file exists
if [ ! -f "$MARKER_FILE" ]; then
    echo -e "${YELLOW}⚠️  No installation marker found.${NC}"
    echo -e "${YELLOW}💡 The review system may not be installed or was installed manually.${NC}"
    
    if [ "$FORCE" = false ]; then
        echo -e "${YELLOW}🔍 Checking for review system components...${NC}"
        
        # Check for Git hooks
        if [ -f "$PROJECT_ROOT/.git/hooks/pre-commit" ] || [ -f "$PROJECT_ROOT/.git/hooks/pre-push" ]; then
            echo -e "${GREEN}✅ Found Git hooks${NC}"
        fi
        
        # Check for composer scripts
        if grep -q "review:" "$COMPOSER_FILE" 2>/dev/null; then
            echo -e "${GREEN}✅ Found composer scripts${NC}"
        fi
        
        # Check for autoloading
        if grep -q "ReviewSystem" "$COMPOSER_FILE" 2>/dev/null; then
            echo -e "${GREEN}✅ Found autoloading configuration${NC}"
        fi
        
        echo ""
        read -p "Continue with removal? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            echo -e "${YELLOW}❌ Uninstallation cancelled${NC}"
            exit 0
        fi
    fi
else
    echo -e "${GREEN}✅ Found installation marker${NC}"
    # Display installation info
    INSTALL_INFO=$(php -r "
        \$info = json_decode(file_get_contents('$MARKER_FILE'), true);
        echo 'Installed: ' . \$info['installed_at'] . PHP_EOL;
        echo 'Mode: ' . \$info['install_mode'] . PHP_EOL;
    ")
    echo -e "${CYAN}$INSTALL_INFO${NC}"
fi

# 1. Remove Git hooks (if requested)
if [ "$REMOVE_HOOKS" = true ]; then
    echo -e "${BLUE}🔗 Analyzing Git hooks...${NC}"
    
    # Function to check if a hook contains our review system code
    is_review_system_hook() {
        local hook_file="$1"
        if [ -f "$hook_file" ]; then
            # Check for our specific markers in the hook
            if grep -q "Running pre-commit code review\|Running pre-push code review\|review-system/cli.php" "$hook_file" 2>/dev/null; then
                return 0  # True - it's our hook
            else
                return 1  # False - it's not our hook
            fi
        else
            return 1  # False - file doesn't exist
        fi
    }
    
    # Function to safely remove a hook
    safe_remove_hook() {
        local hook_file="$1"
        local hook_name="$2"
        
        if [ -f "$hook_file" ]; then
            if is_review_system_hook "$hook_file"; then
                echo -e "${YELLOW}🗑️  Removing review system $hook_name hook...${NC}"
                rm "$hook_file"
                echo -e "${GREEN}✅ Removed $hook_name hook${NC}"
            else
                echo -e "${YELLOW}⚠️  Found custom $hook_name hook (not removing)${NC}"
                echo -e "${CYAN}   File: $hook_file${NC}"
                if [ "$FORCE" = false ]; then
                    echo -e "${YELLOW}   💡 This appears to be a custom hook. Use --force to remove it.${NC}"
                else
                    echo -e "${RED}   🗑️  Force removing custom hook...${NC}"
                    rm "$hook_file"
                    echo -e "${GREEN}✅ Removed custom $hook_name hook (forced)${NC}"
                fi
            fi
        else
            echo -e "${YELLOW}⏭️  $hook_name hook not found${NC}"
        fi
    }
    
    # Check and remove pre-commit hook
    safe_remove_hook "$PROJECT_ROOT/.git/hooks/pre-commit" "pre-commit"
    
    # Check and remove pre-push hook
    safe_remove_hook "$PROJECT_ROOT/.git/hooks/pre-push" "pre-push"
    
    echo -e "${GREEN}✅ Git hooks analysis complete${NC}"
    
    # Restore custom hooks if requested
    if [ "$RESTORE_HOOKS" = true ]; then
        echo -e "${BLUE}🔄 Restoring custom hooks from backup...${NC}"
        
        if [ -f "$PROJECT_ROOT/.git/hooks/pre-commit.backup" ]; then
            cp "$PROJECT_ROOT/.git/hooks/pre-commit.backup" "$PROJECT_ROOT/.git/hooks/pre-commit"
            chmod +x "$PROJECT_ROOT/.git/hooks/pre-commit"
            echo -e "${GREEN}✅ Restored pre-commit hook from backup${NC}"
        else
            echo -e "${YELLOW}⏭️  No pre-commit backup found${NC}"
        fi
        
        if [ -f "$PROJECT_ROOT/.git/hooks/pre-push.backup" ]; then
            cp "$PROJECT_ROOT/.git/hooks/pre-push.backup" "$PROJECT_ROOT/.git/hooks/pre-push"
            chmod +x "$PROJECT_ROOT/.git/hooks/pre-push"
            echo -e "${GREEN}✅ Restored pre-push hook from backup${NC}"
        else
            echo -e "${YELLOW}⏭️  No pre-push backup found${NC}"
        fi
        
        # Clean up backup files
        rm -f "$PROJECT_ROOT/.git/hooks/pre-commit.backup"
        rm -f "$PROJECT_ROOT/.git/hooks/pre-push.backup"
        echo -e "${GREEN}✅ Backup files cleaned up${NC}"
    fi
else
    echo -e "${YELLOW}⏭️  Skipping Git hooks removal${NC}"
fi

# Function to analyze package dependencies
analyze_package_dependencies() {
    local packages=("$@")
    local dependency_map=()
    
    echo -e "${BLUE}🔍 Analyzing package dependencies...${NC}"
    
    for package in "${packages[@]}"; do
        if composer show "$package" --working-dir "$PROJECT_ROOT" > /dev/null 2>&1; then
            local dependents=$(composer why "$package" --working-dir "$PROJECT_ROOT" 2>/dev/null | grep -E "^[a-zA-Z]" | cut -d' ' -f1 | tr '\n' ' ')
            if [ -n "$dependents" ]; then
                echo -e "${YELLOW}   $package is required by: $dependents${NC}"
                dependency_map+=("$package:$dependents")
            else
                echo -e "${GREEN}   $package has no dependents${NC}"
                dependency_map+=("$package:")
            fi
        fi
    done
    
    # Sort packages by dependency count (most dependent first)
    local sorted_packages=()
    for package in "${packages[@]}"; do
        local dep_count=0
        for dep_info in "${dependency_map[@]}"; do
            if [[ "$dep_info" == *"$package"* ]]; then
                dep_count=$(echo "$dep_info" | cut -d':' -f2 | wc -w)
                break
            fi
        done
        sorted_packages+=("$dep_count:$package")
    done
    
    # Sort by dependency count (descending) and extract package names
    IFS=$'\n' sorted_packages=($(sort -nr <<<"${sorted_packages[*]}"))
    unset IFS
    
    local result=()
    for item in "${sorted_packages[@]}"; do
        result+=("$(echo "$item" | cut -d':' -f2)")
    done
    
    echo -e "${GREEN}✅ Dependency analysis complete${NC}"
    echo -e "${CYAN}📋 Removal order: ${result[*]}${NC}"
    
    echo "${result[@]}"
}

# 2. Remove external packages (if requested)
if [ "$REMOVE_PACKAGES" = true ]; then
    echo -e "${BLUE}📦 Removing external packages...${NC}"
    
    # Define packages to remove
    PACKAGES_TO_REMOVE=("phpstan/phpstan" "squizlabs/php_codesniffer" "nunomaduro/larastan")
    
    # Analyze dependencies and get optimal removal order
    OPTIMAL_ORDER=($(analyze_package_dependencies "${PACKAGES_TO_REMOVE[@]}"))
    
    # Use optimal order if analysis succeeded, otherwise use default order
    if [ ${#OPTIMAL_ORDER[@]} -eq ${#PACKAGES_TO_REMOVE[@]} ]; then
        PACKAGES_TO_REMOVE=("${OPTIMAL_ORDER[@]}")
    else
        # Fallback to manual order (larastan first as it depends on phpstan)
        PACKAGES_TO_REMOVE=("nunomaduro/larastan" "squizlabs/php_codesniffer" "phpstan/phpstan")
    fi
    
    # Track removal success for better error reporting
    REMOVAL_SUCCESS=()
    REMOVAL_FAILED=()
    
    for package in "${PACKAGES_TO_REMOVE[@]}"; do
        if composer show "$package" --working-dir "$PROJECT_ROOT" > /dev/null 2>&1; then
            echo -e "${YELLOW}🗑️  Removing $package...${NC}"
            
            # Store current state for potential rollback
            ROLLBACK_NEEDED=true
            add_rollback_step "composer require --dev $package --working-dir $PROJECT_ROOT --no-interaction"
            
            if composer remove --dev "$package" --working-dir "$PROJECT_ROOT" --no-interaction; then
                echo -e "${GREEN}✅ Successfully removed $package${NC}"
                REMOVAL_SUCCESS+=("$package")
                # Remove rollback step since removal was successful
                ROLLBACK_STEPS=("${ROLLBACK_STEPS[@]:1}")
            else
                echo -e "${RED}❌ Failed to remove $package${NC}"
                REMOVAL_FAILED+=("$package")
                
                # Check if it's a dependency issue
                if composer why "$package" --working-dir "$PROJECT_ROOT" > /dev/null 2>&1; then
                    echo -e "${YELLOW}💡 $package is required by other packages. Will be removed when dependencies are removed.${NC}"
                fi
            fi
        else
            echo -e "${YELLOW}⏭️  $package not installed${NC}"
        fi
    done
    
    # Final verification of package removal
    echo -e "${BLUE}🔍 Verifying package removal...${NC}"
    for package in "${PACKAGES_TO_REMOVE[@]}"; do
        if composer show "$package" --working-dir "$PROJECT_ROOT" > /dev/null 2>&1; then
            echo -e "${RED}⚠️  $package is still installed${NC}"
        else
            echo -e "${GREEN}✅ $package successfully removed${NC}"
        fi
    done
    
    echo -e "${GREEN}✅ Package removal completed${NC}"
else
    echo -e "${YELLOW}⏭️  Skipping package removal${NC}"
fi

# 3. Remove composer configuration (if requested)
if [ "$REMOVE_CONFIG" = true ]; then
    echo -e "${BLUE}📝 Removing composer configuration...${NC}"
    
    # Check if backup exists and is valid
    if [ -f "$COMPOSER_FILE.bak.review-system" ] && [ "$RESTORE_BACKUP" = true ]; then
        echo -e "${YELLOW}🔄 Restoring composer.json from backup...${NC}"
        
        # Validate backup file before restoration
        if php -r "json_decode(file_get_contents('$COMPOSER_FILE.bak.review-system')); echo json_last_error() === JSON_ERROR_NONE ? 'valid' : 'invalid';" | grep -q "valid"; then
            cp "$COMPOSER_FILE.bak.review-system" "$COMPOSER_FILE"
            echo -e "${GREEN}✅ composer.json restored from backup${NC}"
        else
            echo -e "${RED}❌ Backup file is invalid JSON, falling back to manual cleanup${NC}"
            RESTORE_BACKUP=false
        fi
    elif [ "$RESTORE_BACKUP" = true ]; then
        echo -e "${YELLOW}⚠️  Backup file not found, falling back to manual cleanup${NC}"
        RESTORE_BACKUP=false
    fi
    
    # If backup restoration failed or wasn't requested, do manual cleanup
    if [ "$RESTORE_BACKUP" = false ]; then
        echo -e "${BLUE}🔧 Removing review system scripts and autoloading...${NC}"
        
        php <<PHP
<?php
\$composerPath = '$COMPOSER_FILE';
\$composerJson = json_decode(file_get_contents(\$composerPath), true);

if (\$composerJson === null) {
    echo "❌ Error: Invalid JSON in composer.json\n";
    exit(1);
}

// Remove review system scripts
\$scriptsToRemove = [
    'review:quick', 'review:full', 'review', 'review:install', 'review:validate',
    'phpstan', 'phpcs', 'phpcbf', 'fix-style', 'validate', 'review:complete'
];

if (isset(\$composerJson['scripts'])) {
    foreach (\$scriptsToRemove as \$script) {
        if (isset(\$composerJson['scripts'][\$script])) {
            unset(\$composerJson['scripts'][\$script]);
        }
    }
}

// Remove ReviewSystem autoloading
if (isset(\$composerJson['autoload-dev']['psr-4'])) {
    if (isset(\$composerJson['autoload-dev']['psr-4']['ReviewSystem\\\\Engine\\\\'])) {
        unset(\$composerJson['autoload-dev']['psr-4']['ReviewSystem\\\\Engine\\\\']);
    }
    if (isset(\$composerJson['autoload-dev']['psr-4']['ReviewSystem\\\\Rules\\\\'])) {
        unset(\$composerJson['autoload-dev']['psr-4']['ReviewSystem\\\\Rules\\\\']);
    }
}

// Remove classmap entries for review-system
if (isset(\$composerJson['autoload-dev']['classmap'])) {
    \$composerJson['autoload-dev']['classmap'] = array_filter(
        \$composerJson['autoload-dev']['classmap'],
        function(\$path) {
            return strpos(\$path, 'review-system/') === false;
        }
    );
}

// Clean up empty autoload-dev sections
if (isset(\$composerJson['autoload-dev']['psr-4']) && empty(\$composerJson['autoload-dev']['psr-4'])) {
    unset(\$composerJson['autoload-dev']['psr-4']);
}
if (isset(\$composerJson['autoload-dev']['classmap']) && empty(\$composerJson['autoload-dev']['classmap'])) {
    unset(\$composerJson['autoload-dev']['classmap']);
}
if (isset(\$composerJson['autoload-dev']) && empty(\$composerJson['autoload-dev'])) {
    unset(\$composerJson['autoload-dev']);
}

// Write back the cleaned composer.json
if (file_put_contents(\$composerPath, json_encode(\$composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL)) {
    echo "✅ composer.json cleaned successfully.\n";
} else {
    echo "❌ Error: Failed to write composer.json\n";
    exit(1);
}
PHP
        
        if [ $? -eq 0 ]; then
            echo -e "${GREEN}✅ Composer configuration cleaned${NC}"
        else
            echo -e "${RED}❌ Failed to clean composer configuration${NC}"
        fi
    fi
    
    # Remove backup file after successful composer configuration cleanup
    if [ "$RESTORE_BACKUP" = true ] && [ -f "$COMPOSER_FILE.bak.review-system" ]; then
        rm "$COMPOSER_FILE.bak.review-system"
        echo -e "${GREEN}✅ Removed composer.json backup${NC}"
    fi
else
    echo -e "${YELLOW}⏭️  Skipping composer configuration removal${NC}"
fi

# 4. Remove configuration files
if [ "$REMOVE_CONFIG" = true ]; then
    echo -e "${BLUE}🗂️  Removing configuration files...${NC}"
    
    # Function to detect Laravel environment
    detect_laravel_environment() {
        if [ -f "$PROJECT_ROOT/artisan" ] && [ -d "$PROJECT_ROOT/app" ] && [ -d "$PROJECT_ROOT/config" ]; then
            return 0  # True - Laravel detected
        else
            return 1  # False - Not Laravel
        fi
    }
    
    # Remove standalone config
    if [ -f "$PROJECT_ROOT/codementor-ai/config.php" ]; then
        rm "$PROJECT_ROOT/codementor-ai/config.php"
        echo -e "${GREEN}✅ Removed standalone config.php${NC}"
    fi
    
    # Remove Laravel config if in Laravel environment
    if detect_laravel_environment; then
        if [ -f "$PROJECT_ROOT/config/codementor-ai.php" ]; then
            rm "$PROJECT_ROOT/config/codementor-ai.php"
            echo -e "${GREEN}✅ Removed Laravel config/codementor-ai.php${NC}"
        fi
    fi
    
    if [ -f "$MARKER_FILE" ]; then
        rm "$MARKER_FILE"
        echo -e "${GREEN}✅ Removed install-marker.json${NC}"
    fi
else
    echo -e "${YELLOW}⏭️  Skipping configuration file removal${NC}"
fi

# 5. Regenerate autoloader
if [ "$REMOVE_CONFIG" = true ]; then
    echo -e "${BLUE}🔄 Regenerating autoloader...${NC}"
    composer dump-autoload --working-dir "$PROJECT_ROOT" --quiet
    echo -e "${GREEN}✅ Autoloader regenerated${NC}"
fi

# 6. Final summary
echo ""
echo -e "${GREEN}🎉 Uninstallation complete!${NC}"
echo "=================================="

# Detailed summary
if [ "$REMOVE_HOOKS" = true ]; then
    echo -e "${GREEN}✅ Git hooks removed${NC}"
fi

if [ "$REMOVE_PACKAGES" = true ]; then
    echo -e "${GREEN}✅ External packages removed${NC}"
    
    # Show detailed package removal results
    if [ ${#REMOVAL_SUCCESS[@]} -gt 0 ]; then
        echo -e "${CYAN}   Successfully removed:${NC}"
        for package in "${REMOVAL_SUCCESS[@]}"; do
            echo -e "${GREEN}     • $package${NC}"
        done
    fi
    
    if [ ${#REMOVAL_FAILED[@]} -gt 0 ]; then
        echo -e "${YELLOW}   Failed to remove:${NC}"
        for package in "${REMOVAL_FAILED[@]}"; do
            echo -e "${RED}     • $package${NC}"
        done
    fi
fi

if [ "$REMOVE_CONFIG" = true ]; then
    echo -e "${GREEN}✅ Configuration removed${NC}"
fi

if [ "$RESTORE_BACKUP" = true ]; then
    echo -e "${GREEN}✅ Original composer.json restored${NC}"
fi

# Final verification
echo ""
echo -e "${BLUE}🔍 Final verification...${NC}"
if [ "$REMOVE_PACKAGES" = true ]; then
    REMAINING_PACKAGES=()
    for package in "${PACKAGES_TO_REMOVE[@]}"; do
        if composer show "$package" --working-dir "$PROJECT_ROOT" > /dev/null 2>&1; then
            REMAINING_PACKAGES+=("$package")
        fi
    done
    
    if [ ${#REMAINING_PACKAGES[@]} -gt 0 ]; then
        echo -e "${YELLOW}⚠️  Some packages are still installed:${NC}"
        for package in "${REMAINING_PACKAGES[@]}"; do
            echo -e "${YELLOW}   • $package${NC}"
        done
        echo -e "${YELLOW}💡 These may be required by other packages in your project${NC}"
    else
        echo -e "${GREEN}✅ All target packages successfully removed${NC}"
    fi
fi

echo ""
echo -e "${YELLOW}📋 What was removed:${NC}"
if [ "$REMOVE_HOOKS" = true ]; then
    echo "  • Pre-commit hook"
    echo "  • Pre-push hook"
fi

if [ "$REMOVE_PACKAGES" = true ]; then
    echo "  • PHPStan (static analysis)"
    echo "  • PHP_CodeSniffer (code style)"
    echo "  • Larastan (Laravel analysis)"
fi

if [ "$REMOVE_CONFIG" = true ]; then
    echo "  • Composer scripts (review:quick, review:full, etc.)"
    echo "  • Autoloading configuration"
    echo "  • Configuration files"
fi

echo ""
echo -e "${CYAN}💡 To reinstall: ./codementor-ai/install.sh --full${NC}" 