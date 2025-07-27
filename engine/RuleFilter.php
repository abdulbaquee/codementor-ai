<?php

namespace ReviewSystem\Engine;

/**
 * Rule filtering and categorization utilities
 */
class RuleFilter
{
    private array $rules = [];
    private array $filters = [];

    public function __construct(array $rules = [])
    {
        $this->rules = $rules;
    }

    /**
     * Add a rule to the filter
     * 
     * @param RuleInterface $rule The rule to add
     * @return self
     */
    public function addRule(RuleInterface $rule): self
    {
        $this->rules[] = $rule;
        return $this;
    }

    /**
     * Add multiple rules to the filter
     * 
     * @param array $rules Array of RuleInterface instances
     * @return self
     */
    public function addRules(array $rules): self
    {
        foreach ($rules as $rule) {
            if ($rule instanceof RuleInterface) {
                $this->rules[] = $rule;
            }
        }
        return $this;
    }

    /**
     * Filter rules by category
     * 
     * @param string|array $categories Category or array of categories to filter by
     * @return self
     */
    public function byCategory($categories): self
    {
        $categories = is_array($categories) ? $categories : [$categories];
        $this->filters['categories'] = $categories;
        return $this;
    }

    /**
     * Filter rules by severity
     * 
     * @param string|array $severities Severity or array of severities to filter by
     * @return self
     */
    public function bySeverity($severities): self
    {
        $severities = is_array($severities) ? $severities : [$severities];
        $this->filters['severities'] = $severities;
        return $this;
    }

    /**
     * Filter rules by tags
     * 
     * @param string|array $tags Tag or array of tags to filter by
     * @return self
     */
    public function byTags($tags): self
    {
        $tags = is_array($tags) ? $tags : [$tags];
        $this->filters['tags'] = $tags;
        return $this;
    }

    /**
     * Filter rules by enabled status
     * 
     * @param bool $enabled Whether to filter for enabled or disabled rules
     * @return self
     */
    public function byEnabled(bool $enabled): self
    {
        $this->filters['enabled'] = $enabled;
        return $this;
    }

    /**
     * Filter rules by author
     * 
     * @param string|array $authors Author or array of authors to filter by
     * @return self
     */
    public function byAuthor($authors): self
    {
        $authors = is_array($authors) ? $authors : [$authors];
        $this->filters['authors'] = $authors;
        return $this;
    }

    /**
     * Filter rules by class name pattern
     * 
     * @param string $pattern Regex pattern to match class names
     * @return self
     */
    public function byClassName(string $pattern): self
    {
        $this->filters['class_name_pattern'] = $pattern;
        return $this;
    }

    /**
     * Filter rules by priority (category-based)
     * 
     * @param int $minPriority Minimum priority level (1 = highest)
     * @param int $maxPriority Maximum priority level (10 = lowest)
     * @return self
     */
    public function byPriority(int $minPriority = 1, int $maxPriority = 10): self
    {
        $this->filters['priority_range'] = [
            'min' => $minPriority,
            'max' => $maxPriority
        ];
        return $this;
    }

    /**
     * Clear all filters
     * 
     * @return self
     */
    public function clearFilters(): self
    {
        $this->filters = [];
        return $this;
    }

    /**
     * Get filtered rules
     * 
     * @return array Array of filtered RuleInterface instances
     */
    public function getFilteredRules(): array
    {
        $filtered = [];

        foreach ($this->rules as $rule) {
            if ($this->matchesFilters($rule)) {
                $filtered[] = $rule;
            }
        }

        return $filtered;
    }

    /**
     * Get rules grouped by category
     * 
     * @return array Rules grouped by category
     */
    public function getGroupedByCategory(): array
    {
        $grouped = [];

        foreach ($this->rules as $rule) {
            $category = $rule->getCategory();
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $rule;
        }

        return $grouped;
    }

    /**
     * Get rules grouped by severity
     * 
     * @return array Rules grouped by severity
     */
    public function getGroupedBySeverity(): array
    {
        $grouped = [];

        foreach ($this->rules as $rule) {
            $severity = $rule->getSeverity();
            if (!isset($grouped[$severity])) {
                $grouped[$severity] = [];
            }
            $grouped[$severity][] = $rule;
        }

        return $grouped;
    }

    /**
     * Get rules grouped by priority
     * 
     * @return array Rules grouped by priority level
     */
    public function getGroupedByPriority(): array
    {
        $grouped = [];

        foreach ($this->rules as $rule) {
            $priority = RuleCategory::getPriority($rule->getCategory());
            if (!isset($grouped[$priority])) {
                $grouped[$priority] = [];
            }
            $grouped[$priority][] = $rule;
        }

        ksort($grouped);
        return $grouped;
    }

