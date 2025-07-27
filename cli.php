<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ReviewSystem\Engine\RuleRunner;
use ReviewSystem\Engine\ReportWriter;
use ReviewSystem\Engine\ConfigurationLoader;
use ReviewSystem\Engine\ProgressIndicator;

// Self-healing function
function attemptSelfHealing(): bool {
    $configFile = __DIR__ . '/config.php';
    $composerFile = __DIR__ . '/../composer.json';
    
    // Check if config.php is missing or invalid
    if (!file_exists($configFile) || !is_readable($configFile)) {
        echo "🚑 Self-healing: config.php is missing or invalid\n";
        echo "💡 Creating default configuration...\n";
        
        $defaultConfig = '<?php

return [
    "rules" => [
        "ReviewSystem\Rules\CodeStyleRule",
        "ReviewSystem\Rules\LaravelBestPracticesRule",
        "ReviewSystem\Rules\NoMongoInControllerRule",
    ],
    "cache" => [
        "enabled" => true,
        "ttl" => 3600,
    ],
    "reporting" => [
        "format" => "html",
        "output" => "reports/",
        "filters" => ["critical", "warning"],
    ],
    "quick_mode" => [
        "enabled" => true,
        "rules" => ["ReviewSystem\Rules\CodeStyleRule"],
        "max_files" => 50,
    ],
];';
        
        if (file_put_contents($configFile, $defaultConfig) !== false) {
            echo "✅ config.php created successfully\n";
        } else {
            echo "❌ Failed to create config.php\n";
            return false;
        }
    }
    
    // Check if autoloading is configured
    if (file_exists($composerFile)) {
        $composerContent = file_get_contents($composerFile);
        if (!str_contains($composerContent, 'ReviewSystem')) {
            echo "🚑 Self-healing: ReviewSystem autoloading missing\n";
            echo "💡 Run: php review-system/update-composer.php && composer dump-autoload\n";
            return false;
        }
    }
    
    return true;
}

// Parse command line arguments
$args = $argv ?? [];
$mode = 'full'; // Default mode

if (in_array('--quick', $args)) {
    $mode = 'quick';
} elseif (in_array('--full', $args)) {
    $mode = 'full';
}

// Attempt self-healing if needed
if (!attemptSelfHealing()) {
    echo "❌ Self-healing failed. Please run: ./review-system/recover.sh --all\n";
    exit(1);
}

// Load configuration using the new ConfigurationLoader
$configLoader = new ConfigurationLoader();
$config = $configLoader->getConfiguration();

// Apply mode-specific configuration
if ($mode === 'quick') {
    $config['quick_mode'] = $config['quick_mode'] ?? [
        'enabled' => true,
        'rules' => ['psr12', 'security'],
        'max_files' => 50,
    ];
    
    // Override rules for quick mode
    if (isset($config['quick_mode']['rules'])) {
        $config['rules'] = array_intersect(
            $config['rules'], 
            $config['quick_mode']['rules']
        );
    }
    
    // Limit files for quick mode
    if (isset($config['quick_mode']['max_files'])) {
        $config['max_files'] = $config['quick_mode']['max_files'];
    }
}

// Display configuration information
$configInfo = $configLoader->getConfigurationInfo();
echo "🔧 Review System Configuration ({$mode} mode)\n";
echo "============================================\n";
echo "Environment: {$configInfo['environment']}\n";
echo "Config File: {$configInfo['config_file']}\n";
echo "Environment Overrides: " . ($configInfo['has_env_overrides'] ? 'Yes' : 'No') . "\n";
echo "Mode: " . strtoupper($mode) . "\n";
if ($mode === 'quick') {
    echo "Quick Mode Rules: " . implode(', ', array_keys($config['rules'])) . "\n";
    echo "Max Files: " . ($config['max_files'] ?? 'unlimited') . "\n";
}
echo "\n";

// Create progress indicator
$progress = new ProgressIndicator();

// Create rule runner and set progress callback
$runner = new RuleRunner($config);
$runner->setProgressCallback(function(int $step, int $total, string $message) use ($progress) {
    $progress->update($step, $message);
});

