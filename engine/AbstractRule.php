<?php

namespace ReviewSystem\Engine;

abstract class AbstractRule implements RuleInterface
{
    /**
     * Default category for rules that don't specify one
     */
    protected const DEFAULT_CATEGORY = 'general';

    /**
     * Default severity for rules that don't specify one
     */
    protected const DEFAULT_SEVERITY = 'warning';

    /**
     * Get the rule's category
     * 
     * Override this method in your rule class to specify a custom category
     * 
     * @return string The rule category
     */
    public function getCategory(): string
    {
        return static::DEFAULT_CATEGORY;
    }

    /**
     * Get the rule's name
     * 
     * Override this method in your rule class to provide a custom name
     * 
     * @return string A human-readable name for the rule
     */
    public function getName(): string
    {
        // Default to class name without namespace
        $className = (new \ReflectionClass($this))->getShortName();
        return $this->formatClassName($className);
    }

    /**
     * Get the rule's description
     * 
     * Override this method in your rule class to provide a custom description
     * 
     * @return string A detailed description of what the rule checks for
     */
    public function getDescription(): string
    {
        return 'No description provided for this rule.';
    }

    /**
     * Get the rule's severity level
     * 
     * Override this method in your rule class to specify a custom severity
     * 
     * @return string The severity level
     */
    public function getSeverity(): string
    {
        return static::DEFAULT_SEVERITY;
    }

    /**
     * Get the rule's tags for additional categorization
     * 
     * Override this method in your rule class to provide custom tags
     * 
     * @return array Array of tags that describe the rule
     */
    public function getTags(): array
    {
        return [];
    }

    /**
     * Check if the rule is enabled by default
     * 
     * Override this method in your rule class to change the default state
     * 
     * @return bool Whether the rule should be enabled by default
     */
    public function isEnabledByDefault(): bool
    {
        return true;
    }

    /**
     * Get the rule's configuration options
     * 
     * Override this method in your rule class to provide configuration options
     * 
     * @return array Array of configuration options and their default values
     */
    public function getConfigurationOptions(): array
    {
        return [];
    }

    /**
     * Get the rule's metadata
     * 
     * @return array Complete metadata about the rule
     */
    public function getMetadata(): array
    {
        return [
            'class' => get_class($this),
            'category' => $this->getCategory(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'severity' => $this->getSeverity(),
            'tags' => $this->getTags(),
            'enabled_by_default' => $this->isEnabledByDefault(),
            'configuration_options' => $this->getConfigurationOptions(),
            'version' => $this->getVersion(),
            'author' => $this->getAuthor(),
            'created_at' => $this->getCreatedAt(),
            'updated_at' => $this->getUpdatedAt(),
        ];
    }

    /**
     * Get the rule's version
     * 
     * Override this method in your rule class to specify a version
     * 
     * @return string The rule version
     */
    protected function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * Get the rule's author
     * 
     * Override this method in your rule class to specify an author
     * 
     * @return string The rule author
     */
    protected function getAuthor(): string
    {
        return 'Review System';
    }

    /**
     * Get the rule's creation date
     * 
     * Override this method in your rule class to specify a creation date
     * 
     * @return string The creation date in ISO 8601 format
     */
    protected function getCreatedAt(): string
    {
        return date('Y-m-d\TH:i:s\Z');
    }

    /**
     * Get the rule's last update date
     * 
     * Override this method in your rule class to specify an update date
     * 
     * @return string The update date in ISO 8601 format
     */
    protected function getUpdatedAt(): string
    {
        return date('Y-m-d\TH:i:s\Z');
    }

    /**
     * Format a class name for display
     * 
     * @param string $className The class name to format
     * @return string The formatted class name
     */
    protected function formatClassName(string $className): string
    {
        // Remove "Rule" suffix if present
        $name = preg_replace('/Rule$/', '', $className);
        
        // Convert camelCase to Title Case
        $name = preg_replace('/([A-Z])/', ' $1', $name);
        $name = trim($name);
        
        return ucwords($name);
    }

    /**
     * Validate that a violation has the required fields
     * 
     * @param array $violation The violation to validate
     * @param string $filePath The file path for context
     * @return array The validated violation with defaults applied
     */
    protected function validateViolation(array $violation, string $filePath): array
    {
        // Ensure required fields are present
        if (!isset($violation['message']) || empty($violation['message'])) {
            throw new \InvalidArgumentException('Violation must have a message field');
        }

        // Apply defaults
        $violation['file'] = $violation['file'] ?? $filePath;
        $violation['rule'] = $violation['rule'] ?? get_class($this);
        $violation['category'] = $violation['category'] ?? $this->getCategory();
        $violation['severity'] = $violation['severity'] ?? $this->getSeverity();
        $violation['bad'] = $violation['bad'] ?? 'N/A';
        $violation['good'] = $violation['good'] ?? 'N/A';
        $violation['line'] = $violation['line'] ?? null;
        $violation['tags'] = $violation['tags'] ?? $this->getTags();

        return $violation;
    }

    /**
     * Create a violation with proper metadata
     * 
     * @param string $message The violation message
     * @param int|null $line The line number where the violation occurred
     * @param string|null $bad The problematic code
     * @param string|null $good The suggested fix
     * @param array $additionalData Additional data for the violation
     * @return array The violation array
     */
    protected function createViolation(
        string $message,
        ?int $line = null,
        ?string $bad = null,
        ?string $good = null,
        array $additionalData = []
    ): array {
        $violation = array_merge([
            'message' => $message,
            'line' => $line,
            'bad' => $bad,
            'good' => $good,
        ], $additionalData);

        return $violation;
    }
} 