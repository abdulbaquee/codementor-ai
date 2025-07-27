<?php

namespace ReviewSystem\Engine;

use InvalidArgumentException;
use RuntimeException;
use Throwable;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;

/**
 * Comprehensive rule validation system
 * 
 * Validates:
 * - Rule class existence and autoloading
 * - Rule interface implementation
 * - Rule instantiation requirements
 * - Rule method signatures
 * - Rule configuration integrity
 * - Rule dependencies and requirements
 */
class RuleValidator
{
    private array $config;
    private array $validationErrors = [];
    private array $validationWarnings = [];
    private array $validationInfo = [];

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Validate the entire rule configuration
     */
    public function validateConfiguration(): array
    {
        $this->validationErrors = [];
        $this->validationWarnings = [];
        $this->validationInfo = [];

        $this->validateConfigStructure();
        $this->validateScanPaths();
        $this->validateRulesArray();
        $this->validateIndividualRules();
        $this->validateRuleDependencies();
        $this->validateRulePerformance();

        return [
            'is_valid' => empty($this->validationErrors),
            'errors' => $this->validationErrors,
            'warnings' => $this->validationWarnings,
            'info' => $this->validationInfo,
            'summary' => $this->generateValidationSummary(),
        ];
    }

    /**
     * Validate a specific rule class
     */
    public function validateRule(string $ruleClass): array
    {
        $errors = [];
        $warnings = [];
        $info = [];

        // 1. Basic class validation
        $classValidation = $this->validateRuleClass($ruleClass);
        $errors = array_merge($errors, $classValidation['errors']);
        $warnings = array_merge($warnings, $classValidation['warnings']);
        $info = array_merge($info, $classValidation['info']);

        // If class doesn't exist, stop validation here
        if (!class_exists($ruleClass)) {
            return [
                'is_valid' => false,
                'errors' => $errors,
                'warnings' => $warnings,
                'info' => $info,
            ];
        }

        // 2. Interface validation
        $interfaceValidation = $this->validateRuleInterface($ruleClass);
        $errors = array_merge($errors, $interfaceValidation['errors']);
        $warnings = array_merge($warnings, $interfaceValidation['warnings']);

        // 3. Method validation
        $methodValidation = $this->validateRuleMethods($ruleClass);
        $errors = array_merge($errors, $methodValidation['errors']);
        $warnings = array_merge($warnings, $methodValidation['warnings']);

        // 4. Constructor validation
        $constructorValidation = $this->validateRuleConstructor($ruleClass);
        $errors = array_merge($errors, $constructorValidation['errors']);
        $warnings = array_merge($warnings, $constructorValidation['warnings']);

        // 5. Performance validation
        $this->checkRulePerformanceIssues($ruleClass);

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'info' => $info,
        ];
    }

    /**
     * Validate configuration structure
     */
    private function validateConfigStructure(): void
    {
        $requiredKeys = ['scan_paths', 'rules', 'reporting'];
        $optionalKeys = ['file_scanner'];

        foreach ($requiredKeys as $key) {
            if (!isset($this->config[$key])) {
                $this->validationErrors[] = [
                    'type' => 'missing_config_key',
                    'message' => "Required configuration key '{$key}' is missing",
                    'suggestion' => "Add '{$key}' to your configuration array",
                    'severity' => 'error',
                ];
            }
        }

        // Validate scan_paths is an array
        if (isset($this->config['scan_paths']) && !is_array($this->config['scan_paths'])) {
            $this->validationErrors[] = [
                'type' => 'invalid_scan_paths',
                'message' => "Configuration 'scan_paths' must be an array",
                'suggestion' => "Change scan_paths to an array of directory paths",
                'severity' => 'error',
            ];
        }

        // Validate rules is an array
        if (isset($this->config['rules']) && !is_array($this->config['rules'])) {
            $this->validationErrors[] = [
                'type' => 'invalid_rules',
                'message' => "Configuration 'rules' must be an array",
                'suggestion' => "Change rules to an array of rule class names",
                'severity' => 'error',
            ];
        }
    }

    /**
     * Validate scan paths
     */
    private function validateScanPaths(): void
    {
        if (!isset($this->config['scan_paths'])) {
            return;
        }

        $scanPaths = $this->config['scan_paths'];
        
        if (empty($scanPaths)) {
            $this->validationErrors[] = [
                'type' => 'empty_scan_paths',
                'message' => "No scan paths configured",
                'suggestion' => "Add at least one directory path to scan_paths",
                'severity' => 'error',
            ];
            return;
        }

        foreach ($scanPaths as $index => $path) {
            if (!is_string($path)) {
                $this->validationErrors[] = [
                    'type' => 'invalid_scan_path_type',
                    'message' => "Scan path at index {$index} is not a string",
                    'suggestion' => "Ensure all scan paths are string values",
                    'severity' => 'error',
                ];
                continue;
            }

            if (!file_exists($path)) {
                $this->validationErrors[] = [
                    'type' => 'scan_path_not_found',
                    'message' => "Scan path does not exist: {$path}",
                    'suggestion' => "Check if the path exists or update the configuration",
                    'severity' => 'error',
                ];
            } elseif (!is_dir($path)) {
                $this->validationErrors[] = [
                    'type' => 'scan_path_not_directory',
                    'message' => "Scan path is not a directory: {$path}",
                    'suggestion' => "Ensure the path points to a directory, not a file",
                    'severity' => 'error',
                ];
            } elseif (!is_readable($path)) {
                $this->validationErrors[] = [
                    'type' => 'scan_path_not_readable',
                    'message' => "Scan path is not readable: {$path}",
                    'suggestion' => "Check file permissions for the directory",
                    'severity' => 'error',
                ];
            }
        }
    }

    /**
     * Validate rules array
     */
    private function validateRulesArray(): void
    {
        if (!isset($this->config['rules'])) {
            return;
        }

        $rules = $this->config['rules'];
        
        if (empty($rules)) {
            $this->validationWarnings[] = [
                'type' => 'empty_rules',
                'message' => "No rules configured",
                'suggestion' => "Add at least one rule class to the rules array",
                'severity' => 'warning',
            ];
            return;
        }

        foreach ($rules as $index => $ruleClass) {
            if (!is_string($ruleClass)) {
                $this->validationErrors[] = [
                    'type' => 'invalid_rule_type',
                    'message' => "Rule at index {$index} is not a string",
                    'suggestion' => "Ensure all rules are fully qualified class names",
                    'severity' => 'error',
                ];
            }
        }
    }

    /**
     * Validate individual rules
     */
    private function validateIndividualRules(): void
    {
        if (!isset($this->config['rules'])) {
            return;
        }

        foreach ($this->config['rules'] as $ruleClass) {
            if (!is_string($ruleClass)) {
                continue; // Already handled in validateRulesArray
            }

            $ruleValidation = $this->validateRule($ruleClass);
            
            foreach ($ruleValidation['errors'] as $error) {
                $error['rule_class'] = $ruleClass;
                $this->validationErrors[] = $error;
            }
            
            foreach ($ruleValidation['warnings'] as $warning) {
                $warning['rule_class'] = $ruleClass;
                $this->validationWarnings[] = $warning;
            }
            
            foreach ($ruleValidation['info'] as $info) {
                $info['rule_class'] = $ruleClass;
                $this->validationInfo[] = $info;
            }
        }
    }

    /**
     * Validate rule dependencies
     */
    private function validateRuleDependencies(): void
    {
        if (!isset($this->config['rules'])) {
            return;
        }

        $requiredExtensions = [];
        $requiredPackages = [];

        foreach ($this->config['rules'] as $ruleClass) {
            if (!is_string($ruleClass) || !class_exists($ruleClass)) {
                continue;
            }

            $reflection = new ReflectionClass($ruleClass);
            
            // Check for required PHP extensions
            $this->checkRequiredExtensions($reflection, $ruleClass);
            
            // Check for required Composer packages
            $this->checkRequiredPackages($reflection, $ruleClass);
        }
    }

    /**
     * Validate rule performance
     */
    private function validateRulePerformance(): void
    {
        if (!isset($this->config['rules'])) {
            return;
        }

        $ruleCount = count($this->config['rules']);
        
        if ($ruleCount > 20) {
            $this->validationWarnings[] = [
                'type' => 'too_many_rules',
                'message' => "Large number of rules configured: {$ruleCount}",
                'suggestion' => "Consider grouping related rules or optimizing rule performance",
                'severity' => 'warning',
            ];
        }

        // Check for potential performance issues in individual rules
        foreach ($this->config['rules'] as $ruleClass) {
            if (!is_string($ruleClass) || !class_exists($ruleClass)) {
                continue;
            }

            $this->checkRulePerformanceIssues($ruleClass);
        }
    }

    /**
     * Validate rule class existence and basic structure
     */
    private function validateRuleClass(string $ruleClass): array
    {
        $errors = [];
        $warnings = [];
        $info = [];

        // Check if class exists
        if (!class_exists($ruleClass)) {
            $errors[] = [
                'type' => 'class_not_found',
                'message' => "Rule class '{$ruleClass}' not found",
                'suggestion' => "Check if the class exists and is properly autoloaded",
                'severity' => 'error',
            ];
            return ['errors' => $errors, 'warnings' => $warnings, 'info' => $info];
        }

        $reflection = new ReflectionClass($ruleClass);
        
        // Check if class is instantiable
        if (!$reflection->isInstantiable()) {
            $errors[] = [
                'type' => 'class_not_instantiable',
                'message' => "Rule class '{$ruleClass}' is not instantiable",
                'suggestion' => "Ensure the class is not abstract or an interface",
                'severity' => 'error',
            ];
        }

        // Check class documentation
        if (empty($reflection->getDocComment())) {
            $warnings[] = [
                'type' => 'missing_documentation',
                'message' => "Rule class '{$ruleClass}' lacks documentation",
                'suggestion' => "Add PHPDoc comments to describe the rule's purpose",
                'severity' => 'warning',
            ];
        }

        $info[] = [
            'type' => 'class_info',
            'message' => "Rule class '{$ruleClass}' is valid",
            'details' => [
                'namespace' => $reflection->getNamespaceName(),
                'short_name' => $reflection->getShortName(),
                'file' => $reflection->getFileName(),
            ],
        ];

        return ['errors' => $errors, 'warnings' => $warnings, 'info' => $info];
    }

    /**
     * Validate rule interface implementation
     */
    private function validateRuleInterface(string $ruleClass): array
    {
        $errors = [];
        $warnings = [];

        if (!is_subclass_of($ruleClass, RuleInterface::class)) {
            $errors[] = [
                'type' => 'interface_not_implemented',
                'message' => "Rule class '{$ruleClass}' does not implement RuleInterface",
                'suggestion' => "Add 'implements RuleInterface' to the class declaration",
                'severity' => 'error',
            ];
        } else {
            $warnings[] = [
                'type' => 'interface_implemented',
                'message' => "Rule class '{$ruleClass}' correctly implements RuleInterface",
                'severity' => 'info',
            ];
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Validate rule methods
     */
    private function validateRuleMethods(string $ruleClass): array
    {
        $errors = [];
        $warnings = [];

        $reflection = new ReflectionClass($ruleClass);
        
        // Check if check method exists
        if (!$reflection->hasMethod('check')) {
            $errors[] = [
                'type' => 'missing_check_method',
                'message' => "Rule class '{$ruleClass}' is missing required 'check' method",
                'suggestion' => "Implement public function check(string \$filePath): array",
                'severity' => 'error',
            ];
        } else {
            $checkMethod = $reflection->getMethod('check');
            
            // Validate method visibility
            if (!$checkMethod->isPublic()) {
                $errors[] = [
                    'type' => 'check_method_not_public',
                    'message' => "Check method in '{$ruleClass}' is not public",
                    'suggestion' => "Make the check method public",
                    'severity' => 'error',
                ];
            }

            // Validate method signature
            $this->validateCheckMethodSignature($checkMethod, $ruleClass, $errors, $warnings);
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Validate check method signature
     */
    private function validateCheckMethodSignature(ReflectionMethod $method, string $ruleClass, array &$errors, array &$warnings): void
    {
        $parameters = $method->getParameters();
        
        if (count($parameters) !== 1) {
            $errors[] = [
                'type' => 'invalid_check_method_parameters',
                'message' => "Check method in '{$ruleClass}' must have exactly one parameter",
                'suggestion' => "Method signature should be: check(string \$filePath): array",
                'severity' => 'error',
            ];
            return;
        }

        $parameter = $parameters[0];
        
        // Check parameter type
        if (!$parameter->getType() || $parameter->getType()->getName() !== 'string') {
            $errors[] = [
                'type' => 'invalid_check_method_parameter_type',
                'message' => "Check method parameter in '{$ruleClass}' must be string",
                'suggestion' => "Change parameter type to string",
                'severity' => 'error',
            ];
        }

        // Check parameter name
        if ($parameter->getName() !== 'filePath') {
            $warnings[] = [
                'type' => 'non_standard_parameter_name',
                'message' => "Check method parameter in '{$ruleClass}' has non-standard name: {$parameter->getName()}",
                'suggestion' => "Consider renaming parameter to 'filePath' for consistency",
                'severity' => 'warning',
            ];
        }

        // Check return type
        $returnType = $method->getReturnType();
        if (!$returnType || $returnType->getName() !== 'array') {
            $errors[] = [
                'type' => 'invalid_check_method_return_type',
                'message' => "Check method in '{$ruleClass}' must return array",
                'suggestion' => "Add return type declaration: : array",
                'severity' => 'error',
            ];
        }
    }

    /**
     * Validate rule constructor
     */
    private function validateRuleConstructor(string $ruleClass): array
    {
        $errors = [];
        $warnings = [];

        $reflection = new ReflectionClass($ruleClass);
        $constructor = $reflection->getConstructor();

        if ($constructor && $constructor->getNumberOfRequiredParameters() > 0) {
            $errors[] = [
                'type' => 'constructor_requires_parameters',
                'message' => "Rule class '{$ruleClass}' constructor requires parameters",
                'suggestion' => "Rules must be instantiable without parameters. Use dependency injection or default values.",
                'severity' => 'error',
            ];
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Check required PHP extensions
     */
    private function checkRequiredExtensions(ReflectionClass $reflection, string $ruleClass): void
    {
        $docComment = $reflection->getDocComment();
        if (!$docComment) {
            return;
        }

        // Look for @requires-extension annotations
        if (preg_match_all('/@requires-extension\s+(\w+)/', $docComment, $matches)) {
            foreach ($matches[1] as $extension) {
                if (!extension_loaded($extension)) {
                    $this->validationErrors[] = [
                        'type' => 'missing_extension',
                        'message' => "Rule '{$ruleClass}' requires PHP extension '{$extension}'",
                        'suggestion' => "Install the {$extension} PHP extension",
                        'severity' => 'error',
                        'rule_class' => $ruleClass,
                    ];
                }
            }
        }
    }

    /**
     * Check required Composer packages
     */
    private function checkRequiredPackages(ReflectionClass $reflection, string $ruleClass): void
    {
        $docComment = $reflection->getDocComment();
        if (!$docComment) {
            return;
        }

        // Look for @requires-package annotations
        if (preg_match_all('/@requires-package\s+([^\s]+)/', $docComment, $matches)) {
            foreach ($matches[1] as $package) {
                if (!$this->isPackageInstalled($package)) {
                    $this->validationWarnings[] = [
                        'type' => 'missing_package',
                        'message' => "Rule '{$ruleClass}' may require package '{$package}'",
                        'suggestion' => "Install the package: composer require {$package}",
                        'severity' => 'warning',
                        'rule_class' => $ruleClass,
                    ];
                }
            }
        }
    }

    /**
     * Check if a Composer package is installed
     */
    private function isPackageInstalled(string $package): bool
    {
        $composerLockFile = __DIR__ . '/../../composer.lock';
        if (!file_exists($composerLockFile)) {
            return false;
        }

        $lockData = json_decode(file_get_contents($composerLockFile), true);
        if (!$lockData || !isset($lockData['packages'])) {
            return false;
        }

        foreach ($lockData['packages'] as $installedPackage) {
            if ($installedPackage['name'] === $package) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for potential performance issues in rules
     */
    private function checkRulePerformanceIssues(string $ruleClass): void
    {
        $reflection = new ReflectionClass($ruleClass);
        $fileName = $reflection->getFileName();
        
        if (!$fileName || !file_exists($fileName)) {
            return;
        }

        $content = file_get_contents($fileName);
        
        // Check for potential performance issues
        $performancePatterns = [
            'file_get_contents' => 'Consider using FileScanner for large files',
            'preg_match_all' => 'Consider using AST parsing for complex patterns',
            'foreach.*file' => 'Consider batching file processing',
            'new.*Parser' => 'Consider caching parser instances',
        ];

        foreach ($performancePatterns as $pattern => $suggestion) {
            if (preg_match('/' . $pattern . '/', $content)) {
                $this->validationWarnings[] = [
                    'type' => 'performance_concern',
                    'message' => "Rule '{$ruleClass}' may have performance issues",
                    'suggestion' => $suggestion,
                    'severity' => 'warning',
                    'rule_class' => $ruleClass,
                ];
            }
        }
    }

    /**
     * Generate validation summary
     */
    private function generateValidationSummary(): array
    {
        $errorCount = count($this->validationErrors);
        $warningCount = count($this->validationWarnings);
        $infoCount = count($this->validationInfo);

        $summary = [
            'total_errors' => $errorCount,
            'total_warnings' => $warningCount,
            'total_info' => $infoCount,
            'is_valid' => $errorCount === 0,
            'severity' => $errorCount > 0 ? 'error' : ($warningCount > 0 ? 'warning' : 'success'),
        ];

        if ($errorCount > 0) {
            $summary['message'] = "Configuration has {$errorCount} error(s) that must be fixed";
        } elseif ($warningCount > 0) {
            $summary['message'] = "Configuration has {$warningCount} warning(s) to review";
        } else {
            $summary['message'] = "Configuration is valid";
        }

        return $summary;
    }

    /**
     * Get detailed validation report
     */
    public function getDetailedReport(): array
    {
        $validation = $this->validateConfiguration();
        
        return [
            'validation' => $validation,
            'config_analysis' => $this->analyzeConfiguration(),
            'rule_analysis' => $this->analyzeRules(),
            'recommendations' => $this->generateRecommendations(),
        ];
    }

    /**
     * Analyze configuration structure
     */
    private function analyzeConfiguration(): array
    {
        return [
            'scan_paths_count' => isset($this->config['scan_paths']) ? count($this->config['scan_paths']) : 0,
            'rules_count' => isset($this->config['rules']) ? count($this->config['rules']) : 0,
            'has_file_scanner_config' => isset($this->config['file_scanner']),
            'has_reporting_config' => isset($this->config['reporting']),
            'config_keys' => array_keys($this->config),
        ];
    }

    /**
     * Analyze rules configuration
     */
    private function analyzeRules(): array
    {
        if (!isset($this->config['rules'])) {
            return ['rules_found' => 0];
        }

        $rules = $this->config['rules'];
        $validRules = [];
        $invalidRules = [];

        foreach ($rules as $ruleClass) {
            if (is_string($ruleClass) && class_exists($ruleClass)) {
                $validRules[] = $ruleClass;
            } else {
                $invalidRules[] = $ruleClass;
            }
        }

        return [
            'total_rules' => count($rules),
            'valid_rules' => count($validRules),
            'invalid_rules' => count($invalidRules),
            'valid_rule_classes' => $validRules,
            'invalid_rule_classes' => $invalidRules,
        ];
    }

    /**
     * Generate recommendations based on validation results
     */
    private function generateRecommendations(): array
    {
        $recommendations = [];

        if (empty($this->validationErrors) && empty($this->validationWarnings)) {
            $recommendations[] = [
                'type' => 'success',
                'message' => 'Configuration is well-structured and ready for use',
                'priority' => 'low',
            ];
        }

        if (count($this->validationErrors) > 0) {
            $recommendations[] = [
                'type' => 'critical',
                'message' => 'Fix all validation errors before running the review system',
                'priority' => 'high',
            ];
        }

        if (count($this->validationWarnings) > 0) {
            $recommendations[] = [
                'type' => 'improvement',
                'message' => 'Review and address validation warnings for better performance',
                'priority' => 'medium',
            ];
        }

        return $recommendations;
    }
} 