<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ReviewSystem\Engine\RuleValidator;
use ReviewSystem\Engine\ConfigurationLoader;

echo "🔍 Review System Configuration Validator\n";
echo "=======================================\n\n";

// Load configuration using the new ConfigurationLoader
$configLoader = new ConfigurationLoader();
$config = $configLoader->getConfiguration();

// Display configuration information
$configInfo = $configLoader->getConfigurationInfo();
echo "📋 Configuration Source:\n";
echo "Environment: {$configInfo['environment']}\n";
echo "Config File: {$configInfo['config_file']}\n";
echo "Environment Overrides: " . ($configInfo['has_env_overrides'] ? 'Yes' : 'No') . "\n\n";

// Create validator
$validator = new RuleValidator($config);

// Validate configuration
$validation = $validator->validateConfiguration();

// Display results
echo "📊 Validation Results:\n";
echo "----------------------\n";
echo "✅ Is Valid: " . ($validation['is_valid'] ? 'Yes' : 'No') . "\n";
echo "❌ Errors: " . count($validation['errors']) . "\n";
echo "⚠️  Warnings: " . count($validation['warnings']) . "\n";
echo "ℹ️  Info: " . count($validation['info']) . "\n\n";

// Display errors
if (!empty($validation['errors'])) {
    echo "❌ Errors Found:\n";
    foreach ($validation['errors'] as $error) {
        echo "   • {$error['message']}\n";
        if (isset($error['suggestion'])) {
            echo "     Suggestion: {$error['suggestion']}\n";
        }
        echo "\n";
    }
}

// Display warnings
if (!empty($validation['warnings'])) {
    echo "⚠️  Warnings:\n";
    foreach ($validation['warnings'] as $warning) {
        echo "   • {$warning['message']}\n";
        if (isset($warning['suggestion'])) {
            echo "     Suggestion: {$warning['suggestion']}\n";
        }
        echo "\n";
    }
}

// Display info
if (!empty($validation['info'])) {
    echo "ℹ️  Information:\n";
    foreach ($validation['info'] as $info) {
        echo "   • {$info['message']}\n";
        if (isset($info['details'])) {
            foreach ($info['details'] as $key => $value) {
                echo "     {$key}: {$value}\n";
            }
        }
        echo "\n";
    }
}

// Display summary
if (isset($validation['summary'])) {
    echo "📋 Summary:\n";
    echo "-----------\n";
    echo "{$validation['summary']['message']}\n";
    echo "Severity: {$validation['summary']['severity']}\n\n";
}

// Exit with appropriate code
if (!$validation['is_valid']) {
    echo "❌ Configuration validation failed!\n";
    echo "Please fix the errors above before running the review system.\n";
    exit(1);
} elseif (!empty($validation['warnings'])) {
    echo "⚠️  Configuration has warnings but is valid.\n";
    echo "Consider addressing the warnings for better performance.\n";
    exit(0);
} else {
    echo "✅ Configuration is valid and ready to use!\n";
    exit(0);
}
