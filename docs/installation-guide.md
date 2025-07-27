# Review System Installation Guide

## Quick Installation

The review system provides an **automatic configuration installer** that makes setup incredibly easy for developers.

### 🚀 One-Command Installation

```bash
# Install configuration to Laravel config directory
composer review:install-config
```

That's it! The installer will:
- ✅ Detect your Laravel application
- ✅ Create `config/review-system.php` with Laravel-style configuration
- ✅ Convert paths to use Laravel helpers (`app_path()`, `storage_path()`, etc.)
- ✅ Add environment variable support
- ✅ Backup existing configuration if needed
- ✅ Test the configuration automatically

### 🔧 Manual Installation (Alternative)

If you prefer manual installation:

```bash
# Direct command
php review-system/install-config.php
```

## What Gets Installed

### Configuration File
The installer creates `config/review-system.php` with:

```php
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
        'max_file_size' => env('REVIEW_MAX_FILE_SIZE', 10 * 1024 * 1024),
        'enable_caching' => env('REVIEW_ENABLE_CACHING', true),
        'cache_file' => storage_path('review-system/cache/file_scanner_cache.json'),
        'cache_expiry_time' => env('REVIEW_CACHE_EXPIRY_TIME', 3600),
        'use_file_mod_time' => env('REVIEW_USE_FILE_MOD_TIME', true),
        // ... comprehensive configuration
    ],

    'reporting' => [
        'output_path' => storage_path('review-system/reports'),
        'filename_format' => env('REVIEW_FILENAME_FORMAT', 'report-{timestamp}.html'),
        'exit_on_violation' => env('REVIEW_EXIT_ON_VIOLATION', true),
        // ... HTML and JSON reporting options
    ],

    'rules' => [
        ReviewSystem\Rules\NoMongoInControllerRule::class,
    ],

    'validation' => [
        'enable_config_validation' => env('REVIEW_VALIDATE_CONFIG', true),
        'enable_rule_validation' => env('REVIEW_VALIDATE_RULES', true),
        'strict_mode' => env('REVIEW_STRICT_VALIDATION', false),
        'error_handling' => env('REVIEW_VALIDATION_ERROR_HANDLING', 'log'),
    ],

    'performance' => [
        'enable_monitoring' => env('REVIEW_PERFORMANCE_MONITORING', true),
        'memory_limit' => env('REVIEW_MEMORY_LIMIT', '256M'),
        'time_limit' => env('REVIEW_TIME_LIMIT', 300),
        'parallel_processing' => env('REVIEW_PARALLEL_PROCESSING', false),
        'max_workers' => env('REVIEW_MAX_WORKERS', 4),
    ],

    'logging' => [
        'enabled' => env('REVIEW_LOGGING_ENABLED', true),
        'level' => env('REVIEW_LOG_LEVEL', 'info'),
        'file' => storage_path('logs/review-system.log'),
        'format' => env('REVIEW_LOG_FORMAT', '[ReviewSystem] {datetime} - {level}: {message}'),
        'console_output' => env('REVIEW_LOG_CONSOLE_OUTPUT', true),
    ],

    'security' => [
        'allowed_paths' => [
            app_path(),
            base_path('routes'),
        ],
        'forbidden_patterns' => [
            '/\.env$/',
            '/config\/.*\.key$/',
            '/storage\/.*\.key$/',
            '/\.git/',
        ],
        'validate_paths' => env('REVIEW_VALIDATE_PATHS', true),
        'max_file_count' => env('REVIEW_MAX_FILE_COUNT', 10000),
    ],

    'integration' => [
        'ci_cd' => [
            'auto_detect' => env('REVIEW_CI_AUTO_DETECT', true),
            'settings' => [
                'exit_on_violation' => true,
                'enable_caching' => false,
                'show_performance_stats' => false,
                'log_level' => 'warning',
            ],
        ],
        'ide' => [
            'compatible_output' => env('REVIEW_IDE_COMPATIBLE_OUTPUT', false),
            'output_format' => env('REVIEW_IDE_OUTPUT_FORMAT', 'json'),
        ],
    ],
];
```

## Environment Variables

After installation, you can customize the configuration using environment variables in your `.env` file:

### File Scanner
```bash
REVIEW_ENABLE_CACHING=true
REVIEW_MAX_FILE_SIZE=10485760
REVIEW_CACHE_EXPIRY_TIME=3600
REVIEW_USE_FILE_MOD_TIME=true
```

