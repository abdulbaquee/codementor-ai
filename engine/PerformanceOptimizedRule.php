<?php

namespace ReviewSystem\Engine;

use PhpParser\ParserFactory;
use PhpParser\NodeFinder;
use PhpParser\Parser;

/**
 * Performance-optimized base class for rules with caching and optimization features
 * 
 * This class provides:
 * - Shared parser instances to avoid recreation
 * - AST caching for repeated file analysis
 * - Memory-efficient processing
 * - Performance monitoring capabilities
 */
abstract class PerformanceOptimizedRule extends AbstractRule
{
    /** @var Parser|null Shared parser instance */
    private static $sharedParser = null;
    
    /** @var NodeFinder|null Shared node finder instance */
    private static $sharedNodeFinder = null;
    
    /** @var array AST cache for file contents */
    private static $astCache = [];
    
    /** @var array Performance metrics */
    private static $performanceMetrics = [];
    
    /** @var int Maximum cache size */
    private const MAX_CACHE_SIZE = 100;
    
    /** @var int Cache TTL in seconds */
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get shared parser instance (singleton pattern)
     */
    protected function getParser(): Parser
    {
        if (self::$sharedParser === null) {
            self::$sharedParser = (new ParserFactory)->createForHostVersion();
        }
        return self::$sharedParser;
    }

    /**
     * Get shared node finder instance (singleton pattern)
     */
    protected function getNodeFinder(): NodeFinder
    {
        if (self::$sharedNodeFinder === null) {
            self::$sharedNodeFinder = new NodeFinder();
        }
        return self::$sharedNodeFinder;
    }

    /**
     * Parse file with caching support
     * 
     * @param string $filePath Path to the file
     * @return array|null Parsed AST or null if parsing failed
     */
    protected function parseFileWithCache(string $filePath): ?array
    {
        $cacheKey = $this->generateCacheKey($filePath);
        
        // Check cache first
        if (isset(self::$astCache[$cacheKey])) {
            $cached = self::$astCache[$cacheKey];
            if ($cached['expires'] > time()) {
                return $cached['ast'];
            }
            unset(self::$astCache[$cacheKey]);
        }
        
        // Parse file
        $startTime = microtime(true);
        $code = file_get_contents($filePath);
        $ast = $this->getParser()->parse($code);
        $parseTime = microtime(true) - $startTime;
        
        // Cache the result
        if ($ast !== null) {
            $this->cacheAst($cacheKey, $ast, $parseTime);
        }
        
        return $ast;
    }

    /**
     * Generate cache key for file
     */
    private function generateCacheKey(string $filePath): string
    {
        return md5($filePath . filemtime($filePath));
    }

    /**
     * Cache AST with performance metrics
     */
    private function cacheAst(string $cacheKey, array $ast, float $parseTime): void
    {
        // Implement LRU cache eviction
        if (count(self::$astCache) >= self::MAX_CACHE_SIZE) {
            $oldestKey = array_key_first(self::$astCache);
            unset(self::$astCache[$oldestKey]);
        }
        
        self::$astCache[$cacheKey] = [
            'ast' => $ast,
            'expires' => time() + self::CACHE_TTL,
            'parse_time' => $parseTime
        ];
        
        // Track performance metrics
        $this->trackPerformance('parse_time', $parseTime);
    }

    /**
     * Track performance metrics
     */
    protected function trackPerformance(string $metric, float $value): void
    {
        $ruleName = static::class;
        if (!isset(self::$performanceMetrics[$ruleName])) {
            self::$performanceMetrics[$ruleName] = [];
        }
        
        if (!isset(self::$performanceMetrics[$ruleName][$metric])) {
            self::$performanceMetrics[$ruleName][$metric] = [];
        }
        
        self::$performanceMetrics[$ruleName][$metric][] = $value;
    }

    /**
     * Get performance metrics for this rule
     */
    public static function getPerformanceMetrics(): array
    {
        return self::$performanceMetrics;
    }

    /**
     * Clear AST cache
     */
    public static function clearCache(): void
    {
        self::$astCache = [];
    }

    /**
     * Get cache statistics
     */
    public static function getCacheStats(): array
    {
        return [
            'size' => count(self::$astCache),
            'max_size' => self::MAX_CACHE_SIZE,
            'ttl' => self::CACHE_TTL,
            'hit_rate' => self::calculateHitRate()
        ];
    }

    /**
     * Calculate cache hit rate (simplified)
     */
    private static function calculateHitRate(): float
    {
        // This would need more sophisticated tracking in a real implementation
        return 0.0; // Placeholder
    }

    /**
     * Optimized file checking with performance monitoring
     */
    public function check(string $filePath): array
    {
        $startTime = microtime(true);
        
        if (!file_exists($filePath)) {
            return [];
        }

        // Parse file with caching
        $ast = $this->parseFileWithCache($filePath);
        if ($ast === null) {
            return [];
        }

        // Run rule-specific checks
        $violations = $this->performChecks($ast, $filePath);
        
        // Track total processing time
        $totalTime = microtime(true) - $startTime;
        $this->trackPerformance('total_time', $totalTime);
        
        return $violations;
    }

    /**
     * Abstract method for rule-specific checks
     * 
     * @param array $ast Parsed AST
     * @param string $filePath File path
     * @return array Violations found
     */
    abstract protected function performChecks(array $ast, string $filePath): array;

    /**
     * Memory-efficient file processing for large files
     */
    protected function processLargeFile(string $filePath, callable $processor): array
    {
        $violations = [];
        $chunkSize = 1024 * 1024; // 1MB chunks
        
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return $violations;
        }
        
        $buffer = '';
        while (!feof($handle)) {
            $buffer .= fread($handle, $chunkSize);
            
            // Process complete lines
            $lines = explode("\n", $buffer);
            $buffer = array_pop($lines); // Keep incomplete line
            
            foreach ($lines as $line) {
                $result = $processor($line);
                if (!empty($result)) {
                    $violations = array_merge($violations, $result);
                }
            }
        }
        
        fclose($handle);
        return $violations;
    }
} 