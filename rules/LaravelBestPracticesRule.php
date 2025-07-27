<?php

namespace ReviewSystem\Rules;

use ReviewSystem\Engine\PerformanceOptimizedRule;
use ReviewSystem\Engine\RuleCategory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;

/**
 * Laravel Best Practices Rule
 *
 * This rule enforces Laravel-specific best practices including:
 * - Input validation in controllers
 * - Security vulnerability detection
 * - Performance anti-patterns
 * - Common architectural issues
 *
 * The rule uses AST parsing to analyze PHP code and identify violations
 * of Laravel best practices, providing detailed suggestions for improvements.
 *
 * @package ReviewSystem\Rules
 * @author Review System
 * @version 1.0.0
 */
class LaravelBestPracticesRule extends PerformanceOptimizedRule
{
    public function getCategory(): string
    {
        return RuleCategory::BEST_PRACTICE;
    }

    public function getName(): string
    {
        return 'Laravel Best Practices';
    }

    public function getDescription(): string
    {
        return 'Enforces Laravel best practices including validation, security, and common anti-patterns.';
    }

    public function getSeverity(): string
    {
        return 'warning';
    }

    public function getTags(): array
    {
        return ['laravel', 'best-practices', 'validation', 'security', 'performance'];
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
     * Perform Laravel best practices checks on the AST
     */
    protected function performChecks(array $ast, string $filePath): array
    {
        $violations = [];

        // Check for various Laravel best practices
        $violations = array_merge($violations, $this->checkValidationUsage($ast, $filePath));
        $violations = array_merge($violations, $this->checkSecurityIssues($ast, $filePath));
        $violations = array_merge($violations, $this->checkPerformanceIssues($ast, $filePath));
        $violations = array_merge($violations, $this->checkCommonAntiPatterns($ast, $filePath));

        return $violations;
    }

    private function checkValidationUsage(array $ast, string $filePath): array
    {
        $violations = [];
        $nodeFinder = $this->getNodeFinder();
        $classes = $nodeFinder->findInstanceOf($ast, Class_::class);

        foreach ($classes as $class) {
            if (!$this->isController($class)) {
                continue;
            }

            $methods = $nodeFinder->findInstanceOf($class, ClassMethod::class);

            foreach ($methods as $method) {
                if ($this->isPublicMethod($method)) {
                    // Check for missing validation in public methods
                    if (!$this->hasValidation($method) && $this->acceptsUserInput($method)) {
                        $violations[] = [
                            'file' => $filePath,
                            'line' => $method->getStartLine(),
                            'message' => 'Public controller method should include validation for user input.',
                            'bad_code' => $this->formatBadCode($method),
                            'suggested_fix' => 'Add Form Request validation or use Validator facade.',
                            'severity' => 'warning',
                            'category' => 'validation'
                        ];
                    }
                }
            }
        }

        return $violations;
    }

    private function checkSecurityIssues(array $ast, string $filePath): array
    {
        $violations = [];
        $nodeFinder = $this->getNodeFinder();

        // Check for eval() usage
        $evalCalls = $nodeFinder->find($ast, function (Node $node) {
            return $node instanceof FuncCall &&
                   $node->name instanceof Name &&
                   $node->name->toString() === 'eval';
        });

        foreach ($evalCalls as $evalCall) {
            $violations[] = [
                'file' => $filePath,
                'line' => $evalCall->getStartLine(),
                'message' => 'Avoid using eval() as it poses security risks.',
                'bad_code' => 'eval($code);',
                'suggested_fix' => 'Use safe alternatives like json_decode() or custom parsers.',
                'severity' => 'error',
                'category' => 'security'
            ];
        }

        // Check for direct SQL queries
        $dbCalls = $nodeFinder->find($ast, function (Node $node) {
            return $node instanceof MethodCall &&
                   $node->var instanceof Variable &&
                   $node->var->name === 'DB' &&
                   $node->name->toString() === 'raw';
        });

        foreach ($dbCalls as $dbCall) {
            $violations[] = [
                'file' => $filePath,
                'line' => $dbCall->getStartLine(),
                'message' => 'Avoid raw SQL queries. Use Eloquent ORM or Query Builder.',
                'bad_code' => 'DB::raw($sql);',
                'suggested_fix' => 'Use Eloquent models or Query Builder methods.',
                'severity' => 'warning',
                'category' => 'security'
            ];
        }

        return $violations;
    }

    private function checkPerformanceIssues(array $ast, string $filePath): array
    {
        $violations = [];
        $nodeFinder = $this->getNodeFinder();

        // Check for N+1 query patterns
        $foreachLoops = $nodeFinder->find($ast, function (Node $node) {
            return $node instanceof Node\Stmt\Foreach_;
        });

        foreach ($foreachLoops as $foreach) {
            if ($this->containsDatabaseQuery($foreach)) {
                $violations[] = [
                    'file' => $filePath,
                    'line' => $foreach->getStartLine(),
                    'message' => 'Potential N+1 query issue detected in foreach loop.',
                    'bad_code' => 'foreach($items as $item) { $item->relation; }',
                    'suggested_fix' => 'Use eager loading: Model::with(\'relation\')->get()',
                    'severity' => 'warning',
                    'category' => 'performance'
                ];
            }
        }

        return $violations;
    }

    private function checkCommonAntiPatterns(array $ast, string $filePath): array
    {
        $violations = [];
        $nodeFinder = $this->getNodeFinder();

        // Check for large controller methods
        $methods = $nodeFinder->findInstanceOf($ast, ClassMethod::class);

        foreach ($methods as $method) {
            $lineCount = $method->getEndLine() - $method->getStartLine();
            if ($lineCount > 50) {
                $violations[] = [
                    'file' => $filePath,
                    'line' => $method->getStartLine(),
                    'message' => 'Controller method is too large (' . $lineCount . ' lines).',
                    'bad_code' => 'Large method with many responsibilities',
                    'suggested_fix' => 'Extract logic into service classes or smaller methods.',
                    'severity' => 'warning',
                    'category' => 'architecture'
                ];
            }
        }

        return $violations;
    }

    private function isController(Class_ $class): bool
    {
        return str_contains($class->name->toString(), 'Controller');
    }

    private function isPublicMethod(ClassMethod $method): bool
    {
        return $method->isPublic() && !$method->isStatic();
    }

    private function hasValidation(ClassMethod $method): bool
    {
        // Check for validation patterns
        $validationPatterns = [
            'validate',
            'Validator::make',
            'FormRequest'
        ];

        $methodCode = $this->formatBadCode($method);
        foreach ($validationPatterns as $pattern) {
            if (str_contains($methodCode, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function acceptsUserInput(ClassMethod $method): bool
    {
        // Check if method accepts Request or has parameters
        return !empty($method->params);
    }

    private function containsDatabaseQuery(Node $node): bool
    {
        // Simplified check for database queries
        $code = $this->formatBadCode($node);
        $dbPatterns = ['->find', '->get', '->first', '->where', '->select'];

        foreach ($dbPatterns as $pattern) {
            if (str_contains($code, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function formatBadCode(Node $node): string
    {
        // Simplified code formatting
        if ($node instanceof ClassMethod) {
            return $node->name->toString() . '() method';
        }

        return 'code snippet';
    }
}
