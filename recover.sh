#!/bin/bash

# 🚑 Review System Recovery Script
# Purpose: Fix common issues with review system installation

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

echo -e "${PURPLE}🚑 Review System Recovery Script${NC}"
echo "====================================="

# Parse command line arguments
RECOVER_CONFIG=true
RECOVER_AUTOLOAD=true
RECOVER_HOOKS=false
RECOVER_PACKAGES=false
FORCE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --config)
            RECOVER_CONFIG=true
            RECOVER_AUTOLOAD=false
            RECOVER_HOOKS=false
            RECOVER_PACKAGES=false
            shift
            ;;
        --autoload)
            RECOVER_CONFIG=false
            RECOVER_AUTOLOAD=true
            RECOVER_HOOKS=false
            RECOVER_PACKAGES=false
            shift
            ;;
        --hooks)
            RECOVER_CONFIG=false
            RECOVER_AUTOLOAD=false
            RECOVER_HOOKS=true
            RECOVER_PACKAGES=false
            shift
            ;;
        --packages)
            RECOVER_CONFIG=false
            RECOVER_AUTOLOAD=false
            RECOVER_HOOKS=false
            RECOVER_PACKAGES=true
            shift
            ;;
        --all)
            RECOVER_CONFIG=true
            RECOVER_AUTOLOAD=true
            RECOVER_HOOKS=true
            RECOVER_PACKAGES=true
            shift
            ;;
        --force)
            FORCE=true
            shift
            ;;
        --help)
            echo "🚑 Review System Recovery Script"
            echo ""
            echo "Usage: $0 [OPTIONS]"
            echo ""
            echo "Options:"
            echo "  --config     Recover only configuration files"
            echo "  --autoload   Recover only autoloading configuration"
            echo "  --hooks      Recover only Git hooks"
            echo "  --packages   Recover only external packages"
            echo "  --all        Recover everything (recommended)"
            echo "  --force      Force recovery without confirmation"
            echo "  --help       Show this help message"
            echo ""
            echo "Examples:"
            echo "  $0 --all      # Recover everything"
            echo "  $0 --config   # Fix missing config.php"
            echo "  $0 --autoload # Fix autoloading issues"
            exit 0
            ;;
        *)
            echo -e "${RED}❌ Unknown option: $1${NC}"
            echo "Use --help for usage information"
            exit 1
            ;;
    esac
done

# Display recovery mode
if [ "$RECOVER_CONFIG" = true ] && [ "$RECOVER_AUTOLOAD" = true ] && [ "$RECOVER_HOOKS" = true ] && [ "$RECOVER_PACKAGES" = true ]; then
    echo -e "${RED}📋 Mode: Full Recovery${NC}"
elif [ "$RECOVER_CONFIG" = true ]; then
    echo -e "${YELLOW}📋 Mode: Configuration Recovery${NC}"
elif [ "$RECOVER_AUTOLOAD" = true ]; then
    echo -e "${BLUE}📋 Mode: Autoload Recovery${NC}"
elif [ "$RECOVER_HOOKS" = true ]; then
    echo -e "${CYAN}📋 Mode: Hooks Recovery${NC}"
elif [ "$RECOVER_PACKAGES" = true ]; then
    echo -e "${PURPLE}📋 Mode: Packages Recovery${NC}"
fi
echo ""

# 1. Recover configuration files
if [ "$RECOVER_CONFIG" = true ]; then
    echo -e "${BLUE}📝 Recovering configuration files...${NC}"
    
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
            if php -l "$laravel_config_file" > /dev/null 2>&1; then
                echo -e "${GREEN}✅ Laravel config is valid${NC}"
            else
                echo -e "${YELLOW}⚠️  Laravel config is invalid, regenerating...${NC}"
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
            if php -l "$standalone_config_file" > /dev/null 2>&1; then
                echo -e "${GREEN}✅ Standalone config is valid${NC}"
            else
                echo -e "${YELLOW}⚠️  Standalone config is invalid, regenerating...${NC}"
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
    
    # Smart configuration recovery
    if detect_laravel_environment; then
        echo -e "${GREEN}🎯 Laravel project detected!${NC}"
        create_laravel_config
    else
        echo -e "${BLUE}📁 Standalone project detected${NC}"
        create_standalone_config
    fi
fi

# 2. Recover autoloading configuration
if [ "$RECOVER_AUTOLOAD" = true ]; then
    echo -e "${BLUE}🔄 Recovering autoloading configuration...${NC}"
    
    # Check if autoloading is properly configured
    if ! grep -q "ReviewSystem" "$COMPOSER_FILE" 2>/dev/null; then
        echo -e "${YELLOW}⚠️  ReviewSystem autoloading missing, adding...${NC}"
        php review-system/update-composer.php
        echo -e "${GREEN}✅ Autoloading configuration restored${NC}"
    else
        echo -e "${GREEN}✅ Autoloading configuration exists${NC}"
    fi
    
    # Regenerate autoloader
    echo -e "${BLUE}🔄 Regenerating autoloader...${NC}"
    composer dump-autoload --working-dir "$PROJECT_ROOT" --quiet
    echo -e "${GREEN}✅ Autoloader regenerated${NC}"
fi

