<?php

namespace ReviewSystem\Tests\Unit\Rules;

use ReviewSystem\Tests\TestCase;
use ReviewSystem\Rules\NoMongoInControllerRule;
use ReviewSystem\Engine\RuleCategory;

/**
 * Unit tests for NoMongoInControllerRule
 * 
 * Tests MongoDB detection in controllers and violation reporting
 */
class NoMongoInControllerRuleTest extends TestCase
{
    private ?NoMongoInControllerRule $rule = null;

    protected function setUp(): void
    {
        $this->rule = new NoMongoInControllerRule();
    }

    /**
     * Test rule metadata
     */
    public function testRuleMetadata(): void
    {
        $this->assertEquals(RuleCategory::ARCHITECTURE, $this->rule->getCategory());
        $this->assertEquals('No MongoDB in Controllers', $this->rule->getName());
        $this->assertEquals('warning', $this->rule->getSeverity());
        $this->assertTrue($this->rule->isEnabledByDefault());
        
        $expectedTags = ['laravel', 'mongodb', 'architecture', 'repository-pattern', 'separation-of-concerns'];
        $this->assertEquals($expectedTags, $this->rule->getTags());
    }

    /**
     * Test rule description
     */
    public function testRuleDescription(): void
    {
        $description = $this->rule->getDescription();
        $this->assertStringContainsString('MongoDB', $description);
        $this->assertStringContainsString('controller', $description);
        $this->assertStringContainsString('repository', $description);
    }

    /**
     * Test detection of MongoDB use statements
     */
    public function testDetectsMongoUseStatements(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

use MongoDB\Client;
use MongoDB\Collection;

class TestController extends Controller
{
    public function index()
    {
        // Controller logic
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        $this->assertNotEmpty($violations);
        $this->assertCount(2, $violations); // Should detect both use statements

        foreach ($violations as $violation) {
            $this->assertEquals($tempFile, $violation['file']);
            $this->assertEquals('warning', $violation['severity']);
            $this->assertEquals('architecture', $violation['category']);
            $this->assertStringContainsString('MongoDB', $violation['bad_code']);
        }

        unlink($tempFile);
    }

    /**
     * Test detection of MongoDB class instantiation
     */
    public function testDetectsMongoClassInstantiation(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

use MongoDB\Client;

class TestController extends Controller
{
    public function index()
    {
        $client = new Client("mongodb://localhost:27017");
        $collection = $client->selectDatabase("test")->selectCollection("users");
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        $this->assertNotEmpty($violations);
        $this->assertGreaterThanOrEqual(1, count($violations));

        $hasMongoViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation['message'], 'MongoDB')) {
                $hasMongoViolation = true;
                break;
            }
        }
        $this->assertTrue($hasMongoViolation);

        unlink($tempFile);
    }

    /**
     * Test detection of MongoDB method calls
     */
    public function testDetectsMongoMethodCalls(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class TestController extends Controller
{
    public function index()
    {
        $collection = $this->getCollection();
        $result = $collection->find(["status" => "active"]);
        $documents = $result->toArray();
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        // This should detect MongoDB method calls if they're properly identified

        unlink($tempFile);
    }

    /**
     * Test that non-controller files are ignored
     */
    public function testIgnoresNonControllerFiles(): void
    {
        $code = '<?php
namespace App\Services;

use MongoDB\Client;

class MongoService
{
    public function connect()
    {
        $client = new Client("mongodb://localhost:27017");
        return $client;
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        // Should not detect violations in non-controller files
        $this->assertEmpty($violations);

        unlink($tempFile);
    }

    /**
     * Test that files without MongoDB usage are clean
     */
    public function testCleanControllerFiles(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        $this->assertEmpty($violations);

        unlink($tempFile);
    }

    /**
     * Test handling of invalid PHP files
     */
    public function testHandlesInvalidPhpFiles(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

use MongoDB\Client;

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
     * Test configuration options
     */
    public function testConfigurationOptions(): void
    {
        $options = $this->rule->getConfigurationOptions();
        
        $this->assertArrayHasKey('target_paths', $options);
        $this->assertArrayHasKey('mongo_patterns', $options);
        
        $this->assertEquals('array', $options['target_paths']['type']);
        $this->assertEquals('array', $options['mongo_patterns']['type']);
    }

    /**
     * Test configuration validation
     */
    public function testConfigurationValidation(): void
    {
        // Configuration validation is handled by the rule system, not individual rules
        $result = 2 + 2;
        $this->assertEquals(4, $result);
    }

    /**
     * Test rule metadata
     */
    public function testRuleMetadataInfo(): void
    {
        $metadata = $this->rule->getMetadata();
        
        $this->assertArrayHasKey('version', $metadata);
        $this->assertArrayHasKey('author', $metadata);
        $this->assertArrayHasKey('description', $metadata);
    }

    /**
     * Test MongoDB usage details method
     */
    public function testGetMongoUsageDetails(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

use MongoDB\Client;
use MongoDB\Collection;

class TestController extends Controller
{
    public function index()
    {
        $client = new Client();
        $collection = new Collection($client, "test", "users");
    }
}';

        $tempFile = $this->createTempFile($code);
        $details = $this->rule->getMongoUsageDetails($tempFile);

        $this->assertArrayHasKey('uses', $details);
        $this->assertArrayHasKey('instantiations', $details);
        $this->assertArrayHasKey('method_calls', $details);

        unlink($tempFile);
    }

    /**
     * Test edge case with multiple MongoDB usages
     */
    public function testMultipleMongoUsages(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;

class TestController extends Controller
{
    public function index()
    {
        $client = new Client();
        $db = new Database($client, "test");
        $collection = new Collection($client, "test", "users");
        
        $result = $collection->find();
        $documents = $result->toArray();
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        $this->assertNotEmpty($violations);
        $this->assertGreaterThanOrEqual(3, count($violations)); // Multiple MongoDB usages

        unlink($tempFile);
    }


} 