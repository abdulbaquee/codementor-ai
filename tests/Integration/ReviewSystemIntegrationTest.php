<?php

namespace ReviewSystem\Tests\Integration;

use ReviewSystem\Tests\TestCase;
use ReviewSystem\Engine\RuleRunner;

use ReviewSystem\Engine\FileScanner;
use ReviewSystem\Engine\ReportWriter;
use ReviewSystem\Engine\ProgressIndicator;
use ReviewSystem\Engine\ErrorHandler;

/**
 * Integration tests for the complete review system workflow
 * 
 * Tests the entire system from configuration loading to report generation
 */
class ReviewSystemIntegrationTest extends TestCase
{
    private string $testDir = '';
    private string $configFile = '';
    private string $reportDir = '';

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/review-system-test-' . uniqid();
        $this->configFile = $this->testDir . '/config.php';
        $this->reportDir = $this->testDir . '/reports';
        
        // Create test directory structure with proper Laravel structure
        mkdir($this->testDir, 0755, true);
        mkdir($this->testDir . '/app', 0755, true);
        mkdir($this->testDir . '/app/Http', 0755, true);
        mkdir($this->testDir . '/app/Http/Controllers', 0755, true);
        mkdir($this->reportDir, 0755, true);
        
        // Clear any previous errors
        ErrorHandler::clear();
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $this->removeDirectory($this->testDir);
    }

    /**
     * Test complete workflow from configuration to report generation
     */
    public function testCompleteWorkflow(): void
    {
        // 1. Create test configuration
        $this->createTestConfiguration();
        
        // 2. Create test files with violations
        $this->createTestFiles();
        
        // 3. Load test configuration directly
        $config = require $this->configFile;
        
        // 4. Initialize components
        $fileScanner = new FileScanner($config['file_scanner']);
        $progressIndicator = new ProgressIndicator();
        $ruleRunner = new RuleRunner($config);
        
        // 5. Set up progress callback
        $ruleRunner->setProgressCallback(function(int $step, int $total, string $message) use ($progressIndicator) {
            $progressIndicator->update($step, $message);
        });
        
        // 6. Run the review process
        $violations = $ruleRunner->run();
        
        // 7. Verify results
        $this->assertGreaterThan(0, count($violations), 'Should detect violations in test files');
        
        // 8. Generate report
        $reportWriter = new ReportWriter($config);
        
        $reportPath = $reportWriter->writeHtml($violations);
        
        // 9. Verify report was generated
        $this->assertFileExists($reportPath);
        $this->assertStringContainsString('.html', $reportPath);
        
        // 10. Verify report content
        $reportContent = file_get_contents($reportPath);
        $this->assertStringContainsString('violations', $reportContent);
        $this->assertStringContainsString('MongoDB', $reportContent);
    }

    /**
     * Test system with different rule configurations
     */
    public function testDifferentRuleConfigurations(): void
    {
        // Test with only MongoDB rule
        $this->createTestConfiguration(['ReviewSystem\Rules\NoMongoInControllerRule']);
        $this->createTestFiles();
        
        $config = require $this->configFile;
        $ruleRunner = new RuleRunner($config);
        
        $violations = $ruleRunner->run();
        
        $this->assertGreaterThan(0, count($violations));
        

        
        // Verify only MongoDB violations are detected
        foreach ($violations as $violation) {
            $this->assertStringContainsString('MongoDB', $violation['message']);
        }
    }

    /**
     * Test error handling in complete workflow
     */
    public function testErrorHandlingInWorkflow(): void
    {
        // Create configuration with invalid rule
        $this->createTestConfiguration(['NonExistentRule']);
        $this->createTestFiles();
        
        $config = require $this->configFile;
        $ruleRunner = new RuleRunner($config);
        
        // Should handle invalid rules gracefully
        $violations = $ruleRunner->run();
        
        // Check for error handling
        $errors = ErrorHandler::getErrors();
    }

    /**
     * Test performance with large number of files
     */
    public function testPerformanceWithManyFiles(): void
    {
        // Create many test files
        $this->createTestConfiguration();
        $this->createManyTestFiles(50);
        
        $config = require $this->configFile;
        $ruleRunner = new RuleRunner($config);
        
        $startTime = microtime(true);
        $violations = $ruleRunner->run();
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        
        $this->assertLessThan(30, $executionTime, 'Should complete within 30 seconds');
        $this->assertGreaterThan(0, count($violations), 'Should detect violations');
    }

    /**
     * Test file scanning with different patterns
     */
    public function testFileScanningPatterns(): void
    {
        $this->createTestConfiguration();
        $this->createTestFilesWithDifferentExtensions();
        
        $config = require $this->configFile;
        $fileScanner = new FileScanner($config['file_scanner']);
        
        $files = $fileScanner->scan($config['scan_paths']);
        
        $this->assertGreaterThan(0, count($files));
        
        // Should only include PHP files
        foreach ($files as $file) {
            $this->assertStringEndsWith('.php', $file);
        }
    }

    /**
     * Test report generation with different formats
     */
    public function testReportGenerationFormats(): void
    {
        $this->createTestConfiguration();
        $this->createTestFiles();
        
        $config = require $this->configFile;
        $ruleRunner = new RuleRunner($config);
        
        $violations = $ruleRunner->run();
        
        $reportWriter = new ReportWriter($config);
        
        // Test HTML report
        $htmlReport = $reportWriter->writeHtml($violations);
        $this->assertFileExists($htmlReport);
        $this->assertStringContainsString('.html', $htmlReport);
        
        // Verify HTML content
        $htmlContent = file_get_contents($htmlReport);
        $this->assertStringContainsString('<html', $htmlContent);
        $this->assertStringContainsString('violations', $htmlContent);
    }

    /**
     * Test configuration validation in workflow
     */
    public function testConfigurationValidation(): void
    {
        // Create invalid configuration
        $this->createInvalidConfiguration();
        
        // Should handle invalid configuration gracefully
        $config = require $this->configFile;
        
        $this->assertArrayHasKey('scan_paths', $config);
        $this->assertArrayHasKey('rules', $config);
    }

    /**
     * Test progress indicator integration
     */
    public function testProgressIndicatorIntegration(): void
    {
        $this->createTestConfiguration();
        $this->createTestFiles();
        
        $config = require $this->configFile;
        $ruleRunner = new RuleRunner($config);
        
        $progressUpdates = [];
        $ruleRunner->setProgressCallback(function(int $step, int $total, string $message) use (&$progressUpdates) {
            $progressUpdates[] = [
                'step' => $step,
                'total' => $total,
                'message' => $message
            ];
        });
        
        $violations = $ruleRunner->run();
        
        $this->assertGreaterThan(0, count($progressUpdates), 'Should receive progress updates');
        
        // Verify progress updates
        foreach ($progressUpdates as $update) {
            $this->assertGreaterThan(0, $update['total']);
            $this->assertGreaterThanOrEqual(0, $update['step']);
            $this->assertLessThanOrEqual($update['total'], $update['step']);
        }
    }

    /**
     * Test memory usage during large scans
     */
    public function testMemoryUsage(): void
    {
        $this->createTestConfiguration();
        $this->createManyTestFiles(100);
        
        $initialMemory = memory_get_usage();
        
        $config = require $this->configFile;
        $ruleRunner = new RuleRunner($config);
        
        $violations = $ruleRunner->run();
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        
        $this->assertLessThan(100 * 1024 * 1024, $memoryIncrease, 'Memory increase should be reasonable (< 100MB)');
    }

    /**
     * Test concurrent rule execution
     */
    public function testConcurrentRuleExecution(): void
    {
        $this->createTestConfiguration();
        $this->createTestFiles();
        
        $config = require $this->configFile;
        
        // Run multiple instances
        $results = [];
        for ($i = 0; $i < 3; $i++) {
            $ruleRunner = new RuleRunner($config);
            $results[] = $ruleRunner->run();
        }
        
        // All results should be consistent
        foreach ($results as $result) {
            $this->assertGreaterThan(0, count($result));
        }
        
        // Results should be identical (deterministic)
        $this->assertEquals($results[0], $results[1]);
        $this->assertEquals($results[1], $results[2]);
    }

    /**
     * Create test configuration file
     */
    private function createTestConfiguration(?array $rules = null): void
    {
        $defaultRules = [
            'ReviewSystem\Rules\NoMongoInControllerRule',
            'ReviewSystem\Rules\LaravelBestPracticesRule',
            'ReviewSystem\Rules\CodeStyleRule'
        ];
        
        $rules = $rules ?? $defaultRules;
        
        $config = [
            'scan_paths' => [$this->testDir],
            'file_scanner' => [
                'include_patterns' => ['*.php'],
                'exclude_patterns' => ['*/vendor/*', '*/tests/*'],
                'max_file_size' => 1024 * 1024,
                'follow_symlinks' => false
            ],
            'reporting' => [
                'output_path' => $this->reportDir,
                'filename_format' => 'test-report-{timestamp}.html',
                'exit_on_violation' => false,
                'html' => [
                    'title' => 'Test Code Review Report',
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
            'rules' => $rules
        ];
        
        file_put_contents($this->configFile, '<?php return ' . var_export($config, true) . ';');
    }

    /**
     * Create invalid configuration file
     */
    private function createInvalidConfiguration(): void
    {
        $config = [
            'scan_paths' => 'invalid_path',
            'rules' => 'invalid_rules'
        ];
        
        file_put_contents($this->configFile, '<?php return ' . var_export($config, true) . ';');
    }

    /**
     * Create test files with violations
     */
    private function createTestFiles(): void
    {
        // Create controller with MongoDB usage
        $mongoController = '<?php
namespace App\Http\Controllers;

use MongoDB\Client;
use MongoDB\Collection;

class TestMongoController extends Controller
{
    public function index()
    {
        $client = new Client("mongodb://localhost:27017");
        $collection = new Collection($client, "test", "users");
        return response()->json($collection->find());
    }
}';
        
        file_put_contents($this->testDir . '/app/Http/Controllers/TestMongoController.php', $mongoController);
        
        // Create controller with validation issues
        $validationController = '<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function store(Request $request)
    {
        $user = new User();
        $user->name = $request->input("name");
        $user->email = $request->input("email");
        $user->save();
        
        return response()->json($user);
    }
}';
        
        file_put_contents($this->testDir . '/app/Http/Controllers/UserController.php', $validationController);
        
        // Create file with style issues
        $styleFile = '<?php
namespace App\Http\Controllers;

class userController extends Controller
{
    public function GetUser()
    {
        $veryLongVariableName = "This is a very long line that exceeds the recommended character limit and should be detected by the code style rule as a violation of the line length guidelines";
        return response()->json($veryLongVariableName);
    }
}';
        
        file_put_contents($this->testDir . '/app/Http/Controllers/userController.php', $styleFile);
    }

    /**
     * Create many test files for performance testing
     */
    private function createManyTestFiles(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $content = '<?php
namespace App\Http\Controllers;

use MongoDB\Client;

class TestController' . $i . ' extends Controller
{
    public function index()
    {
        $client = new Client();
        return response()->json(["test" => ' . $i . ']);
    }
}';
            
            file_put_contents($this->testDir . "/app/Http/Controllers/TestController{$i}.php", $content);
        }
    }

    /**
     * Create test files with different extensions
     */
    private function createTestFilesWithDifferentExtensions(): void
    {
        // PHP file
        file_put_contents($this->testDir . '/test.php', '<?php echo "test";');
        
        // Non-PHP file (should be ignored)
        file_put_contents($this->testDir . '/test.txt', 'This is not PHP');
        
        // PHP file in subdirectory
        mkdir($this->testDir . '/subdir', 0755, true);
        file_put_contents($this->testDir . '/subdir/test.php', '<?php echo "subdir test";');
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