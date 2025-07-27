# Configuration Injection System

## Overview

The Review System now uses dependency injection for configuration management, providing better testability, flexibility, and maintainability. The system supports both constructor injection and backward-compatible static methods.

## Architecture

### Before vs After

| Before | After |
|--------|-------|
| Hardcoded config loading | Injected configuration |
| Tight coupling | Loose coupling |
| Difficult to test | Easy to test |
| Static methods only | Instance methods with DI |

### Benefits

- ✅ **Testability**: Easy to inject mock configurations for testing
- ✅ **Flexibility**: Runtime configuration changes
- ✅ **Maintainability**: Clear dependencies and responsibilities
- ✅ **Extensibility**: Easy to add new configuration options
- ✅ **Backward Compatibility**: Existing code continues to work

## Configuration Structure

### Basic Configuration
```php
// config.php
return [
    'scan_paths' => [
        realpath(__DIR__ . '/../app'),
        realpath(__DIR__ . '/../routes'),
    ],
    'reporting' => [
        'output_path' => __DIR__ . '/reports',
        'filename_format' => 'report-{timestamp}.html',
        'exit_on_violation' => true,
        'html' => [
            'title' => 'Code Review Report',
            'include_css' => true,
            'css_path' => 'style.css',
            'show_timestamp' => true,
            'show_violation_count' => true,
            'table_columns' => [
                'file_path' => 'File Path',
                'message' => 'Violation Message',
                'bad_code' => 'Bad Code Sample',
                'suggested_fix' => 'Suggested Fix',
            ],
        ],
    ],
    'rules' => [
        ReviewSystem\Rules\NoMongoInControllerRule::class,
    ],
];
```

### Advanced Configuration Options

#### HTML Report Configuration
```php
'reporting' => [
    'html' => [
        'title' => 'Custom Report Title',
        'include_css' => true,
        'css_path' => 'custom-style.css',
        'show_timestamp' => false,
        'show_violation_count' => true,
        'table_columns' => [
            'file_path' => 'File',
            'message' => 'Issue',
            'bad_code' => 'Problem Code',
            'suggested_fix' => 'Solution',
            'severity' => 'Priority',
        ],
    ],
],
```

## Usage Examples

### 1. Constructor Injection (Recommended)

```php
// Load configuration
$config = require 'config.php';

// Create ReportWriter with injected config
$reportWriter = new ReportWriter($config);

// Generate report
$reportPath = $reportWriter->writeHtml($violations);
```

### 2. Runtime Configuration Changes

```php
$config = require 'config.php';
$reportWriter = new ReportWriter($config);

// Change configuration at runtime
$reportWriter->setConfig([
    'reporting' => [
        'output_path' => '/custom/path',
        'html' => [
            'title' => 'Custom Title',
        ],
    ],
]);

$reportPath = $reportWriter->writeHtml($violations);
```

### 3. Configuration Value Access

```php
$reportWriter = new ReportWriter($config);

// Get specific configuration values
$outputPath = $reportWriter->getConfigValue('reporting.output_path');
$title = $reportWriter->getConfigValue('reporting.html.title', 'Default Title');

// Get entire configuration
$fullConfig = $reportWriter->getConfig();
```

### 4. Backward Compatibility

```php
// Old way still works (deprecated but functional)
$reportPath = ReportWriter::writeHtmlStatic($violations);
```

## Testing with Configuration Injection

### Unit Testing
```php
class ReportWriterTest extends TestCase
{
    public function test_generates_html_report()
    {
        // Mock configuration
        $config = [
            'reporting' => [
                'output_path' => '/tmp/test',
                'filename_format' => 'test-{timestamp}.html',
                'html' => [
                    'title' => 'Test Report',
                    'include_css' => false,
                ],
            ],
        ];

        $reportWriter = new ReportWriter($config);
        $violations = [
            [
                'file' => 'test.php',
                'message' => 'Test violation',
                'bad' => 'bad code',
                'good' => 'good code',
            ],
        ];

        $reportPath = $reportWriter->writeHtml($violations);
        
        $this->assertFileExists($reportPath);
        $this->assertStringContainsString('Test Report', file_get_contents($reportPath));
    }
}
```

### Integration Testing
```php
class ReviewSystemIntegrationTest extends TestCase
{
    public function test_complete_workflow()
    {
        $config = require 'config.php';
        
        // Test with custom configuration
        $config['reporting']['html']['title'] = 'Integration Test Report';
        
        $runner = new RuleRunner($config);
        $reportWriter = new ReportWriter($config);
        
        $violations = $runner->run();
        $reportPath = $reportWriter->writeHtml($violations);
        
        $this->assertFileExists($reportPath);
    }
}
```

