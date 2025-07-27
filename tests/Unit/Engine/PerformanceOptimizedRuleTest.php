<?php

namespace ReviewSystem\Tests\Unit\Engine;

use ReviewSystem\Tests\TestCase;
use ReviewSystem\Engine\PerformanceOptimizedRule;
use ReviewSystem\Engine\RuleCategory;

/**
 * Mock rule class for testing PerformanceOptimizedRule
 */
class MockPerformanceRule extends PerformanceOptimizedRule
{
    public function getCategory(): string
    {
        return RuleCategory::STYLE;
    }

    public function getName(): string
    {
        return 'Mock Performance Rule';
    }

    public function getDescription(): string
    {
        return 'Mock rule for testing performance optimizations';
    }

    public function getSeverity(): string
    {
        return 'warning';
    }

    public function getTags(): array
    {
        return ['test', 'performance'];
    }

    public function isEnabledByDefault(): bool
    {
        return true;
    }

    protected function performChecks(array $ast, string $filePath): array
    {
        // Mock implementation that returns a simple violation
        return [
            [
                'file' => $filePath,
                'line' => 1,
                'message' => 'Mock violation',
                'bad_code' => 'test',
                'suggested_fix' => 'Fix test',
                'severity' => 'warning',
                'category' => 'test'
            ]
        ];
    }
}

/**
 * Unit tests for PerformanceOptimizedRule
 * 
 * Tests caching, performance tracking, and optimization features
 */
class PerformanceOptimizedRuleTest extends TestCase
{
    private MockPerformanceRule $rule;

    protected function setUp(): void
    {
        $this->rule = new MockPerformanceRule();
        PerformanceOptimizedRule::clearCache();
    }

    protected function tearDown(): void
    {
        PerformanceOptimizedRule::clearCache();
    }

    /**
     * Test rule metadata
     */
    public function testRuleMetadata(): void
    {
        $this->assertEquals(RuleCategory::STYLE, $this->rule->getCategory());
        $this->assertEquals('Mock Performance Rule', $this->rule->getName());
        $this->assertEquals('warning', $this->rule->getSeverity());
        $this->assertTrue($this->rule->isEnabledByDefault());
        
        $expectedTags = ['test', 'performance'];
        $this->assertEquals($expectedTags, $this->rule->getTags());
    }

    /**
     * Test file parsing with caching
     */
    public function testParseFileWithCache(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class TestController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }
}';

        $tempFile = $this->createTempFile($code);
        
        // Test through the check method which uses parseFileWithCache internally
        $violations1 = $this->rule->check($tempFile);
        $this->assertIsArray($violations1);
        
        // Second check should use cache
        $violations2 = $this->rule->check($tempFile);
        $this->assertIsArray($violations2);
        $this->assertEquals($violations1, $violations2);
        
        unlink($tempFile);
    }

    /**
     * Test cache invalidation on file modification
     */
    public function testCacheInvalidationOnFileModification(): void
    {
        $code1 = '<?php
namespace App\Http\Controllers;

class TestController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }
}';

        $tempFile = $this->createTempFile($code1);
        
        // First check
        $violations1 = $this->rule->check($tempFile);
        
        // Modify file
        $code2 = '<?php
namespace App\Http\Controllers;

