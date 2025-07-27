<?php

namespace ReviewSystem\Engine;

interface RuleInterface
{
    /**
     * Check a file for violations
     * 
     * @param string $filePath The path to the file to check
     * @return array Array of violations found
     */
    public function check(string $filePath): array;

    /**
     * Get the rule's category
     * 
     * @return string The rule category (e.g., 'security', 'performance', 'style', 'best_practice')
     */
    public function getCategory(): string;

    /**
     * Get the rule's name
     * 
     * @return string A human-readable name for the rule
     */
    public function getName(): string;

    /**
     * Get the rule's description
     * 
     * @return string A detailed description of what the rule checks for
     */
    public function getDescription(): string;

    /**
     * Get the rule's severity level
     * 
     * @return string The severity level ('error', 'warning', 'info', 'suggestion')
     */
    public function getSeverity(): string;

    /**
     * Get the rule's tags for additional categorization
     * 
     * @return array Array of tags that describe the rule
     */
    public function getTags(): array;

    /**
     * Check if the rule is enabled by default
     * 
     * @return bool Whether the rule should be enabled by default
     */
    public function isEnabledByDefault(): bool;

    /**
     * Get the rule's configuration options
     * 
     * @return array Array of configuration options and their default values
     */
    public function getConfigurationOptions(): array;

    /**
     * Get the rule's metadata
     * 
     * @return array Complete metadata about the rule
     */
    public function getMetadata(): array;
}
