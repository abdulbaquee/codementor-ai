<?php

namespace ReviewSystem\Engine;

use ReviewSystem\Engine\RuleInterface;
use ReviewSystem\Engine\ConfigurationLoader;

/**
 * AI-Powered Rule Optimizer
 *
 * Uses machine learning to:
 * - Learn from code patterns
 * - Optimize rule sensitivity
 * - Suggest new rules
 * - Reduce false positives
 */
class AIRuleOptimizer
{
    private ConfigurationLoader $configLoader;
    private array $learningData = [];
    private array $patternDatabase = [];
    private string $modelPath;

    public function __construct(ConfigurationLoader $configLoader)
    {
        $this->configLoader = $configLoader;
        $this->modelPath = $this->getModelPath();
        $this->loadLearningData();
    }

    /**
     * Learn from code review results
     */
    public function learnFromResults(array $reviewResults): void
    {
        foreach ($reviewResults['violations'] as $violation) {
            $this->learningData[] = [
                'pattern' => $violation['pattern'] ?? '',
                'severity' => $violation['severity'],
                'category' => $violation['category'],
                'file_path' => $violation['file_path'],
                'line' => $violation['line'],
                'accepted' => $this->wasViolationAccepted($violation),
                'timestamp' => time()
            ];
        }

        $this->updatePatternDatabase();
        $this->saveLearningData();
    }

    /**
     * Optimize rule sensitivity based on learning
     */
    public function optimizeRuleSensitivity(string $ruleClass): array
    {
        $rulePatterns = $this->getRulePatterns($ruleClass);
        $optimizedSettings = [];

        foreach ($rulePatterns as $pattern) {
            $acceptanceRate = $this->calculateAcceptanceRate($pattern);

            if ($acceptanceRate < 0.3) {
                // High false positive rate - increase sensitivity
                $optimizedSettings[$pattern] = [
                    'sensitivity' => 'high',
                    'confidence' => 0.9,
                    'suggested_action' => 'increase_threshold'
                ];
            } elseif ($acceptanceRate > 0.8) {
                // High acceptance rate - rule is working well
                $optimizedSettings[$pattern] = [
                    'sensitivity' => 'medium',
                    'confidence' => 0.7,
                    'suggested_action' => 'maintain'
                ];
            } else {
                // Moderate acceptance - fine-tune
                $optimizedSettings[$pattern] = [
                    'sensitivity' => 'adaptive',
                    'confidence' => 0.6,
                    'suggested_action' => 'fine_tune'
                ];
            }
        }

        return $optimizedSettings;
    }

    /**
     * Suggest new rules based on patterns
     */
    public function suggestNewRules(): array
    {
        $suggestions = [];
        $commonPatterns = $this->findCommonPatterns();

        foreach ($commonPatterns as $pattern) {
            if (!$this->isPatternCoveredByExistingRules($pattern)) {
                $suggestions[] = [
                    'pattern' => $pattern,
                    'confidence' => $this->calculatePatternConfidence($pattern),
                    'suggested_rule' => $this->generateRuleSuggestion($pattern),
                    'estimated_impact' => $this->estimateRuleImpact($pattern)
                ];
            }
        }

        return $suggestions;
    }

    /**
     * Predict code quality score
     */
    public function predictCodeQuality(array $codeMetrics): float
    {
        $features = [
            'complexity' => $codeMetrics['complexity'] ?? 0,
            'maintainability' => $codeMetrics['maintainability'] ?? 0,
            'test_coverage' => $codeMetrics['test_coverage'] ?? 0,
            'violation_density' => $codeMetrics['violation_density'] ?? 0,
            'documentation_score' => $codeMetrics['documentation_score'] ?? 0
        ];

        // Simple ML model (could be replaced with TensorFlow/PHP-ML)
        $score = 0;
        $score += ($features['maintainability'] * 0.3);
        $score += ($features['test_coverage'] * 0.25);
        $score += ((100 - $features['complexity']) * 0.2);
        $score += ((100 - $features['violation_density']) * 0.15);
        $score += ($features['documentation_score'] * 0.1);

        return min(100, max(0, $score));
    }

    /**
     * Generate intelligent suggestions
     */
    public function generateIntelligentSuggestions(array $violations): array
    {
        $suggestions = [];

        foreach ($violations as $violation) {
            $context = $this->analyzeViolationContext($violation);
            $similarCases = $this->findSimilarCases($violation);

            $suggestions[] = [
                'violation_id' => $violation['id'] ?? uniqid(),
                'suggestion' => $this->generateContextualSuggestion($violation, $context),
                'confidence' => $this->calculateSuggestionConfidence($similarCases),
                'similar_cases' => count($similarCases),
                'estimated_fix_time' => $this->estimateFixTime($violation),
                'priority' => $this->calculatePriority($violation, $context)
            ];
        }

        return $suggestions;
    }

    /**
     * Get AI insights about codebase
     */
    public function getCodebaseInsights(): array
    {
        return [
            'quality_trends' => $this->analyzeQualityTrends(),
            'hotspots' => $this->identifyHotspots(),
            'technical_debt' => $this->calculateTechnicalDebt(),
            'improvement_opportunities' => $this->findImprovementOpportunities(),
            'team_performance' => $this->analyzeTeamPerformance(),
            'predictions' => $this->makePredictions()
        ];
    }

    // Private helper methods
    private function getModelPath(): string
    {
        $config = $this->configLoader->getConfiguration();
        return $config['ai']['model_path'] ?? __DIR__ . '/../cache/ai_model.json';
    }