class TestController extends Controller
{
    public function index()
    {
        return response()->json(["modified" => true]);
    }
}';
        
        file_put_contents($tempFile, $code2);
        
        // Second check should handle modification
        $violations2 = $this->rule->check($tempFile);
        
        $this->assertIsArray($violations1);
        $this->assertIsArray($violations2);
        
        unlink($tempFile);
    }

    /**
     * Test performance tracking
     */
    public function testPerformanceTracking(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class TestController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }
}';

        $tempFile = $this->createTempFile($code);
        
        // Check file to trigger performance tracking
        $this->rule->check($tempFile);
        
        $metrics = PerformanceOptimizedRule::getPerformanceMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey(MockPerformanceRule::class, $metrics);
        $this->assertArrayHasKey('total_time', $metrics[MockPerformanceRule::class]);
        
        unlink($tempFile);
    }

    /**
     * Test cache statistics
     */
    public function testCacheStats(): void
    {
        $stats = PerformanceOptimizedRule::getCacheStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('size', $stats);
        $this->assertArrayHasKey('max_size', $stats);
        $this->assertArrayHasKey('ttl', $stats);
        $this->assertArrayHasKey('hit_rate', $stats);
        
        $this->assertEquals(0, $stats['size']);
        $this->assertEquals(100, $stats['max_size']);
        $this->assertEquals(300, $stats['ttl']);
    }

    /**
     * Test cache clearing
     */
    public function testCacheClearing(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class TestController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }
}';

        $tempFile = $this->createTempFile($code);
        
        // Add something to cache
        $this->rule->check($tempFile);
        
        $statsBefore = PerformanceOptimizedRule::getCacheStats();
        $this->assertGreaterThan(0, $statsBefore['size']);
        
        // Clear cache
        PerformanceOptimizedRule::clearCache();
        
        $statsAfter = PerformanceOptimizedRule::getCacheStats();
        $this->assertEquals(0, $statsAfter['size']);
        
        unlink($tempFile);
    }

    /**
     * Test file checking with performance monitoring
     */
    public function testFileCheckingWithPerformanceMonitoring(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class TestController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }
}';

        $tempFile = $this->createTempFile($code);
        
        $violations = $this->rule->check($tempFile);
        
        $this->assertIsArray($violations);
        $this->assertCount(1, $violations);
        $this->assertEquals($tempFile, $violations[0]['file']);
        $this->assertEquals('Mock violation', $violations[0]['message']);
        
        // Check performance metrics
        $metrics = PerformanceOptimizedRule::getPerformanceMetrics();
        $this->assertArrayHasKey(MockPerformanceRule::class, $metrics);
        $this->assertArrayHasKey('total_time', $metrics[MockPerformanceRule::class]);
        
        unlink($tempFile);
    }

    /**
     * Test handling of non-existent files
     */
    public function testHandlesNonExistentFiles(): void
    {
        $violations = $this->rule->check('/path/to/nonexistent/file.php');
        $this->assertEmpty($violations);
    }

    /**
     * Test handling of invalid PHP files
     */
    public function testHandlesInvalidPhpFiles(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class TestController extends Controller
{
    public function index()
    {
        // Invalid PHP syntax
        if (true {
            echo "missing closing brace";
    }
}';

        $tempFile = $this->createTempFile($code);
        
        $violations = $this->rule->check($tempFile);
        
        // Should handle parsing errors gracefully
        $this->assertIsArray($violations);
        
        unlink($tempFile);
    }

    /**
     * Test multiple rule instances share cache
     */
    public function testMultipleRuleInstancesShareCache(): void
    {
        $rule1 = new MockPerformanceRule();
        $rule2 = new MockPerformanceRule();
        
        $code = '<?php
namespace App\Http\Controllers;

class TestController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }
}';

        $tempFile = $this->createTempFile($code);
        
        // Check with first rule
        $violations1 = $rule1->check($tempFile);
        
        // Check with second rule (should use cache)
        $violations2 = $rule2->check($tempFile);
        
        $this->assertEquals($violations1, $violations2);
        
        unlink($tempFile);
    }

    /**
     * Test performance metrics aggregation
     */
    public function testPerformanceMetricsAggregation(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class TestController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }
}';

        $tempFile = $this->createTempFile($code);
        
        // Multiple checks to accumulate metrics
        for ($i = 0; $i < 3; $i++) {
            $this->rule->check($tempFile);
        }
        
        $metrics = PerformanceOptimizedRule::getPerformanceMetrics();
        $ruleMetrics = $metrics[MockPerformanceRule::class];
        
        $this->assertArrayHasKey('parse_time', $ruleMetrics);
        $this->assertArrayHasKey('total_time', $ruleMetrics);
        
        // Should have multiple entries for each metric
        $this->assertGreaterThan(1, count($ruleMetrics['parse_time']));
        $this->assertGreaterThan(1, count($ruleMetrics['total_time']));
        
        unlink($tempFile);
    }



    /**
     * Helper method to generate large file content for testing
     */
    private function generateLargeFileContent(): string
    {
        $content = "<?php\n";
        $content .= "namespace App\Http\Controllers;\n\n";
        $content .= "class TestController extends Controller\n";
        $content .= "{\n";
        
        // Generate many lines to test chunked processing
        for ($i = 0; $i < 1000; $i++) {
            $content .= "    public function method{$i}()\n";
            $content .= "    {\n";
            $content .= "        // This is line {$i} with some violation content\n";
            $content .= "        return 'result';\n";
            $content .= "    }\n\n";
        }
        
        $content .= "}\n";
        
        return $content;
    }
} 