# Rule Validation System

## Overview

The Rule Validation System provides comprehensive validation for rule configurations, rule classes, and their dependencies. It ensures that the review system runs reliably by catching configuration errors, rule implementation issues, and performance concerns before execution.

## Features

### 🔍 **Comprehensive Validation**
- Configuration structure validation
- Rule class existence and autoloading
- Interface implementation verification
- Method signature validation
- Constructor parameter validation
- Performance analysis
- Dependency checking

### 🚨 **Error Detection**
- Missing configuration keys
- Invalid scan paths
- Non-existent rule classes
- Interface violations
- Method signature mismatches
- Constructor issues

### ⚠️ **Warning System**
- Performance concerns
- Missing documentation
- Large rule configurations
- Potential optimization opportunities

### 📊 **Detailed Reporting**
- Validation summaries
- Configuration analysis
- Rule analysis
- Actionable recommendations

## Validation Categories

### 1. Configuration Structure Validation

#### Required Keys
- `scan_paths` - Array of directories to scan
- `rules` - Array of rule class names
- `reporting` - Reporting configuration

#### Optional Keys
- `file_scanner` - File scanner configuration

#### Validation Rules
```php
// Valid configuration
$config = [
    'scan_paths' => ['/path/to/app', '/path/to/routes'],
    'rules' => ['MyRule\Class'],
    'reporting' => ['output_path' => '/reports'],
];

// Invalid configuration
$config = [
    'scan_paths' => 'not_an_array', // ❌ Must be array
    'rules' => 'single_rule',       // ❌ Must be array
    // Missing 'reporting' key      // ❌ Required
];
```

### 2. Scan Paths Validation

#### Checks Performed
- Path existence
- Directory type verification
- Read permissions
- String type validation

#### Examples
```php
// Valid paths
$scan_paths = [
    realpath(__DIR__ . '/../app'),     // ✅ Exists and readable
    realpath(__DIR__ . '/../routes'),  // ✅ Exists and readable
];

// Invalid paths
$scan_paths = [
    '/non/existent/path',              // ❌ Path doesn't exist
    '/etc/passwd',                     // ❌ Not a directory
    '/root',                           // ❌ Not readable
];
```

### 3. Rule Class Validation

#### Basic Class Validation
- Class existence check
- Autoloading verification
- Instantiation capability
- Documentation presence

#### Interface Validation
- `RuleInterface` implementation
- Required method presence
- Method signature verification

#### Method Validation
```php
// Required method signature
public function check(string $filePath): array
{
    // Rule implementation
    return [];
}
```

#### Constructor Validation
- No required parameters
- Optional parameters allowed
- Dependency injection support

### 4. Performance Validation

#### Performance Checks
- Rule count analysis
- File size considerations
- Memory usage patterns
- Caching opportunities

#### Performance Warnings
```php
// Triggers performance warning
$rules = [
    'Rule1', 'Rule2', 'Rule3', 'Rule4', 'Rule5',
    'Rule6', 'Rule7', 'Rule8', 'Rule9', 'Rule10',
    'Rule11', 'Rule12', 'Rule13', 'Rule14', 'Rule15',
    'Rule16', 'Rule17', 'Rule18', 'Rule19', 'Rule20',
    'Rule21', // ⚠️ Triggers "too many rules" warning
];
```

### 5. Dependency Validation

#### PHP Extensions
```php
/**
 * @requires-extension json
 * @requires-extension tokenizer
 */
class MyRule implements RuleInterface
{
    // Rule implementation
}
```

#### Composer Packages
```php
/**
 * @requires-package nikic/php-parser
 * @requires-package symfony/console
 */
class MyRule implements RuleInterface
{
    // Rule implementation
}
```

## Usage Examples

### Basic Validation

```php
use ReviewSystem\Engine\RuleValidator;

$config = require 'config.php';
$validator = new RuleValidator($config);

$validation = $validator->validateConfiguration();

if (!$validation['is_valid']) {
    echo "Configuration errors found:\n";
    foreach ($validation['errors'] as $error) {
        echo "- {$error['message']}\n";
        echo "  Suggestion: {$error['suggestion']}\n";
    }
    exit(1);
}

echo "Configuration is valid!\n";
```

### Individual Rule Validation

```php
$ruleClass = 'MyNamespace\MyRule';
$ruleValidation = $validator->validateRule($ruleClass);

if ($ruleValidation['is_valid']) {
    echo "Rule '{$ruleClass}' is valid\n";
} else {
    echo "Rule '{$ruleClass}' has issues:\n";
    foreach ($ruleValidation['errors'] as $error) {
        echo "- {$error['message']}\n";
    }
}
```

### Detailed Reporting

