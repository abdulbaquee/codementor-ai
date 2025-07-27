<?php

namespace ReviewSystem\Tests\Integration;

use ReviewSystem\Tests\TestCase;

/**
 * End-to-end workflow tests for the review system
 * 
 * Tests the complete user experience from CLI execution to report generation
 */
class EndToEndWorkflowTest extends TestCase
{
    private string $testDir;
    private string $originalConfig;

    protected function setUp(): void
    {
        $this->testDir = sys_get_temp_dir() . '/review-system-e2e-' . uniqid();
        $this->originalConfig = __DIR__ . '/../../review-system/config.php';
        
        // Create test directory structure
        mkdir($this->testDir, 0755, true);
        mkdir($this->testDir . '/app/Http/Controllers', 0755, true);
        mkdir($this->testDir . '/routes', 0755, true);
        
        // Backup original config
        if (file_exists($this->originalConfig)) {
            copy($this->originalConfig, $this->originalConfig . '.backup');
        }
    }

    protected function tearDown(): void
    {
        // Restore original config
        if (file_exists($this->originalConfig . '.backup')) {
            copy($this->originalConfig . '.backup', $this->originalConfig);
            unlink($this->originalConfig . '.backup');
        }
        
        // Clean up test files
        $this->removeDirectory($this->testDir);
    }

    /**
     * Test complete CLI workflow
     */
    public function testCliWorkflow(): void
    {
        // 1. Create test Laravel project structure
        $this->createLaravelProjectStructure();
        
        // 2. Create test configuration
        $this->createTestConfiguration();
        
        // 3. Execute CLI command
        $output = $this->executeCliCommand();
        
        // 4. Verify CLI output
        $this->assertStringContainsString('Code Review Process', $output);
        $this->assertStringContainsString('Files Scanned', $output);
        $this->assertStringContainsString('Total Violations', $output);
        $this->assertStringContainsString('Report saved to', $output);
        
        // 5. Verify report was generated
        $reportFiles = glob($this->testDir . '/review-system/reports/*.html');
        $this->assertNotEmpty($reportFiles, 'Report file should be generated');
        
        // 6. Verify report content
        $reportContent = file_get_contents($reportFiles[0]);
        $this->assertStringContainsString('violations', $reportContent);
        $this->assertStringContainsString('MongoDB', $reportContent);
    }

    /**
     * Test workflow with different rule combinations
     */
    public function testRuleCombinationWorkflow(): void
    {
        $this->createLaravelProjectStructure();
        
        // Test with only MongoDB rule
        $this->createTestConfiguration(['NoMongoInControllerRule']);
        $output = $this->executeCliCommand();
        
        $this->assertStringContainsString('Total Violations', $output);
        
        // Test with only Laravel best practices rule
        $this->createTestConfiguration(['LaravelBestPracticesRule']);
        $output = $this->executeCliCommand();
        
        $this->assertStringContainsString('Total Violations', $output);
        
        // Test with only code style rule
        $this->createTestConfiguration(['CodeStyleRule']);
        $output = $this->executeCliCommand();
        
        $this->assertStringContainsString('Total Violations', $output);
    }

    /**
     * Test workflow with error handling
     */
    public function testErrorHandlingWorkflow(): void
    {
        $this->createLaravelProjectStructure();
        
        // Create configuration with invalid rule
        $this->createTestConfiguration(['NonExistentRule']);
        $output = $this->executeCliCommand();
        
        // Should handle errors gracefully
        $this->assertStringContainsString('Code Review Process', $output);
        $this->assertStringContainsString('Warnings', $output);
    }

    /**
     * Test workflow with performance monitoring
     */
    public function testPerformanceWorkflow(): void
    {
        $this->createLaravelProjectStructure();
        $this->createManyTestFiles(20);
        
        $startTime = microtime(true);
        $output = $this->executeCliCommand();
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        
        $this->assertStringContainsString('Performance Metrics', $output);
        $this->assertStringContainsString('Total time', $output);
        $this->assertLessThan(60, $executionTime, 'Should complete within 60 seconds');
    }

    /**
     * Test workflow with different file types
     */
    public function testFileTypeWorkflow(): void
    {
        $this->createLaravelProjectStructure();
        
        // Create files with different extensions
        file_put_contents($this->testDir . '/app/test.txt', 'This is not PHP');
        file_put_contents($this->testDir . '/app/test.js', 'console.log("JavaScript");');
        file_put_contents($this->testDir . '/app/test.php', '<?php echo "PHP file";');
        
        $output = $this->executeCliCommand();
        
        $this->assertStringContainsString('Files Scanned', $output);
        $this->assertStringContainsString('Total Violations', $output);
    }

    /**
     * Test workflow with empty project
     */
    public function testEmptyProjectWorkflow(): void
    {
        $this->createLaravelProjectStructure();
        
        // Don't create any test files
        $output = $this->executeCliCommand();
        
        $this->assertStringContainsString('Code Review Process', $output);
        $this->assertStringContainsString('Files Scanned', $output);
        $this->assertStringContainsString('Total Violations: 0', $output);
    }

