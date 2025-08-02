#!/bin/bash

# 🚀 Comprehensive Code Review System Installer
# Purpose: Install and configure the complete review system with Git hooks

set -e

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

# Parse command line arguments
INSTALL_PACKAGES=true
INSTALL_HOOKS=true
INSTALL_CONFIG=true
QUICK_MODE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --full)
            INSTALL_PACKAGES=true
            INSTALL_HOOKS=true
            INSTALL_CONFIG=true
            shift
            ;;
        --hooks)
            INSTALL_PACKAGES=false
            INSTALL_HOOKS=true
            INSTALL_CONFIG=true
            shift
            ;;
        --quick)
            INSTALL_PACKAGES=false
            INSTALL_HOOKS=true
            INSTALL_CONFIG=true
            QUICK_MODE=true
            shift
            ;;
        --packages-only)
            INSTALL_PACKAGES=true
            INSTALL_HOOKS=false
            INSTALL_CONFIG=false
            shift
            ;;
        --help)
            echo "🚀 Code Review System Installer"
            echo ""
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --full         Complete installation (packages + hooks + config)"
            echo "  --hooks        Install only Git hooks and configuration"
            echo "  --quick        Quick setup (hooks + config, no packages)"
            echo "  --packages-only Install only external packages"
            echo "  --help         Show this help message"
            echo ""
            echo "Examples:"
            echo "  $0 --full      # Complete setup (recommended)"
            echo "  $0 --hooks     # Only Git integration"
            echo "  $0 --quick     # Minimal setup"
            exit 0
            ;;
        *)
            echo -e "${RED}❌ Unknown option: $1${NC}"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

echo -e "${PURPLE}🚀 Code Review System Installer${NC}"
echo "=================================="

# Display installation mode
if [ "$QUICK_MODE" = true ]; then
    echo -e "${YELLOW}📋 Mode: Quick Setup${NC}"
elif [ "$INSTALL_PACKAGES" = true ] && [ "$INSTALL_HOOKS" = true ]; then
    echo -e "${GREEN}📋 Mode: Full Installation${NC}"
elif [ "$INSTALL_HOOKS" = true ]; then
    echo -e "${BLUE}📋 Mode: Git Hooks Only${NC}"
else
    echo -e "${CYAN}📋 Mode: Packages Only${NC}"
fi
echo ""

# 1. Validate composer.json before modification
echo -e "${BLUE}🔍 Validating composer.json...${NC}"
if [ -f "$COMPOSER_FILE" ]; then
    composer validate --no-check-lock --working-dir "$PROJECT_ROOT" > /dev/null || {
        echo -e "${RED}❌ composer.json is invalid. Aborting.${NC}"
        exit 1
    }
    echo -e "${GREEN}✅ composer.json is valid${NC}"
else
    echo -e "${RED}❌ composer.json not found. Aborting.${NC}"
    exit 1
fi

# 2. Create a backup of current state
echo -e "${BLUE}💾 Creating backup...${NC}"
cp "$COMPOSER_FILE" "$COMPOSER_FILE.bak.review-system"
echo -e "${GREEN}✅ Backup created: composer.json.bak.review-system${NC}"

