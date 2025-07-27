<?php

namespace ReviewSystem\Engine;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

class RuleRunner
{
    private array $config;
    private array $errors = [];
    private array $warnings = [];
    private array $info = [];
    private array $performance = [];
    private array $statistics = [];
    private $progressCallback = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Set a progress callback function
     *
     * @param callable $callback Function that receives (step, total, message)
     */
    public function setProgressCallback($callback): void
    {
        $this->progressCallback = $callback;
    }

    /**
     * Trigger progress callback if set
     */
    private function updateProgress(int $step, int $total, string $message = ''): void
    {
        if ($this->progressCallback) {
            call_user_func($this->progressCallback, $step, $total, $message);
        }
    }

    public function run(): array
    {
        $startTime = microtime(true);
        $this->resetLogs();

        try {
            $this->logInfo('Starting code review process...');
            $this->updateProgress(0, 100, 'Starting code review process...');

            // Validate configuration before running
            $this->logInfo('Validating configuration...');
            $this->updateProgress(5, 100, 'Validating configuration...');
            $validator = new RuleValidator($this->config);
            $validation = $validator->validateConfiguration();

            if (!$validation['is_valid']) {
                $this->logError('Configuration validation failed', [
                    'category' => 'CONFIGURATION',
                    'severity' => 'CRITICAL',
                    'details' => 'The review system configuration contains errors that must be fixed before proceeding.'
                ]);

                foreach ($validation['errors'] as $error) {
                    $this->logError($error['message'], [
                        'category' => 'CONFIGURATION',
                        'severity' => 'ERROR',
                        'suggestion' => $error['suggestion'] ?? 'Check your configuration file',
                        'context' => $error['context'] ?? []
                    ]);
                }
                throw new RuntimeException(
                    'Invalid configuration detected. Please fix validation errors before running.'
                );
            }

            if (!empty($validation['warnings'])) {
                $this->logWarning('Configuration warnings detected', [
                    'category' => 'CONFIGURATION',
                    'count' => count($validation['warnings'])
                ]);

                foreach ($validation['warnings'] as $warning) {
                    $this->logWarning($warning['message'], [
                        'category' => 'CONFIGURATION',
                        'suggestion' => $warning['suggestion'] ?? '',
                        'context' => $warning['context'] ?? []
                    ]);
                }
            }

            // Create FileScanner with configuration
            $this->logInfo('Initializing file scanner...');
            $this->updateProgress(10, 100, 'Initializing file scanner...');
            $scannerConfig = $this->config['file_scanner'] ?? [];
            $fileScanner = new FileScanner($scannerConfig);

            $this->logInfo('Scanning files for analysis...');
            $this->updateProgress(15, 100, 'Scanning files for analysis...');
            $scanStartTime = microtime(true);
            $files = $fileScanner->scan($this->config['scan_paths']);
            $scanTime = microtime(true) - $scanStartTime;

            $this->performance['file_scanning'] = $scanTime;
            $this->statistics['files_scanned'] = count($files);
            $this->statistics['scan_paths'] = $this->config['scan_paths'];

            if (empty($files)) {
                $this->logWarning('No PHP files found in scan paths', [
                    'category' => 'FILE_SCANNING',
                    'scan_paths' => $this->config['scan_paths'],
                    'suggestion' => 'Check if the scan paths are correct and contain PHP files'
                ]);
                $this->updateProgress(100, 100, 'No files found to analyze');
                return [];
            }

            $this->logInfo("Found " . count($files) . " PHP files to analyze");
            $this->updateProgress(20, 100, "Found " . count($files) . " PHP files to analyze");

            $allViolations = [];
            $rulesProcessed = 0;
            $rulesFailed = 0;
            $totalRules = count($this->config['rules']);
            $filesPerRule = count($files);

            foreach ($this->config['rules'] as $ruleIndex => $ruleClass) {
                try {
                    $ruleProgress = 20 + (($ruleIndex / $totalRules) * 70); // 20-90% for rules
                    $this->logInfo("Processing rule: {$ruleClass}");
                    $this->updateProgress((int)$ruleProgress, 100, "Processing rule: {$ruleClass}");

                    $ruleStartTime = microtime(true);

                    $rule = $this->createRuleInstance($ruleClass);
                    $ruleViolations = $this->processRuleWithProgress(
                        $rule,
                        $files,
                        $ruleProgress,
                        $totalRules,
                        $ruleIndex
                    );
                    $allViolations = array_merge($allViolations, $ruleViolations);

                    $ruleTime = microtime(true) - $ruleStartTime;
                    $this->performance['rules'][$ruleClass] = $ruleTime;
                    $rulesProcessed++;

                    $this->logInfo("Rule '{$ruleClass}' completed successfully", [
                        'category' => 'RULE_PROCESSING',
                        'violations_found' => count($ruleViolations),
                        'processing_time' => round($ruleTime, 3) . 's'
                    ]);
                } catch (Throwable $e) {
                    $rulesFailed++;
                    $this->handleRuleError($ruleClass, $e);
                }
            }

            $totalTime = microtime(true) - $startTime;
            $this->performance['total_time'] = $totalTime;
            $this->statistics['rules_processed'] = $rulesProcessed;
            $this->statistics['rules_failed'] = $rulesFailed;
            $this->statistics['total_violations'] = count($allViolations);

            $this->logInfo('Code review process completed', [
                'category' => 'COMPLETION',
                'total_time' => round($totalTime, 3) . 's',
                'rules_processed' => $rulesProcessed,
                'rules_failed' => $rulesFailed,
                'violations_found' => count($allViolations)
            ]);

            $this->updateProgress(95, 100, 'Generating final report...');

            return $allViolations;
        } catch (Throwable $e) {
            $totalTime = microtime(true) - $startTime;
            $this->performance['total_time'] = $totalTime;

            $this->logError('Critical error in RuleRunner', [
                'category' => 'CRITICAL',
                'severity' => 'CRITICAL',
                'error_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'total_time' => round($totalTime, 3) . 's'
            ]);

            $this->updateProgress(100, 100, 'Error occurred during processing');
            throw new RuntimeException('Failed to run code review: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get any errors that occurred during the run
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get any warnings that occurred during the run
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Get informational messages from the run
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    /**
     * Check if any errors occurred during the run
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Check if any warnings occurred during the run
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Get performance metrics from the run
     */
    public function getPerformance(): array
    {
        return $this->performance;
    }

    /**
     * Get statistics from the run
     */
    public function getStatistics(): array
    {
        return $this->statistics;
    }

    /**
     * Get a comprehensive report of the run
     */
    public function getRunReport(): array
    {
        return [
            'summary' => [
                'has_errors' => $this->hasErrors(),
                'has_warnings' => $this->hasWarnings(),
                'error_count' => count($this->errors),
                'warning_count' => count($this->warnings),
                'info_count' => count($this->info)
            ],
            'performance' => $this->performance,
            'statistics' => $this->statistics,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'info' => $this->info
        ];
    }

    /**
     * Get validation information for the current configuration
     */
    public function getValidationInfo(): array
    {
        $validator = new RuleValidator($this->config);
        return $validator->validateConfiguration();
    }

    /**
     * Get detailed validation report
     */
    public function getDetailedValidationReport(): array
    {
        $validator = new RuleValidator($this->config);
        return $validator->getDetailedReport();
    }

    /**
     * Reset all log arrays
     */
    private function resetLogs(): void
    {
        $this->errors = [];
        $this->warnings = [];
        $this->info = [];
        $this->performance = [];
        $this->statistics = [];
    }

    /**
     * Create a rule instance with proper validation
     */
    private function createRuleInstance(string $ruleClass): RuleInterface
    {
        // Validate class exists
        if (!class_exists($ruleClass)) {
            throw new InvalidArgumentException(
                "Rule class '{$ruleClass}' not found. Check if the class exists and is autoloaded."
            );
        }

        // Validate class implements RuleInterface
        if (!is_subclass_of($ruleClass, RuleInterface::class)) {
            throw new InvalidArgumentException(
                "Rule class '{$ruleClass}' must implement " . RuleInterface::class
            );
        }

        // Validate class is instantiable (not abstract or interface)
        $reflection = new \ReflectionClass($ruleClass);
        if (!$reflection->isInstantiable()) {
            throw new InvalidArgumentException(
                "Rule class '{$ruleClass}' is not instantiable (abstract class or interface)"
            );
        }

        // Validate constructor parameters
        $constructor = $reflection->getConstructor();
        if ($constructor && $constructor->getNumberOfRequiredParameters() > 0) {
            throw new InvalidArgumentException(
                "Rule class '{$ruleClass}' has required constructor parameters. " .
                "Rules must be instantiable without parameters."
            );
        }

        return new $ruleClass();
    }

    /**
     * Process a single rule against all files with progress updates
     */
    private function processRuleWithProgress(
        RuleInterface $rule,
        array $files,
        float $ruleStartProgress,
        int $totalRules,
        int $ruleIndex
    ): array {
        $ruleViolations = [];
        $ruleClass = get_class($rule);
        $filesProcessed = 0;
        $filesFailed = 0;
        $totalFiles = count($files);

        foreach ($files as $fileIndex => $file) {
            try {
                // Calculate progress within this rule
                $fileProgress = $ruleStartProgress + (($fileIndex / $totalFiles) * (70 / $totalRules));
                $this->updateProgress(
                    (int)$fileProgress,
                    100,
                    "Processing {$file} with {$ruleClass}"
                );

                if (!file_exists($file)) {
                    $this->logWarning("File not found during rule processing", [
                        'category' => 'FILE_PROCESSING',
                        'file' => $file,
                        'rule' => $ruleClass,
                        'suggestion' => 'Check if the file was moved or deleted'
                    ]);
                    $filesFailed++;
                    continue;
                }

                if (!is_readable($file)) {
                    $this->logWarning("File not readable during rule processing", [
                        'category' => 'FILE_PROCESSING',
                        'file' => $file,
                        'rule' => $ruleClass,
                        'suggestion' => 'Check file permissions'
                    ]);
                    $filesFailed++;
                    continue;
                }

                $violations = $rule->check($file);

                // Validate violation format
                if (!empty($violations)) {
                    $validatedViolations = $this->validateViolations($violations, $file, $ruleClass);
                    $ruleViolations = array_merge($ruleViolations, $validatedViolations);
                }

                $filesProcessed++;
            } catch (Throwable $e) {
                $filesFailed++;
                $this->logError("Error processing file with rule", [
                    'category' => 'FILE_PROCESSING',
                    'file' => $file,
                    'rule' => $ruleClass,
                    'error_type' => get_class($e),
                    'message' => $e->getMessage(),
                    'suggestion' => 'Check if the file contains valid PHP code'
                ]);
            }
        }

        // Log rule processing summary
        if ($filesFailed > 0) {
            $this->logWarning("Rule processing completed with some failures", [
                'category' => 'RULE_PROCESSING',
                'rule' => $ruleClass,
                'files_processed' => $filesProcessed,
                'files_failed' => $filesFailed,
                'violations_found' => count($ruleViolations)
            ]);
        }

        return $ruleViolations;
    }

    /**
     * Validate violation format and add metadata
     */
    private function validateViolations(array $violations, string $file, string $ruleClass): array
    {
        $validatedViolations = [];
        $invalidViolations = 0;

        foreach ($violations as $index => $violation) {
            try {
                $validatedViolation = $this->validateViolation($violation, $file, $ruleClass, $index);
                if ($validatedViolation) {
                    $validatedViolations[] = $validatedViolation;
                } else {
                    $invalidViolations++;
                }
            } catch (Throwable $e) {
                $invalidViolations++;
                $this->logError("Invalid violation format", [
                    'category' => 'VIOLATION_VALIDATION',
                    'rule' => $ruleClass,
                    'file' => $file,
                    'violation_index' => $index,
                    'error' => $e->getMessage(),
                    'suggestion' => 'Check the violation format in the rule implementation'
                ]);
            }
        }

        if ($invalidViolations > 0) {
            $this->logWarning("Some violations were invalid and skipped", [
                'category' => 'VIOLATION_VALIDATION',
                'rule' => $ruleClass,
                'file' => $file,
                'invalid_count' => $invalidViolations,
                'valid_count' => count($validatedViolations)
            ]);
        }

        return $validatedViolations;
    }

    /**
     * Validate a single violation and add metadata
     */
    private function validateViolation(array $violation, string $file, string $ruleClass, int $index): ?array
    {
        // Required fields
        $requiredFields = ['message'];
        foreach ($requiredFields as $field) {
            if (!isset($violation[$field]) || empty($violation[$field])) {
                $this->logError("Violation missing required field", [
                    'category' => 'VIOLATION_VALIDATION',
                    'rule' => $ruleClass,
                    'file' => $file,
                    'violation_index' => $index,
                    'missing_field' => $field,
                    'suggestion' => 'Ensure all violations have a message field'
                ]);
                return null;
            }
        }

        // Set default values for optional fields
        $violation['file'] = $violation['file'] ?? $file;
        $violation['rule'] = $violation['rule'] ?? $ruleClass;
        $violation['bad'] = $violation['bad'] ?? 'N/A';
        $violation['good'] = $violation['good'] ?? 'N/A';
        $violation['line'] = $violation['line'] ?? null;
        $violation['severity'] = $violation['severity'] ?? 'warning';

        return $violation;
    }

    /**
     * Handle errors for a specific rule
     */
    private function handleRuleError(string $ruleClass, Throwable $e): void
    {
        $errorContext = [
            'category' => 'RULE_ERROR',
            'rule' => $ruleClass,
            'error_type' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];

        if ($e instanceof InvalidArgumentException) {
            $errorContext['severity'] = 'ERROR';
            $errorContext['suggestion'] = 'Check your configuration file and ensure the rule class is ' .
                'properly configured';
        } else {
            $errorContext['severity'] = 'CRITICAL';
            $errorContext['suggestion'] = 'Check the rule implementation for bugs or compatibility issues';
        }

        $this->logError("Rule '{$ruleClass}' failed: " . $e->getMessage(), $errorContext);
    }

    /**
     * Log an error message with context
     */
    private function logError(string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $error = array_merge([
            'timestamp' => $timestamp,
            'message' => $message,
            'level' => 'ERROR'
        ], $context);

        $this->errors[] = $error;

        // Also log to PHP error log for debugging
        $logMessage = "[ReviewSystem ERROR] {$timestamp} - {$message}";
        if (!empty($context['category'])) {
            $logMessage .= " [{$context['category']}]";
        }
        error_log($logMessage);
    }

    /**
     * Log a warning message with context
     */
    private function logWarning(string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $warning = array_merge([
            'timestamp' => $timestamp,
            'message' => $message,
            'level' => 'WARNING'
        ], $context);

        $this->warnings[] = $warning;

        // Also log to PHP error log for debugging
        $logMessage = "[ReviewSystem WARNING] {$timestamp} - {$message}";
        if (!empty($context['category'])) {
            $logMessage .= " [{$context['category']}]";
        }
        error_log($logMessage);
    }

    /**
     * Log an informational message with context
     */
    private function logInfo(string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $info = array_merge([
            'timestamp' => $timestamp,
            'message' => $message,
            'level' => 'INFO'
        ], $context);

        $this->info[] = $info;

        // Also log to PHP error log for debugging
        $logMessage = "[ReviewSystem INFO] {$timestamp} - {$message}";
        if (!empty($context['category'])) {
            $logMessage .= " [{$context['category']}]";
        }
        error_log($logMessage);
    }
}
