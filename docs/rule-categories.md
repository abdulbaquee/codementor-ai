# Rule Categories System

## Overview

The Review System now features a comprehensive rule categorization system that organizes rules into logical groups, making it easier to manage, filter, and prioritize code quality checks. This system provides 10 standard categories with metadata, priority levels, and filtering capabilities.

## Features

### 🏷️ **Standard Categories**
- **10 predefined categories** covering all aspects of code quality
- **Category metadata** including display names, descriptions, and icons
- **Priority-based organization** (1-10 scale) for rule importance
- **Default severity levels** for each category

### 🔍 **Advanced Filtering**
- **Multi-criteria filtering** by category, severity, tags, and more
- **Priority-based filtering** for high, medium, and low priority rules
- **Grouping capabilities** by category, severity, and priority
- **Statistical analysis** of rule distribution

### 📊 **Rule Metadata**
- **Comprehensive metadata** for each rule
- **Configuration options** with defaults and descriptions
- **Tag-based categorization** for additional organization
- **Version tracking** and authorship information

## Standard Categories

### 🔒 **Security** (Priority: 1)
- **Default Severity**: `error`
- **Description**: Rules that check for security vulnerabilities and best practices
- **Examples**: SQL injection prevention, XSS protection, authentication checks

### ⚡ **Performance** (Priority: 2)
- **Default Severity**: `warning`
- **Description**: Rules that identify performance issues and optimization opportunities
- **Examples**: Database query optimization, memory usage, caching strategies

### ✅ **Best Practices** (Priority: 3)
- **Default Severity**: `warning`
- **Description**: Rules that enforce general best practices and conventions
- **Examples**: Design patterns, SOLID principles, code organization

### 🔧 **Maintainability** (Priority: 4)
- **Default Severity**: `warning`
- **Description**: Rules that improve code maintainability and readability
- **Examples**: Code complexity, method length, naming conventions

### 🏗️ **Architecture** (Priority: 5)
- **Default Severity**: `warning`
- **Description**: Rules that enforce architectural patterns and design principles
- **Examples**: Layer separation, dependency injection, service boundaries

### 🧪 **Testing** (Priority: 6)
- **Default Severity**: `warning`
- **Description**: Rules that ensure proper testing practices and coverage
- **Examples**: Test coverage, test naming, mocking practices

### 🔗 **Compatibility** (Priority: 7)
- **Default Severity**: `warning`
- **Description**: Rules that ensure compatibility across different environments
- **Examples**: PHP version compatibility, framework version checks

### 📚 **Documentation** (Priority: 8)
- **Default Severity**: `info`
- **Description**: Rules that check for proper documentation and comments
- **Examples**: PHPDoc completeness, README quality, inline comments

### 🎨 **Code Style** (Priority: 9)
- **Default Severity**: `info`
- **Description**: Rules that enforce coding style and formatting standards
- **Examples**: PSR standards, indentation, line length

### 📋 **General** (Priority: 10)
- **Default Severity**: `warning`
- **Description**: General rules that don't fit into specific categories
- **Examples**: Miscellaneous quality checks, project-specific rules

## Usage

### Basic Category Access

```php
use ReviewSystem\Engine\RuleCategory;

// Get all categories
$categories = RuleCategory::getAll();

// Get category metadata
$metadata = RuleCategory::getWithMetadata();

// Get category display name
$displayName = RuleCategory::getDisplayName('security'); // "Security"

// Get category icon
$icon = RuleCategory::getIcon('performance'); // "⚡"

// Get category priority
$priority = RuleCategory::getPriority('architecture'); // 5
```

### Rule Filtering

```php
use ReviewSystem\Engine\RuleFilter;
use ReviewSystem\Rules\NoMongoInControllerRule;

// Create rule filter
$filter = new RuleFilter([
    new NoMongoInControllerRule(),
    // Add more rules...
]);

// Filter by category
$securityRules = $filter->byCategory(RuleCategory::SECURITY)->getFilteredRules();

// Filter by severity
$errorRules = $filter->bySeverity('error')->getFilteredRules();

// Filter by tags
$laravelRules = $filter->byTags('laravel')->getFilteredRules();

// Complex filtering
$highPriorityRules = $filter
    ->byPriority(1, 3) // High priority only
    ->bySeverity(['error', 'warning'])
    ->byEnabled(true)
    ->getFilteredRules();
```

### Rule Grouping

```php
// Group by category
$groupedByCategory = $filter->getGroupedByCategory();

// Group by severity
$groupedBySeverity = $filter->getGroupedBySeverity();

// Group by priority
$groupedByPriority = $filter->getGroupedByPriority();

// Get high priority rules
$highPriority = $filter->getHighPriorityRules();

// Get medium priority rules
$mediumPriority = $filter->getMediumPriorityRules();

// Get low priority rules
$lowPriority = $filter->getLowPriorityRules();
```

### Rule Statistics