# 3. Install required packages (if requested)
if [ "$INSTALL_PACKAGES" = true ]; then
    echo -e "${BLUE}📦 Checking and installing required packages...${NC}"
    REQUIRED_PACKAGES=("phpstan/phpstan" "squizlabs/php_codesniffer" "nunomaduro/larastan")

    # Check which packages are missing
    MISSING_PACKAGES=()
    for package in "${REQUIRED_PACKAGES[@]}"; do
        if ! composer show "$package" --working-dir "$PROJECT_ROOT" > /dev/null 2>&1; then
            MISSING_PACKAGES+=("$package")
        else
            echo -e "${GREEN}✅ $package is already installed${NC}"
        fi
    done

    # Install all missing packages in one command if any are missing
    if [ ${#MISSING_PACKAGES[@]} -gt 0 ]; then
        echo -e "${YELLOW}📥 Installing missing packages: ${MISSING_PACKAGES[*]}${NC}"
        # Try normal installation first
        if ! composer require --dev "${MISSING_PACKAGES[@]}" --working-dir "$PROJECT_ROOT" --no-interaction; then
            echo -e "${YELLOW}⚠️  Installation failed, trying with --ignore-platform-reqs...${NC}"
            composer require --dev "${MISSING_PACKAGES[@]}" --working-dir "$PROJECT_ROOT" --ignore-platform-reqs --no-interaction
        fi
        echo -e "${GREEN}✅ All packages installed successfully${NC}"
    else
        echo -e "${GREEN}✅ All required packages are already installed${NC}"
    fi
else
    echo -e "${YELLOW}⏭️  Skipping package installation${NC}"
fi

# 4. Create Git hooks (if requested)
if [ "$INSTALL_HOOKS" = true ]; then
    echo -e "${BLUE}🔗 Setting up Git hooks...${NC}"
    
    # Check if we're in a Git repository
    if [ ! -d "$PROJECT_ROOT/.git" ]; then
        echo -e "${RED}❌ Not in a Git repository. Git hooks cannot be installed.${NC}"
        echo -e "${YELLOW}💡 Initialize Git repository first: git init${NC}"
        exit 1
    fi

    # Create hooks directory if it doesn't exist
    mkdir -p "$PROJECT_ROOT/.git/hooks"

    # Function to check if a hook contains our review system code
    is_review_system_hook() {
        local hook_file="$1"
        if [ -f "$hook_file" ]; then
            # Check for our specific markers in the hook
            if grep -q "Running pre-commit code review\|Running pre-push code review\|codementor-ai/cli.php\|composer run review" "$hook_file" 2>/dev/null; then
                return 0  # True - it's our hook
            else
                return 1  # False - it's not our hook
            fi
        else
            return 1  # False - file doesn't exist
        fi
    }

    # Function to safely create a hook
    safe_create_hook() {
        local hook_file="$1"
        local hook_name="$2"
        local hook_content="$3"
        
        if [ -f "$hook_file" ]; then
            if is_review_system_hook "$hook_file"; then
                echo -e "${YELLOW}🔄 Updating existing review system $hook_name hook...${NC}"
            else
                echo -e "${YELLOW}⚠️  Found existing custom $hook_name hook${NC}"
                echo -e "${CYAN}   File: $hook_file${NC}"
                echo -e "${YELLOW}   💡 Backing up existing hook to ${hook_file}.backup${NC}"
                cp "$hook_file" "${hook_file}.backup"
                echo -e "${YELLOW}   💡 Replacing with review system hook${NC}"
            fi
        else
            echo -e "${BLUE}📝 Creating new $hook_name hook...${NC}"
        fi
        
        # Create the hook
        cat > "$hook_file" << EOF
$hook_content
EOF
        chmod +x "$hook_file"
        echo -e "${GREEN}✅ $hook_name hook ready${NC}"
    }

    # Create pre-commit hook
    safe_create_hook "$PROJECT_ROOT/.git/hooks/pre-commit" "pre-commit" '#!/bin/bash

# 🚀 Pre-Commit Hook - Quick Code Review
# This runs before each commit to ensure code quality

echo "🔍 Running pre-commit code review..."

# Check if composer is available
if command -v composer &> /dev/null; then
    # Run quick review using composer script
    composer run review:quick 2>/dev/null || {
        echo "❌ Quick code review failed. Please fix issues before committing."
        echo "💡 Run '\''composer run review:quick'\'' to see details."
        exit 1
    }
else
    # Fallback to direct PHP execution
    php codementor-ai/cli.php --quick 2>/dev/null || {
        echo "❌ Quick code review failed. Please fix issues before committing."
        echo "💡 Run '\''php codementor-ai/cli.php --quick'\'' to see details."
        exit 1
    }
fi

echo "✅ Pre-commit review passed!"
exit 0'

    echo -e "${GREEN}✅ Git hooks setup complete${NC}"
    
    # Note: Pre-commit hook will run during installation, which is expected
    # The hook will fail if there are violations, but installation continues

    # Create pre-push hook
    safe_create_hook "$PROJECT_ROOT/.git/hooks/pre-push" "pre-push" '#!/bin/bash

# 🚀 Pre-Push Hook - Full Code Review
# This runs before pushing to ensure comprehensive code quality

echo "🔍 Running pre-push code review..."

# Check if composer is available
if command -v composer &> /dev/null; then
    # Run full review using composer script
    composer run review:full 2>/dev/null || {
        echo "❌ Full code review failed. Please fix issues before pushing."
        echo "💡 Run '\''composer run review:full'\'' to see details."
        exit 1
    }
else
    # Fallback to direct PHP execution
    php codementor-ai/cli.php --full 2>/dev/null || {
        echo "❌ Full code review failed. Please fix issues before pushing."
        echo "💡 Run '\''php codementor-ai/cli.php --full'\'' to see details."
        exit 1
    }
fi

echo "✅ Pre-push review passed!"
exit 0'

    echo -e "${GREEN}✅ Git hooks setup complete${NC}"
else
    echo -e "${YELLOW}⏭️  Skipping Git hooks installation${NC}"
fi

# 5. Update composer.json with scripts and autoload-dev
if [ "$INSTALL_CONFIG" = true ]; then
    echo -e "${BLUE}📝 Updating composer.json...${NC}"
    
    php <<PHP
<?php
\$composerPath = '$COMPOSER_FILE';
\$composerJson = json_decode(file_get_contents(\$composerPath), true);

// Define all possible scripts
\$allScripts = [
    // External tool scripts
    "phpstan" => "php vendor/bin/phpstan analyse --configuration=codementor-ai/phpstan.neon",
    "phpcs" => "php vendor/bin/phpcs --standard=codementor-ai/phpcs.xml",
    "phpcbf" => "php vendor/bin/phpcbf --standard=codementor-ai/phpcs.xml",
    "fix-style" => "composer phpcbf",
    
    // Review system scripts
            "review:quick" => "php codementor-ai/cli.php --quick",
        "review:full" => "php codementor-ai/cli.php --full",
        "review" => "php codementor-ai/cli.php",
            "review:install" => "php codementor-ai/install-config.php",
        "review:validate" => "php codementor-ai/validate-config.php",
    
    // Combined scripts
            "validate" => "php codementor-ai/validate-config.php",
            "review:complete" => "composer validate && composer phpstan && composer phpcs && php codementor-ai/cli.php",
];

// Add scripts
if (!isset(\$composerJson["scripts"])) {
    \$composerJson["scripts"] = [];
}

foreach (\$allScripts as \$key => \$command) {
    if (!isset(\$composerJson["scripts"][\$key])) {
        \$composerJson["scripts"][\$key] = \$command;
    }
}

// Add autoload-dev for ReviewSystem namespaces
if (!isset(\$composerJson["autoload-dev"])) {
    \$composerJson["autoload-dev"] = [];
}
if (!isset(\$composerJson["autoload-dev"]["psr-4"])) {
    \$composerJson["autoload-dev"]["psr-4"] = [];
}

// Add specific namespace mappings for ReviewSystem
if (!isset(\$composerJson["autoload-dev"]["psr-4"]["ReviewSystem\\\\Engine\\\\"])) {
    \$composerJson["autoload-dev"]["psr-4"]["ReviewSystem\\\\Engine\\\\"] = "codementor-ai/engine/";
}
if (!isset(\$composerJson["autoload-dev"]["psr-4"]["ReviewSystem\\\\Rules\\\\"])) {
    \$composerJson["autoload-dev"]["psr-4"]["ReviewSystem\\\\Rules\\\\"] = "codementor-ai/rules/";
}

// Create a marker file to track what we added
\$markerData = [
    'installed_at' => date('Y-m-d H:i:s'),
    'install_mode' => '$QUICK_MODE' ? 'quick' : ('$INSTALL_PACKAGES' ? 'full' : 'hooks'),
    'packages_installed' => '$INSTALL_PACKAGES',
    'hooks_installed' => '$INSTALL_HOOKS',
    'scripts_added' => array_keys(\$allScripts),
    'autoload_added' => ['ReviewSystem\\\\Engine\\\\', 'ReviewSystem\\\\Rules\\\\']
];
file_put_contents(dirname('$COMPOSER_FILE') . '/codementor-ai/install-marker.json', json_encode(\$markerData, JSON_PRETTY_PRINT));

file_put_contents(\$composerPath, json_encode(\$composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
echo "✅ composer.json updated successfully with scripts and autoload-dev.\n";
PHP

    echo -e "${GREEN}✅ Composer configuration updated${NC}"
    
    # 6. Smart configuration setup
    echo -e "${BLUE}📝 Setting up configuration files...${NC}"
    
    # Function to detect Laravel environment
    detect_laravel_environment() {
        if [ -f "$PROJECT_ROOT/artisan" ] && [ -d "$PROJECT_ROOT/app" ] && [ -d "$PROJECT_ROOT/config" ]; then
            return 0  # True - Laravel detected
        else
            return 1  # False - Not Laravel
        fi
    }
    
    # Function to create Laravel config
    create_laravel_config() {
        local laravel_config_file="$PROJECT_ROOT/config/codementor-ai.php"
        
        if [ -f "$laravel_config_file" ]; then
            echo -e "${YELLOW}⚠️  Laravel config already exists${NC}"
            echo -e "${CYAN}   File: $laravel_config_file${NC}"
            
            # Check if it's valid
            if php -l "$laravel_config_file" > /dev/null 2>&1; then
                echo -e "${GREEN}✅ Laravel config is valid${NC}"
            else
                echo -e "${YELLOW}⚠️  Laravel config is invalid, creating backup and regenerating${NC}"
                mv "$laravel_config_file" "$laravel_config_file.bak.$(date +%Y%m%d_%H%M%S)"
                create_laravel_config_content "$laravel_config_file"
            fi
        else
            echo -e "${BLUE}📝 Creating Laravel configuration...${NC}"
            create_laravel_config_content "$laravel_config_file"
        fi
    }
    
    # Function to create Laravel config content
    create_laravel_config_content() {
        local config_file="$1"
        cat > "$config_file" << 'EOF'
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Review System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all configuration options for the review system.
    | The review system performs static analysis and code quality checks
    | on your PHP/Laravel codebase.
    |
    */

    'scan_paths' => [
        app_path(),
        base_path('routes'),
    ],
    'file_scanner' => [
        'max_file_size' => env('REVIEW_MAX_FILE_SIZE', 10 * 1024 * 1024), // 10MB
        'enable_caching' => env('REVIEW_ENABLE_CACHING', true),
                        'cache_file' => storage_path('codementor-ai/cache/file_scanner_cache.json'),
        'cache_expiry_time' => env('REVIEW_CACHE_EXPIRY_TIME', 3600), // 1 hour
        'use_file_mod_time' => env('REVIEW_USE_FILE_MOD_TIME', true),
        'exclude_patterns' => [
            '/vendor/',
            '/cache/',
            '/storage/',
            '/node_modules/',
            '/.git/',
        ],
        'include_extensions' => ['php'],
        'debug' => false,
    ],
    'reporting' => [
        'output_path' => storage_path('codementor-ai/reports'),
        'filename_format' => 'report-{timestamp}.html',
        'exit_on_violation' => env('REVIEW_EXIT_ON_VIOLATION', true),
        'html' => [
            'title' => env('REVIEW_HTML_TITLE', 'Code Review Report'),
            'include_css' => env('REVIEW_HTML_INCLUDE_CSS', true),
            'css_path' => 'style.css',
            'show_timestamp' => env('REVIEW_HTML_SHOW_TIMESTAMP', true),
            'show_violation_count' => env('REVIEW_HTML_SHOW_VIOLATION_COUNT', true),
            'enable_filtering' => env('REVIEW_HTML_ENABLE_FILTERING', true),
            'filter_options' => [
                'enable_severity_filter' => env('REVIEW_HTML_ENABLE_SEVERITY_FILTER', true),
                'enable_category_filter' => env('REVIEW_HTML_ENABLE_CATEGORY_FILTER', true),
                'enable_tag_filter' => env('REVIEW_HTML_ENABLE_TAG_FILTER', true),
                'enable_file_filter' => env('REVIEW_HTML_ENABLE_FILE_FILTER', true),
                'enable_search_filter' => env('REVIEW_HTML_ENABLE_SEARCH_FILTER', true),
                'real_time_filtering' => env('REVIEW_HTML_REAL_TIME_FILTERING', true),
            ],
            'table_columns' => [
                'file_path' => 'File Path',
                'message' => 'Violation Message',
                'bad_code' => 'Bad Code Sample',
                'suggested_fix' => 'Suggested Fix',
            ],
        ],
    ],
    'rules' => [
        'ReviewSystem\Rules\CodeStyleRule',
        'ReviewSystem\Rules\LaravelBestPracticesRule',
        'ReviewSystem\Rules\NoMongoInControllerRule',
    ],
    'validation' => [
        'enable_config_validation' => true,
        'enable_rule_validation' => true,
        'strict_mode' => false,
        'error_handling' => 'log',
    ],
    'performance' => [
        'enable_monitoring' => true,
        'memory_limit' => '256M',
        'time_limit' => 300,
        'parallel_processing' => false,
        'max_workers' => 4,
    ],
    'logging' => [
        'enabled' => true,
        'level' => env('REVIEW_LOG_LEVEL', 'info'),
        'file' => storage_path('logs/codementor-ai.log'),
        'format' => '[ReviewSystem] {datetime} - {level}: {message}',
        'console_output' => true,
    ],
    'quick_mode' => [
        'enabled' => true,
        'rules' => ['ReviewSystem\Rules\CodeStyleRule'],
        'max_files' => 50,
    ],
];
EOF
        echo -e "${GREEN}✅ Laravel configuration created${NC}"
    }
    
    # Function to create standalone config
    create_standalone_config() {
        local standalone_config_file="$PROJECT_ROOT/codementor-ai/config.php"
        
        if [ -f "$standalone_config_file" ]; then
            echo -e "${YELLOW}⚠️  Standalone config already exists${NC}"
            echo -e "${CYAN}   File: $standalone_config_file${NC}"
            
            # Check if it's valid
            if php -l "$standalone_config_file" > /dev/null 2>&1; then
                echo -e "${GREEN}✅ Standalone config is valid${NC}"
            else
                echo -e "${YELLOW}⚠️  Standalone config is invalid, creating backup and regenerating${NC}"
                mv "$standalone_config_file" "$standalone_config_file.bak.$(date +%Y%m%d_%H%M%S)"
                create_standalone_config_content "$standalone_config_file"
            fi
        else
            echo -e "${BLUE}📝 Creating standalone configuration...${NC}"
            create_standalone_config_content "$standalone_config_file"
        fi
    }
    
    # Function to create standalone config content
    create_standalone_config_content() {
        local config_file="$1"
        cat > "$config_file" << 'EOF'
<?php

return [
    'rules' => [
        'ReviewSystem\Rules\CodeStyleRule',
        'ReviewSystem\Rules\LaravelBestPracticesRule',
        'ReviewSystem\Rules\NoMongoInControllerRule',
    ],
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
    ],
    'reporting' => [
        'format' => 'html',
        'output' => 'reports/',
        'filters' => ['critical', 'warning'],
    ],
    'quick_mode' => [
        'enabled' => true,
        'rules' => ['ReviewSystem\Rules\CodeStyleRule'],
        'max_files' => 50,
    ],
];
EOF
        echo -e "${GREEN}✅ Standalone configuration created${NC}"
    }
    
    # Smart configuration detection and creation
    if detect_laravel_environment; then
        echo -e "${GREEN}🎯 Laravel project detected!${NC}"
        echo -e "${CYAN}   Creating Laravel-style configuration...${NC}"
        create_laravel_config
        echo -e "${GREEN}✅ Laravel integration ready${NC}"
        echo -e "${YELLOW}💡 You can customize settings via .env file${NC}"
    else
        echo -e "${BLUE}📁 Standalone project detected${NC}"
        echo -e "${CYAN}   Creating standalone configuration...${NC}"
        create_standalone_config
        echo -e "${GREEN}✅ Standalone configuration ready${NC}"
    fi
else
    echo -e "${YELLOW}⏭️  Skipping configuration installation${NC}"
fi

# 7. Run composer dump-autoload to register the new autoload-dev
echo -e "${BLUE}🔄 Regenerating autoloader...${NC}"
composer dump-autoload --working-dir "$PROJECT_ROOT" --quiet
echo -e "${GREEN}✅ Autoloader regenerated${NC}"

# 8. Final summary
echo ""
echo -e "${GREEN}🎉 Installation complete!${NC}"
echo "=================================="

if [ "$INSTALL_PACKAGES" = true ]; then
    echo -e "${GREEN}✅ External packages installed${NC}"
fi

if [ "$INSTALL_HOOKS" = true ]; then
    echo -e "${GREEN}✅ Git hooks configured${NC}"
    echo "  • Pre-commit: Quick review before commits"
    echo "  • Pre-push: Full review before pushing"
fi

if [ "$INSTALL_CONFIG" = true ]; then
    echo -e "${GREEN}✅ Composer scripts added${NC}"
    echo "  • composer run review:quick   # Quick review"
    echo "  • composer run review:full    # Full review"
    echo "  • composer run review         # Standard review"
    if [ "$INSTALL_PACKAGES" = true ]; then
        echo "  • composer run phpstan        # Static analysis"
        echo "  • composer run phpcs          # Code style check"
        echo "  • composer run fix-style      # Auto-fix style"
    fi
fi

echo ""
echo -e "${YELLOW}🚀 Next steps:${NC}"
if [ "$INSTALL_HOOKS" = true ]; then
    echo "  • git commit -m 'message'     # Test pre-commit hook"
    echo "  • git push origin branch      # Test pre-push hook"
fi
echo "  • composer run review:quick   # Manual quick review"
echo "  • composer run review:full    # Manual full review"
echo ""
echo -e "${CYAN}📖 Documentation: codementor-ai/docs/${NC}"
echo -e "${CYAN}🔧 Uninstall: ./codementor-ai/uninstall.sh${NC}"
