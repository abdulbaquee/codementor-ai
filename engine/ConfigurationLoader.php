<?php

namespace ReviewSystem\Engine;

class ConfigurationLoader
{
    private array $config = [];
    private bool $isLaravelEnvironment;

    public function __construct()
    {
        $this->isLaravelEnvironment = $this->detectLaravelEnvironment();
        $this->loadConfiguration();
    }

    /**
     * Detect if we're running in a Laravel environment
     */
    private function detectLaravelEnvironment(): bool
    {
        // First, check if we're in a Laravel application by looking for Laravel files
        $projectRoot = dirname(__DIR__, 2); // Go up two levels from review-system/engine/
        $artisanFile = $projectRoot . '/artisan';
        $appDir = $projectRoot . '/app';
        $configDir = $projectRoot . '/config';

        // Check for Laravel structure
        $hasLaravelStructure = file_exists($artisanFile) &&
                              is_dir($appDir) &&
                              is_dir($configDir);

        if (!$hasLaravelStructure) {
            return false;
        }

        // If we have Laravel structure, also check if Laravel functions are available
        if (function_exists('app_path') && function_exists('base_path') && function_exists('storage_path')) {
            try {
                $appPath = app_path();
                $basePath = base_path();
                $storagePath = storage_path();

                // Verify the functions return the expected paths
                return is_dir($appPath) &&
                       is_dir($basePath) &&
                       is_dir($storagePath) &&
                       file_exists($basePath . '/artisan');
            } catch (\Throwable $e) {
                // If Laravel functions fail, but we have Laravel structure, still consider it Laravel
                return true;
            }
        }

        // If we have Laravel structure but no functions, still consider it Laravel
        return true;
    }

    /**
     * Load configuration from the appropriate source
     */
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

    /**
     * Load configuration from Laravel config system
     */
    private function loadLaravelConfiguration(): void
    {
        try {
            // Try to load from Laravel config using config() helper
            if (function_exists('config')) {
                $this->config = config('codementor-ai', []);
            }

            // If Laravel config is empty or config() function not available, try to load from file directly
            if (empty($this->config)) {
                $configFile = $this->getLaravelConfigPath();
                if (file_exists($configFile)) {
                    $this->config = require $configFile;
                }
            }

            // If still empty, fall back to standalone configuration
            if (empty($this->config)) {
                $this->loadStandaloneConfiguration();
            }
        } catch (\Throwable $e) {
            error_log("Error loading Laravel configuration: " . $e->getMessage());
            // Fall back to standalone configuration
            $this->loadStandaloneConfiguration();
        }
    }

    /**
     * Get the path to Laravel config file
     */
    private function getLaravelConfigPath(): string
    {
        // Try to use base_path() if available
        if (function_exists('base_path')) {
            return base_path('config/codementor-ai.php');
        }

        // Fallback: construct path manually
        $projectRoot = dirname(__DIR__, 2); // Go up two levels from codementor-ai/engine/
        return $projectRoot . '/config/codementor-ai.php';
    }

