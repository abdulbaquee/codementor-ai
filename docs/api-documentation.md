# Review System API Documentation

## Overview

The Review System provides a comprehensive API for creating custom rules, handling errors, and extending functionality. This documentation covers all public interfaces and provides examples for common use cases.

## Table of Contents

1. [Rule Interface](#rule-interface)
2. [Abstract Rule Base Class](#abstract-rule-base-class)
3. [Performance Optimized Rule](#performance-optimized-rule)
4. [Error Handling](#error-handling)
5. [Configuration](#configuration)
6. [Examples](#examples)

## Rule Interface

### `ReviewSystem\Engine\RuleInterface`

The core interface that all rules must implement.

```php
interface RuleInterface
{
    /**
     * Get the rule's category
     * 
     * @return string Category constant from RuleCategory class
     */
    public function getCategory(): string;

    /**
     * Get the rule's name
     * 
     * @return string Human-readable rule name
     */
    public function getName(): string;

    /**
     * Get the rule's description
     * 
     * @return string Detailed description of what the rule checks
     */
    public function getDescription(): string;

    /**
     * Get the rule's severity level
     * 
     * @return string 'error', 'warning', 'info', or 'suggestion'
     */
    public function getSeverity(): string;

    /**
     * Get the rule's tags for additional categorization
     * 
     * @return array Array of tag strings
     */
    public function getTags(): array;

    /**
     * Check if the rule is enabled by default
     * 
     * @return bool True if rule should be enabled by default
     */
    public function isEnabledByDefault(): bool;

    /**
     * Check a file for violations
     * 
     * @param string $filePath Path to the file to check
     * @return array Array of violation arrays
     */
    public function check(string $filePath): array;
}
```

## Abstract Rule Base Class

### `ReviewSystem\Engine\AbstractRule`

Provides common functionality and default implementations for rules.

```php
abstract class AbstractRule implements RuleInterface
{
    /**
     * Get the rule's configuration options
     * 
     * @return array Configuration options with type, default, and description
     */
    public function getConfigurationOptions(): array;

    /**
     * Validate rule configuration
     * 
     * @param array $config Configuration array
     * @return array Validation errors (empty if valid)
     */
    public function validateConfiguration(array $config): array;

    /**
     * Get rule metadata
     * 
     * @return array Rule metadata including version, author, etc.
     */
    public function getMetadata(): array;
}
```

## Performance Optimized Rule

### `ReviewSystem\Engine\PerformanceOptimizedRule`

Advanced base class with caching and performance optimizations.

```php
abstract class PerformanceOptimizedRule extends AbstractRule
{
    /**
     * Parse file with caching support
     * 
     * @param string $filePath Path to the file
     * @return array|null Parsed AST or null if parsing failed
     */
    protected function parseFileWithCache(string $filePath): ?array;

    /**
     * Track performance metrics
     * 
     * @param string $metric Metric name
     * @param float $value Metric value
     */
    protected function trackPerformance(string $metric, float $value): void;

    /**
     * Get performance metrics for this rule
     * 
     * @return array Performance metrics
     */
    public static function getPerformanceMetrics(): array;

    /**
     * Clear AST cache
     */
    public static function clearCache(): void;

    /**
     * Get cache statistics
     * 
     * @return array Cache statistics
     */
    public static function getCacheStats(): array;

    /**
     * Memory-efficient file processing for large files
     * 
     * @param string $filePath Path to the file
     * @param callable $processor Processing function
     * @return array Violations found
     */
    protected function processLargeFile(string $filePath, callable $processor): array;

    /**
     * Abstract method for rule-specific checks
     * 
     * @param array $ast Parsed AST
     * @param string $filePath File path
     * @return array Violations found
     */
    abstract protected function performChecks(array $ast, string $filePath): array;
}
```

## Error Handling

### `ReviewSystem\Engine\ErrorHandler`

Comprehensive error handling system with categorization and recovery.

```php
class ErrorHandler
{
    /**
     * Handle parsing errors with detailed context
     * 
     * @param string $filePath Path to the file
     * @param \Throwable $error Error object
     * @return array Violation array for the error
     */
    public static function handleParsingError(string $filePath, \Throwable $error): array;

    /**
     * Handle nullable type errors gracefully
     * 
     * @param string $filePath Path to the file
     * @param \Throwable $error Error object
     * @return array Violation array for the error
     */
    public static function handleNullableTypeError(string $filePath, \Throwable $error): array;

    /**
     * Handle file access errors
     * 
     * @param string $filePath Path to the file
     * @param \Throwable $error Error object
     * @return array Violation array for the error
     */
    public static function handleFileAccessError(string $filePath, \Throwable $error): array;

    /**
     * Handle memory issues
     * 
     * @param string $filePath Path to the file
     * @param \Throwable $error Error object
     * @return array Violation array for the error
     */
    public static function handleMemoryError(string $filePath, \Throwable $error): array;

    /**
     * Handle performance warnings
     * 
     * @param string $filePath Path to the file
     * @param string $message Warning message
     * @param float $threshold Performance threshold
     * @return array Violation array for the warning
     */
    public static function handlePerformanceWarning(string $filePath, string $message, float $threshold = 1.0): array;

    /**
     * Get all collected errors
     * 
     * @return array Array of error information
     */
    public static function getErrors(): array;

    /**
     * Get all collected warnings
     * 
     * @return array Array of warning information
     */
    public static function getWarnings(): array;

    /**
     * Get error statistics
     * 
     * @return array Error statistics by category
     */
    public static function getErrorStats(): array;

    /**
     * Get detailed error report
     * 
     * @return array Comprehensive error report with suggestions
     */
    public static function getDetailedReport(): array;
}
```

## Configuration

### Rule Categories

Available categories from `RuleCategory` class:

```php
class RuleCategory
{
    public const SECURITY = 'security';
    public const PERFORMANCE = 'performance';
    public const STYLE = 'style';
    public const BEST_PRACTICE = 'best_practice';
    public const MAINTAINABILITY = 'maintainability';
    public const COMPATIBILITY = 'compatibility';
    public const DOCUMENTATION = 'documentation';
    public const TESTING = 'testing';
    public const ARCHITECTURE = 'architecture';
    public const GENERAL = 'general';
}
```

### Severity Levels

Available severity levels:

- `error`: Critical issues that must be fixed
- `warning`: Issues that should be addressed
- `info`: Informational messages
- `suggestion`: Optional improvements

## Examples

### Basic Rule Implementation

```php
<?php

namespace ReviewSystem\Rules;

use ReviewSystem\Engine\AbstractRule;
use ReviewSystem\Engine\RuleCategory;

/**
 * Example rule that checks for TODO comments
 */
class TodoCommentRule extends AbstractRule
{
    public function getCategory(): string
    {
        return RuleCategory::MAINTAINABILITY;
    }

    public function getName(): string
    {
        return 'TODO Comment Detection';
    }

    public function getDescription(): string
    {
        return 'Detects TODO comments that should be addressed';
    }

    public function getSeverity(): string
    {
        return 'warning';
    }

    public function getTags(): array
    {
        return ['maintenance', 'comments', 'todo'];
    }

    public function isEnabledByDefault(): bool
    {
        return true;
    }

    public function check(string $filePath): array
    {
        $violations = [];
        
        if (!file_exists($filePath)) {
            return $violations;
        }

        $lines = file($filePath);
        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/\bTODO\b/i', $line)) {
                $violations[] = [
                    'file' => $filePath,
                    'line' => $lineNumber + 1,
                    'message' => 'TODO comment found - should be addressed',
                    'bad_code' => trim($line),
                    'suggested_fix' => 'Replace TODO with actual implementation or create issue',
                    'severity' => 'warning',
                    'category' => 'maintenance'
                ];
            }
        }

        return $violations;
    }
}
```

### Performance Optimized Rule

```php
<?php

namespace ReviewSystem\Rules;

use ReviewSystem\Engine\PerformanceOptimizedRule;
use ReviewSystem\Engine\RuleCategory;
use PhpParser\Node\Stmt\Class_;

/**
 * Performance-optimized rule example
 */
class OptimizedRule extends PerformanceOptimizedRule
{
    protected function performChecks(array $ast, string $filePath): array
    {
        $violations = [];
        
        // Use shared node finder for better performance
        $nodeFinder = $this->getNodeFinder();
        $classes = $nodeFinder->findInstanceOf($ast, Class_::class);
        
        foreach ($classes as $class) {
            // Rule-specific logic here
            if (strlen($class->name->toString()) < 3) {
                $violations[] = [
                    'file' => $filePath,
                    'line' => $class->getStartLine(),
                    'message' => 'Class name too short',
                    'bad_code' => 'class ' . $class->name->toString(),
                    'suggested_fix' => 'Use a more descriptive class name',
                    'severity' => 'warning',
                    'category' => 'naming'
                ];
            }
        }
        
        return $violations;
    }

    public function getCategory(): string
    {
        return RuleCategory::STYLE;
    }

    public function getName(): string
    {
        return 'Optimized Style Check';
    }

    public function getDescription(): string
    {
        return 'Performance-optimized style checking';
    }

    public function getSeverity(): string
    {
        return 'warning';
    }

    public function getTags(): array
    {
        return ['style', 'performance'];
    }

    public function isEnabledByDefault(): bool
    {
        return true;
    }
}
```

### Error Handling Example

```php
<?php

use ReviewSystem\Engine\ErrorHandler;

try {
    $ast = $parser->parse($code);
    // Process AST...
} catch (\PhpParser\Error $error) {
    $violation = ErrorHandler::handleParsingError($filePath, $error);
    $violations[] = $violation;
} catch (\Error $error) {
    if (str_contains($error->getMessage(), 'NullableType')) {
        $violation = ErrorHandler::handleNullableTypeError($filePath, $error);
        $violations[] = $violation;
    }
}

// Get error statistics
$errorStats = ErrorHandler::getErrorStats();
$detailedReport = ErrorHandler::getDetailedReport();
```

## Best Practices

### Rule Development

1. **Use appropriate base class**: Choose `AbstractRule` for simple rules or `PerformanceOptimizedRule` for complex ones
2. **Implement all interface methods**: Ensure all required methods are implemented
3. **Provide meaningful descriptions**: Help users understand what the rule checks
4. **Use proper categorization**: Choose the most appropriate category
5. **Handle errors gracefully**: Use ErrorHandler for consistent error handling

### Performance Considerations

1. **Use caching**: Leverage AST caching for repeated file analysis
2. **Optimize AST traversal**: Use NodeFinder efficiently
3. **Process large files**: Use chunked processing for memory efficiency
4. **Track metrics**: Monitor performance for optimization opportunities

### Error Handling

1. **Categorize errors**: Use appropriate error categories
2. **Provide context**: Include file paths and line numbers
3. **Suggest fixes**: Give actionable suggestions for errors
4. **Handle edge cases**: Gracefully handle unexpected situations

## Version History

- **1.0.0**: Initial API documentation
- **1.1.0**: Added PerformanceOptimizedRule documentation
- **1.2.0**: Added ErrorHandler documentation
- **1.3.0**: Added comprehensive examples and best practices 