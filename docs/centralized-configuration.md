# Centralized Configuration System

## Overview

The Centralized Configuration System provides a unified way to manage all review system settings through a single, Laravel-style configuration file. It supports both Laravel and standalone environments, with automatic environment detection and fallback mechanisms.

## Features

### 🏗️ **Unified Configuration Management**
- Single configuration file for all settings
- Laravel-style configuration structure
- Environment variable overrides
- Automatic environment detection

### 🔄 **Multi-Environment Support**
- Laravel application integration
- Standalone mode for non-Laravel projects
- Automatic fallback mechanisms
- Environment-specific configurations

### ⚙️ **Flexible Configuration Sources**
- Laravel config system integration
- Direct file loading
- Environment variable overrides
- Default configuration fallbacks

### 🔍 **Configuration Validation**
- Automatic configuration normalization
- Path resolution and validation
- Type checking and validation
- Error handling and logging

## Configuration Structure

### Laravel Environment (`config/review-system.php`)

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
        // Add more paths as needed
    ],

    'file_scanner' => [
        'max_file_size' => env('REVIEW_MAX_FILE_SIZE', 10 * 1024 * 1024),
        'enable_caching' => env('REVIEW_ENABLE_CACHING', true),
        'cache_file' => storage_path('review-system/cache/file_scanner_cache.json'),
        'cache_expiry_time' => env('REVIEW_CACHE_EXPIRY_TIME', 3600),
        'use_file_mod_time' => env('REVIEW_USE_FILE_MOD_TIME', true),
        'exclude_patterns' => [
            '/vendor/',
            '/node_modules/',
            '/storage/',
            // ... more patterns
        ],
    ],

    'reporting' => [
        'output_path' => storage_path('review-system/reports'),
        'filename_format' => env('REVIEW_FILENAME_FORMAT', 'report-{timestamp}.html'),
        'exit_on_violation' => env('REVIEW_EXIT_ON_VIOLATION', true),
        'html' => [
            'title' => env('REVIEW_HTML_TITLE', 'Code Review Report'),
            'include_css' => env('REVIEW_HTML_INCLUDE_CSS', true),
            // ... more HTML settings
        ],
    ],

    'rules' => [
        ReviewSystem\Rules\NoMongoInControllerRule::class,
        // Add your custom rules here
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

### Standalone Environment (`review-system/config.php`)

```php
<?php

return [
    'scan_paths' => [
        realpath(__DIR__ . '/../app'),
        realpath(__DIR__ . '/../routes'),
    ],
    'file_scanner' => [
        'max_file_size' => 10 * 1024 * 1024,
        'enable_caching' => true,
        'cache_file' => __DIR__ . '/cache/file_scanner_cache.json',
        'cache_expiry_time' => 3600,
        'use_file_mod_time' => true,
        'exclude_patterns' => [
            '/vendor/',
            '/cache/',
            '/storage/',
            '/node_modules/',
            '/.git/',
        ],
    ],
    'reporting' => [
        'output_path' => __DIR__ . '/reports',
        'filename_format' => 'report-{timestamp}.html',
        'exit_on_violation' => true,
        'html' => [
            'title' => 'Code Review Report',
            'include_css' => true,
            'css_path' => 'style.css',
            'show_timestamp' => true,
            'show_violation_count' => true,
            'table_columns' => [
                'file_path' => 'File Path',
                'message' => 'Violation Message',
                'bad_code' => 'Bad Code Sample',
                'suggested_fix' => 'Suggested Fix',
            ],
        ],
    ],
    'rules' => [
        ReviewSystem\Rules\NoMongoInControllerRule::class,
    ],
];
```

## Configuration Sections

### 1. Scan Paths
```php
'scan_paths' => [
    app_path(),                    // Laravel app directory
    base_path('routes'),           // Laravel routes directory
    base_path('app/Http/Controllers'), // Custom controller directory
    base_path('app/Models'),       // Custom models directory
    base_path('app/Services'),     // Custom services directory
],
```

### 2. File Scanner
```php
'file_scanner' => [
    'max_file_size' => 10 * 1024 * 1024, // 10MB
    'enable_caching' => true,
    'cache_file' => storage_path('review-system/cache/file_scanner_cache.json'),
    'cache_expiry_time' => 3600, // 1 hour
    'use_file_mod_time' => true,
    'exclude_patterns' => [
        '/vendor/',
        '/node_modules/',
        '/storage/',
        '/bootstrap/cache/',
        '/.git/',
        '/tests/',
        '/database/migrations/',
        '/database/seeders/',
        '/database/factories/',
        '/public/',
        '/resources/views/',
        '/resources/js/',
        '/resources/css/',
    ],
    'include_extensions' => ['php'],
    'debug' => false,
],
```

### 3. Reporting
```php
'reporting' => [
    'output_path' => storage_path('review-system/reports'),
    'filename_format' => 'report-{timestamp}.html',
    'exit_on_violation' => true,
    'html' => [
        'title' => 'Code Review Report',
        'include_css' => true,
        'css_path' => 'reports/style.css',
        'show_timestamp' => true,
        'show_violation_count' => true,
        'show_performance_stats' => true,
        'table_columns' => [
            'file_path' => 'File Path',
            'line' => 'Line',
            'message' => 'Violation Message',
            'bad_code' => 'Bad Code Sample',
            'suggested_fix' => 'Suggested Fix',
            'severity' => 'Severity',
        ],
        'max_code_length' => 200,
        'syntax_highlighting' => true,
    ],
    'json' => [
        'enabled' => false,
        'filename_format' => 'report-{timestamp}.json',
        'include_performance_stats' => true,
    ],
],
```

### 4. Rules
```php
'rules' => [
    // Built-in rules
    ReviewSystem\Rules\NoMongoInControllerRule::class,
    
    // Custom rules
    App\Rules\CustomRule::class,
    App\Rules\SecurityRule::class,
    App\Rules\PerformanceRule::class,
],
```

### 5. Validation
```php
'validation' => [
    'enable_config_validation' => true,
    'enable_rule_validation' => true,
    'strict_mode' => false,
    'error_handling' => 'log', // 'log', 'throw', 'ignore'
],
```

### 6. Performance
```php
'performance' => [
    'enable_monitoring' => true,
    'memory_limit' => '256M',
    'time_limit' => 300, // 5 minutes
    'parallel_processing' => false,
    'max_workers' => 4,
],
```

### 7. Logging
```php
'logging' => [
    'enabled' => true,
    'level' => 'info', // debug, info, warning, error
    'file' => storage_path('logs/review-system.log'),
    'format' => '[ReviewSystem] {datetime} - {level}: {message}',
    'console_output' => true,
],
```

### 8. Security
```php
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
        '/\.svn/',
        '/\.hg/',
    ],
    'validate_paths' => true,
    'max_file_count' => 10000,
],
```

### 9. Integration
```php
'integration' => [
    'ci_cd' => [
        'auto_detect' => true,
        'settings' => [
            'exit_on_violation' => true,
            'enable_caching' => false,
            'show_performance_stats' => false,
            'log_level' => 'warning',
        ],
    ],
    'ide' => [
        'compatible_output' => false,
        'output_format' => 'json', // 'json', 'xml', 'text'
    ],
],
```

## Environment Variables

### File Scanner
- `REVIEW_MAX_FILE_SIZE` - Maximum file size in bytes
- `REVIEW_ENABLE_CACHING` - Enable/disable file scanning cache
- `REVIEW_CACHE_EXPIRY_TIME` - Cache expiry time in seconds
- `REVIEW_USE_FILE_MOD_TIME` - Use file modification time for cache invalidation

### Reporting
- `REVIEW_OUTPUT_PATH` - Output directory for reports
- `REVIEW_FILENAME_FORMAT` - Report filename format
- `REVIEW_EXIT_ON_VIOLATION` - Exit with error code on violations
- `REVIEW_HTML_TITLE` - HTML report title
- `REVIEW_HTML_INCLUDE_CSS` - Include CSS in HTML reports
- `REVIEW_HTML_SHOW_TIMESTAMP` - Show timestamp in reports
- `REVIEW_HTML_SHOW_VIOLATION_COUNT` - Show violation count in reports
- `REVIEW_HTML_SHOW_PERFORMANCE_STATS` - Show performance statistics
- `REVIEW_HTML_MAX_CODE_LENGTH` - Maximum code snippet length
- `REVIEW_HTML_SYNTAX_HIGHLIGHTING` - Enable syntax highlighting
- `REVIEW_JSON_ENABLED` - Enable JSON report generation
- `REVIEW_JSON_FILENAME_FORMAT` - JSON report filename format
- `REVIEW_JSON_INCLUDE_PERFORMANCE_STATS` - Include performance stats in JSON

### Validation
- `REVIEW_VALIDATE_CONFIG` - Enable configuration validation
- `REVIEW_VALIDATE_RULES` - Enable rule validation
- `REVIEW_STRICT_VALIDATION` - Enable strict validation mode
- `REVIEW_VALIDATION_ERROR_HANDLING` - Error handling method

### Performance
- `REVIEW_PERFORMANCE_MONITORING` - Enable performance monitoring
- `REVIEW_MEMORY_LIMIT` - Memory limit for processing
- `REVIEW_TIME_LIMIT` - Time limit for processing
- `REVIEW_PARALLEL_PROCESSING` - Enable parallel processing
- `REVIEW_MAX_WORKERS` - Maximum parallel workers

### Logging
- `REVIEW_LOGGING_ENABLED` - Enable logging
- `REVIEW_LOG_LEVEL` - Log level (debug, info, warning, error)
- `REVIEW_LOG_FORMAT` - Log message format
- `REVIEW_LOG_CONSOLE_OUTPUT` - Enable console output

### Security
- `REVIEW_VALIDATE_PATHS` - Enable path validation
- `REVIEW_MAX_FILE_COUNT` - Maximum number of files to scan

### Integration
- `REVIEW_CI_AUTO_DETECT` - Auto-detect CI/CD environment
- `REVIEW_IDE_COMPATIBLE_OUTPUT` - Generate IDE-compatible output
- `REVIEW_IDE_OUTPUT_FORMAT` - IDE output format

### Scan Paths
- `REVIEW_SCAN_PATHS` - Comma-separated list of scan paths

## Usage Examples

### Basic Usage
```php
use ReviewSystem\Engine\ConfigurationLoader;

// Load configuration
$configLoader = new ConfigurationLoader();
$config = $configLoader->getConfiguration();

// Access specific configuration sections
$scanPaths = $configLoader->getScanPaths();
$fileScannerConfig = $configLoader->getFileScannerConfig();
$rules = $configLoader->getRules();
```

### Environment-Specific Configuration
```php
// Development environment
putenv('REVIEW_ENABLE_CACHING=false');
putenv('REVIEW_LOG_LEVEL=debug');
putenv('REVIEW_EXIT_ON_VIOLATION=false');

// Production environment
putenv('REVIEW_ENABLE_CACHING=true');
putenv('REVIEW_LOG_LEVEL=warning');
putenv('REVIEW_EXIT_ON_VIOLATION=true');
```

### Custom Configuration Access
```php
// Access nested configuration using dot notation
$maxFileSize = $configLoader->get('file_scanner.max_file_size');
$htmlTitle = $configLoader->get('reporting.html.title');
$defaultValue = $configLoader->get('non.existent.key', 'default_value');

// Check if configuration key exists
if ($configLoader->has('file_scanner.enable_caching')) {
    $cachingEnabled = $configLoader->get('file_scanner.enable_caching');
}
```

### Configuration Information
```php
// Get configuration source information
$configInfo = $configLoader->getConfigurationInfo();

echo "Environment: {$configInfo['environment']}\n";
echo "Config File: {$configInfo['config_file']}\n";
echo "Environment Overrides: " . ($configInfo['has_env_overrides'] ? 'Yes' : 'No') . "\n";
```

## Configuration Loading Process

### 1. Environment Detection
```php
private function detectLaravelEnvironment(): bool
{
    // Check if we're in a Laravel application context
    if (!function_exists('app_path') || !function_exists('base_path') || !function_exists('storage_path')) {
        return false;
    }

    // Check if we can actually call these functions without errors
    try {
        $appPath = app_path();
        $basePath = base_path();
        $storagePath = storage_path();
        
        // Check if these paths actually exist and are Laravel-like
        return is_dir($appPath) && 
               is_dir($basePath) && 
               is_dir($storagePath) &&
               file_exists($basePath . '/artisan');
    } catch (\Throwable $e) {
        return false;
    }
}
```

### 2. Configuration Loading
```php
private function loadConfiguration(): void
{
    if ($this->isLaravelEnvironment) {
        $this->loadLaravelConfiguration();
    } else {
        $this->loadStandaloneConfiguration();
    }

    // Apply environment overrides
    $this->applyEnvironmentOverrides();
    
    // Validate and normalize configuration
    $this->normalizeConfiguration();
}
```

### 3. Environment Variable Overrides
```php
private function applyEnvironmentOverrides(): void
{
    // Override scan paths if specified
    if ($scanPaths = getenv('REVIEW_SCAN_PATHS')) {
        $this->config['scan_paths'] = array_filter(explode(',', $scanPaths));
    }

    // Override file scanner settings
    if ($maxFileSize = getenv('REVIEW_MAX_FILE_SIZE')) {
        $this->config['file_scanner']['max_file_size'] = (int) $maxFileSize;
    }

    // ... more overrides
}
```

### 4. Configuration Normalization
```php
private function normalizeConfiguration(): void
{
    $defaults = $this->getDefaultConfiguration();
    
    // Merge with defaults to ensure all keys exist
    $this->config = array_replace_recursive($defaults, $this->config);

    // Ensure proper structure
    if (!isset($this->config['file_scanner'])) {
        $this->config['file_scanner'] = $defaults['file_scanner'];
    }

    // Normalize paths to absolute paths
    $this->normalizePaths();
}
```

## Best Practices

### 1. Environment-Specific Configuration
```php
// .env file
REVIEW_ENABLE_CACHING=true
REVIEW_LOG_LEVEL=info
REVIEW_EXIT_ON_VIOLATION=true

// Development
REVIEW_ENABLE_CACHING=false
REVIEW_LOG_LEVEL=debug
REVIEW_EXIT_ON_VIOLATION=false
```

### 2. Custom Rules Configuration
```php
'rules' => [
    // Built-in rules
    ReviewSystem\Rules\NoMongoInControllerRule::class,
    
    // Custom rules for your project
    App\Rules\CustomSecurityRule::class,
    App\Rules\PerformanceRule::class,
    App\Rules\CodingStandardsRule::class,
],
```

### 3. Security Configuration
```php
'security' => [
    'allowed_paths' => [
        app_path(),
        base_path('routes'),
        // Only include paths you want to scan
    ],
    'forbidden_patterns' => [
        '/\.env$/',
        '/config\/.*\.key$/',
        '/storage\/.*\.key$/',
        '/\.git/',
        // Add patterns for sensitive files
    ],
    'validate_paths' => true,
    'max_file_count' => 10000,
],
```

### 4. Performance Optimization
```php
'performance' => [
    'enable_monitoring' => true,
    'memory_limit' => '512M', // Increase for large codebases
    'time_limit' => 600, // 10 minutes for large scans
    'parallel_processing' => false, // Enable for large codebases
    'max_workers' => 8, // Adjust based on CPU cores
],
```

### 5. CI/CD Integration
```php
'integration' => [
    'ci_cd' => [
        'auto_detect' => true,
        'settings' => [
            'exit_on_violation' => true,
            'enable_caching' => false, // Disable in CI
            'show_performance_stats' => false,
            'log_level' => 'warning',
        ],
    ],
],
```

## Troubleshooting

### Common Issues

1. **Configuration Not Loading**
   - Check if the configuration file exists
   - Verify file permissions
   - Check for syntax errors in configuration file

2. **Environment Detection Issues**
   - Ensure Laravel functions are available
   - Check if running in proper Laravel context
   - Verify Laravel installation

3. **Path Resolution Problems**
   - Check if paths exist
   - Verify absolute path resolution
   - Ensure proper file permissions

4. **Environment Variable Overrides Not Working**
   - Check environment variable names
   - Verify variable values
   - Ensure proper variable format

### Debug Mode
```php
// Enable debug mode for file scanner
'file_scanner' => [
    'debug' => true,
    // ... other settings
],

// Check configuration information
$configInfo = $configLoader->getConfigurationInfo();
print_r($configInfo);
```

## Migration from Old Configuration

### Before (Old System)
```php
// review-system/config.php
return [
    'scan_paths' => [
        realpath(__DIR__ . '/../app'),
        realpath(__DIR__ . '/../routes'),
    ],
    'file_scanner' => [
        'max_file_size' => 10 * 1024 * 1024,
        'enable_caching' => true,
        'cache_file' => __DIR__ . '/cache/file_scanner_cache.json',
        // ... other settings
    ],
    // ... other configuration
];
```

### After (New System)
```php
// config/review-system.php (Laravel)
return [
    'scan_paths' => [
        app_path(),
        base_path('routes'),
    ],
    'file_scanner' => [
        'max_file_size' => env('REVIEW_MAX_FILE_SIZE', 10 * 1024 * 1024),
        'enable_caching' => env('REVIEW_ENABLE_CACHING', true),
        'cache_file' => storage_path('review-system/cache/file_scanner_cache.json'),
        // ... other settings
    ],
    // ... other configuration
];
```

### Automatic Migration
The system automatically detects and loads the old configuration file if the new one doesn't exist, ensuring backward compatibility.

## Conclusion

The Centralized Configuration System provides a robust, flexible, and maintainable way to manage review system settings across different environments.

Key benefits:
- **Unified configuration** across all environments
- **Environment variable support** for dynamic configuration
- **Automatic environment detection** and fallback
- **Backward compatibility** with existing configurations
- **Comprehensive validation** and error handling
- **Easy integration** with Laravel applications

This makes the review system much more configurable and easier to maintain in different deployment scenarios. 