### Reporting
```bash
REVIEW_EXIT_ON_VIOLATION=true
REVIEW_HTML_TITLE="My Project Code Review"
REVIEW_HTML_INCLUDE_CSS=true
REVIEW_HTML_SHOW_TIMESTAMP=true
REVIEW_HTML_SHOW_VIOLATION_COUNT=true
REVIEW_HTML_SHOW_PERFORMANCE_STATS=true
```

### Validation
```bash
REVIEW_VALIDATE_CONFIG=true
REVIEW_VALIDATE_RULES=true
REVIEW_STRICT_VALIDATION=false
REVIEW_VALIDATION_ERROR_HANDLING=log
```

### Performance
```bash
REVIEW_PERFORMANCE_MONITORING=true
REVIEW_MEMORY_LIMIT=256M
REVIEW_TIME_LIMIT=300
REVIEW_PARALLEL_PROCESSING=false
REVIEW_MAX_WORKERS=4
```

### Logging
```bash
REVIEW_LOGGING_ENABLED=true
REVIEW_LOG_LEVEL=info
REVIEW_LOG_FORMAT="[ReviewSystem] {datetime} - {level}: {message}"
REVIEW_LOG_CONSOLE_OUTPUT=true
```

### Security
```bash
REVIEW_VALIDATE_PATHS=true
REVIEW_MAX_FILE_COUNT=10000
```

### Integration
```bash
REVIEW_CI_AUTO_DETECT=true
REVIEW_IDE_COMPATIBLE_OUTPUT=false
REVIEW_IDE_OUTPUT_FORMAT=json
```

## Installation Options

### Backup Existing Configuration
If a configuration file already exists, the installer offers three options:

1. **Backup existing and install new** (recommended)
   - Creates a timestamped backup
   - Installs the new configuration
   - Safest option

2. **Overwrite existing file**
   - Replaces the existing configuration
   - No backup created
   - Use with caution

3. **Skip installation**
   - Keeps existing configuration
   - No changes made

## Post-Installation Steps

### 1. Review Configuration
Check the generated configuration at `config/review-system.php` and customize as needed.

### 2. Add Environment Variables
Add relevant environment variables to your `.env` file for customization.

### 3. Test the System
```bash
# Run the review system
php review-system/cli.php

# Validate configuration
composer validate
```

### 4. Customize Rules
Add your custom rules to the configuration:

```php
'rules' => [
    ReviewSystem\Rules\NoMongoInControllerRule::class,
    // Add your custom rules here
    App\Rules\CustomSecurityRule::class,
    App\Rules\PerformanceRule::class,
],
```

## Troubleshooting

### Installation Fails
- **Error**: "This script must be run from within a Laravel application"
  - **Solution**: Run the script from your Laravel project root directory (where `artisan` is located)

- **Error**: "Source configuration file not found"
  - **Solution**: Ensure the review-system is properly installed and `review-system/config.php` exists

### Configuration Issues
- **Error**: "Configuration test failed"
  - **Solution**: Check the generated configuration file for syntax errors
  - **Solution**: Verify that all required paths exist

### Permission Issues
- **Error**: "Unable to write configuration file"
  - **Solution**: Check write permissions on the `config` directory
  - **Solution**: Run with appropriate permissions

## Benefits of Automatic Installation

### 🏗️ **Laravel Integration**
- Uses Laravel's configuration system
- Integrates with Laravel's environment variables
- Uses Laravel's path helpers (`app_path()`, `storage_path()`, etc.)

### 🔧 **Easy Customization**
- Environment variable support for dynamic configuration
- Easy to customize without editing PHP files
- Environment-specific settings

### 🛡️ **Safe Installation**
- Automatic backup of existing configurations
- Validation of generated configuration
- Clear error messages and guidance

### 📁 **Proper Path Resolution**
- Converts standalone paths to Laravel paths
- Uses Laravel's storage directory for cache and reports
- Proper integration with Laravel's directory structure

## Manual Configuration (Advanced)

If you prefer to create the configuration manually:

1. Copy the configuration template from `config/review-system.php`
2. Customize the settings as needed
3. Ensure all paths are correct for your Laravel application
4. Test the configuration using `composer validate`

## Next Steps

After successful installation:

1. **Read the documentation**:
   - [Centralized Configuration](centralized-configuration.md)
   - [Enhanced Caching System](enhanced-caching-system.md)
   - [Rule Validation System](rule-validation-system.md)

2. **Customize for your project**:
   - Add custom scan paths
   - Configure exclusion patterns
   - Add custom rules
   - Adjust performance settings

3. **Integrate with your workflow**:
   - Add to CI/CD pipelines
   - Set up IDE integration
   - Configure automated testing

The review system is now ready to use! 🎉 