    /**
     * Get statistics about the rules
     * 
     * @return array Statistics about the rules
     */
    public function getStatistics(): array
    {
        $stats = [
            'total_rules' => count($this->rules),
            'categories' => [],
            'severities' => [],
            'tags' => [],
            'authors' => [],
        ];

        foreach ($this->rules as $rule) {
            // Category stats
            $category = $rule->getCategory();
            $stats['categories'][$category] = ($stats['categories'][$category] ?? 0) + 1;

            // Severity stats
            $severity = $rule->getSeverity();
            $stats['severities'][$severity] = ($stats['severities'][$severity] ?? 0) + 1;

            // Tag stats
            foreach ($rule->getTags() as $tag) {
                $stats['tags'][$tag] = ($stats['tags'][$tag] ?? 0) + 1;
            }

            // Author stats
            $author = $rule->getMetadata()['author'] ?? 'Unknown';
            $stats['authors'][$author] = ($stats['authors'][$author] ?? 0) + 1;
        }

        return $stats;
    }

    /**
     * Get rules with full metadata
     * 
     * @return array Array of rules with their metadata
     */
    public function getRulesWithMetadata(): array
    {
        $rulesWithMetadata = [];

        foreach ($this->rules as $rule) {
            $metadata = $rule->getMetadata();
            $metadata['priority'] = RuleCategory::getPriority($rule->getCategory());
            $metadata['category_display_name'] = RuleCategory::getDisplayName($rule->getCategory());
            $metadata['category_icon'] = RuleCategory::getIcon($rule->getCategory());
            
            $rulesWithMetadata[] = $metadata;
        }

        return $rulesWithMetadata;
    }

    /**
     * Check if a rule matches all applied filters
     * 
     * @param RuleInterface $rule The rule to check
     * @return bool Whether the rule matches all filters
     */
    private function matchesFilters(RuleInterface $rule): bool
    {
        // Category filter
        if (isset($this->filters['categories'])) {
            if (!in_array($rule->getCategory(), $this->filters['categories'], true)) {
                return false;
            }
        }

        // Severity filter
        if (isset($this->filters['severities'])) {
            if (!in_array($rule->getSeverity(), $this->filters['severities'], true)) {
                return false;
            }
        }

        // Tags filter
        if (isset($this->filters['tags'])) {
            $ruleTags = $rule->getTags();
            $hasMatchingTag = false;
            foreach ($this->filters['tags'] as $tag) {
                if (in_array($tag, $ruleTags, true)) {
                    $hasMatchingTag = true;
                    break;
                }
            }
            if (!$hasMatchingTag) {
                return false;
            }
        }

        // Enabled filter
        if (isset($this->filters['enabled'])) {
            if ($rule->isEnabledByDefault() !== $this->filters['enabled']) {
                return false;
            }
        }

        // Author filter
        if (isset($this->filters['authors'])) {
            $author = $rule->getMetadata()['author'] ?? 'Unknown';
            if (!in_array($author, $this->filters['authors'], true)) {
                return false;
            }
        }

        // Class name pattern filter
        if (isset($this->filters['class_name_pattern'])) {
            $className = get_class($rule);
            if (!preg_match($this->filters['class_name_pattern'], $className)) {
                return false;
            }
        }

        // Priority range filter
        if (isset($this->filters['priority_range'])) {
            $priority = RuleCategory::getPriority($rule->getCategory());
            $range = $this->filters['priority_range'];
            if ($priority < $range['min'] || $priority > $range['max']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get rules by category with metadata
     * 
     * @param string $category The category to filter by
     * @return array Rules in the category with metadata
     */
    public function getRulesByCategory(string $category): array
    {
        return $this->byCategory($category)->getRulesWithMetadata();
    }

    /**
     * Get rules by severity with metadata
     * 
     * @param string $severity The severity to filter by
     * @return array Rules with the specified severity and metadata
     */
    public function getRulesBySeverity(string $severity): array
    {
        return $this->bySeverity($severity)->getRulesWithMetadata();
    }

    /**
     * Get high priority rules (security, performance, best practices)
     * 
     * @return array High priority rules with metadata
     */
    public function getHighPriorityRules(): array
    {
        return $this->byPriority(1, 3)->getRulesWithMetadata();
    }

    /**
     * Get medium priority rules (maintainability, architecture, testing)
     * 
     * @return array Medium priority rules with metadata
     */
    public function getMediumPriorityRules(): array
    {
        return $this->byPriority(4, 6)->getRulesWithMetadata();
    }

    /**
     * Get low priority rules (documentation, style, general)
     * 
     * @return array Low priority rules with metadata
     */
    public function getLowPriorityRules(): array
    {
        return $this->byPriority(7, 10)->getRulesWithMetadata();
    }
} 