<?php

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

// Self-healing function
function attemptSelfHealing(): bool
{
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
function parseArguments(array $args): array
{
    $mode = 'full'; // Default mode

    if (in_array('--quick', $args)) {
        $mode = 'quick';
    } elseif (in_array('--full', $args)) {
        $mode = 'full';
    }

    return [
        'mode' => $mode,
        'args' => $args
    ];
}

// Generate web-accessible URL for the report
function generateWebUrl(string $reportPath): string
{
    // Extract the filename from the report path
    $filename = basename($reportPath);
    
    // Check if we're in a Laravel project structure
    $projectRoot = getProjectRoot();
    $relativePath = str_replace($projectRoot, '', dirname($reportPath));
    
    // Generate web URLs for different scenarios
    $urls = [];
    
    // Apache with DocumentRoot at /Users/abdul/Sites
    $urls[] = "http://localhost" . $relativePath . "/" . $filename;
    
    // PHP Development Server (if running on port 8080)
    $urls[] = "http://localhost:8080/" . $filename;
    
    // Alternative ports
    $urls[] = "http://localhost:8000/" . $filename;
    $urls[] = "http://localhost:3000/" . $filename;
    
    // Return the most likely URL (Apache)
    return $urls[0];
}

// Generate multiple web URL options
function generateWebUrlOptions(string $reportPath): array
{
    $filename = basename($reportPath);
    $projectRoot = getProjectRoot();
    $relativePath = str_replace($projectRoot, '', dirname($reportPath));
    
    // Clean up the relative path for Apache
    $apachePath = str_replace('/Users/abdul/Sites', '', $projectRoot);
    $apachePath = $apachePath . '/codementor-ai/reports/' . $filename;
    
    return [
        "Apache (localhost)" => "http://localhost" . $apachePath,
        "PHP Dev Server (8080)" => "http://localhost:8080/" . $filename,
        "PHP Dev Server (8000)" => "http://localhost:8000/" . $filename,
        "Alternative (3000)" => "http://localhost:3000/" . $filename,
    ];
}

// Get the project root directory
function getProjectRoot(): string
{
    $currentDir = __DIR__;
    
    // If we're in a Laravel project, go up to the project root
    if (file_exists($currentDir . '/../artisan')) {
        return realpath($currentDir . '/../');
    }
    
    // If we're in the codementor-ai directory, go up one level
    if (basename($currentDir) === 'codementor-ai') {
        return realpath($currentDir . '/../');
    }
    
    // Default to current directory
    return $currentDir;
}

// Check if PHP development server is running
function isDevServerRunning(): bool
{
    $ports = [8080, 8000, 3000];
    
    foreach ($ports as $port) {
        $connection = @fsockopen('localhost', $port, $errno, $errstr, 1);
        if (is_resource($connection)) {
            fclose($connection);
            return true;
        }
    }
    
    return false;
}

// Main execution function
function main(): void
{
    global $argv;
    $parsed = parseArguments($argv ?? []);
    $mode = $parsed['mode'];

    // Attempt self-healing if needed
    if (!attemptSelfHealing()) {
        echo "❌ Self-healing failed. Please run: ./review-system/recover.sh --all\n";
        exit(1);
    }

    // Load configuration using the new ConfigurationLoader
    $configLoader = new \ReviewSystem\Engine\ConfigurationLoader();
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
    }

    // Initialize components
    $runner = new \ReviewSystem\Engine\RuleRunner($config);
    $progress = new \ReviewSystem\Engine\ProgressIndicator();
    $reportWriter = new \ReviewSystem\Engine\ReportWriter($config);

    // Get target directory from command line arguments (skip flags)
    $targetDir = '.';
    foreach ($argv as $arg) {
        if (!str_starts_with($arg, '--') && $arg !== $argv[0]) {
            $targetDir = $arg;
            break;
        }
    }
    
    if (!is_dir($targetDir)) {
        echo "❌ Error: Target directory '$targetDir' does not exist.\n";
        exit(1);
    }

    echo "🚀 Starting code review...\n";
    echo "📁 Target: $targetDir\n";
    echo "🎯 Mode: $mode\n";
    echo "📋 Rules: " . count($config['rules']) . " enabled\n\n";

    // Run the review
    $progress->initialize(100, 'Code Review Process');
    $results = $runner->run();
    $progress->complete('Code review completed successfully');

    // Generate report
    echo "\n📊 Generating report...\n";
    $reportPath = $reportWriter->writeHtml($results);

    // Display summary
    $totalIssues = count($results);
    $criticalIssues = count(array_filter($results, fn($issue) => $issue['severity'] === 'critical'));
    $warningIssues = count(array_filter($results, fn($issue) => $issue['severity'] === 'warning'));

    echo "\n📈 Review Summary:\n";
    echo "================\n";
    echo "Total Issues: $totalIssues\n";
    echo "Critical: $criticalIssues\n";
    echo "Warnings: $warningIssues\n";
    
    // Always show web-accessible URL
    if ($reportPath && file_exists($reportPath)) {
        $webUrlOptions = generateWebUrlOptions($reportPath);
        echo "Report: $reportPath\n";
        echo "🌐 Web URLs:\n";
        foreach ($webUrlOptions as $server => $url) {
            echo "   $server: $url\n";
        }
        
        // Check if PHP dev server is running and provide instructions
        if (!isDevServerRunning()) {
            echo "\n💡 To start a PHP development server for easy access:\n";
            echo "   cd codementor-ai/reports && php -S localhost:8080\n";
            echo "   Then visit: http://localhost:8080/" . basename($reportPath) . "\n";
        }
    } else {
        // Fallback: generate a timestamp-based filename and show potential URLs
        $timestamp = date('Ymd_His');
        $fallbackFilename = "report-{$timestamp}.html";
        $webUrlOptions = generateWebUrlOptions("codementor-ai/reports/" . $fallbackFilename);
        echo "Report: Report generation may have failed\n";
        echo "🌐 Web URLs:\n";
        foreach ($webUrlOptions as $server => $url) {
            echo "   $server: $url\n";
        }
        echo "💡 If report file doesn't exist, check the reports directory\n";
    }

    if ($totalIssues === 0) {
        echo "\n🎉 No issues found! Your code looks great!\n";
    } elseif ($criticalIssues > 0) {
        echo "\n⚠️  Critical issues found. Please review and fix them.\n";
        exit(1);
    } else {
        echo "\n💡 Some warnings found. Consider addressing them for better code quality.\n";
    }
}

// Load autoloader and execute main function
require_once __DIR__ . '/../vendor/autoload.php';
main();