    /**
     * Test workflow with large files
     */
    public function testLargeFileWorkflow(): void
    {
        $this->createLaravelProjectStructure();
        
        // Create a large PHP file
        $largeContent = '<?php' . PHP_EOL;
        for ($i = 0; $i < 1000; $i++) {
            $largeContent .= "class LargeClass{$i} {\n";
            $largeContent .= "    public function method{$i}() {\n";
            $largeContent .= "        return 'large file content';\n";
            $largeContent .= "    }\n";
            $largeContent .= "}\n\n";
        }
        
        file_put_contents($this->testDir . '/app/Http/Controllers/LargeController.php', $largeContent);
        
        $output = $this->executeCliCommand();
        
        $this->assertStringContainsString('Code Review Process', $output);
        $this->assertStringContainsString('Files Scanned', $output);
    }

    /**
     * Test workflow with nested directories
     */
    public function testNestedDirectoryWorkflow(): void
    {
        $this->createLaravelProjectStructure();
        
        // Create nested directory structure
        mkdir($this->testDir . '/app/Http/Controllers/Api/V1', 0755, true);
        mkdir($this->testDir . '/app/Services/External', 0755, true);
        
        // Create files in nested directories
        file_put_contents($this->testDir . '/app/Http/Controllers/Api/V1/UserController.php', 
            '<?php namespace App\Http\Controllers\Api\V1; class UserController {}');
        file_put_contents($this->testDir . '/app/Services/External/PaymentService.php', 
            '<?php namespace App\Services\External; class PaymentService {}');
        
        $output = $this->executeCliCommand();
        
        $this->assertStringContainsString('Files Scanned', $output);
        $this->assertStringContainsString('Total Violations', $output);
    }

    /**
     * Create Laravel project structure
     */
    private function createLaravelProjectStructure(): void
    {
        // Create basic Laravel structure
        $directories = [
            'app/Http/Controllers',
            'app/Models',
            'app/Services',
            'routes',
            'database/migrations',
            'resources/views',
            'config',
            'storage/logs',
            'bootstrap/cache'
        ];
        
        foreach ($directories as $dir) {
            mkdir($this->testDir . '/' . $dir, 0755, true);
        }
        
        // Create test files with violations
        $this->createTestFiles();
    }

    /**
     * Create test files with violations
     */
    private function createTestFiles(): void
    {
        // MongoDB controller
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
        
        // Controller with validation issues
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
        
        // Controller with style issues
        $styleController = '<?php
namespace App\Http\Controllers;

class userController extends Controller
{
    public function GetUser()
    {
        $veryLongVariableName = "This is a very long line that exceeds the recommended character limit and should be detected by the code style rule as a violation of the line length guidelines";
        return response()->json($veryLongVariableName);
    }
}';
        
        file_put_contents($this->testDir . '/app/Http/Controllers/userController.php', $styleController);
        
        // Routes file
        $routesFile = '<?php
use Illuminate\Support\Facades\Route;

Route::get("/users", [UserController::class, "index"]);
Route::post("/users", [UserController::class, "store"]);
Route::get("/test", [TestMongoController::class, "index"]);
';
        
        file_put_contents($this->testDir . '/routes/web.php', $routesFile);
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
     * Create test configuration
     */
    private function createTestConfiguration(array $rules = null): void
    {
        $defaultRules = [
            'ReviewSystem\Rules\NoMongoInControllerRule',
            'ReviewSystem\Rules\LaravelBestPracticesRule',
            'ReviewSystem\Rules\CodeStyleRule'
        ];
        
        $rules = $rules ?? $defaultRules;
        
        $config = [
            'scan_paths' => [
                $this->testDir . '/app',
                $this->testDir . '/routes'
            ],
            'file_scanner' => [
                'include_patterns' => ['*.php'],
                'exclude_patterns' => ['*/vendor/*', '*/tests/*'],
                'max_file_size' => 1024 * 1024,
                'follow_symlinks' => false
            ],
            'reporting' => [
                'output_format' => 'html',
                'output_directory' => $this->testDir . '/review-system/reports',
                'include_timestamp' => true,
                'include_statistics' => true
            ],
            'rules' => $rules
        ];
        
        // Create review-system directory
        mkdir($this->testDir . '/review-system', 0755, true);
        mkdir($this->testDir . '/review-system/reports', 0755, true);
        
        file_put_contents($this->testDir . '/review-system/config.php', '<?php return ' . var_export($config, true) . ';');
    }

    /**
     * Execute CLI command
     */
    private function executeCliCommand(): string
    {
        // Change to test directory
        $originalDir = getcwd();
        chdir($this->testDir);
        
        // Execute CLI command
        $command = 'php review-system/cli.php 2>&1';
        $output = shell_exec($command);
        
        // Restore original directory
        chdir($originalDir);
        
        return $output ?? '';
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