```php
$stats = $filter->getStatistics();

echo "Total Rules: {$stats['total_rules']}\n";
echo "Categories: " . count($stats['categories']) . "\n";
echo "Severities: " . count($stats['severities']) . "\n";
echo "Tags: " . count($stats['tags']) . "\n";
echo "Authors: " . count($stats['authors']) . "\n";
```

## Creating Rules with Categories

### Using AbstractRule Base Class

```php
<?php

namespace ReviewSystem\Rules;

use ReviewSystem\Engine\AbstractRule;
use ReviewSystem\Engine\RuleCategory;

class MyCustomRule extends AbstractRule
{
    public function check(string $filePath): array
    {
        // Your rule logic here
        $violations = [];
        
        // Use the helper method to create violations
        if ($hasViolation) {
            $violations[] = $this->createViolation(
                'Description of the violation',
                $lineNumber,
                'Bad code example',
                'Good code example',
                [
                    'additional_data' => 'value'
                ]
            );
        }
        
        return $violations;
    }

    public function getCategory(): string
    {
        return RuleCategory::SECURITY; // Choose appropriate category
    }

    public function getName(): string
    {
        return 'My Custom Security Rule';
    }

    public function getDescription(): string
    {
        return 'This rule checks for specific security vulnerabilities.';
    }

    public function getSeverity(): string
    {
        return 'error'; // Override default if needed
    }

    public function getTags(): array
    {
        return ['security', 'authentication', 'custom'];
    }

    public function getConfigurationOptions(): array
    {
        return [
            'check_pattern' => [
                'type' => 'string',
                'default' => '/pattern/',
                'description' => 'Regex pattern to check'
            ],
            'max_occurrences' => [
                'type' => 'int',
                'default' => 1,
                'description' => 'Maximum allowed occurrences'
            ]
        ];
    }
}
```

### Implementing RuleInterface Directly

```php
<?php

namespace ReviewSystem\Rules;

use ReviewSystem\Engine\RuleInterface;
use ReviewSystem\Engine\RuleCategory;

class MyCustomRule implements RuleInterface
{
    public function check(string $filePath): array
    {
        // Your rule logic here
        return [];
    }

    public function getCategory(): string
    {
        return RuleCategory::PERFORMANCE;
    }

    public function getName(): string
    {
        return 'My Performance Rule';
    }

    public function getDescription(): string
    {
        return 'Checks for performance issues.';
    }

    public function getSeverity(): string
    {
        return 'warning';
    }

    public function getTags(): array
    {
        return ['performance', 'optimization'];
    }

    public function isEnabledByDefault(): bool
    {
        return true;
    }

    public function getConfigurationOptions(): array
    {
        return [];
    }

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
            'version' => '1.0.0',
            'author' => 'Your Name',
            'created_at' => date('Y-m-d\TH:i:s\Z'),
            'updated_at' => date('Y-m-d\TH:i:s\Z'),
        ];
    }
}
```

## RuleCategory Class Reference

### Static Methods

#### `getAll(): array`
Returns all available category constants.

#### `getDisplayNames(): array`
Returns mapping of category keys to display names.

#### `getDescriptions(): array`
Returns mapping of category keys to descriptions.

#### `getIcons(): array`
Returns mapping of category keys to icons/emojis.

#### `getDefaultSeverities(): array`
Returns mapping of category keys to default severity levels.

#### `getPriorities(): array`
Returns mapping of category keys to priority levels (1-10).

#### `isValid(string $category): bool`
Checks if a category is valid.

#### `getDisplayName(string $category): string`
Gets display name for a category.

#### `getDescription(string $category): string`
Gets description for a category.

#### `getIcon(string $category): string`
Gets icon for a category.

#### `getDefaultSeverity(string $category): string`
Gets default severity for a category.

#### `getPriority(string $category): int`
Gets priority for a category.

#### `getGroupedByPriority(): array`
Gets categories grouped by priority level.

#### `getWithMetadata(): array`
Gets all categories with full metadata.

## RuleFilter Class Reference

### Constructor
```php
$filter = new RuleFilter($rules);
```

### Filtering Methods

#### `byCategory($categories): self`
Filter rules by category or array of categories.

#### `bySeverity($severities): self`
Filter rules by severity or array of severities.

#### `byTags($tags): self`
Filter rules by tag or array of tags.

#### `byEnabled(bool $enabled): self`
Filter rules by enabled status.

#### `byAuthor($authors): self`
Filter rules by author or array of authors.

#### `byClassName(string $pattern): self`
Filter rules by class name regex pattern.

#### `byPriority(int $minPriority, int $maxPriority): self`
Filter rules by priority range.

#### `clearFilters(): self`
Clear all applied filters.

### Retrieval Methods

#### `getFilteredRules(): array`
Get rules matching all applied filters.

#### `getGroupedByCategory(): array`
Get rules grouped by category.

#### `getGroupedBySeverity(): array`
Get rules grouped by severity.

