<?php

namespace ReviewSystem\Tests\Unit\Engine;

use ReviewSystem\Tests\TestCase;
use ReviewSystem\Engine\ErrorHandler;

/**
 * Unit tests for ErrorHandler
 * 
 * Tests error handling, categorization, and reporting functionality
 */
class ErrorHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        ErrorHandler::clear();
    }

    protected function tearDown(): void
    {
        ErrorHandler::clear();
    }

    /**
     * Test handling parsing errors
     */
    public function testHandleParsingError(): void
    {
        $filePath = '/path/to/test.php';
        $error = new \PhpParser\Error('Syntax error, unexpected T_STRING');
        
        $violation = ErrorHandler::handleParsingError($filePath, $error);
        
        $this->assertIsArray($violation);
        $this->assertEquals($filePath, $violation['file']);
        $this->assertEquals(1, $violation['line']);
        $this->assertStringContainsString('PHP parsing error', $violation['message']);
        $this->assertEquals('Invalid PHP syntax', $violation['bad_code']);
        $this->assertEquals('error', $violation['severity']);
        $this->assertEquals('parsing', $violation['category']);
    }

    /**
     * Test handling nullable type errors
     */
    public function testHandleNullableTypeError(): void
    {
        $filePath = '/path/to/test.php';
        $error = new \Error('Call to undefined method PhpParser\Node\NullableType::toString()');
        
        $violation = ErrorHandler::handleNullableTypeError($filePath, $error);
        
        $this->assertIsArray($violation);
        $this->assertEquals($filePath, $violation['file']);
        $this->assertEquals(1, $violation['line']);
        $this->assertStringContainsString('Type handling issue', $violation['message']);
        $this->assertEquals('Nullable type access', $violation['bad_code']);
        $this->assertEquals('warning', $violation['severity']);
        $this->assertEquals('validation', $violation['category']);
    }

    /**
     * Test handling file access errors
     */
    public function testHandleFileAccessError(): void
    {
        $filePath = '/path/to/test.php';
        $error = new \Exception('Permission denied');
        
        $violation = ErrorHandler::handleFileAccessError($filePath, $error);
        
        $this->assertIsArray($violation);
        $this->assertEquals($filePath, $violation['file']);
        $this->assertEquals(1, $violation['line']);
        $this->assertStringContainsString('Cannot access file', $violation['message']);
        $this->assertEquals('File access denied', $violation['bad_code']);
        $this->assertEquals('error', $violation['severity']);
        $this->assertEquals('file_access', $violation['category']);
    }

    /**
     * Test handling memory errors
     */
    public function testHandleMemoryError(): void
    {
        $filePath = '/path/to/test.php';
        $error = new \Error('Allowed memory size exhausted');
        
        $violation = ErrorHandler::handleMemoryError($filePath, $error);
        
        $this->assertIsArray($violation);
        $this->assertEquals($filePath, $violation['file']);
        $this->assertEquals(1, $violation['line']);
        $this->assertStringContainsString('Memory limit exceeded', $violation['message']);
        $this->assertEquals('Large file processing', $violation['bad_code']);
        $this->assertEquals('error', $violation['severity']);
        $this->assertEquals('memory', $violation['category']);
    }

    /**
     * Test handling performance warnings
     */
    public function testHandlePerformanceWarning(): void
    {
        $filePath = '/path/to/test.php';
        $message = 'Rule processing took longer than expected';
        
        $violation = ErrorHandler::handlePerformanceWarning($filePath, $message);
        
        $this->assertIsArray($violation);
        $this->assertEquals($filePath, $violation['file']);
        $this->assertEquals(1, $violation['line']);
        $this->assertStringContainsString('Performance issue detected', $violation['message']);
        $this->assertEquals('Slow processing detected', $violation['bad_code']);
        $this->assertEquals('warning', $violation['severity']);
        $this->assertEquals('performance', $violation['category']);
    }

    /**
     * Test getting all errors
     */
    public function testGetErrors(): void
    {
        $filePath = '/path/to/test.php';
        $error = new \Exception('Test error');
        
        ErrorHandler::handleFileAccessError($filePath, $error);
        
        $errors = ErrorHandler::getErrors();
        
        $this->assertIsArray($errors);
        $this->assertCount(1, $errors);
        $this->assertEquals('FILE_ACCESS', $errors[0]['type']);
        $this->assertEquals($filePath, $errors[0]['file']);
    }

    /**
     * Test getting all warnings
     */
    public function testGetWarnings(): void
    {
        $filePath = '/path/to/test.php';
        $message = 'Test warning';
        
        ErrorHandler::handlePerformanceWarning($filePath, $message);
        
        $warnings = ErrorHandler::getWarnings();
        
        $this->assertIsArray($warnings);
        $this->assertCount(1, $warnings);
        $this->assertEquals('PERFORMANCE', $warnings[0]['type']);
        $this->assertEquals($filePath, $warnings[0]['file']);
    }

    /**
     * Test clearing errors and warnings
     */
    public function testClear(): void
    {
        $filePath = '/path/to/test.php';
        $error = new \Exception('Test error');
        $message = 'Test warning';
        
        ErrorHandler::handleFileAccessError($filePath, $error);
        ErrorHandler::handlePerformanceWarning($filePath, $message);
        
        $this->assertCount(1, ErrorHandler::getErrors());
        $this->assertCount(1, ErrorHandler::getWarnings());
        
        ErrorHandler::clear();
        
        $this->assertCount(0, ErrorHandler::getErrors());
        $this->assertCount(0, ErrorHandler::getWarnings());
    }

    /**
     * Test error statistics
     */
    public function testGetErrorStats(): void
    {
        $filePath = '/path/to/test.php';
        $error1 = new \Exception('Test error 1');
        $error2 = new \Exception('Test error 2');
        $message = 'Test warning';
        
        ErrorHandler::handleFileAccessError($filePath, $error1);
        ErrorHandler::handleFileAccessError($filePath, $error2);
        ErrorHandler::handlePerformanceWarning($filePath, $message);
        
        $stats = ErrorHandler::getErrorStats();
        
        $this->assertIsArray($stats);
        $this->assertEquals(2, $stats['total_errors']);
        $this->assertEquals(1, $stats['total_warnings']);
        $this->assertArrayHasKey('categories', $stats);
        $this->assertEquals(2, $stats['categories']['FILE_ACCESS']['count']);
        $this->assertEquals(1, $stats['categories']['PERFORMANCE']['count']);
    }

    /**
     * Test checking for recoverable errors
     */
    public function testHasRecoverableErrors(): void
    {
        $filePath = '/path/to/test.php';
        
        // Add non-recoverable error
        $error1 = new \Exception('Test error');
        ErrorHandler::handleFileAccessError($filePath, $error1);
        
        $this->assertFalse(ErrorHandler::hasRecoverableErrors());
        
        // Add recoverable error
        $error2 = new \Error('Test error');
        ErrorHandler::handleMemoryError($filePath, $error2);
        
        $this->assertTrue(ErrorHandler::hasRecoverableErrors());
    }

    /**
     * Test detailed error report
     */
    public function testGetDetailedReport(): void
    {
        $filePath = '/path/to/test.php';
        $error = new \Exception('Test error');
        $message = 'Test warning';
        
        ErrorHandler::handleFileAccessError($filePath, $error);
        ErrorHandler::handlePerformanceWarning($filePath, $message);
        
        $report = ErrorHandler::getDetailedReport();
        
        $this->assertIsArray($report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('errors', $report);
        $this->assertArrayHasKey('warnings', $report);
        $this->assertArrayHasKey('has_recoverable_errors', $report);
        $this->assertArrayHasKey('suggestions', $report);
        
        $this->assertEquals(1, $report['summary']['total_errors']);
        $this->assertEquals(1, $report['summary']['total_warnings']);
        $this->assertFalse($report['has_recoverable_errors']);
        $this->assertIsArray($report['suggestions']);
    }

    /**
     * Test error categories constant
     */
    public function testErrorCategories(): void
    {
        $categories = ErrorHandler::ERROR_CATEGORIES;
        
        $this->assertIsArray($categories);
        $this->assertArrayHasKey('PARSING', $categories);
        $this->assertArrayHasKey('FILE_ACCESS', $categories);
        $this->assertArrayHasKey('MEMORY', $categories);
        $this->assertArrayHasKey('PERFORMANCE', $categories);
        $this->assertArrayHasKey('VALIDATION', $categories);
        $this->assertArrayHasKey('CONFIGURATION', $categories);
        $this->assertArrayHasKey('RULE_PROCESSING', $categories);
    }

    /**
     * Test multiple errors of different types
     */
    public function testMultipleErrorTypes(): void
    {
        $filePath = '/path/to/test.php';
        
        $error1 = new \PhpParser\Error('Syntax error');
        $error2 = new \Error('Memory error');
        $error3 = new \Exception('File access error');
        $message = 'Performance warning';
        
        ErrorHandler::handleParsingError($filePath, $error1);
        ErrorHandler::handleMemoryError($filePath, $error2);
        ErrorHandler::handleFileAccessError($filePath, $error3);
        ErrorHandler::handlePerformanceWarning($filePath, $message);
        
        $errors = ErrorHandler::getErrors();
        $warnings = ErrorHandler::getWarnings();
        
        $this->assertCount(3, $errors);
        $this->assertCount(1, $warnings);
        
        $errorTypes = array_column($errors, 'type');
        $this->assertContains('PARSING', $errorTypes);
        $this->assertContains('MEMORY', $errorTypes);
        $this->assertContains('FILE_ACCESS', $errorTypes);
        
        $warningTypes = array_column($warnings, 'type');
        $this->assertContains('PERFORMANCE', $warningTypes);
    }

    /**
     * Test error message formatting for different error types
     */
    public function testErrorMessageFormatting(): void
    {
        $filePath = '/path/to/test.php';
        
        // Test syntax error
        $syntaxError = new \PhpParser\Error('syntax error, unexpected T_STRING');
        $violation1 = ErrorHandler::handleParsingError($filePath, $syntaxError);
        $this->assertStringContainsString('Check for missing semicolons', $violation1['suggested_fix']);
        
        // Test unexpected token error
        $unexpectedError = new \PhpParser\Error('unexpected T_IF');
        $violation2 = ErrorHandler::handleParsingError($filePath, $unexpectedError);
        $this->assertStringContainsString('Check for syntax errors around', $violation2['suggested_fix']);
        
        // Test unterminated error
        $unterminatedError = new \PhpParser\Error('unterminated string');
        $violation3 = ErrorHandler::handleParsingError($filePath, $unterminatedError);
        $this->assertStringContainsString('Check for unclosed strings', $violation3['suggested_fix']);
    }

    /**
     * Test suggestions generation based on error types
     */
    public function testSuggestionsGeneration(): void
    {
        $filePath = '/path/to/test.php';
        
        // Add parsing errors
        $error1 = new \PhpParser\Error('syntax error');
        $error2 = new \PhpParser\Error('syntax error');
        ErrorHandler::handleParsingError($filePath, $error1);
        ErrorHandler::handleParsingError($filePath, $error2);
        
        // Add memory errors
        $error3 = new \Error('memory error');
        ErrorHandler::handleMemoryError($filePath, $error3);
        
        // Add performance errors
        $error4 = new \Error('performance error');
        ErrorHandler::handlePerformanceWarning($filePath, 'slow processing');
        
        $report = ErrorHandler::getDetailedReport();
        $suggestions = $report['suggestions'];
        
        $this->assertIsArray($suggestions);
        $this->assertGreaterThan(0, count($suggestions));
        
        // Check for specific suggestions
        $suggestionTexts = implode(' ', $suggestions);
        $this->assertStringContainsString('syntax validation', $suggestionTexts);
        $this->assertStringContainsString('chunked processing', $suggestionTexts);
        $this->assertStringContainsString('AST caching', $suggestionTexts);
    }
} 