```php
$detailedReport = $validator->getDetailedReport();

echo "Configuration Analysis:\n";
$analysis = $detailedReport['config_analysis'];
echo "- Scan Paths: {$analysis['scan_paths_count']}\n";
echo "- Rules: {$analysis['rules_count']}\n";
echo "- Has File Scanner Config: " . ($analysis['has_file_scanner_config'] ? 'Yes' : 'No') . "\n";

echo "\nRules Analysis:\n";
$rulesAnalysis = $detailedReport['rule_analysis'];
echo "- Total Rules: {$rulesAnalysis['total_rules']}\n";
echo "- Valid Rules: {$rulesAnalysis['valid_rules']}\n";
echo "- Invalid Rules: {$rulesAnalysis['invalid_rules']}\n";

echo "\nRecommendations:\n";
foreach ($detailedReport['recommendations'] as $rec) {
    echo "- [{$rec['priority']}] {$rec['message']}\n";
}
```

### RuleRunner Integration

```php
use ReviewSystem\Engine\RuleRunner;

$runner = new RuleRunner($config);

// Get validation info
$validationInfo = $runner->getValidationInfo();
if (!$validationInfo['is_valid']) {
    echo "Configuration validation failed!\n";
    exit(1);
}

// Get detailed report
$detailedReport = $runner->getDetailedValidationReport();
echo "Validation Summary: {$detailedReport['validation']['summary']['message']}\n";

// Run the review system
$violations = $runner->run();
```

## Validation Results

### Success Response
```php
[
    'is_valid' => true,
    'errors' => [],
    'warnings' => [
        [
            'type' => 'missing_documentation',
            'message' => 'Rule class lacks documentation',
            'suggestion' => 'Add PHPDoc comments',
            'severity' => 'warning'
        ]
    ],
    'info' => [
        [
            'type' => 'class_info',
            'message' => 'Rule class is valid',
            'details' => [
                'namespace' => 'ReviewSystem\Rules',
                'short_name' => 'NoMongoInControllerRule',
                'file' => '/path/to/rule.php'
            ]
        ]
    ],
    'summary' => [
        'total_errors' => 0,
        'total_warnings' => 1,
        'total_info' => 1,
        'is_valid' => true,
        'severity' => 'warning',
        'message' => 'Configuration has 1 warning(s) to review'
    ]
]
```

### Error Response
```php
[
    'is_valid' => false,
    'errors' => [
        [
            'type' => 'class_not_found',
            'message' => 'Rule class \'NonExistentRule\' not found',
            'suggestion' => 'Check if the class exists and is properly autoloaded',
            'severity' => 'error'
        ],
        [
            'type' => 'scan_path_not_found',
            'message' => 'Scan path does not exist: /invalid/path',
            'suggestion' => 'Check if the path exists or update the configuration',
            'severity' => 'error'
        ]
    ],
    'warnings' => [],
    'info' => [],
    'summary' => [
        'total_errors' => 2,
        'total_warnings' => 0,
        'total_info' => 0,
        'is_valid' => false,
        'severity' => 'error',
        'message' => 'Configuration has 2 error(s) that must be fixed'
    ]
]
```

## Error Types

### Configuration Errors
- `missing_config_key` - Required configuration key is missing
- `invalid_scan_paths` - Scan paths is not an array
- `invalid_rules` - Rules is not an array
- `empty_scan_paths` - No scan paths configured
- `invalid_scan_path_type` - Scan path is not a string
- `scan_path_not_found` - Scan path doesn't exist
- `scan_path_not_directory` - Scan path is not a directory
- `scan_path_not_readable` - Scan path is not readable

### Rule Errors
- `class_not_found` - Rule class doesn't exist
- `class_not_instantiable` - Rule class is abstract or interface
- `interface_not_implemented` - Rule doesn't implement RuleInterface
- `missing_check_method` - Required check method is missing
- `check_method_not_public` - Check method is not public
- `invalid_check_method_parameters` - Wrong number of parameters
- `invalid_check_method_parameter_type` - Parameter type is not string
- `invalid_check_method_return_type` - Return type is not array
- `constructor_requires_parameters` - Constructor has required parameters

### Dependency Errors
- `missing_extension` - Required PHP extension is not loaded
- `missing_package` - Required Composer package is not installed

### Performance Warnings
- `too_many_rules` - Large number of rules configured
- `missing_documentation` - Rule class lacks documentation
- `performance_concern` - Potential performance issues detected

## Best Practices

### 1. Rule Development

```php
/**
 * Validates that controllers don't use raw MongoDB access
 * 
 * @requires-package nikic/php-parser
 * @requires-extension tokenizer
 */
class NoMongoInControllerRule implements RuleInterface
{
    /**
     * Check a file for MongoDB usage violations
     * 
     * @param string $filePath Path to the file to check
     * @return array Array of violations found
     */
    public function check(string $filePath): array
    {
        // Rule implementation
        return [];
    }
}
```

