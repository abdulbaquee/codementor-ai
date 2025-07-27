# Error Handling in Review System

## Overview

The Review System implements comprehensive error handling to ensure robustness and provide clear feedback when issues occur. All errors are logged, categorized, and reported to help developers identify and resolve problems quickly.

## Error Categories

### 1. Configuration Errors
Errors related to rule configuration and setup.

**Examples:**
- Invalid rule class names
- Missing rule classes
- Rules that don't implement `RuleInterface`
- Rules with required constructor parameters

**Error Message Format:**
```
Rule 'NonExistentRule' failed: Rule class 'NonExistentRule' not found. Check if the class exists and is autoloaded.
```

### 2. File System Errors
Errors related to file access and processing.

**Examples:**
- Files not found
- Files not readable
- Empty scan paths
- No PHP files found

**Error Message Format:**
```
File not found: /path/to/file.php
File not readable: /path/to/file.php
No PHP files found in scan paths: /path1, /path2
```

### 3. Rule Processing Errors
Errors that occur during rule execution.

**Examples:**
- Exceptions thrown by rules
- Invalid violation formats
- Missing required violation fields

**Error Message Format:**
```
Error processing file '/path/to/file.php' with rule 'RuleClass': Exception message
Invalid violation format in rule 'RuleClass' for file '/path/to/file.php': Field 'message' is required
```

### 4. Validation Errors
Errors related to violation data validation.

**Examples:**
- Missing required violation fields
- Invalid violation structure
- Malformed violation data

**Error Message Format:**
```
Violation 0 in rule 'RuleClass' missing required field 'message'
```

## Error Handling Features

### 1. Graceful Degradation
- System continues processing even if individual rules fail
- Other rules continue to run even if one rule encounters an error
- Partial results are still returned

### 2. Comprehensive Logging
- All errors are logged with timestamps
- Errors are written to PHP error log for debugging
- Errors are collected and displayed in CLI output

### 3. Detailed Error Messages
- Clear, actionable error messages
- Context information included (file paths, rule names)
- Suggestions for fixing common issues

### 4. Error Recovery
- System attempts to recover from non-critical errors
- Continues processing remaining files and rules
- Provides summary of what succeeded and what failed

## Error Reporting

### CLI Output
Errors are displayed in the CLI with clear formatting:

```
⚠️  Warnings/Errors during processing:
   • Rule 'NonExistentRule' failed: Rule class 'NonExistentRule' not found
   • Configuration issue with rule 'NonExistentRule'. Please check your config.php file.
```

### PHP Error Log
All errors are also written to the PHP error log with the `[ReviewSystem]` prefix:

```
[ReviewSystem] 2025-07-26 18:06:13 - Rule 'NonExistentRule' failed: Rule class 'NonExistentRule' not found
```

### Programmatic Access
Errors can be accessed programmatically:

```php
$runner = new RuleRunner($config);
$violations = $runner->run();

if ($runner->hasErrors()) {
    $errors = $runner->getErrors();
    foreach ($errors as $error) {
        echo "Error: {$error['message']}\n";
    }
}
```

## Rule Validation

### Class Validation
- **Existence**: Checks if the rule class exists
- **Interface**: Verifies the class implements `RuleInterface`
- **Instantiation**: Ensures the class can be instantiated
- **Constructor**: Validates no required constructor parameters

### Violation Validation
- **Required Fields**: Ensures `message` field is present
- **Default Values**: Sets defaults for optional fields
- **Structure**: Validates violation array format
- **Metadata**: Adds rule and file information

## Best Practices

### 1. Rule Development
```php
class MyRule implements RuleInterface
{
    public function check(string $filePath): array
    {
        try {
            // Your rule logic here
            return [
                [
                    'message' => 'Your violation message', // Required
                    'file' => $filePath,                   // Optional (auto-filled)
                    'bad' => 'Bad code example',           // Optional
                    'good' => 'Good code example',         // Optional
                    'line' => 42,                          // Optional
                    'severity' => 'warning'                // Optional
                ]
            ];
        } catch (Throwable $e) {
            // Log the error but don't throw it
            error_log("Error in MyRule: " . $e->getMessage());
            return [];
        }
    }
}
```

### 2. Configuration
```php
// config.php
return [
    'rules' => [
        // Always use fully qualified class names
        ReviewSystem\Rules\MyRule::class,
        
        // Avoid string literals
        // 'MyRule', // ❌ Bad
    ],
];
```

### 3. Error Monitoring
```php
// Check for errors after running
$runner = new RuleRunner($config);
$violations = $runner->run();

if ($runner->hasErrors()) {
    // Handle errors appropriately
    foreach ($runner->getErrors() as $error) {
        // Log to your monitoring system
        // Send notifications
        // Update status dashboard
    }
}
```

## Troubleshooting

### Common Issues

1. **"Rule class not found"**
   - Check if the class exists in the correct namespace
   - Verify autoloading is configured correctly
   - Run `composer dump-autoload`

2. **"Rule class must implement RuleInterface"**
   - Ensure your rule class implements `ReviewSystem\Engine\RuleInterface`
   - Check for typos in the interface name

3. **"Rule class is not instantiable"**
   - Make sure the rule class is not abstract
   - Ensure the rule class has a public constructor
   - Check that the constructor has no required parameters

4. **"Missing required field 'message'"**
   - Ensure all violations have a `message` field
   - Check violation array structure
   - Verify rule implementation returns correct format

### Debug Mode
For detailed debugging, you can enable error reporting:

```php
// In your test script
error_reporting(E_ALL);
ini_set('display_errors', 1);

$runner = new RuleRunner($config);
$violations = $runner->run();

// Print all errors
print_r($runner->getErrors());
```

## Error Codes and Severity

| Error Type | Severity | Action Required |
|------------|----------|-----------------|
| Configuration | High | Fix configuration immediately |
| File System | Medium | Check file permissions/paths |
| Rule Processing | Medium | Review rule implementation |
| Validation | Low | Fix rule output format |

## Future Enhancements

- Error categorization by severity
- Error reporting to external systems
- Error statistics and analytics
- Automatic error recovery strategies
- Error notification system 