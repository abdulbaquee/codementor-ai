# Progress Indicators System

## Overview

The Review System now features comprehensive progress indicators that provide real-time feedback during code review operations, making it easy to monitor progress for large codebases and long-running processes.

## Features

### 🎯 **Real-Time Progress Bars**
- **Interactive Terminals**: Beautiful ASCII progress bars with percentage and status
- **Non-Interactive Fallback**: Text-based progress for CI/CD and redirected output
- **Smart Detection**: Automatically detects terminal capabilities and CI environments

### 📊 **Comprehensive Progress Tracking**
- **Multi-Level Progress**: Configuration validation, file scanning, rule processing
- **File-Level Updates**: Real-time updates as individual files are processed
- **Rule-Level Progress**: Progress tracking across multiple rules
- **Time Tracking**: Execution time and processing rate calculation

### 🔄 **Status Messages**
- **Contextual Updates**: Detailed status messages during each phase
- **Error Integration**: Progress indicators work seamlessly with error reporting
- **Completion Summary**: Comprehensive summary with statistics and recent activity

### 🎨 **User Experience**
- **Visual Feedback**: Clear progress visualization with Unicode characters
- **Responsive Design**: Adapts to terminal width and capabilities
- **Non-Blocking**: Progress updates don't interfere with processing
- **Accessibility**: Works in both interactive and non-interactive environments

## Usage

### Basic Usage

```php
use ReviewSystem\Engine\ProgressIndicator;
use ReviewSystem\Engine\RuleRunner;

// Create progress indicator
$progress = new ProgressIndicator();

// Create rule runner and set progress callback
$runner = new RuleRunner($config);
$runner->setProgressCallback(function(int $step, int $total, string $message) use ($progress) {
    $progress->update($step, $message);
});

// Initialize and run
$progress->initialize(100, 'Code Review Process');
$violations = $runner->run();
$progress->complete('Code review completed successfully');
```

### CLI Integration

The CLI automatically uses progress indicators:

```bash
php review-system/cli.php
```

**Interactive Terminal Output:**
```
🚀 Code Review Process
[██████████████████████████████████████████████████] 100% - Code review completed successfully
⏱️  Total time: 0.052s
```

**Non-Interactive Output:**
```
🚀 Code Review Process
Progress: 0/100 (0%)
Progress: 5/100 (5%) - Validating configuration...
Progress: 10/100 (10%) - Initializing file scanner...
Progress: 15/100 (15%) - Scanning files for analysis...
Progress: 20/100 (20%) - Found 23 PHP files to analyze
Progress: 100/100 (100%) - Code review completed successfully
⏱️  Total time: 0.052s
```

## ProgressIndicator Class

### Constructor
```php
$progress = new ProgressIndicator();
```

### Methods

#### `initialize(int $totalSteps, string $title = 'Processing'): void`
Initialize the progress indicator with total steps and title.

```php
$progress->initialize(100, 'Code Review Process');
```

#### `update(int $step, string $status = ''): void`
Update progress to a specific step with optional status message.

```php
$progress->update(50, 'Processing files...');
```

#### `advance(string $status = ''): void`
Advance progress by one step with optional status message.

```php
$progress->advance('File processed successfully');
```

#### `complete(string $message = 'Completed'): void`
Complete the progress indicator with final message and timing.

```php
$progress->complete('Code review completed successfully');
```

#### `status(string $message): void`
Display an informational status message.

```php
$progress->status('Generating HTML report...');
```

#### `warning(string $message): void`
Display a warning message.

```php
$progress->warning('Some files could not be processed');
```

#### `error(string $message): void`
Display an error message.

```php
$progress->error('Critical error occurred');
```

#### `success(string $message): void`
Display a success message.

```php
$progress->success('No violations found');
```

#### `displaySummary(): void`
Display a comprehensive summary of the progress.

```php
$progress->displaySummary();
```

**Output:**
```
📊 Summary:
   • Total steps: 100
   • Completed: 95
   • Total time: 0.095s
   • Rate: 1049.62 steps/second
   • Recent activity:
     - Processing file1.php with RuleClass
     - Processing file2.php with RuleClass
     - Generating final report...
```

## RuleRunner Integration

### Setting Progress Callback

```php
$runner = new RuleRunner($config);
$runner->setProgressCallback(function(int $step, int $total, string $message) use ($progress) {
    $progress->update($step, $message);
});
```

### Progress Phases

The RuleRunner provides progress updates for these phases:

1. **Configuration Validation** (0-5%)
   - Validating configuration settings
   - Checking rule classes and dependencies

2. **File Scanner Initialization** (5-10%)
   - Setting up file scanner
   - Preparing scan paths

3. **File Scanning** (10-15%)
   - Discovering PHP files
   - Building file list

4. **Rule Processing** (20-90%)
   - Processing each rule against all files
   - File-level progress updates within each rule

5. **Report Generation** (95-100%)
   - Generating final report
   - Completing the process

### Progress Calculation

```php
// Rule progress calculation
$ruleProgress = 20 + (($ruleIndex / $totalRules) * 70);

// File progress within rule
$fileProgress = $ruleProgress + (($fileIndex / $totalFiles) * (70 / $totalRules));
```

## Terminal Detection

### Interactive Terminal Features
- **Progress Bars**: Visual progress bars with Unicode characters
- **Real-time Updates**: In-place progress updates
- **Status Messages**: Inline status text
- **Terminal Width**: Adaptive to terminal size

### Non-Interactive Fallback
- **Text-based Progress**: Simple text progress lines
- **CI/CD Compatible**: Works in automated environments
- **Redirected Output**: Handles piped output gracefully

### Detection Logic

