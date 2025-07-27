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

    // Get target directory from command line arguments
    $targetDir = $argv[1] ?? '.';
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
    echo "Report: $reportPath\n";

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
