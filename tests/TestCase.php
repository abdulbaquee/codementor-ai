<?php

namespace ReviewSystem\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base test case for CodeMentor AI tests
 */
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure autoloading is available
        if (!class_exists('ReviewSystem\Engine\ConfigurationLoader')) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }
    }
    
    /**
     * Create a temporary file for testing
     */
    protected function createTempFile(string $content): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'codementor_test_');
        file_put_contents($tempFile, $content);
        return $tempFile;
    }
    
    /**
     * Clean up temporary files
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
