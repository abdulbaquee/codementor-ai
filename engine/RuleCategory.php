<?php

namespace ReviewSystem\Engine;

/**
 * Standard rule categories for the review system
 */
class RuleCategory
{
    // Core categories
    public const SECURITY = 'security';
    public const PERFORMANCE = 'performance';
    public const STYLE = 'style';
    public const BEST_PRACTICE = 'best_practice';
    public const MAINTAINABILITY = 'maintainability';
    public const COMPATIBILITY = 'compatibility';
    public const DOCUMENTATION = 'documentation';
    public const TESTING = 'testing';
    public const ARCHITECTURE = 'architecture';
    public const GENERAL = 'general';

    /**
     * Get all available categories
     * 
     * @return array Array of all category constants
     */
    public static function getAll(): array
    {
        return [
            self::SECURITY,
            self::PERFORMANCE,
            self::STYLE,
            self::BEST_PRACTICE,
            self::MAINTAINABILITY,
            self::COMPATIBILITY,
            self::DOCUMENTATION,
            self::TESTING,
            self::ARCHITECTURE,
            self::GENERAL,
        ];
    }

    /**
     * Get category display names
     * 
     * @return array Array mapping category keys to display names
     */
    public static function getDisplayNames(): array
    {
        return [
            self::SECURITY => 'Security',
            self::PERFORMANCE => 'Performance',
            self::STYLE => 'Code Style',
            self::BEST_PRACTICE => 'Best Practices',
            self::MAINTAINABILITY => 'Maintainability',
            self::COMPATIBILITY => 'Compatibility',
            self::DOCUMENTATION => 'Documentation',
            self::TESTING => 'Testing',
            self::ARCHITECTURE => 'Architecture',
            self::GENERAL => 'General',
        ];
    }

    /**
     * Get category descriptions
     * 
     * @return array Array mapping category keys to descriptions
     */
    public static function getDescriptions(): array
    {
        return [
            self::SECURITY => 'Rules that check for security vulnerabilities and best practices',
            self::PERFORMANCE => 'Rules that identify performance issues and optimization opportunities',
            self::STYLE => 'Rules that enforce coding style and formatting standards',
            self::BEST_PRACTICE => 'Rules that enforce general best practices and conventions',
            self::MAINTAINABILITY => 'Rules that improve code maintainability and readability',
            self::COMPATIBILITY => 'Rules that ensure compatibility across different environments',
            self::DOCUMENTATION => 'Rules that check for proper documentation and comments',
            self::TESTING => 'Rules that ensure proper testing practices and coverage',
            self::ARCHITECTURE => 'Rules that enforce architectural patterns and design principles',
            self::GENERAL => 'General rules that don\'t fit into specific categories',
        ];
    }

    /**
     * Get category icons/emojis
     * 
     * @return array Array mapping category keys to icons
     */
    public static function getIcons(): array
    {
        return [
            self::SECURITY => '🔒',
            self::PERFORMANCE => '⚡',
            self::STYLE => '🎨',
            self::BEST_PRACTICE => '✅',
            self::MAINTAINABILITY => '🔧',
            self::COMPATIBILITY => '🔗',
            self::DOCUMENTATION => '📚',
            self::TESTING => '🧪',
            self::ARCHITECTURE => '🏗️',
            self::GENERAL => '📋',
        ];
    }

    /**
     * Get category severity levels
     * 
     * @return array Array mapping category keys to default severity levels
     */
    public static function getDefaultSeverities(): array
    {
        return [
            self::SECURITY => 'error',
            self::PERFORMANCE => 'warning',
            self::STYLE => 'info',
            self::BEST_PRACTICE => 'warning',
            self::MAINTAINABILITY => 'warning',
            self::COMPATIBILITY => 'warning',
            self::DOCUMENTATION => 'info',
            self::TESTING => 'warning',
            self::ARCHITECTURE => 'warning',
            self::GENERAL => 'warning',
        ];
    }

    /**
     * Get category priority levels (1 = highest, 10 = lowest)
     * 
     * @return array Array mapping category keys to priority levels
     */
    public static function getPriorities(): array
    {
        return [
            self::SECURITY => 1,
            self::PERFORMANCE => 2,
            self::BEST_PRACTICE => 3,
            self::MAINTAINABILITY => 4,
            self::ARCHITECTURE => 5,
            self::TESTING => 6,
            self::COMPATIBILITY => 7,
            self::DOCUMENTATION => 8,
            self::STYLE => 9,
            self::GENERAL => 10,
        ];
    }

    /**
     * Check if a category is valid
     * 
     * @param string $category The category to validate
     * @return bool Whether the category is valid
     */
    public static function isValid(string $category): bool
    {
        return in_array($category, self::getAll(), true);
    }

    /**
     * Get display name for a category
     * 
     * @param string $category The category key
     * @return string The display name
     */
    public static function getDisplayName(string $category): string
    {
        $displayNames = self::getDisplayNames();
        return $displayNames[$category] ?? ucfirst($category);
    }

    /**
     * Get description for a category
     * 
     * @param string $category The category key
     * @return string The description
     */
    public static function getDescription(string $category): string
    {
        $descriptions = self::getDescriptions();
        return $descriptions[$category] ?? 'No description available';
    }

    /**
     * Get icon for a category
     * 
     * @param string $category The category key
     * @return string The icon
     */
    public static function getIcon(string $category): string
    {
        $icons = self::getIcons();
        return $icons[$category] ?? '📋';
    }

    /**
     * Get default severity for a category
     * 
     * @param string $category The category key
     * @return string The default severity
     */
    public static function getDefaultSeverity(string $category): string
    {
        $severities = self::getDefaultSeverities();
        return $severities[$category] ?? 'warning';
    }

    /**
     * Get priority for a category
     * 
     * @param string $category The category key
     * @return int The priority level
     */
    public static function getPriority(string $category): int
    {
        $priorities = self::getPriorities();
        return $priorities[$category] ?? 10;
    }

    /**
     * Get categories grouped by priority
     * 
     * @return array Categories grouped by priority level
     */
    public static function getGroupedByPriority(): array
    {
        $grouped = [];
        $priorities = self::getPriorities();
        
        foreach ($priorities as $category => $priority) {
            $grouped[$priority][] = $category;
        }
        
        ksort($grouped);
        return $grouped;
    }

    /**
     * Get categories with full metadata
     * 
     * @return array Array of categories with all metadata
     */
    public static function getWithMetadata(): array
    {
        $categories = [];
        
        foreach (self::getAll() as $category) {
            $categories[$category] = [
                'key' => $category,
                'display_name' => self::getDisplayName($category),
                'description' => self::getDescription($category),
                'icon' => self::getIcon($category),
                'default_severity' => self::getDefaultSeverity($category),
                'priority' => self::getPriority($category),
            ];
        }
        
        return $categories;
    }
} 