# 3. Recover Git hooks
if [ "$RECOVER_HOOKS" = true ]; then
    echo -e "${BLUE}🔗 Recovering Git hooks...${NC}"
    
    if [ ! -d "$PROJECT_ROOT/.git" ]; then
        echo -e "${RED}❌ Not in a Git repository. Cannot recover hooks.${NC}"
    else
        # Check if hooks exist and are valid
        if [ ! -f "$PROJECT_ROOT/.git/hooks/pre-commit" ] || [ ! -f "$PROJECT_ROOT/.git/hooks/pre-push" ]; then
            echo -e "${YELLOW}⚠️  Git hooks missing, recreating...${NC}"
            
            # Create pre-commit hook
            cat > "$PROJECT_ROOT/.git/hooks/pre-commit" << 'EOF'
#!/bin/bash

# 🚀 Pre-Commit Hook - Quick Code Review
# This runs before each commit to ensure code quality

echo "🔍 Running pre-commit code review..."

# Check if composer is available
if command -v composer &> /dev/null; then
    # Run quick review using composer script
    composer run review:quick 2>/dev/null || {
        echo "❌ Quick code review failed. Please fix issues before committing."
        echo "💡 Run 'composer run review:quick' to see details."
        exit 1
    }
else
    # Fallback to direct PHP execution
    php review-system/cli.php --quick 2>/dev/null || {
        echo "❌ Quick code review failed. Please fix issues before committing."
        echo "💡 Run 'php review-system/cli.php --quick' to see details."
        exit 1
    }
fi

echo "✅ Pre-commit review passed!"
exit 0
EOF

            # Create pre-push hook
            cat > "$PROJECT_ROOT/.git/hooks/pre-push" << 'EOF'
#!/bin/bash

# 🚀 Pre-Push Hook - Full Code Review
# This runs before pushing to ensure comprehensive code quality

echo "🔍 Running pre-push code review..."

# Check if composer is available
if command -v composer &> /dev/null; then
    # Run full review using composer script
    composer run review:full 2>/dev/null || {
        echo "❌ Full code review failed. Please fix issues before pushing."
        echo "💡 Run 'composer run review:full' to see details."
        exit 1
    }
else
    # Fallback to direct PHP execution
    php review-system/cli.php --full 2>/dev/null || {
        echo "❌ Full code review failed. Please fix issues before pushing."
        echo "💡 Run 'php review-system/cli.php --full' to see details."
        exit 1
    }
fi

echo "✅ Pre-push review passed!"
exit 0
EOF

            chmod +x "$PROJECT_ROOT/.git/hooks/pre-commit"
            chmod +x "$PROJECT_ROOT/.git/hooks/pre-push"
            echo -e "${GREEN}✅ Git hooks recreated${NC}"
        else
            echo -e "${GREEN}✅ Git hooks exist${NC}"
        fi
    fi
fi

# 4. Recover external packages
if [ "$RECOVER_PACKAGES" = true ]; then
    echo -e "${BLUE}📦 Recovering external packages...${NC}"
    
    REQUIRED_PACKAGES=("phpstan/phpstan" "squizlabs/php_codesniffer" "nunomaduro/larastan")
    MISSING_PACKAGES=()
    
    for package in "${REQUIRED_PACKAGES[@]}"; do
        if ! composer show "$package" --working-dir "$PROJECT_ROOT" > /dev/null 2>&1; then
            MISSING_PACKAGES+=("$package")
        fi
    done
    
    if [ ${#MISSING_PACKAGES[@]} -gt 0 ]; then
        echo -e "${YELLOW}📥 Installing missing packages: ${MISSING_PACKAGES[*]}${NC}"
        composer require --dev "${MISSING_PACKAGES[@]}" --working-dir "$PROJECT_ROOT" --no-interaction || {
            echo -e "${YELLOW}⚠️  Installation failed, trying with --ignore-platform-reqs...${NC}"
            composer require --dev "${MISSING_PACKAGES[@]}" --working-dir "$PROJECT_ROOT" --ignore-platform-reqs --no-interaction
        }
        echo -e "${GREEN}✅ Packages installed${NC}"
    else
        echo -e "${GREEN}✅ All required packages are installed${NC}"
    fi
fi

# 5. Test the system
echo -e "${BLUE}🧪 Testing review system...${NC}"
if php review-system/cli.php --quick > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Review system is working correctly${NC}"
else
    echo -e "${YELLOW}⚠️  Review system test failed, but recovery completed${NC}"
    echo -e "${CYAN}   Run 'php review-system/cli.php --quick' to see details${NC}"
fi

# 6. Final summary
echo ""
echo -e "${GREEN}🎉 Recovery complete!${NC}"
echo "=================================="

if [ "$RECOVER_CONFIG" = true ]; then
    echo -e "${GREEN}✅ Configuration files recovered${NC}"
fi

if [ "$RECOVER_AUTOLOAD" = true ]; then
    echo -e "${GREEN}✅ Autoloading configuration recovered${NC}"
fi

if [ "$RECOVER_HOOKS" = true ]; then
    echo -e "${GREEN}✅ Git hooks recovered${NC}"
fi

if [ "$RECOVER_PACKAGES" = true ]; then
    echo -e "${GREEN}✅ External packages recovered${NC}"
fi

echo ""
echo -e "${CYAN}💡 Next steps:${NC}"
echo "  • Test: php review-system/cli.php --quick"
echo "  • Test: composer run review:quick"
echo "  • Test Git hooks: git commit -m 'test'"
echo ""
echo -e "${CYAN}📖 Documentation: review-system/docs/${NC}" 