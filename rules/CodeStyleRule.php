<?php

namespace ReviewSystem\Rules;

use ReviewSystem\Engine\PerformanceOptimizedRule;
use ReviewSystem\Engine\RuleCategory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp\Concat;

class CodeStyleRule extends PerformanceOptimizedRule
{
    public function getCategory(): string
    {
        return RuleCategory::STYLE;
    }

    public function getName(): string
    {
        return 'Code Style Standards';
    }

    public function getDescription(): string
    {
        return 'Enforces comprehensive code style standards including naming conventions, structure, and readability.';
    }

    public function getSeverity(): string
    {
        return 'warning';
    }

    public function getTags(): array
    {
        return ['style', 'naming', 'structure', 'readability', 'conventions'];
    }

    public function isEnabledByDefault(): bool
    {
        return true;
    }

    public function check(string $filePath): array
    {
        return parent::check($filePath);
    }

    /**
     * Perform code style checks on the AST
     */
    protected function performChecks(array $ast, string $filePath): array
    {
        $violations = [];

        // Check for various style issues
        $violations = array_merge($violations, $this->checkNamingConventions($ast, $filePath));
        $violations = array_merge($violations, $this->checkCodeStructure($ast, $filePath));
        $violations = array_merge($violations, $this->checkReadabilityIssues($ast, $filePath));
        $violations = array_merge($violations, $this->checkLaravelConventions($ast, $filePath));

        return $violations;
    }

    private function checkNamingConventions(array $ast, string $filePath): array
    {
        $violations = [];
        $nodeFinder = $this->getNodeFinder();

        // Check class naming
        $classes = $nodeFinder->findInstanceOf($ast, Class_::class);
        foreach ($classes as $class) {
            $className = $class->name->toString();

            // Check for proper PascalCase
            if (!preg_match('/^[A-Z][a-zA-Z0-9]*$/', $className)) {
                $violations[] = [
                    'file' => $filePath,
                    'line' => $class->getStartLine(),
                    'message' => 'Class name should use PascalCase: ' . $className,
                    'bad_code' => 'class ' . $className,
                    'suggested_fix' => 'class ' . ucfirst($className),
                    'severity' => 'warning',
                    'category' => 'naming'
                ];
            }

            // Check for descriptive names
            if (strlen($className) < 3) {
                $violations[] = [
                    'file' => $filePath,
                    'line' => $class->getStartLine(),
                    'message' => 'Class name should be descriptive: ' . $className,
                    'bad_code' => 'class ' . $className,
                    'suggested_fix' => 'Use a more descriptive name',
                    'severity' => 'info',
                    'category' => 'naming'
                ];
            }
        }

        // Check method naming
        $methods = $nodeFinder->findInstanceOf($ast, ClassMethod::class);
        foreach ($methods as $method) {
            $methodName = $method->name->toString();

            // Check for proper camelCase
            if (!preg_match('/^[a-z][a-zA-Z0-9]*$/', $methodName)) {
                $violations[] = [
                    'file' => $filePath,
                    'line' => $method->getStartLine(),
                    'message' => 'Method name should use camelCase: ' . $methodName,
                    'bad_code' => 'function ' . $methodName,
                    'suggested_fix' => 'function ' . lcfirst($methodName),
                    'severity' => 'warning',
                    'category' => 'naming'
                ];
            }

            // Check for boolean method naming
            if ($this->isBooleanMethod($method) && !$this->hasBooleanPrefix($methodName)) {
                $violations[] = [
                    'file' => $filePath,
                    'line' => $method->getStartLine(),
                    'message' => 'Boolean method should start with is/has/can: ' . $methodName,
                    'bad_code' => 'function ' . $methodName,
                    'suggested_fix' => 'function is' . ucfirst($methodName),
                    'severity' => 'info',
                    'category' => 'naming'
                ];
            }
        }

        return $violations;
    }

