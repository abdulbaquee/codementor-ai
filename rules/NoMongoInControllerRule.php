<?php

namespace ReviewSystem\Rules;

use ReviewSystem\Engine\PerformanceOptimizedRule;
use ReviewSystem\Engine\RuleCategory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Name;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\MethodCall;

class NoMongoInControllerRule extends PerformanceOptimizedRule
{

    /**
     * Get the rule's category
     */
    public function getCategory(): string
    {
        return RuleCategory::ARCHITECTURE;
    }

    /**
     * Get the rule's name
     */
    public function getName(): string
    {
        return 'No MongoDB in Controllers';
    }

    /**
     * Get the rule's description
     */
    public function getDescription(): string
    {
        return 'Detects raw MongoDB access inside Laravel controllers and suggests using a Repository layer for better separation of concerns and maintainability.';
    }

    /**
     * Get the rule's severity level
     */
    public function getSeverity(): string
    {
        return 'warning';
    }

    /**
     * Get the rule's tags for additional categorization
     */
    public function getTags(): array
    {
        return ['laravel', 'mongodb', 'architecture', 'repository-pattern', 'separation-of-concerns'];
    }

    /**
     * Check if the rule is enabled by default
     */
    public function isEnabledByDefault(): bool
    {
        return true;
    }

    /**
     * Get the rule's configuration options
     */
    public function getConfigurationOptions(): array
    {
        return [
            'target_paths' => [
                'type' => 'array',
                'default' => ['app/Http/Controllers/'],
                'description' => 'Paths to scan for MongoDB usage'
            ],
            'mongo_patterns' => [
                'type' => 'array',
                'default' => [
                    'MongoDB\\',
                    'Mongo\\',
                    'MongoDB\\Client',
                    'MongoDB\\Collection',
                    'MongoDB\\Database',
                    'MongoDB\\Cursor',
                    'MongoDB\\BSON\\',
                ],
                'description' => 'MongoDB namespace patterns to detect'
            ],
            'suggested_fix' => [
                'type' => 'string',
                'default' => 'Use a Repository layer to abstract MongoDB queries.',
                'description' => 'Suggested fix for MongoDB usage'
            ]
        ];
    }

    public function check(string $filePath): array
    {
        // Only target files under app/Http/Controllers/
        if (!str_contains($filePath, 'app/Http/Controllers/')) {
            return [];
        }

        return parent::check($filePath);
    }

    /**
     * Perform MongoDB usage checks on the AST
     */
    protected function performChecks(array $ast, string $filePath): array
    {
        $mongoUsage = $this->findMongoUsage($ast);
        
        if (!empty($mongoUsage)) {
            return [$this->createViolation(
                'Avoid raw MongoDB access inside controllers.',
                $mongoUsage[0]->getLine() ?? null,
                $this->formatBadCode($mongoUsage[0]),
                'Use a Repository layer to abstract MongoDB queries.',
                [
                    'category' => $this->getCategory(),
                    'severity' => $this->getSeverity(),
                    'tags' => $this->getTags(),
                    'file' => $filePath,
                    'rule' => get_class($this),
                ]
            )];
        }

        return [];
    }

    /**
     * Find MongoDB usage patterns in the AST
     */
    private function findMongoUsage(array $ast): array
    {
        $mongoUsage = [];
        $nodeFinder = $this->getNodeFinder();

        // 1. Check for MongoDB use statements
        $mongoUses = $nodeFinder->find($ast, function(Node $node) {
            return $node instanceof Use_ && $this->isMongoUse($node);
        });
        $mongoUsage = array_merge($mongoUsage, $mongoUses);

        // 2. Check for MongoDB class instantiation
        $mongoNew = $nodeFinder->find($ast, function(Node $node) {
            return $node instanceof New_ && $this->isMongoClass($node->class);
        });
        $mongoUsage = array_merge($mongoUsage, $mongoNew);

        // 3. Check for MongoDB static calls
        $mongoStaticCalls = $nodeFinder->find($ast, function(Node $node) {
            return $node instanceof StaticCall && $this->isMongoClass($node->class);
        });
        $mongoUsage = array_merge($mongoUsage, $mongoStaticCalls);

        // 4. Check for MongoDB method calls
        $mongoMethodCalls = $nodeFinder->find($ast, function(Node $node) {
            return $node instanceof MethodCall && $this->isMongoMethodCall($node);
        });
        $mongoUsage = array_merge($mongoUsage, $mongoMethodCalls);

        // 5. Check for MongoDB function calls
        $mongoFuncCalls = $nodeFinder->find($ast, function(Node $node) {
            return $node instanceof FuncCall && $this->isMongoFunction($node);
        });
        $mongoUsage = array_merge($mongoUsage, $mongoFuncCalls);

        return $mongoUsage;
    }

