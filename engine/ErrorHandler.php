<?php

namespace ReviewSystem\Engine;

/**
 * Comprehensive error handling system for the review system
 * 
 * This class provides:
 * - Detailed error messages with context
 * - Graceful handling of edge cases
 * - Error categorization and severity levels
 * - Recovery mechanisms for common issues
 */
class ErrorHandler
{
    /** @var array Collected errors */
    private static $errors = [];
    
    /** @var array Collected warnings */
    private static $warnings = [];
    
    /** @var array Error categories */
    public const ERROR_CATEGORIES = [
        'PARSING' => 'PHP parsing errors',
        'FILE_ACCESS' => 'File access issues',
        'MEMORY' => 'Memory-related issues',
        'PERFORMANCE' => 'Performance warnings',
        'VALIDATION' => 'Data validation errors',
        'CONFIGURATION' => 'Configuration issues',
        'RULE_PROCESSING' => 'Rule processing errors'
    ];

    /**
     * Handle parsing errors with detailed context
     */
    public static function handleParsingError(string $filePath, \Throwable $error): array
    {
        $errorInfo = [
            'type' => 'PARSING',
            'file' => $filePath,
            'message' => self::formatParsingErrorMessage($error),
            'suggestion' => self::getParsingErrorSuggestion($error),
            'severity' => 'error',
            'recoverable' => false
        ];
        
        self::$errors[] = $errorInfo;
        
        return [
            'file' => $filePath,
            'line' => 1,
            'message' => 'PHP parsing error: ' . $error->getMessage(),
            'bad_code' => 'Invalid PHP syntax',
            'suggested_fix' => 'Fix PHP syntax errors in the file',
            'severity' => 'error',
            'category' => 'parsing'
        ];
    }

    /**
     * Handle nullable type errors gracefully
     */
    public static function handleNullableTypeError(string $filePath, \Throwable $error): array
    {
        $errorInfo = [
            'type' => 'VALIDATION',
            'file' => $filePath,
            'message' => 'Nullable type handling error: ' . $error->getMessage(),
            'suggestion' => 'Update rule to handle nullable types properly',
            'severity' => 'warning',
            'recoverable' => true
        ];
        
        self::$warnings[] = $errorInfo;
        
        return [
            'file' => $filePath,
            'line' => 1,
            'message' => 'Type handling issue detected',
            'bad_code' => 'Nullable type access',
            'suggested_fix' => 'Update rule implementation to handle nullable types',
            'severity' => 'warning',
            'category' => 'validation'
        ];
    }

    /**
     * Handle file access errors
     */
    public static function handleFileAccessError(string $filePath, \Throwable $error): array
    {
        $errorInfo = [
            'type' => 'FILE_ACCESS',
            'file' => $filePath,
            'message' => 'File access error: ' . $error->getMessage(),
            'suggestion' => 'Check file permissions and existence',
            'severity' => 'error',
            'recoverable' => false
        ];
        
        self::$errors[] = $errorInfo;
        
        return [
            'file' => $filePath,
            'line' => 1,
            'message' => 'Cannot access file: ' . basename($filePath),
            'bad_code' => 'File access denied',
            'suggested_fix' => 'Check file permissions and ensure file exists',
            'severity' => 'error',
            'category' => 'file_access'
        ];
    }

    /**
     * Handle memory issues
     */
    public static function handleMemoryError(string $filePath, \Throwable $error): array
    {
        $errorInfo = [
            'type' => 'MEMORY',
            'file' => $filePath,
            'message' => 'Memory limit exceeded: ' . $error->getMessage(),
            'suggestion' => 'Increase memory limit or process file in chunks',
            'severity' => 'error',
            'recoverable' => true
        ];
        
        self::$errors[] = $errorInfo;
        
        return [
            'file' => $filePath,
            'line' => 1,
            'message' => 'Memory limit exceeded while processing file',
            'bad_code' => 'Large file processing',
            'suggested_fix' => 'Increase PHP memory_limit or use chunked processing',
            'severity' => 'error',
            'category' => 'memory'
        ];
    }