    private function checkCodeStructure(array $ast, string $filePath): array
    {
        $violations = [];
        $nodeFinder = $this->getNodeFinder();

        // Check for proper namespace usage
        $namespaces = $nodeFinder->findInstanceOf($ast, Namespace_::class);
        if (empty($namespaces)) {
            $violations[] = [
                'file' => $filePath,
                'line' => 1,
                'message' => 'File should use proper namespace declaration.',
                'bad_code' => 'No namespace declared',
                'suggested_fix' => 'namespace App\\YourNamespace;',
                'severity' => 'warning',
                'category' => 'structure'
            ];
        }

        // Check for proper use statements
        $uses = $nodeFinder->findInstanceOf($ast, Use_::class);
        foreach ($uses as $use) {
            foreach ($use->uses as $useUse) {
                $className = $useUse->name->toString();

                // Check for unused imports (simplified check)
                if (!$this->isClassUsed($ast, $className)) {
                    $violations[] = [
                        'file' => $filePath,
                        'line' => $use->getStartLine(),
                        'message' => 'Unused import: ' . $className,
                        'bad_code' => 'use ' . $className . ';',
                        'suggested_fix' => 'Remove unused import',
                        'severity' => 'warning',
                        'category' => 'structure'
                    ];
                }
            }
        }

        return $violations;
    }

    private function checkReadabilityIssues(array $ast, string $filePath): array
    {
        $violations = [];
        $nodeFinder = $this->getNodeFinder();

        // Check for magic numbers
        $magicNumbers = $nodeFinder->findInstanceOf($ast, Node\Scalar\LNumber::class);

        foreach ($magicNumbers as $number) {
            if ($number->value > 10 && $number->value !== 1000) { // Common threshold
                $violations[] = [
                    'file' => $filePath,
                    'line' => $number->getStartLine(),
                    'message' => 'Magic number detected: ' . $number->value,
                    'bad_code' => (string) $number->value,
                    'suggested_fix' => 'Define as a named constant',
                    'severity' => 'info',
                    'category' => 'readability'
                ];
            }
        }

        // Check for long lines (simplified)
        $lines = file($filePath);
        foreach ($lines as $lineNumber => $line) {
            if (strlen($line) > 120) {
                $violations[] = [
                    'file' => $filePath,
                    'line' => $lineNumber + 1,
                    'message' => 'Line is too long (' . strlen($line) . ' characters)',
                    'bad_code' => trim($line),
                    'suggested_fix' => 'Break into multiple lines',
                    'severity' => 'warning',
                    'category' => 'readability'
                ];
            }
        }

        return $violations;
    }

    private function checkLaravelConventions(array $ast, string $filePath): array
    {
        $violations = [];
        $nodeFinder = $this->getNodeFinder();

        // Check for Laravel-specific naming conventions
        $classes = $nodeFinder->findInstanceOf($ast, Class_::class);
        foreach ($classes as $class) {
            $className = $class->name->toString();

            // Check controller naming
            if (str_contains($className, 'Controller') && !str_ends_with($className, 'Controller')) {
                $violations[] = [
                    'file' => $filePath,
                    'line' => $class->getStartLine(),
                    'message' => 'Controller class should end with "Controller": ' . $className,
                    'bad_code' => 'class ' . $className,
                    'suggested_fix' => 'class ' . $className . 'Controller',
                    'severity' => 'warning',
                    'category' => 'conventions'
                ];
            }

            // Check model naming
            if (str_contains($className, 'Model') && !str_ends_with($className, 'Model')) {
                $violations[] = [
                    'file' => $filePath,
                    'line' => $class->getStartLine(),
                    'message' => 'Model class should end with "Model": ' . $className,
                    'bad_code' => 'class ' . $className,
                    'suggested_fix' => 'class ' . $className . 'Model',
                    'severity' => 'warning',
                    'category' => 'conventions'
                ];
            }
        }

        return $violations;
    }

    private function isBooleanMethod(ClassMethod $method): bool
    {
        // Check if method returns boolean
        if ($method->returnType) {
            return $method->returnType->toString() === 'bool';
        }

        // Check method name for boolean indicators
        $methodName = $method->name->toString();
        return str_starts_with($methodName, 'is') ||
               str_starts_with($methodName, 'has') ||
               str_starts_with($methodName, 'can');
    }

    private function hasBooleanPrefix(string $methodName): bool
    {
        return str_starts_with($methodName, 'is') ||
               str_starts_with($methodName, 'has') ||
               str_starts_with($methodName, 'can');
    }

    private function isClassUsed(array $ast, string $className): bool
    {
        // Simplified check - in real implementation, you'd need more sophisticated analysis
        $code = $this->astToString($ast);
        $shortName = $this->getShortClassName($className);

        return str_contains($code, $shortName) || str_contains($code, $className);
    }

    private function getShortClassName(string $fullClassName): string
    {
        $parts = explode('\\', $fullClassName);
        return end($parts);
    }

    private function astToString(array $ast): string
    {
        // Simplified AST to string conversion
        return 'ast_content';
    }
}
