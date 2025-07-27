# Enhanced Error Reporting System

## Overview

The Review System now features a comprehensive error reporting system that provides detailed insights into the code review process, including performance metrics, statistics, warnings, errors, and informational messages.

## Features

### 🔍 **Comprehensive Error Categorization**
- **CONFIGURATION**: Issues with system configuration
- **FILE_SCANNING**: Problems during file discovery
- **FILE_PROCESSING**: Errors while processing individual files
- **RULE_PROCESSING**: Issues with rule execution
- **VIOLATION_VALIDATION**: Problems with violation format
- **RULE_ERROR**: Rule-specific errors
- **CRITICAL**: System-critical failures

### 📊 **Performance Metrics**
- **Total Time**: Complete execution time
- **File Scanning**: Time spent discovering files
- **Rule Processing**: Individual rule execution times
- **Detailed Breakdown**: Per-rule performance tracking

### 📈 **Statistics Collection**
- **Files Scanned**: Number of files processed
- **Rules Processed**: Successfully executed rules
- **Rules Failed**: Rules that encountered errors
- **Total Violations**: Number of issues found
- **Scan Paths**: Directories analyzed

### ⚠️ **Multi-Level Logging**
- **ERROR**: Critical issues that need attention
- **WARNING**: Non-critical issues with suggestions
- **INFO**: Informational messages about process flow

### 💡 **User-Friendly Messages**
- **Contextual Suggestions**: Actionable advice for each issue
- **Detailed Context**: Additional information for debugging
- **Structured Format**: Consistent, readable output

## Usage

### Basic Usage

```php
$runner = new RuleRunner($config);
$violations = $runner->run();

// Get comprehensive report
$runReport = $runner->getRunReport();
```

### Accessing Different Log Types

```php
// Check for issues
if ($runner->hasErrors()) {
    $errors = $runner->getErrors();
}

if ($runner->hasWarnings()) {
    $warnings = $runner->getWarnings();
}

// Get informational messages
$info = $runner->getInfo();

// Performance data
$performance = $runner->getPerformance();

// Statistics
$statistics = $runner->getStatistics();
```

### Complete Run Report

```php
$runReport = $runner->getRunReport();

// Structure:
[
    'summary' => [
        'has_errors' => bool,
        'has_warnings' => bool,
        'error_count' => int,
        'warning_count' => int,
        'info_count' => int
    ],
    'performance' => [
        'total_time' => float,
        'file_scanning' => float,
        'rules' => [
            'RuleClass' => float
        ]
    ],
    'statistics' => [
        'files_scanned' => int,
        'rules_processed' => int,
        'rules_failed' => int,
        'total_violations' => int
    ],
    'errors' => [...],
    'warnings' => [...],
    'info' => [...]
]
```

## Error Message Structure

### Error Entry
```php
[
    'timestamp' => '2025-07-26 19:29:06',
    'message' => 'Error description',
    'level' => 'ERROR',
    'category' => 'FILE_PROCESSING',
    'severity' => 'CRITICAL',
    'suggestion' => 'Actionable advice',
    'context' => [
        'file' => '/path/to/file.php',
        'rule' => 'RuleClass',
        'error_type' => 'RuntimeException'
    ]
]
```

### Warning Entry
```php
[
    'timestamp' => '2025-07-26 19:29:06',
    'message' => 'Warning description',
    'level' => 'WARNING',
    'category' => 'CONFIGURATION',
    'suggestion' => 'Improvement suggestion',
    'context' => [
        'count' => 5,
        'details' => 'Additional information'
    ]
]
```

### Info Entry
```php
[
    'timestamp' => '2025-07-26 19:29:06',
    'message' => 'Process information',
    'level' => 'INFO',
    'category' => 'RULE_PROCESSING',
    'context' => [
        'violations_found' => 3,
        'processing_time' => '0.039s'
    ]
]
```

## CLI Output Example

```
🔧 Review System Configuration
==============================
Environment: Standalone
Config File: review-system/config.php
Environment Overrides: No

📊 Performance Metrics
=====================
Total Time: 0.041s
File Scanning: 0.001s
Rule Processing:
  • ReviewSystem\Rules\NoMongoInControllerRule: 0.039s

📈 Statistics
=============
Files Scanned: 23
Rules Processed: 1
Rules Failed: 0
Total Violations: 1

⚠️  Warnings
===========
• [CONFIGURATION] Configuration warnings detected
• [CONFIGURATION] Rule 'ReviewSystem\Rules\NoMongoInControllerRule' may have performance issues
  💡 Suggestion: Consider caching parser instances
• [CONFIGURATION] Rule class 'ReviewSystem\Rules\NoMongoInControllerRule' lacks documentation
  💡 Suggestion: Add PHPDoc comments to describe the rule's purpose

❌ Errors
=========
• [FILE_PROCESSING] File not found during rule processing
  💡 Suggestion: Check if the file was moved or deleted
  📍 Context: {"file":"/path/to/missing.php","rule":"RuleClass"}

== Violations found. ==

📄 Report saved to: /path/to/report.html
🌐 View in browser: http://localhost/report.html
```

