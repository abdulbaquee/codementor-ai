<?php

/**
 * Review System Configuration Installer
 * 
 * This script automatically installs the review-system configuration
 * to the Laravel config directory, making it easy for developers
 * to set up the review system.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use ReviewSystem\Engine\ConfigurationLoader;

echo "🔧 Review System Configuration Installer\n";
echo "========================================\n\n";

// Check if we're in a Laravel environment
$projectRoot = dirname(__DIR__);
$artisanFile = $projectRoot . '/artisan';
$configDir = $projectRoot . '/config';

if (!file_exists($artisanFile)) {
    echo "❌ Error: This script must be run from within a Laravel application.\n";
    echo "Please run this script from your Laravel project root directory.\n";
    echo "Expected artisan file at: $artisanFile\n\n";
    exit(1);
}

echo "✅ Laravel application detected at: $projectRoot\n";
$targetConfigFile = $configDir . '/review-system.php';
$sourceConfigFile = __DIR__ . '/config.php';

echo "📋 Installation Information:\n";
echo "----------------------------\n";
echo "Source Config: $sourceConfigFile\n";
echo "Target Config: $targetConfigFile\n";
echo "Config Directory: $configDir\n\n";

// Check if source config exists
if (!file_exists($sourceConfigFile)) {
    echo "❌ Error: Source configuration file not found.\n";
    echo "Please ensure the review-system is properly installed.\n\n";
    exit(1);
}

// Check if target config already exists
if (file_exists($targetConfigFile)) {
    echo "⚠️  Warning: Configuration file already exists at:\n";
    echo "   $targetConfigFile\n\n";
    
    echo "Options:\n";
    echo "1. Backup existing and install new (recommended)\n";
    echo "2. Overwrite existing file\n";
    echo "3. Skip installation\n\n";
    
    echo "Enter your choice (1-3): ";
    $handle = fopen("php://stdin", "r");
    $choice = trim(fgets($handle));
    fclose($handle);
    
    switch ($choice) {
        case '1':
            $backupFile = $targetConfigFile . '.backup.' . date('Y-m-d_H-i-s');
            if (copy($targetConfigFile, $backupFile)) {
                echo "✅ Existing config backed up to: $backupFile\n";
            } else {
                echo "❌ Failed to create backup. Aborting.\n";
                exit(1);
            }
            break;
        case '2':
            echo "⚠️  Overwriting existing configuration file...\n";
            break;
        case '3':
            echo "✅ Installation skipped.\n";
            exit(0);
        default:
            echo "❌ Invalid choice. Aborting.\n";
            exit(1);
    }
}

// Read source configuration
$sourceConfig = file_get_contents($sourceConfigFile);
if ($sourceConfig === false) {
    echo "❌ Error: Unable to read source configuration file.\n";
    exit(1);
}

// Convert standalone config to Laravel config
$laravelConfig = convertToLaravelConfig($sourceConfig);

// Ensure config directory exists
if (!is_dir($configDir)) {
    if (!mkdir($configDir, 0755, true)) {
        echo "❌ Error: Unable to create config directory.\n";
        exit(1);
    }
}

// Write Laravel configuration file
if (file_put_contents($targetConfigFile, $laravelConfig) === false) {
    echo "❌ Error: Unable to write configuration file.\n";
    exit(1);
}

echo "✅ Configuration file installed successfully!\n";
echo "📁 Location: $targetConfigFile\n\n";

// Test the configuration
echo "🧪 Testing configuration...\n";
try {
    $configLoader = new ConfigurationLoader();
    $config = $configLoader->getConfiguration();
    $configInfo = $configLoader->getConfigurationInfo();
    
    echo "✅ Configuration test successful!\n";
    echo "📊 Environment: {$configInfo['environment']}\n";
    echo "📁 Config File: {$configInfo['config_file']}\n";
    echo "🔧 Scan Paths: " . count($config['scan_paths']) . " configured\n";
    echo "🔍 Rules: " . count($config['rules']) . " configured\n\n";
    
} catch (Exception $e) {
    echo "⚠️  Configuration test failed: " . $e->getMessage() . "\n";
    echo "Please check the configuration file manually.\n\n";
}

echo "📚 Next Steps:\n";
echo "--------------\n";
echo "1. Review the configuration at: $targetConfigFile\n";
echo "2. Customize settings as needed for your project\n";
echo "3. Add environment variables to your .env file if desired\n";
echo "4. Run the review system: php review-system/cli.php\n\n";

echo "🔧 Environment Variables (Optional):\n";
echo "------------------------------------\n";
echo "Add these to your .env file for customization:\n\n";

$envExamples = [
    'REVIEW_ENABLE_CACHING=true',
    'REVIEW_LOG_LEVEL=info',
    'REVIEW_EXIT_ON_VIOLATION=true',
    'REVIEW_HTML_TITLE="My Project Code Review"',
    'REVIEW_MAX_FILE_SIZE=10485760',
    'REVIEW_CACHE_EXPIRY_TIME=3600',
    'REVIEW_USE_FILE_MOD_TIME=true',
    'REVIEW_VALIDATE_CONFIG=true',
    'REVIEW_PERFORMANCE_MONITORING=true',
    'REVIEW_LOGGING_ENABLED=true',
];

foreach ($envExamples as $envVar) {
    echo "   $envVar\n";
}

echo "\n✅ Installation completed successfully!\n";

/**
 * Convert standalone configuration to Laravel configuration
 */