### 2. Configuration Validation

```php
// Always validate configuration before running
$validator = new RuleValidator($config);
$validation = $validator->validateConfiguration();

if (!$validation['is_valid']) {
    // Handle validation errors
    foreach ($validation['errors'] as $error) {
        error_log("Configuration error: {$error['message']}");
    }
    throw new RuntimeException('Invalid configuration');
}

// Handle warnings
foreach ($validation['warnings'] as $warning) {
    error_log("Configuration warning: {$warning['message']}");
}
```

### 3. Error Handling

```php
try {
    $rule = new RuleValidator($config);
    $validation = $rule->validateConfiguration();
    
    if (!$validation['is_valid']) {
        // Log detailed error information
        foreach ($validation['errors'] as $error) {
            error_log("Validation error: {$error['type']} - {$error['message']}");
        }
        return false;
    }
    
    return true;
} catch (Throwable $e) {
    error_log("Validation system error: " . $e->getMessage());
    return false;
}
```

### 4. Performance Optimization

```php
// Cache validator instances for repeated use
class ValidationManager
{
    private static $validators = [];
    
    public static function getValidator(array $config): RuleValidator
    {
        $configHash = md5(serialize($config));
        
        if (!isset(self::$validators[$configHash])) {
            self::$validators[$configHash] = new RuleValidator($config);
        }
        
        return self::$validators[$configHash];
    }
}
```

## Troubleshooting

### Common Issues

1. **Class Not Found Errors**
   - Check autoloading configuration
   - Verify namespace declarations
   - Ensure class files exist

2. **Interface Implementation Errors**
   - Add `implements RuleInterface` to class declaration
   - Implement required `check` method
   - Verify method signature

3. **Configuration Errors**
   - Check required keys are present
   - Verify data types (arrays vs strings)
   - Ensure paths exist and are readable

4. **Performance Warnings**
   - Review rule count and complexity
   - Consider caching strategies
   - Optimize file processing

### Debug Mode

```php
// Enable detailed debugging
$validator = new RuleValidator($config);
$detailedReport = $validator->getDetailedReport();

echo "Configuration Analysis:\n";
print_r($detailedReport['config_analysis']);

echo "Rules Analysis:\n";
print_r($detailedReport['rule_analysis']);

echo "Validation Details:\n";
print_r($detailedReport['validation']);
```

## Integration with CI/CD

### Pre-commit Validation

```bash
#!/bin/bash
# pre-commit-validation.sh

php review-system/validate-config.php

if [ $? -ne 0 ]; then
    echo "Configuration validation failed!"
    exit 1
fi

echo "Configuration validation passed!"
```

### GitHub Actions

```yaml
name: Validate Review System Configuration

on: [push, pull_request]

jobs:
  validate:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
    
    - name: Install dependencies
      run: composer install
    
    - name: Validate configuration
      run: php review-system/validate-config.php
```

## Future Enhancements

### Planned Features

1. **Schema Validation**
   - JSON schema for configuration
   - Type validation for all values
   - Custom validation rules

2. **Performance Profiling**
   - Rule execution time measurement
   - Memory usage tracking
   - Performance recommendations

3. **Rule Testing Framework**
   - Unit test validation
   - Integration test validation
   - Test coverage analysis

4. **Configuration Migration**
   - Version compatibility checking
   - Automatic migration suggestions
   - Backward compatibility validation

### Configuration Schema

```php
class ValidationSchema
{
    private array $schema = [
        'scan_paths' => [
            'type' => 'array',
            'required' => true,
            'items' => ['type' => 'string'],
            'min_items' => 1
        ],
        'rules' => [
            'type' => 'array',
            'required' => true,
            'items' => ['type' => 'string'],
            'min_items' => 1
        ],
        'reporting' => [
            'type' => 'object',
            'required' => true,
            'properties' => [
                'output_path' => ['type' => 'string'],
                'filename_format' => ['type' => 'string']
            ]
        ]
    ];
}
```

## Conclusion

The Rule Validation System provides a robust foundation for ensuring the reliability and performance of the review system. By catching configuration errors early and providing detailed feedback, it helps developers maintain high-quality rule implementations and configurations.

Key benefits:
- **Early Error Detection**: Catch issues before runtime
- **Comprehensive Coverage**: Validate all aspects of the system
- **Actionable Feedback**: Clear suggestions for fixing issues
- **Performance Insights**: Identify optimization opportunities
- **Integration Ready**: Easy to integrate into CI/CD pipelines 