try {
    // Initialize progress indicator
    $progress->initialize(100, 'Code Review Process');
    
    $violations = $runner->run();
    
    // Complete progress indicator
    $progress->complete('Code review completed successfully');
    
    // Display comprehensive run report
    $runReport = $runner->getRunReport();
    
    // Display performance metrics
    if (!empty($runReport['performance'])) {
        echo "📊 Performance Metrics\n";
        echo "=====================\n";
        if (isset($runReport['performance']['total_time'])) {
            echo "Total Time: " . round($runReport['performance']['total_time'], 3) . "s\n";
        }
        if (isset($runReport['performance']['file_scanning'])) {
            echo "File Scanning: " . round($runReport['performance']['file_scanning'], 3) . "s\n";
        }
        if (isset($runReport['performance']['rules'])) {
            echo "Rule Processing:\n";
            foreach ($runReport['performance']['rules'] as $rule => $time) {
                echo "  • {$rule}: " . round($time, 3) . "s\n";
            }
        }
        echo "\n";
    }
    
    // Display statistics
    if (!empty($runReport['statistics'])) {
        echo "📈 Statistics\n";
        echo "=============\n";
        if (isset($runReport['statistics']['files_scanned'])) {
            echo "Files Scanned: {$runReport['statistics']['files_scanned']}\n";
        }
        if (isset($runReport['statistics']['rules_processed'])) {
            echo "Rules Processed: {$runReport['statistics']['rules_processed']}\n";
        }
        if (isset($runReport['statistics']['rules_failed'])) {
            echo "Rules Failed: {$runReport['statistics']['rules_failed']}\n";
        }
        if (isset($runReport['statistics']['total_violations'])) {
            echo "Total Violations: {$runReport['statistics']['total_violations']}\n";
        }
        echo "\n";
    }
    
    // Display warnings
    if ($runner->hasWarnings()) {
        echo "⚠️  Warnings\n";
        echo "===========\n";
        foreach ($runner->getWarnings() as $warning) {
            $category = $warning['category'] ?? 'GENERAL';
            $suggestion = $warning['suggestion'] ?? '';
            echo "• [{$category}] {$warning['message']}\n";
            if ($suggestion) {
                echo "  💡 Suggestion: {$suggestion}\n";
            }
        }
        echo "\n";
    }
    
    // Display errors
    if ($runner->hasErrors()) {
        echo "❌ Errors\n";
        echo "=========\n";
        foreach ($runner->getErrors() as $error) {
            $category = $error['category'] ?? 'GENERAL';
            $severity = $error['severity'] ?? 'ERROR';
            $suggestion = $error['suggestion'] ?? '';
            echo "• [{$category}] {$error['message']}\n";
            if ($suggestion) {
                echo "  💡 Suggestion: {$suggestion}\n";
            }
        }
        echo "\n";
    }
    
    if (empty($violations)) {
        $progress->success("No violations found.");
    } else {
        // Create ReportWriter with injected configuration
        $progress->status("Generating HTML report...");
        $reportWriter = new ReportWriter($config);
        $reportPath = $reportWriter->writeHtml($violations);

        // Generate secure, environment-aware URL
        $webUrl = generateSecureReportUrl($reportPath);

        echo "\n== Violations found. ==\n\n";
        echo "📄 Report saved to: " . realpath($reportPath) . "\n";
        echo "🌐 View in browser: $webUrl\n\n";
        exit(1);
    }
} catch (Throwable $e) {
    $progress->error("Critical error occurred: {$e->getMessage()}");
    echo "\nPlease check your configuration and try again.\n";
    exit(1);
}

/**
 * Generate a secure, environment-aware URL for the report
 * 
 * @param string $reportPath The full path to the report file
 * @return string The secure URL to access the report
 */
function generateSecureReportUrl(string $reportPath): string
{
    // Get the project root directory name for the URL
    $projectRoot = basename(dirname(__DIR__));
    $reportFilename = basename($reportPath);
    
    // Environment-based configuration
    $baseUrl = getBaseUrl();
    $port = getPort();
    
    // Build the secure URL
    $url = $baseUrl;
    
    // Add port if not standard (80 for HTTP, 443 for HTTPS)
    if ($port && $port !== '80' && $port !== '443') {
        $url .= ":$port";
    }
    
    $url .= "/$projectRoot/codementor-ai/reports/$reportFilename";
    
    return $url;
}

/**
 * Get the base URL based on environment
 * 
 * @return string The base URL (protocol + domain)
 */
function getBaseUrl(): string
{
    // Check for environment variables first
    if ($appUrl = getenv('APP_URL')) {
        return rtrim($appUrl, '/');
    }
    
    if ($reviewBaseUrl = getenv('REVIEW_BASE_URL')) {
        return rtrim($reviewBaseUrl, '/');
    }
    
    // Check if we're in a Laravel environment
    if (file_exists(__DIR__ . '/../.env')) {
        $envContent = file_get_contents(__DIR__ . '/../.env');
        if (preg_match('/APP_URL\s*=\s*(.+)/', $envContent, $matches)) {
            $appUrl = trim($matches[1]);
            if ($appUrl && $appUrl !== 'null') {
                // For localhost development, prefer standard ports (80/443) unless explicitly overridden
                if (str_contains($appUrl, 'localhost')) {
                    $parsedUrl = parse_url($appUrl);
                    $port = $parsedUrl['port'] ?? null;
                    
                    // If it's a common development port (8000, 3000, etc.), use standard port
                    if (in_array($port, ['8000', '3000', '8080', '9000'])) {
                        $scheme = $parsedUrl['scheme'] ?? 'http';
                        return $scheme . '://localhost';
                    }
                }
                return rtrim($appUrl, '/');
            }
        }
    }
    
    // Fallback to localhost for development
    return 'http://localhost';
}

/**
 * Get the port number based on environment
 * 
 * @return string|null The port number or null for default
 */
function getPort(): ?string
{
    // Check for environment variables
    if ($port = getenv('REVIEW_PORT')) {
        return $port;
    }
    
    if ($port = getenv('APP_PORT')) {
        return $port;
    }
    
    // Check Laravel .env file
    if (file_exists(__DIR__ . '/../.env')) {
        $envContent = file_get_contents(__DIR__ . '/../.env');
        if (preg_match('/APP_PORT\s*=\s*(.+)/', $envContent, $matches)) {
            $port = trim($matches[1]);
            if ($port && $port !== 'null') {
                return $port;
            }
        }
    }
    
    // Return null for default ports (80/443)
    return null;
}
