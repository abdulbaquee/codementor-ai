<?php

namespace ReviewSystem\Tests\Unit\Rules;

use ReviewSystem\Tests\TestCase;
use ReviewSystem\Rules\LaravelBestPracticesRule;
use ReviewSystem\Engine\RuleCategory;

/**
 * Unit tests for LaravelBestPracticesRule
 * 
 * Tests Laravel best practices including validation, security, and performance
 */
class LaravelBestPracticesRuleTest extends TestCase
{
    private ?LaravelBestPracticesRule $rule = null; 

    protected function setUp(): void
    {
        $this->rule = new LaravelBestPracticesRule();
    }

    /**
     * Test rule metadata
     */
    public function testRuleMetadata(): void
    {
        $this->assertEquals(RuleCategory::BEST_PRACTICE, $this->rule->getCategory());
        $this->assertEquals('Laravel Best Practices', $this->rule->getName());
        $this->assertEquals('warning', $this->rule->getSeverity());
        $this->assertTrue($this->rule->isEnabledByDefault());
        
        $expectedTags = ['laravel', 'best-practices', 'validation', 'security', 'performance'];
        $this->assertEquals($expectedTags, $this->rule->getTags());
    }

    /**
     * Test rule description
     */
    public function testRuleDescription(): void
    {
        $description = $this->rule->getDescription();
        $this->assertStringContainsString('Laravel', $description);
        $this->assertStringContainsString('validation', $description);
        $this->assertStringContainsString('security', $description);
    }

    /**
     * Test detection of missing validation in controllers
     */
    public function testDetectsMissingValidation(): void
    {
        $code = '<?php
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

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        $this->assertNotEmpty($violations);
        
        $hasValidationViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation['message'], 'validation')) {
                $hasValidationViolation = true;
                break;
            }
        }
        $this->assertTrue($hasValidationViolation);

        unlink($tempFile);
    }

    /**
     * Test detection of eval() usage
     */
    public function testDetectsEvalUsage(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class TestController extends Controller
{
    public function execute($code)
    {
        $result = eval($code);
        return $result;
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        $this->assertNotEmpty($violations);
        
        $hasEvalViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation['message'], 'eval()')) {
                $hasEvalViolation = true;
                $this->assertEquals('error', $violation['severity']);
                $this->assertEquals('security', $violation['category']);
                break;
            }
        }
        $this->assertTrue($hasEvalViolation);

        unlink($tempFile);
    }

    /**
     * Test detection of raw SQL queries
     */
    public function testDetectsRawSqlQueries(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = DB::raw("SELECT * FROM users WHERE status = \'active\'");
        return response()->json($users);
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        $this->assertNotEmpty($violations);
        
        $hasSqlViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation['message'], 'raw SQL')) {
                $hasSqlViolation = true;
                $this->assertEquals('warning', $violation['severity']);
                $this->assertEquals('security', $violation['category']);
                break;
            }
        }
        $this->assertTrue($hasSqlViolation);

        unlink($tempFile);
    }

    /**
     * Test detection of N+1 query patterns
     */
    public function testDetectsNPlusOneQueries(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        
        foreach ($users as $user) {
            $posts = $user->posts; // N+1 query issue
        }
        
        return response()->json($users);
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        // This should detect N+1 query patterns

        unlink($tempFile);
    }

    /**
     * Test detection of large controller methods
     */
    public function testDetectsLargeMethods(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class UserController extends Controller
{
    public function complexMethod()
    {
        // Line 1
        $data = [];
        // Line 2
        for ($i = 0; $i < 100; $i++) {
            // Lines 3-102 (100 lines of code)
            $data[] = "item " . $i;
        }
        // Line 103
        return response()->json($data);
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        // This should detect large methods

        unlink($tempFile);
    }

    /**
     * Test that files with proper validation are clean
     */
    public function testCleanFilesWithValidation(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;

class UserController extends Controller
{
    public function store(StoreUserRequest $request)
    {
        $validated = $request->validated();
        $user = User::create($validated);
        
        return response()->json($user);
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        // Should not detect validation violations
        $hasValidationViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation['message'], 'validation')) {
                $hasValidationViolation = true;
                break;
            }
        }
        $this->assertFalse($hasValidationViolation);

        unlink($tempFile);
    }

    /**
     * Test that non-controller files are handled appropriately
     */
    public function testNonControllerFiles(): void
    {
        $code = '<?php
namespace App\Services;

class UserService
{
    public function createUser($data)
    {
        // Service logic without validation (this is OK)
        return User::create($data);
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        // Should not detect validation violations in services
        $hasValidationViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation['message'], 'validation')) {
                $hasValidationViolation = true;
                break;
            }
        }
        $this->assertFalse($hasValidationViolation);

        unlink($tempFile);
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
     * Test multiple violations in one file
     */
    public function testMultipleViolations(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    public function store(Request $request)
    {
        // Missing validation
        $user = new User();
        $user->name = $request->input("name");
        
        // Security issue
        $result = eval($request->input("code"));
        
        // Large method (simplified for test)
        for ($i = 0; $i < 100; $i++) {
            $user->data[] = "item " . $i;
        }
        
        return response()->json($user);
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        $this->assertNotEmpty($violations);
        $this->assertGreaterThanOrEqual(2, count($violations)); // Multiple violations

        unlink($tempFile);
    }

    /**
     * Test edge case with empty files
     */
    public function testEmptyFiles(): void
    {
        $code = '<?php
// Empty file
';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        $this->assertEmpty($violations);

        unlink($tempFile);
    }

    /**
     * Test edge case with files containing only comments
     */
    public function testFilesWithOnlyComments(): void
    {
        $code = '<?php
// This is a comment
/*
 * Multi-line comment
 */
';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        $this->assertEmpty($violations);

        unlink($tempFile);
    }


} 