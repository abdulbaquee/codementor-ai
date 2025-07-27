<?php

/**
 * Test Runner for Review System
 * 
 * This script runs all unit tests for the review system components
 */

echo "🧪 Review System Unit Tests\n";
echo "===========================\n\n";

// Test files to run
$testFiles = [
    'Unit/Rules/NoMongoInControllerRuleTest.php',
    'Unit/Rules/LaravelBestPracticesRuleTest.php',
    'Unit/Rules/CodeStyleRuleTest.php',
    'Unit/Engine/ErrorHandlerTest.php',
    'Unit/Engine/PerformanceOptimizedRuleTest.php'
];

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$errors = [];

foreach ($testFiles as $testFile) {
    $testClass = str_replace(['tests/Unit/', '.php'], '', $testFile);
    $testClass = str_replace('/', '\\', $testClass);
    
    echo "Running {$testClass}...\n";
    
    try {
        // Check if file exists
        if (!file_exists($testFile)) {
            echo "❌ File {$testFile} not found\n\n";
            $failedTests++;
            $errors[] = "File {$testFile} not found";
            continue;
        }
        
        // Run tests using PHPUnit command line
        $command = "../vendor/bin/phpunit {$testFile}";
        $output = [];
        $returnCode = 0;
        exec($command . " 2>&1", $output, $returnCode);
        
        // Parse output to count tests
        $testCount = 0;
        foreach ($output as $line) {
            if (preg_match('/(\d+) tests?/', $line, $matches)) {
                $testCount = (int)$matches[1];
                break;
            }
        }
        
        $totalTests += $testCount;
        
        if ($returnCode === 0) {
            echo "✅ {$testClass}: {$testCount} tests passed\n";
            $passedTests += $testCount;
        } else {
            echo "❌ {$testClass}: {$testCount} tests, some failed\n";
            $failedTests += $testCount;
            
            // Collect errors from output
            foreach ($output as $line) {
                if (str_contains($line, 'FAILURES') || str_contains($line, 'ERRORS')) {
                    $errors[] = $line;
                }
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Error running {$testClass}: " . $e->getMessage() . "\n";
        $failedTests++;
        $errors[] = "Error running {$testClass}: " . $e->getMessage();
    }
    
    echo "\n";
}

// Summary
echo "📊 Test Summary\n";
echo "===============\n";
echo "Total Tests: {$totalTests}\n";
echo "Passed: {$passedTests}\n";
echo "Failed: {$failedTests}\n";
echo "Success Rate: " . ($totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0) . "%\n\n";

if (!empty($errors)) {
    echo "❌ Errors and Failures:\n";
    echo "=======================\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
    echo "\n";
}

if ($failedTests === 0) {
    echo "🎉 All tests passed! Review system is working correctly.\n";
    exit(0);
} else {
    echo "⚠️  Some tests failed. Please review the errors above.\n";
    exit(1);
} 