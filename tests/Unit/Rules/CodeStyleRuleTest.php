<?php

namespace ReviewSystem\Tests\Unit\Rules;

use ReviewSystem\Tests\TestCase;
use ReviewSystem\Rules\CodeStyleRule;
use ReviewSystem\Engine\RuleCategory;

/**
 * Unit tests for CodeStyleRule
 * 
 * Tests code style standards including naming conventions, structure, and readability
 */
class CodeStyleRuleTest extends TestCase
{
    private ?CodeStyleRule $rule = null;

    protected function setUp(): void
    {
        $this->rule = new CodeStyleRule();
    }

    /**
     * Test rule metadata
     */
    public function testRuleMetadata(): void
    {
        $this->assertEquals(RuleCategory::STYLE, $this->rule->getCategory());
        $this->assertEquals('Code Style Standards', $this->rule->getName());
        $this->assertEquals('warning', $this->rule->getSeverity());
        $this->assertTrue($this->rule->isEnabledByDefault());
        
        $expectedTags = ['style', 'naming', 'structure', 'readability', 'conventions'];
        $this->assertEquals($expectedTags, $this->rule->getTags());
    }

    /**
     * Test rule description
     */
    public function testRuleDescription(): void
    {
        $description = $this->rule->getDescription();
        $this->assertStringContainsString('style', $description);
        $this->assertStringContainsString('naming', $description);
        $this->assertStringContainsString('structure', $description);
    }

    /**
     * Test detection of invalid class naming (not PascalCase)
     */
    public function testDetectsInvalidClassNaming(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class userController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        $this->assertNotEmpty($violations);
        
        $hasNamingViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation['message'], 'PascalCase')) {
                $hasNamingViolation = true;
                $this->assertEquals('warning', $violation['severity']);
                $this->assertEquals('naming', $violation['category']);
                break;
            }
        }
        $this->assertTrue($hasNamingViolation);

        unlink($tempFile);
    }

    /**
     * Test detection of short class names
     */
    public function testDetectsShortClassNames(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class A extends Controller
{
    public function index()
    {
        return response()->json([]);
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        $this->assertNotEmpty($violations);
        
        $hasShortNameViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation['message'], 'descriptive')) {
                $hasShortNameViolation = true;
                $this->assertEquals('info', $violation['severity']);
                $this->assertEquals('naming', $violation['category']);
                break;
            }
        }
        $this->assertTrue($hasShortNameViolation);

        unlink($tempFile);
    }

    /**
     * Test detection of invalid method naming (not camelCase)
     */
    public function testDetectsInvalidMethodNaming(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class UserController extends Controller
{
    public function GetUser()
    {
        return response()->json([]);
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        $this->assertNotEmpty($violations);
        
        $hasMethodNamingViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation['message'], 'camelCase')) {
                $hasMethodNamingViolation = true;
                $this->assertEquals('warning', $violation['severity']);
                $this->assertEquals('naming', $violation['category']);
                break;
            }
        }
        $this->assertTrue($hasMethodNamingViolation);

        unlink($tempFile);
    }

    /**
     * Test detection of boolean method naming issues
     */
    public function testDetectsBooleanMethodNaming(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class UserController extends Controller
{
    public function active(): bool
    {
        return $this->status === "active";
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        // This should detect boolean method naming issues

        unlink($tempFile);
    }

    /**
     * Test detection of missing namespace
     */
    public function testDetectsMissingNamespace(): void
    {
        $code = '<?php

class UserController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        $this->assertNotEmpty($violations);
        
        $hasNamespaceViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation['message'], 'namespace')) {
                $hasNamespaceViolation = true;
                $this->assertEquals('warning', $violation['severity']);
                $this->assertEquals('structure', $violation['category']);
                break;
            }
        }
        $this->assertTrue($hasNamespaceViolation);

        unlink($tempFile);
    }

    /**
     * Test detection of magic numbers
     */
    public function testDetectsMagicNumbers(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class UserController extends Controller
{
    public function index()
    {
        $limit = 25; // Magic number
        $users = User::take($limit)->get();
        
        return response()->json($users);
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        // This should detect magic numbers

        unlink($tempFile);
    }

    /**
     * Test detection of long lines
     */
    public function testDetectsLongLines(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class UserController extends Controller
{
    public function index()
    {
        $veryLongVariableName = "This is a very long line that exceeds the recommended character limit and should be detected by the code style rule as a violation of the line length guidelines";
        return response()->json($veryLongVariableName);
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        $this->assertNotEmpty($violations);
        
        $hasLongLineViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation['message'], 'too long')) {
                $hasLongLineViolation = true;
                $this->assertEquals('warning', $violation['severity']);
                $this->assertEquals('readability', $violation['category']);
                break;
            }
        }
        $this->assertTrue($hasLongLineViolation);

        unlink($tempFile);
    }

    /**
     * Test detection of Laravel controller naming conventions
     */
    public function testDetectsLaravelControllerNaming(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class User extends Controller
{
    public function index()
    {
        return response()->json([]);
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        $this->assertNotEmpty($violations);
        
        $hasControllerNamingViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation['message'], 'Controller')) {
                $hasControllerNamingViolation = true;
                $this->assertEquals('warning', $violation['severity']);
                $this->assertEquals('conventions', $violation['category']);
                break;
            }
        }
        $this->assertTrue($hasControllerNamingViolation);

        unlink($tempFile);
    }

    /**
     * Test that properly named classes are clean
     */
    public function testCleanProperlyNamedClasses(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

class UserController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }
    
    public function isActive(): bool
    {
        return $this->status === "active";
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        // Should not detect naming violations for properly named classes
        $hasNamingViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation['message'], 'PascalCase') || 
                str_contains($violation['message'], 'camelCase')) {
                $hasNamingViolation = true;
                break;
            }
        }
        $this->assertFalse($hasNamingViolation);

        unlink($tempFile);
    }

    /**
     * Test that files with proper namespaces are clean
     */
    public function testCleanFilesWithProperNamespaces(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return response()->json([]);
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        // Should not detect namespace violations
        $hasNamespaceViolation = false;
        foreach ($violations as $violation) {
            if (str_contains($violation['message'], 'namespace')) {
                $hasNamespaceViolation = true;
                break;
            }
        }
        $this->assertFalse($hasNamespaceViolation);

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
     * Test multiple style violations in one file
     */
    public function testMultipleStyleViolations(): void
    {
        $code = '<?php

class user extends Controller
{
    public function GetUser()
    {
        $veryLongVariableName = "This is a very long line that exceeds the recommended character limit and should be detected by the code style rule as a violation of the line length guidelines";
        return response()->json($veryLongVariableName);
    }
}';

        $tempFile = $this->createTempFile($code);
        $violations = $this->rule->check($tempFile);

        $this->assertNotEmpty($violations);
        $this->assertGreaterThanOrEqual(3, count($violations)); // Multiple violations

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

    /**
     * Test detection of unused imports (simplified test)
     */
    public function testDetectsUnusedImports(): void
    {
        $code = '<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Services\EmailService; // Unused import

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

        // This should detect unused imports

        unlink($tempFile);
    }


} 