function convertToLaravelConfig(string $standaloneConfig): string
{
    $projectRoot = dirname(__DIR__);
    
    // Replace standalone paths with Laravel paths
    $laravelConfig = str_replace(
        [
            "realpath(__DIR__ . '/../app')",
            "realpath(__DIR__ . '/../routes')",
            "__DIR__ . '/cache/file_scanner_cache.json'",
            "__DIR__ . '/reports'",
        ],
        [
            "app_path()",
            "base_path('routes')",
            "storage_path('review-system/cache/file_scanner_cache.json')",
            "storage_path('review-system/reports')",
        ],
        $standaloneConfig
    );

    // Add Laravel-style header and environment variable support
    $header = <<<'PHP'
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

PHP;

    // Replace the opening array with the header
    $laravelConfig = preg_replace('/^<\?php\s*return\s*\[/', $header, $laravelConfig);

    // Add environment variable support to key settings
    $laravelConfig = str_replace(
        [
            "'max_file_size' => 10 * 1024 * 1024,",
            "'enable_caching' => true,",
            "'cache_expiry_time' => 3600,",
            "'use_file_mod_time' => true,",
            "'exit_on_violation' => true,",
            "'title' => 'Code Review Report',",
            "'include_css' => true,",
            "'show_timestamp' => true,",
            "'show_violation_count' => true,",
        ],
        [
            "'max_file_size' => env('REVIEW_MAX_FILE_SIZE', 10 * 1024 * 1024),",
            "'enable_caching' => env('REVIEW_ENABLE_CACHING', true),",
            "'cache_expiry_time' => env('REVIEW_CACHE_EXPIRY_TIME', 3600),",
            "'use_file_mod_time' => env('REVIEW_USE_FILE_MOD_TIME', true),",
            "'exit_on_violation' => env('REVIEW_EXIT_ON_VIOLATION', true),",
            "'title' => env('REVIEW_HTML_TITLE', 'Code Review Report'),",
            "'include_css' => env('REVIEW_HTML_INCLUDE_CSS', true),",
            "'show_timestamp' => env('REVIEW_HTML_SHOW_TIMESTAMP', true),",
            "'show_violation_count' => env('REVIEW_HTML_SHOW_VIOLATION_COUNT', true),",
        ],
        $laravelConfig
    );

    return $laravelConfig;
} 