#### `getGroupedByPriority(): array`
Get rules grouped by priority.

#### `getStatistics(): array`
Get statistics about the rules.

#### `getRulesWithMetadata(): array`
Get rules with full metadata.

#### `getRulesByCategory(string $category): array`
Get rules by category with metadata.

#### `getRulesBySeverity(string $severity): array`
Get rules by severity with metadata.

#### `getHighPriorityRules(): array`
Get high priority rules (priority 1-3).

#### `getMediumPriorityRules(): array`
Get medium priority rules (priority 4-6).

#### `getLowPriorityRules(): array`
Get low priority rules (priority 7-10).

## Configuration Integration

### Rule Configuration in config/review-system.php

```php
'rules' => [
    // Enable all security rules
    'ReviewSystem\Rules\SecurityRule1',
    'ReviewSystem\Rules\SecurityRule2',
    
    // Enable high priority rules only
    'ReviewSystem\Rules\PerformanceRule1',
    'ReviewSystem\Rules\BestPracticeRule1',
    
    // Disable style rules for now
    // 'ReviewSystem\Rules\StyleRule1',
],

'rule_categories' => [
    'enabled_categories' => [
        RuleCategory::SECURITY,
        RuleCategory::PERFORMANCE,
        RuleCategory::BEST_PRACTICE,
        RuleCategory::MAINTAINABILITY,
        RuleCategory::ARCHITECTURE,
    ],
    'disabled_categories' => [
        RuleCategory::STYLE,
        RuleCategory::DOCUMENTATION,
    ],
    'priority_threshold' => 5, // Only run rules with priority <= 5
],
```

## CLI Integration

### Category-Based Filtering

```bash
# Run only security rules
php review-system/cli.php --categories=security

# Run high priority rules only
php review-system/cli.php --priority=high

# Run multiple categories
php review-system/cli.php --categories=security,performance,architecture

# Exclude certain categories
php review-system/cli.php --exclude-categories=style,documentation
```

### Category Information

```bash
# List all available categories
php review-system/cli.php --list-categories

# Show rules by category
php review-system/cli.php --show-rules-by-category

# Show rule statistics
php review-system/cli.php --show-rule-stats
```

## Benefits

### 🎯 **For Developers**
- **Organized Rules**: Clear categorization makes it easy to understand rule purposes
- **Priority Focus**: Focus on high-priority issues first
- **Customizable**: Easy to enable/disable categories based on project needs
- **Metadata Rich**: Comprehensive information about each rule

### 🏢 **For Teams**
- **Consistent Standards**: Standardized categories across projects
- **Team Collaboration**: Shared understanding of rule importance
- **Progress Tracking**: Track improvements by category
- **Onboarding**: New team members can understand rule purposes quickly

### 🔧 **For System Administrators**
- **Rule Management**: Easy to manage large rule sets
- **Performance Control**: Run only necessary rules based on priorities
- **Compliance**: Ensure critical security and performance rules are always enabled
- **Reporting**: Generate reports by category for stakeholders

## Best Practices

### 1. **Choose Appropriate Categories**
```php
// Good: Security-related rule
public function getCategory(): string
{
    return RuleCategory::SECURITY;
}

// Good: Performance-related rule
public function getCategory(): string
{
    return RuleCategory::PERFORMANCE;
}

// Avoid: Generic category for specific rules
public function getCategory(): string
{
    return RuleCategory::GENERAL; // Too generic
}
```

### 2. **Use Descriptive Tags**
```php
// Good: Specific, descriptive tags
public function getTags(): array
{
    return ['laravel', 'mongodb', 'architecture', 'repository-pattern'];
}

// Avoid: Generic tags
public function getTags(): array
{
    return ['php', 'code']; // Too generic
}
```

### 3. **Set Appropriate Severity**
```php
// Good: Match severity to impact
public function getSeverity(): string
{
    return 'error'; // For security vulnerabilities
}

// Good: Use category default when appropriate
public function getSeverity(): string
{
    return RuleCategory::getDefaultSeverity($this->getCategory());
}
```

### 4. **Provide Configuration Options**
```php
// Good: Flexible configuration
public function getConfigurationOptions(): array
{
    return [
        'max_complexity' => [
            'type' => 'int',
            'default' => 10,
            'description' => 'Maximum cyclomatic complexity'
        ],
        'exclude_patterns' => [
            'type' => 'array',
            'default' => [],
            'description' => 'Patterns to exclude from checking'
        ]
    ];
}
```

## Future Enhancements

### Planned Features
- **Custom Categories**: User-defined categories for project-specific needs
- **Category Inheritance**: Hierarchical category system
- **Dynamic Priority**: Priority adjustment based on project context
- **Category Analytics**: Historical data and trends by category
- **Category Templates**: Predefined category sets for different project types
- **Category Dependencies**: Rules that depend on other categories
- **Category Workflows**: Sequential processing by category priority 