    /**
     * Check if a use statement imports MongoDB classes
     */
    private function isMongoUse(Use_ $node): bool
    {
        foreach ($node->uses as $use) {
            if ($use instanceof UseUse) {
                $name = $use->name->toString();
                if ($this->isMongoNamespace($name)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Check if a class name refers to MongoDB
     */
    private function isMongoClass($class): bool
    {
        if ($class instanceof Name) {
            $name = $class->toString();
            return $this->isMongoNamespace($name);
        }
        return false;
    }

    /**
     * Check if a method call is on a MongoDB object
     */
    private function isMongoMethodCall(MethodCall $node): bool
    {
        // Check if the variable being called on is likely a MongoDB object
        if ($node->var instanceof Variable) {
            $varName = $node->var->name;
            // Look for common MongoDB variable names
            $mongoVarNames = ['collection', 'client', 'db', 'mongo', 'mongodb'];
            if (in_array(strtolower($varName), $mongoVarNames)) {
                return true;
            }
        }

        // Check if it's a method call on a MongoDB property
        if ($node->var instanceof PropertyFetch) {
            $propName = $node->var->name->name ?? '';
            $mongoPropNames = ['collection', 'client', 'db', 'mongo'];
            if (in_array(strtolower($propName), $mongoPropNames)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a function call is a MongoDB function
     */
    private function isMongoFunction(FuncCall $node): bool
    {
        if ($node->name instanceof Name) {
            $funcName = $node->name->toString();
            $mongoFunctions = [
                'mongodb_connect',
                'mongodb_select_db',
                'mongodb_find',
                'mongodb_insert',
                'mongodb_update',
                'mongodb_delete',
            ];
            return in_array(strtolower($funcName), $mongoFunctions);
        }
        return false;
    }

    /**
     * Check if a namespace is MongoDB-related
     */
    private function isMongoNamespace(string $name): bool
    {
        $mongoPatterns = [
            'MongoDB\\',
            'Mongo\\',
            'MongoDB\\Client',
            'MongoDB\\Collection',
            'MongoDB\\Database',
            'MongoDB\\Cursor',
            'MongoDB\\BSON\\',
        ];

        foreach ($mongoPatterns as $pattern) {
            if (strpos($name, $pattern) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Format the bad code example for the violation report
     */
    private function formatBadCode(Node $node): string
    {
        if ($node instanceof Use_) {
            return 'use MongoDB\\Collection;';
        }
        
        if ($node instanceof New_) {
            $className = $node->class instanceof Name ? $node->class->toString() : 'MongoDB\\Class';
            return "new {$className}()";
        }
        
        if ($node instanceof StaticCall) {
            $class = $node->class instanceof Name ? $node->class->toString() : 'MongoDB\\Class';
            $method = $node->name->name ?? 'method';
            return "{$class}::{$method}()";
        }
        
        if ($node instanceof MethodCall) {
            $method = $node->name->name ?? 'method';
            return "\$mongoObject->{$method}()";
        }
        
        if ($node instanceof FuncCall) {
            $func = $node->name instanceof Name ? $node->name->toString() : 'mongodb_function';
            return "{$func}()";
        }

        return 'MongoDB usage detected';
    }

    /**
     * Get detailed information about MongoDB usage for debugging
     */
    public function getMongoUsageDetails(string $filePath): array
    {
        if (!str_contains($filePath, 'app/Http/Controllers/')) {
            return [];
        }

        $fileScanner = new \ReviewSystem\Engine\FileScanner();
        if (!$fileScanner->isFileProcessable($filePath)) {
            return [];
        }

        $contents = $fileScanner->readFileContents($filePath);
        if (empty($contents)) {
            return [];
        }

        try {
            $ast = $this->getParser()->parse($contents);
            if ($ast === null) {
                return [];
            }

            $usage = $this->findMongoUsage($ast);
            $details = [];

            foreach ($usage as $node) {
                $details[] = [
                    'type' => get_class($node),
                    'line' => $node->getLine(),
                    'code' => $this->formatBadCode($node),
                    'node' => $node,
                ];
            }

            return $details;

        } catch (\Throwable $e) {
            return [['error' => $e->getMessage()]];
        }
    }
}
