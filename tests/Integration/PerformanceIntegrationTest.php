<?php

namespace ReviewSystem\Tests\Integration;

use ReviewSystem\Tests\TestCase;
use ReviewSystem\Engine\RuleRunner;
use ReviewSystem\Engine\ConfigurationLoader;
use ReviewSystem\Engine\PerformanceOptimizedRule;

/**
 * Performance integration tests for the review system
 * 
 * Tests system performance under various load conditions
 */
class PerformanceIntegrationTest extends TestCase
{
    private string $testDir;
    private array $performanceResults = [];

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/review-system-performance-' . uniqid();
        mkdir($this->testDir, 0755, true);
        
        // Clear performance metrics
        PerformanceOptimizedRule::clearCache();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->testDir);
    }

    /**
     * Test performance with increasing file counts
     */
    public function testPerformanceWithIncreasingFileCounts(): void
    {
        $fileCounts = [10, 25, 50, 100];
        
        foreach ($fileCounts as $count) {
            $this->createTestFiles($count);
            
            $startTime = microtime(true);
            $startMemory = memory_get_usage();
            
            $violations = $this->runReviewSystem();
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage();
            
            $executionTime = $endTime - $startTime;
            $memoryUsage = $endMemory - $startMemory;
            
            $this->performanceResults[] = [
                'file_count' => $count,
                'execution_time' => $executionTime,
                'memory_usage' => $memoryUsage,
                'violations_found' => count($violations)
            ];
            
            // Performance assertions
            $this->assertLessThan(30, $executionTime, "Should complete within 30 seconds for {$count} files");
            $this->assertLessThan(200 * 1024 * 1024, $memoryUsage, "Memory usage should be reasonable for {$count} files");
            $this->assertGreaterThan(0, count($violations), "Should find violations in test files");
            
            // Clean up for next iteration
            $this->cleanupTestFiles();
        }
        
        // Verify performance scaling
        $this->verifyPerformanceScaling();
    }

    /**
     * Test performance with different file sizes
     */
    public function testPerformanceWithDifferentFileSizes(): void
    {
        $fileSizes = [1, 5, 10, 25]; // KB
        
        foreach ($fileSizes as $sizeKB) {
            $this->createTestFiles(10, $sizeKB);
            
            $startTime = microtime(true);
            $startMemory = memory_get_usage();
            
            $violations = $this->runReviewSystem();
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage();
            
            $executionTime = $endTime - $startTime;
            $memoryUsage = $endMemory - $startMemory;
            
            $this->performanceResults[] = [
                'file_size_kb' => $sizeKB,
                'execution_time' => $executionTime,
                'memory_usage' => $memoryUsage,
                'violations_found' => count($violations)
            ];
            
            // Performance assertions
            $this->assertLessThan(60, $executionTime, "Should complete within 60 seconds for {$sizeKB}KB files");
            $this->assertLessThan(500 * 1024 * 1024, $memoryUsage, "Memory usage should be reasonable for {$sizeKB}KB files");
            
            $this->cleanupTestFiles();
        }
    }

    /**
     * Test caching performance benefits
     */
    public function testCachingPerformanceBenefits(): void
    {
        $this->createTestFiles(50);
        
        // First run (cache miss)
        $startTime = microtime(true);
        $violations1 = $this->runReviewSystem();
        $firstRunTime = microtime(true) - $startTime;
        
        // Second run (cache hit)
        $startTime = microtime(true);
        $violations2 = $this->runReviewSystem();
        $secondRunTime = microtime(true) - $startTime;
        
        // Verify results are identical
        $this->assertEquals($violations1, $violations2, 'Results should be identical between runs');
        
        // Verify caching provides performance benefit
        $this->assertLessThan($firstRunTime, $secondRunTime, 'Second run should be faster due to caching');
        
        // Get cache statistics
        $cacheStats = PerformanceOptimizedRule::getCacheStats();
        $this->assertGreaterThan(0, $cacheStats['size'], 'Cache should contain entries');
    }

    /**
     * Test memory usage under load
     */
    public function testMemoryUsageUnderLoad(): void
    {
        $this->createTestFiles(200);
        
        $memoryPeak = 0;
        $memorySamples = [];
        
        // Monitor memory during execution
        $startTime = microtime(true);
        
        $violations = $this->runReviewSystem();
        
        $executionTime = microtime(true) - $startTime;
        $finalMemory = memory_get_peak_usage();
        
        $this->assertLessThan(500 * 1024 * 1024, $finalMemory, 'Peak memory usage should be reasonable');
        $this->assertLessThan(120, $executionTime, 'Should complete within 2 minutes');
        $this->assertGreaterThan(0, count($violations), 'Should find violations');
    }

    /**
     * Test concurrent execution performance
     */
    public function testConcurrentExecutionPerformance(): void
    {
        $this->createTestFiles(100);
        
        $startTime = microtime(true);
        
        // Run multiple instances concurrently
        $results = [];
        for ($i = 0; $i < 3; $i++) {
            $results[] = $this->runReviewSystem();
        }
        
        $totalTime = microtime(true) - $startTime;
        
        // Verify all results are identical
        $this->assertEquals($results[0], $results[1], 'Concurrent runs should produce identical results');
        $this->assertEquals($results[1], $results[2], 'Concurrent runs should produce identical results');
        
        // Verify performance is reasonable
        $this->assertLessThan(180, $totalTime, 'Concurrent execution should complete within 3 minutes');
    }

    /**
     * Test performance with different rule combinations
     */
    public function testPerformanceWithDifferentRuleCombinations(): void
    {
        $this->createTestFiles(50);
        
        $ruleCombinations = [
            ['NoMongoInControllerRule'],
            ['LaravelBestPracticesRule'],
            ['CodeStyleRule'],
            ['NoMongoInControllerRule', 'LaravelBestPracticesRule'],
            ['NoMongoInControllerRule', 'LaravelBestPracticesRule', 'CodeStyleRule']
        ];
        
        foreach ($ruleCombinations as $rules) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage();
            
            $violations = $this->runReviewSystemWithRules($rules);
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage();
            
            $executionTime = $endTime - $startTime;
            $memoryUsage = $endMemory - $startMemory;
            
            $this->performanceResults[] = [
                'rules' => implode(',', $rules),
                'execution_time' => $executionTime,
                'memory_usage' => $memoryUsage,
                'violations_found' => count($violations)
            ];
            
            // Performance assertions
            $this->assertLessThan(60, $executionTime, "Should complete within 60 seconds for rules: " . implode(',', $rules));
            $this->assertLessThan(300 * 1024 * 1024, $memoryUsage, "Memory usage should be reasonable");
        }
    }

    /**
     * Test performance degradation over time
     */
    public function testPerformanceDegradationOverTime(): void
    {
        $this->createTestFiles(75);
        
        $executionTimes = [];
        
        // Run multiple times to check for performance degradation
        for ($i = 0; $i < 5; $i++) {
            $startTime = microtime(true);
            $violations = $this->runReviewSystem();
            $executionTime = microtime(true) - $startTime;
            
            $executionTimes[] = $executionTime;
            
            $this->assertGreaterThan(0, count($violations), 'Should find violations in each run');
        }
        
        // Check for reasonable performance consistency
        $maxTime = max($executionTimes);
        $minTime = min($executionTimes);
        $timeVariation = ($maxTime - $minTime) / $minTime;
        
        $this->assertLessThan(0.5, $timeVariation, 'Performance should be consistent across runs (variation < 50%)');
    }

    /**
     * Create test files with violations
     */
    private function createTestFiles(int $count, int $sizeKB = 1): void
    {
        for ($i = 0; $i < $count; $i++) {
            $content = '<?php' . PHP_EOL;
            $content .= 'namespace App\Http\Controllers;' . PHP_EOL . PHP_EOL;
            $content .= 'use MongoDB\Client;' . PHP_EOL . PHP_EOL;
            $content .= 'class TestController' . $i . ' extends Controller' . PHP_EOL;
            $content .= '{' . PHP_EOL;
            $content .= '    public function index()' . PHP_EOL;
            $content .= '    {' . PHP_EOL;
            $content .= '        $client = new Client();' . PHP_EOL;
            $content .= '        $veryLongVariableName = "' . str_repeat('a', $sizeKB * 100) . '";' . PHP_EOL;
            $content .= '        return response()->json(["test" => ' . $i . ']);' . PHP_EOL;
            $content .= '    }' . PHP_EOL;
            $content .= '}' . PHP_EOL;
            
            file_put_contents($this->testDir . "/TestController{$i}.php", $content);
        }
    }

    /**
     * Run the review system
     */
    private function runReviewSystem(): array
    {
        $configLoader = new ConfigurationLoader();
        $config = $configLoader->getConfiguration();
        
        // Override scan paths for testing
        $config['scan_paths'] = [$this->testDir];
        
        $ruleRunner = new RuleRunner($config);
        return $ruleRunner->run();
    }

    /**
     * Run the review system with specific rules
     */
    private function runReviewSystemWithRules(array $rules): array
    {
        $configLoader = new ConfigurationLoader();
        $config = $configLoader->getConfiguration();
        
        // Override scan paths and rules for testing
        $config['scan_paths'] = [$this->testDir];
        $config['rules'] = array_map(function($rule) {
            return 'ReviewSystem\Rules\\' . $rule;
        }, $rules);
        
        $ruleRunner = new RuleRunner($config);
        return $ruleRunner->run();
    }

    /**
     * Clean up test files
     */
    private function cleanupTestFiles(): void
    {
        $files = glob($this->testDir . '/*.php');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    /**
     * Verify performance scaling
     */
    private function verifyPerformanceScaling(): void
    {
        if (count($this->performanceResults) < 2) {
            return;
        }
        
        // Check that performance scales reasonably
        for ($i = 1; $i < count($this->performanceResults); $i++) {
            $prev = $this->performanceResults[$i - 1];
            $curr = $this->performanceResults[$i];
            
            $fileRatio = $curr['file_count'] / $prev['file_count'];
            $timeRatio = $curr['execution_time'] / $prev['execution_time'];
            
            // Performance should scale sub-linearly due to caching and optimizations
            $this->assertLessThan($fileRatio * 1.5, $timeRatio, 'Performance should scale reasonably');
        }
    }

    /**
     * Remove directory recursively
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        
        rmdir($dir);
    }
} 