    /**
     * Handle performance warnings
     */
    public static function handlePerformanceWarning(string $filePath, string $message, float $threshold = 1.0): array
    {
        $errorInfo = [
            'type' => 'PERFORMANCE',
            'file' => $filePath,
            'message' => 'Performance warning: ' . $message,
            'suggestion' => 'Consider optimizing rule implementation or using caching',
            'severity' => 'warning',
            'recoverable' => true
        ];
        
        self::$warnings[] = $errorInfo;
        
        return [
            'file' => $filePath,
            'line' => 1,
            'message' => 'Performance issue detected: ' . $message,
            'bad_code' => 'Slow processing detected',
            'suggested_fix' => 'Optimize rule implementation or enable caching',
            'severity' => 'warning',
            'category' => 'performance'
        ];
    }

    /**
     * Format parsing error messages with context
     */
    private static function formatParsingErrorMessage(\Throwable $error): string
    {
        $message = $error->getMessage();
        
        // Add context for common parsing errors
        if (str_contains($message, 'syntax error')) {
            $message .= ' - Check for missing semicolons, brackets, or quotes';
        } elseif (str_contains($message, 'unexpected')) {
            $message .= ' - Check for syntax errors around the unexpected token';
        } elseif (str_contains($message, 'unterminated')) {
            $message .= ' - Check for unclosed strings, comments, or blocks';
        }
        
        return $message;
    }

    /**
     * Get suggestions for parsing errors
     */
    private static function getParsingErrorSuggestion(\Throwable $error): string
    {
        $message = $error->getMessage();
        
        if (str_contains($message, 'syntax error')) {
            return 'Use a PHP syntax checker or IDE to identify and fix syntax errors';
        } elseif (str_contains($message, 'unexpected')) {
            return 'Review the code around the unexpected token for syntax issues';
        } elseif (str_contains($message, 'unterminated')) {
            return 'Check for unclosed quotes, comments, or code blocks';
        }
        
        return 'Review the PHP syntax and ensure the file is valid PHP code';
    }

    /**
     * Get all collected errors
     */
    public static function getErrors(): array
    {
        return self::$errors;
    }

    /**
     * Get all collected warnings
     */
    public static function getWarnings(): array
    {
        return self::$warnings;
    }

    /**
     * Clear all collected errors and warnings
     */
    public static function clear(): void
    {
        self::$errors = [];
        self::$warnings = [];
    }

    /**
     * Get error statistics
     */
    public static function getErrorStats(): array
    {
        $stats = [
            'total_errors' => count(self::$errors),
            'total_warnings' => count(self::$warnings),
            'categories' => []
        ];
        
        foreach (self::ERROR_CATEGORIES as $category => $description) {
            $stats['categories'][$category] = [
                'description' => $description,
                'count' => 0
            ];
        }
        
        // Count errors by category
        foreach (self::$errors as $error) {
            $category = $error['type'];
            if (isset($stats['categories'][$category])) {
                $stats['categories'][$category]['count']++;
            }
        }
        
        return $stats;
    }

    /**
     * Check if errors are recoverable
     */
    public static function hasRecoverableErrors(): bool
    {
        foreach (self::$errors as $error) {
            if ($error['recoverable']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get detailed error report
     */
    public static function getDetailedReport(): array
    {
        return [
            'summary' => self::getErrorStats(),
            'errors' => self::$errors,
            'warnings' => self::$warnings,
            'has_recoverable_errors' => self::hasRecoverableErrors(),
            'suggestions' => self::generateSuggestions()
        ];
    }

    /**
     * Generate improvement suggestions based on errors
     */
    private static function generateSuggestions(): array
    {
        $suggestions = [];
        
        $errorCounts = [];
        foreach (self::$errors as $error) {
            $type = $error['type'];
            $errorCounts[$type] = ($errorCounts[$type] ?? 0) + 1;
        }
        
        if (isset($errorCounts['PARSING']) && $errorCounts['PARSING'] > 0) {
            $suggestions[] = 'Consider adding syntax validation before rule processing';
        }
        
        if (isset($errorCounts['MEMORY']) && $errorCounts['MEMORY'] > 0) {
            $suggestions[] = 'Implement chunked processing for large files';
        }
        
        if (isset($errorCounts['PERFORMANCE']) && $errorCounts['PERFORMANCE'] > 0) {
            $suggestions[] = 'Enable AST caching and optimize rule implementations';
        }
        
        return $suggestions;
    }
} 