```php
private function isInteractiveTerminal(): bool
{
    // Check if we're in a terminal and not redirected
    if (!posix_isatty(STDOUT)) {
        return false;
    }
    
    // Check for common CI environments
    $ciEnvironments = ['CI', 'TRAVIS', 'CIRCLECI', 'JENKINS', 'GITHUB_ACTIONS'];
    foreach ($ciEnvironments as $env) {
        if (getenv($env)) {
            return false;
        }
    }
    
    return true;
}
```

## Progress Bar Design

### Visual Elements

**Interactive Mode:**
```
[██████████████████████████████████████████████████] 100% - Status message
```

**Non-Interactive Mode:**
```
Progress: 100/100 (100%) - Status message
```

### Unicode Characters
- **Filled Block**: `█` (U+2588) for completed progress
- **Empty Block**: `░` (U+2591) for remaining progress
- **Progress Bar**: `[████░░░░]` format

### Terminal Width Adaptation
```php
$barWidth = min(50, $this->terminalWidth - 20);
$filledWidth = round(($percentage / 100) * $barWidth);
$emptyWidth = $barWidth - $filledWidth;
```

## Performance Considerations

### Update Frequency
- **Smart Updates**: Only updates when percentage changes
- **Rate Limiting**: Prevents excessive output in fast operations
- **Memory Efficient**: Minimal memory footprint for progress tracking

### Timing Integration
```php
private float $startTime;

public function __construct()
{
    $this->startTime = microtime(true);
}

public function complete(string $message = 'Completed'): void
{
    $totalTime = microtime(true) - $this->startTime;
    // Display timing information
}
```

## Error Handling

### Progress with Errors
```php
try {
    $progress->initialize(100, 'Code Review Process');
    $violations = $runner->run();
    $progress->complete('Code review completed successfully');
} catch (Throwable $e) {
    $progress->error("Critical error occurred: {$e->getMessage()}");
    // Handle error
}
```

### Error Integration
- **Non-blocking**: Errors don't stop progress updates
- **Error Messages**: Progress indicator displays error messages
- **Graceful Degradation**: Continues to work even with errors

## Best Practices

### 1. **Appropriate Step Counts**
```php
// Good: Realistic step count
$progress->initialize(100, 'Processing');

// Avoid: Too many or too few steps
$progress->initialize(1000000, 'Processing'); // Too many
$progress->initialize(3, 'Processing');       // Too few
```

### 2. **Meaningful Status Messages**
```php
// Good: Descriptive status
$progress->update(50, 'Processing user authentication files');

// Avoid: Generic status
$progress->update(50, 'Working...');
```

### 3. **Error Handling**
```php
// Good: Handle errors gracefully
try {
    $progress->update(75, 'Processing files...');
    // Process files
} catch (Exception $e) {
    $progress->error("Error processing files: {$e->getMessage()}");
}
```

### 4. **Completion Messages**
```php
// Good: Informative completion
$progress->complete("Found {$violationCount} violations");

// Avoid: Generic completion
$progress->complete('Done');
```

## Configuration

### Environment Variables
```bash
# Terminal width (auto-detected if not set)
COLUMNS=120

# CI environment detection
CI=true
TRAVIS=true
CIRCLECI=true
JENKINS=true
GITHUB_ACTIONS=true
```

### Disabling Progress Indicators
```php
// For CI/CD environments, you can disable progress indicators
if (getenv('CI')) {
    // Use minimal output mode
    $progress = new MinimalProgressIndicator();
}
```

## Examples

### Simple Progress
```php
$progress = new ProgressIndicator();
$progress->initialize(10, 'Simple Task');

for ($i = 1; $i <= 10; $i++) {
    // Do work
    $progress->advance("Completed step {$i}");
}

$progress->complete('Task completed');
```

### Complex Multi-Phase Progress
```php
$progress = new ProgressIndicator();
$progress->initialize(100, 'Multi-Phase Process');

// Phase 1: Setup (0-20%)
$progress->update(5, 'Initializing...');
$progress->update(10, 'Loading configuration...');
$progress->update(20, 'Setup complete');

// Phase 2: Processing (20-80%)
for ($i = 0; $i < 60; $i++) {
    $progress->update(20 + $i, "Processing item {$i}");
}

// Phase 3: Finalization (80-100%)
$progress->update(90, 'Finalizing...');
$progress->update(100, 'Process complete');

$progress->complete('Multi-phase process completed successfully');
```

### Integration with RuleRunner
```php
$progress = new ProgressIndicator();
$runner = new RuleRunner($config);

$runner->setProgressCallback(function(int $step, int $total, string $message) use ($progress) {
    $progress->update($step, $message);
});

$progress->initialize(100, 'Code Review Process');
$violations = $runner->run();
$progress->complete("Found " . count($violations) . " violations");
```

## Benefits

### 🎯 **For Developers**
- **Real-time Feedback**: Immediate progress visibility
- **Performance Insights**: Time tracking and rate calculation
- **Debugging Support**: Detailed status messages
- **User Experience**: Professional, polished interface

### 🏢 **For Teams**
- **Large Codebases**: Handle thousands of files efficiently
- **CI/CD Integration**: Works in automated environments
- **Monitoring**: Track processing performance
- **Communication**: Clear status for stakeholders

### 🔧 **For System Administrators**
- **Resource Monitoring**: Track processing time and rates
- **Error Detection**: Immediate visibility into issues
- **Performance Optimization**: Identify bottlenecks
- **Logging Integration**: Structured progress information

## Future Enhancements

### Planned Features
- **Multi-threaded Progress**: Progress tracking for parallel processing
- **Progress Persistence**: Save and resume progress for long operations
- **Custom Progress Bars**: User-defined progress bar styles
- **Progress Analytics**: Historical progress data and trends
- **Web Interface**: Web-based progress monitoring
- **Progress Notifications**: Email/Slack notifications for completion 