    /**
     * Load configuration from standalone config file
     */
    private function loadStandaloneConfiguration(): void
    {
        $configFile = __DIR__ . '/../config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            if (is_array($config)) {
                $this->config = $config;
            } else {
                error_log("Warning: config.php did not return an array, using default configuration");
                $this->config = $this->getDefaultConfiguration();
            }
        } else {
            $this->config = $this->getDefaultConfiguration();
        }
    }

    /**
     * Apply environment variable overrides
     */
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

        if ($enableCaching = getenv('REVIEW_ENABLE_CACHING')) {
            $this->config['file_scanner']['enable_caching'] = filter_var($enableCaching, FILTER_VALIDATE_BOOLEAN);
        }

        if ($cacheExpiry = getenv('REVIEW_CACHE_EXPIRY_TIME')) {
            $this->config['file_scanner']['cache_expiry_time'] = (int) $cacheExpiry;
        }

        if ($useFileModTime = getenv('REVIEW_USE_FILE_MOD_TIME')) {
            $this->config['file_scanner']['use_file_mod_time'] = filter_var($useFileModTime, FILTER_VALIDATE_BOOLEAN);
        }

        // Override reporting settings
        if ($outputPath = getenv('REVIEW_OUTPUT_PATH')) {
            $this->config['reporting']['output_path'] = $outputPath;
        }

        if ($exitOnViolation = getenv('REVIEW_EXIT_ON_VIOLATION')) {
            $this->config['reporting']['exit_on_violation'] = filter_var($exitOnViolation, FILTER_VALIDATE_BOOLEAN);
        }

        // Override validation settings
        if ($validateConfig = getenv('REVIEW_VALIDATE_CONFIG')) {
            $this->config['validation']['enable_config_validation'] =
                filter_var($validateConfig, FILTER_VALIDATE_BOOLEAN);
        }

        if ($strictValidation = getenv('REVIEW_STRICT_VALIDATION')) {
            $this->config['validation']['strict_mode'] = filter_var($strictValidation, FILTER_VALIDATE_BOOLEAN);
        }

        // Override logging settings
        if ($logLevel = getenv('REVIEW_LOG_LEVEL')) {
            $this->config['logging']['level'] = $logLevel;
        }

        if ($logEnabled = getenv('REVIEW_LOGGING_ENABLED')) {
            $this->config['logging']['enabled'] = filter_var($logEnabled, FILTER_VALIDATE_BOOLEAN);
        }
    }

    /**
     * Normalize configuration to ensure all required keys exist
     */
    private function normalizeConfiguration(): void
    {
        $defaults = $this->getDefaultConfiguration();

        // Merge with defaults to ensure all keys exist
        $this->config = array_replace_recursive($defaults, $this->config);

        // Ensure file_scanner is properly structured
        if (!isset($this->config['file_scanner'])) {
            $this->config['file_scanner'] = $defaults['file_scanner'];
        }

        // Ensure reporting is properly structured
        if (!isset($this->config['reporting'])) {
            $this->config['reporting'] = $defaults['reporting'];
        }

        // Ensure rules is an array
        if (!isset($this->config['rules']) || !is_array($this->config['rules'])) {
            $this->config['rules'] = $defaults['rules'];
        }

        // Normalize paths to absolute paths
        $this->normalizePaths();
    }

    /**
     * Normalize file paths to absolute paths
     */
    private function normalizePaths(): void
    {
        // Normalize scan paths
        if (isset($this->config['scan_paths']) && is_array($this->config['scan_paths'])) {
            $this->config['scan_paths'] = array_map(function ($path) {
                return is_string($path) ? (realpath($path) ?: $path) : $path;
            }, $this->config['scan_paths']);
        }

        // Normalize cache file path
        if (
            isset($this->config['file_scanner']['cache_file']) &&
            is_string($this->config['file_scanner']['cache_file'])
        ) {
            $cacheFile = $this->config['file_scanner']['cache_file'];
            if (!is_absolute_path($cacheFile)) {
                $this->config['file_scanner']['cache_file'] = $this->resolvePath($cacheFile);
            }
        }

        // Normalize output path
        if (
            isset($this->config['reporting']['output_path']) &&
            is_string($this->config['reporting']['output_path'])
        ) {
            $outputPath = $this->config['reporting']['output_path'];
            if (!is_absolute_path($outputPath)) {
                $this->config['reporting']['output_path'] = $this->resolvePath($outputPath);
            }
        }

        // Normalize log file path
        if (
            isset($this->config['logging']['file']) &&
            is_string($this->config['logging']['file'])
        ) {
            $logFile = $this->config['logging']['file'];
            if (!is_absolute_path($logFile)) {
                $this->config['logging']['file'] = $this->resolvePath($logFile);
            }
        }
    }

    /**
     * Resolve a relative path to an absolute path
     */
    private function resolvePath(string $path): string
    {
        if ($this->isLaravelEnvironment) {
            // In Laravel, resolve relative to base path
            return base_path($path);
        } else {
            // In standalone mode, resolve relative to review-system directory
            return realpath(__DIR__ . '/../' . $path) ?: __DIR__ . '/../' . $path;
        }
    }

    /**
     * Get the complete configuration
     */
    public function getConfiguration(): array
    {
        return $this->config;
    }

    /**
     * Get a specific configuration value
     */
    public function get(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Check if a configuration key exists
     */
    public function has(string $key): bool
    {
        return data_get($this->config, $key) !== null;
    }

    /**
     * Get scan paths configuration
     */
    public function getScanPaths(): array
    {
        return $this->get('scan_paths', []);
    }

    /**
     * Get file scanner configuration
     */
    public function getFileScannerConfig(): array
    {
        return $this->get('file_scanner', []);
    }

    /**
     * Get reporting configuration
     */
    public function getReportingConfig(): array
    {
        return $this->get('reporting', []);
    }

    /**
     * Get rules configuration
     */
    public function getRules(): array
    {
        return $this->get('rules', []);
    }

    /**
     * Get validation configuration
     */
    public function getValidationConfig(): array
    {
        return $this->get('validation', []);
    }

    /**
     * Get performance configuration
     */
    public function getPerformanceConfig(): array
    {
        return $this->get('performance', []);
    }

    /**
     * Get logging configuration
     */
    public function getLoggingConfig(): array
    {
        return $this->get('logging', []);
    }

    /**
     * Get security configuration
     */
    public function getSecurityConfig(): array
    {
        return $this->get('security', []);
    }

    /**
     * Get integration configuration
     */
    public function getIntegrationConfig(): array
    {
        return $this->get('integration', []);
    }

    /**
     * Check if we're in a Laravel environment
     */
    public function isLaravelEnvironment(): bool
    {
        return $this->isLaravelEnvironment;
    }

    /**
     * Get configuration source information
     */
    public function getConfigurationInfo(): array
    {
        return [
            'environment' => $this->isLaravelEnvironment ? 'Laravel' : 'Standalone',
            'config_file' => $this->isLaravelEnvironment ? 'config/codementor-ai.php' : 'codementor-ai/config.php',
            'has_env_overrides' => !empty(array_filter([
                getenv('REVIEW_SCAN_PATHS'),
                getenv('REVIEW_ENABLE_CACHING'),
                getenv('REVIEW_USE_FILE_MOD_TIME'),
                getenv('REVIEW_EXIT_ON_VIOLATION'),
            ])),
        ];
    }

    /**
     * Get default configuration
     */
    private function getDefaultConfiguration(): array
    {
        // Get project root path
        $projectRoot = dirname(__DIR__, 2); // Go up two levels from codementor-ai/engine/

        // Helper function to safely get Laravel paths
        $getLaravelPath = function ($path) {
            if ($this->isLaravelEnvironment && function_exists($path)) {
                try {
                    return $path();
                } catch (\Throwable $e) {
                    // Fallback to manual path construction
                }
            }
            return null;
        };

        // Determine scan paths
        $appPath = $getLaravelPath('app_path') ?: realpath($projectRoot . '/app');
        $routesPath = null;
        if ($this->isLaravelEnvironment && function_exists('base_path')) {
            try {
                $routesPath = base_path('routes');
            } catch (\Throwable $e) {
                $routesPath = realpath($projectRoot . '/routes');
            }
        } else {
            $routesPath = realpath($projectRoot . '/routes');
        }

        // Determine cache and output paths
        $cachePath = null;
        $outputPath = null;
        $logPath = null;

        if ($this->isLaravelEnvironment && function_exists('storage_path')) {
            try {
                $cachePath = storage_path('codementor-ai/cache/file_scanner_cache.json');
                $outputPath = storage_path('codementor-ai/reports');
                $logPath = storage_path('logs/codementor-ai.log');
            } catch (\Throwable $e) {
                // Fallback to manual path construction
                $cachePath = __DIR__ . '/../cache/file_scanner_cache.json';
                $outputPath = __DIR__ . '/../reports';
                $logPath = __DIR__ . '/../logs/codementor-ai.log';
            }
        } else {
            $cachePath = __DIR__ . '/../cache/file_scanner_cache.json';
            $outputPath = __DIR__ . '/../reports';
            $logPath = __DIR__ . '/../logs/codementor-ai.log';
        }

        return [
            'scan_paths' => [
                $appPath,
                $routesPath,
            ],
            'file_scanner' => [
                'max_file_size' => 10 * 1024 * 1024, // 10MB
                'enable_caching' => true,
                'cache_file' => $cachePath,
                'cache_expiry_time' => 3600, // 1 hour
                'use_file_mod_time' => true,
                'exclude_patterns' => [
                    '/vendor/',
                    '/node_modules/',
                    '/storage/',
                    '/bootstrap/cache/',
                    '/.git/',
                    '/.svn/',
                    '/.hg/',
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
            'reporting' => [
                'output_path' => $outputPath,
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
            'rules' => [
                \ReviewSystem\Rules\NoMongoInControllerRule::class,
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
                'level' => 'info',
                'file' => $logPath,
                'format' => '[ReviewSystem] {datetime} - {level}: {message}',
                'console_output' => true,
            ],
            'security' => [
                'allowed_paths' => [
                    $appPath,
                    $routesPath,
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
                    'output_format' => 'json',
                ],
            ],
        ];
    }
}

/**
 * Helper function to check if a path is absolute
 */
if (!function_exists('is_absolute_path')) {
    function is_absolute_path(string $path): bool
    {
        return $path[0] === '/' || (strlen($path) > 1 && $path[1] === ':');
    }
}

/**
 * Helper function to get nested array values (similar to Laravel's data_get)
 */
if (!function_exists('data_get')) {
    function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        while (!is_null($segment = array_shift($key))) {
            if ($segment === '*') {
                if ($target instanceof \Illuminate\Support\Collection) {
                    $target = $target->all();
                } elseif (!is_array($target)) {
                    return value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = data_get($item, $key);
                }

                return in_array('*', $key) ? array_collapse($result) : $result;
            }

            if (is_array($target) && array_key_exists($segment, $target)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }
}

/**
 * Helper function to get default value
 */
if (!function_exists('value')) {
    function value($value)
    {
        return $value instanceof \Closure ? $value() : $value;
    }
}

/**
 * Helper function to collapse arrays (similar to Laravel's array_collapse)
 */
if (!function_exists('array_collapse')) {
    function array_collapse($array)
    {
        $results = [];

        foreach ($array as $values) {
            if ($values instanceof \Illuminate\Support\Collection) {
                $values = $values->all();
            } elseif (!is_array($values)) {
                continue;
            }

            $results = array_merge($results, $values);
        }

        return $results;
    }
}