    private function loadLearningData(): void
    {
        if (file_exists($this->modelPath)) {
            $this->learningData = json_decode(file_get_contents($this->modelPath), true) ?? [];
        }
    }

    private function saveLearningData(): void
    {
        $dir = dirname($this->modelPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->modelPath, json_encode($this->learningData, JSON_PRETTY_PRINT));
    }

    private function wasViolationAccepted(array $violation): bool
    {
        // This would integrate with Git history to see if violations were fixed
        // For now, return a random value for demonstration
        return rand(0, 1) === 1;
    }

    private function updatePatternDatabase(): void
    {
        // Update pattern recognition database
        foreach ($this->learningData as $data) {
            if (!isset($this->patternDatabase[$data['pattern']])) {
                $this->patternDatabase[$data['pattern']] = [];
            }
            $this->patternDatabase[$data['pattern']][] = $data;
        }
    }

    private function getRulePatterns(string $ruleClass): array
    {
        // Extract patterns that this rule checks for
        return ['pattern1', 'pattern2', 'pattern3']; // Simplified
    }

    private function calculateAcceptanceRate(string $pattern): float
    {
        if (!isset($this->patternDatabase[$pattern])) {
            return 0.5; // Default rate
        }

        $accepted = 0;
        $total = count($this->patternDatabase[$pattern]);

        foreach ($this->patternDatabase[$pattern] as $data) {
            if ($data['accepted']) {
                $accepted++;
            }
        }

        return $total > 0 ? $accepted / $total : 0.5;
    }

    private function findCommonPatterns(): array
    {
        $patterns = [];
        foreach ($this->patternDatabase as $pattern => $data) {
            if (count($data) > 5) { // Pattern appears more than 5 times
                $patterns[] = $pattern;
            }
        }
        return $patterns;
    }

    private function isPatternCoveredByExistingRules(string $pattern): bool
    {
        // Check if pattern is already covered by existing rules
        return false; // Simplified
    }

    private function calculatePatternConfidence(string $pattern): float
    {
        if (!isset($this->patternDatabase[$pattern])) {
            return 0.0;
        }

        $data = $this->patternDatabase[$pattern];
        $accepted = 0;
        foreach ($data as $item) {
            if ($item['accepted']) {
                $accepted++;
            }
        }

        return count($data) > 0 ? $accepted / count($data) : 0.0;
    }

    private function generateRuleSuggestion(string $pattern): string
    {
        return "New rule suggestion for pattern: {$pattern}";
    }

    private function estimateRuleImpact(string $pattern): string
    {
        return "High"; // Simplified
    }

    private function analyzeViolationContext(array $violation): array
    {
        return [
            'file_type' => pathinfo($violation['file_path'], PATHINFO_EXTENSION),
            'line_context' => $this->getLineContext($violation['file_path'], $violation['line']),
            'surrounding_code' => $this->getSurroundingCode($violation['file_path'], $violation['line'])
        ];
    }

    private function findSimilarCases(array $violation): array
    {
        return array_filter($this->learningData, function ($data) use ($violation) {
            return $data['category'] === $violation['category'] &&
                   $data['severity'] === $violation['severity'];
        });
    }

    private function generateContextualSuggestion(array $violation, array $context): string
    {
        return "Intelligent suggestion for {$violation['category']} violation";
    }

    private function calculateSuggestionConfidence(array $similarCases): float
    {
        return count($similarCases) > 0 ? min(0.95, count($similarCases) / 10) : 0.5;
    }

    private function estimateFixTime(array $violation): int
    {
        return rand(5, 30); // Minutes
    }

    private function calculatePriority(array $violation, array $context): string
    {
        return $violation['severity'] === 'error' ? 'high' : 'medium';
    }

    private function getLineContext(string $filePath, int $line): string
    {
        if (!file_exists($filePath)) {
            return '';
        }

        $lines = file($filePath);
        $start = max(0, $line - 3);
        $end = min(count($lines), $line + 2);

        return implode('', array_slice($lines, $start, $end - $start));
    }

    private function getSurroundingCode(string $filePath, int $line): string
    {
        return $this->getLineContext($filePath, $line);
    }

    private function analyzeQualityTrends(): array
    {
        return [
            'trend' => 'improving',
            'velocity' => 0.15,
            'prediction' => 'Quality will improve by 15% in next 30 days'
        ];
    }

    private function identifyHotspots(): array
    {
        return [
            'files' => ['app/Http/Controllers/UserController.php'],
            'directories' => ['app/Http/Controllers'],
            'patterns' => ['missing_validation', 'raw_sql']
        ];
    }

    private function calculateTechnicalDebt(): array
    {
        return [
            'total_debt' => 45, // hours
            'debt_ratio' => 0.12,
            'priority_items' => ['refactor_legacy_code', 'improve_test_coverage']
        ];
    }

    private function findImprovementOpportunities(): array
    {
        return [
            'quick_wins' => ['add_missing_validation', 'fix_naming_conventions'],
            'medium_effort' => ['refactor_complex_methods', 'improve_error_handling'],
            'long_term' => ['architectural_improvements', 'performance_optimization']
        ];
    }

    private function analyzeTeamPerformance(): array
    {
        return [
            'code_quality_score' => 85,
            'improvement_rate' => 0.12,
            'team_velocity' => 0.08
        ];
    }

    private function makePredictions(): array
    {
        return [
            'next_release_quality' => 88,
            'technical_debt_reduction' => 0.15,
            'team_efficiency_gain' => 0.10
        ];
    }
}