## Configuration Validation

### Required Configuration Keys
```php
class ConfigurationValidator
{
    private array $requiredKeys = [
        'scan_paths',
        'reporting.output_path',
        'reporting.filename_format',
        'rules',
    ];

    public function validate(array $config): bool
    {
        foreach ($this->requiredKeys as $key) {
            if (!$this->hasKey($config, $key)) {
                throw new InvalidArgumentException("Missing required configuration key: {$key}");
            }
        }
        return true;
    }

    private function hasKey(array $config, string $key): bool
    {
        $keys = explode('.', $key);
        $value = $config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return false;
            }
            $value = $value[$k];
        }

        return true;
    }
}
```

## Best Practices

### 1. Configuration Organization
```php
// Good: Organized by feature
return [
    'scan_paths' => [...],
    'reporting' => [
        'output_path' => ...,
        'html' => [...],
        'json' => [...],
    ],
    'rules' => [...],
    'logging' => [...],
];

// Bad: Flat structure
return [
    'scan_paths' => [...],
    'output_path' => ...,
    'html_title' => ...,
    'rules' => [...],
];
```

### 2. Default Values
```php
// Always provide sensible defaults
$title = $this->getConfigValue('reporting.html.title', 'Code Review Report');
$includeCss = $this->getConfigValue('reporting.html.include_css', true);
```

### 3. Configuration Validation
```php
// Validate configuration early
$validator = new ConfigurationValidator();
$validator->validate($config);

$reportWriter = new ReportWriter($config);
```

### 4. Environment-Specific Configuration
```php
// config.php
$environment = getenv('APP_ENV') ?: 'production';

$baseConfig = [
    'scan_paths' => [...],
    'rules' => [...],
];

$environmentConfigs = [
    'development' => [
        'reporting' => [
            'output_path' => __DIR__ . '/reports/dev',
            'html' => ['show_timestamp' => true],
        ],
    ],
    'production' => [
        'reporting' => [
            'output_path' => '/var/log/reports',
            'html' => ['show_timestamp' => false],
        ],
    ],
];

return array_merge($baseConfig, $environmentConfigs[$environment] ?? []);
```

## Migration Guide

### From Static Methods to Dependency Injection

#### Before
```php
// Old way
$reportPath = ReportWriter::writeHtml($violations);
```

#### After
```php
// New way
$config = require 'config.php';
$reportWriter = new ReportWriter($config);
$reportPath = $reportWriter->writeHtml($violations);
```

### Gradual Migration
```php
// Step 1: Use both approaches
$config = require 'config.php';

// New approach for new code
$reportWriter = new ReportWriter($config);
$reportPath = $reportWriter->writeHtml($violations);

// Old approach for existing code (deprecated)
$reportPath = ReportWriter::writeHtmlStatic($violations);
```

## Future Enhancements

### 1. Configuration Providers
```php
interface ConfigurationProvider
{
    public function load(): array;
}

class FileConfigurationProvider implements ConfigurationProvider
{
    public function load(): array
    {
        return require 'config.php';
    }
}

class DatabaseConfigurationProvider implements ConfigurationProvider
{
    public function load(): array
    {
        // Load from database
    }
}
```

### 2. Configuration Caching
```php
class CachedConfigurationProvider implements ConfigurationProvider
{
    private $cache;
    private $provider;

    public function load(): array
    {
        if ($this->cache->has('config')) {
            return $this->cache->get('config');
        }

        $config = $this->provider->load();
        $this->cache->set('config', $config, 3600);
        
        return $config;
    }
}
```

### 3. Configuration Validation Schema
```php
class ConfigurationSchema
{
    private array $schema = [
        'scan_paths' => ['type' => 'array', 'required' => true],
        'reporting.output_path' => ['type' => 'string', 'required' => true],
        'reporting.html.title' => ['type' => 'string', 'default' => 'Code Review Report'],
    ];

    public function validate(array $config): bool
    {
        // Validate against schema
    }
}
```

## Troubleshooting

### Common Issues

1. **Configuration not found**
   - Check file paths in configuration
   - Verify configuration file exists
   - Ensure proper permissions

2. **Invalid configuration values**
   - Use configuration validation
   - Check data types
   - Verify required keys

3. **Backward compatibility issues**
   - Use `writeHtmlStatic()` for old code
   - Gradually migrate to constructor injection
   - Test thoroughly after migration

### Debug Configuration
```php
// Debug configuration loading
$config = require 'config.php';
var_dump($config);

$reportWriter = new ReportWriter($config);
echo "Output path: " . $reportWriter->getConfigValue('reporting.output_path') . "\n";
``` 