## Integration with Existing Systems

### RuleValidator Integration
The enhanced error reporting integrates seamlessly with the existing `RuleValidator`:

```php
// Validation errors are automatically categorized
$validation = $validator->validateConfiguration();
if (!$validation['is_valid']) {
    // Errors are logged with CONFIGURATION category
    // Warnings are logged with CONFIGURATION category
}
```

### FileScanner Integration
File scanning issues are automatically captured:

```php
// File scanning performance is tracked
$scanTime = microtime(true) - $scanStartTime;
$this->performance['file_scanning'] = $scanTime;

// File scanning statistics are collected
$this->statistics['files_scanned'] = count($files);
```

### Rule Processing Integration
Rule execution is comprehensively monitored:

```php
// Rule performance is tracked individually
$ruleTime = microtime(true) - $ruleStartTime;
$this->performance['rules'][$ruleClass] = $ruleTime;

// Rule statistics are collected
$this->statistics['rules_processed']++;
```

## Best Practices

### 1. **Error Handling in Rules**
```php
public function check(string $file): array
{
    try {
        // Rule logic
        return $violations;
    } catch (Throwable $e) {
        // Errors are automatically captured by RuleRunner
        throw $e;
    }
}
```

### 2. **Violation Format Validation**
```php
// Always include required fields
$violations[] = [
    'message' => 'Required: Description of the issue',
    'line' => 10,
    'bad' => 'problematic code',
    'good' => 'better code',
    'severity' => 'warning'
];
```

### 3. **Performance Monitoring**
```php
// Monitor rule performance
$startTime = microtime(true);
// ... rule logic ...
$executionTime = microtime(true) - $startTime;

if ($executionTime > 1.0) {
    // Consider optimization
}
```

## Troubleshooting

### Common Issues

#### 1. **High Error Count**
- Check rule implementations for bugs
- Verify file permissions
- Review configuration settings

#### 2. **Performance Issues**
- Monitor individual rule execution times
- Consider caching for expensive operations
- Optimize file scanning patterns

#### 3. **Missing Context**
- Ensure all error/warning entries include relevant context
- Use appropriate categories for better organization
- Provide actionable suggestions

### Debug Mode

Enable detailed logging for debugging:

```php
// All messages are automatically logged to PHP error log
error_log("[ReviewSystem ERROR] Timestamp - Message [CATEGORY]");
error_log("[ReviewSystem WARNING] Timestamp - Message [CATEGORY]");
error_log("[ReviewSystem INFO] Timestamp - Message [CATEGORY]");
```

## Migration from Basic Error Handling

### Before (Basic)
```php
// Simple error collection
private array $errors = [];

private function logError(string $message): void
{
    $this->errors[] = $message;
}
```

### After (Enhanced)
```php
// Comprehensive logging system
private array $errors = [];
private array $warnings = [];
private array $info = [];
private array $performance = [];
private array $statistics = [];

private function logError(string $message, array $context = []): void
{
    $error = array_merge([
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => $message,
        'level' => 'ERROR'
    ], $context);
    
    $this->errors[] = $error;
}
```

## Benefits

### 🎯 **For Developers**
- **Clear Problem Identification**: Categorized errors with context
- **Actionable Feedback**: Specific suggestions for each issue
- **Performance Insights**: Detailed timing and statistics
- **Debugging Support**: Comprehensive logging and context

### 🏢 **For Teams**
- **Consistent Reporting**: Standardized error format across all rules
- **Quality Metrics**: Performance and statistics tracking
- **Maintenance Support**: Detailed context for troubleshooting
- **User Experience**: Friendly, informative messages

### 🔧 **For System Administrators**
- **Monitoring**: Performance metrics for system health
- **Logging**: Structured logs for analysis
- **Troubleshooting**: Detailed error context
- **Optimization**: Performance data for improvements

## Future Enhancements

### Planned Features
- **Export Formats**: JSON, XML, CSV export of reports
- **Email Notifications**: Alert system for critical errors
- **Dashboard Integration**: Web-based reporting interface
- **Trend Analysis**: Historical performance tracking
- **Custom Categories**: User-defined error categories 