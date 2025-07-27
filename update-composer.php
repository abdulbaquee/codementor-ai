<?php

/**
 * Update composer.json with review system configuration
 */

$composerFile = __DIR__ . '/../composer.json';

if (!file_exists($composerFile)) {
    echo "❌ composer.json not found\n";
    exit(1);
}

// Read current composer.json
$composer = json_decode(file_get_contents($composerFile), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ Invalid JSON in composer.json\n";
    exit(1);
}

// Add scripts if they don't exist
if (!isset($composer['scripts'])) {
    $composer['scripts'] = [];
}

    $reviewScripts = [
        'review:quick' => 'php codementor-ai/cli.php --quick',
        'review:full' => 'php codementor-ai/cli.php --full',
        'review' => 'php codementor-ai/cli.php',
        'review:validate' => 'php codementor-ai/validate-config.php',
    ];

    foreach ($reviewScripts as $script => $command) {
        if (!isset($composer['scripts'][$script])) {
            $composer['scripts'][$script] = $command;
        }
    }

// Add autoload-dev configuration
    if (!isset($composer['autoload-dev'])) {
        $composer['autoload-dev'] = [];
    }

    if (!isset($composer['autoload-dev']['psr-4'])) {
        $composer['autoload-dev']['psr-4'] = [];
    }

// Add ReviewSystem namespace
    if (!isset($composer['autoload-dev']['psr-4']['ReviewSystem\\'])) {
        $composer['autoload-dev']['psr-4']['ReviewSystem\\'] = 'review-system/';
    }

    // Add classmap for subdirectories
    if (!isset($composer['autoload-dev']['classmap'])) {
        $composer['autoload-dev']['classmap'] = [];
    }

    $classmapDirs = [
        'codementor-ai/engine/',
        'codementor-ai/rules/'
    ];

    foreach ($classmapDirs as $dir) {
        if (!in_array($dir, $composer['autoload-dev']['classmap'])) {
            $composer['autoload-dev']['classmap'][] = $dir;
        }
    }

// Write back to composer.json
    $json = json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ Error encoding JSON\n";
        exit(1);
    }

    file_put_contents($composerFile, $json);

    echo "✅ composer.json updated successfully\n";
    echo "📝 Added review scripts\n";
    echo "📝 Added ReviewSystem autoloading\n";
    echo "🔄 Run 'composer dump-autoload' to regenerate autoloader\n";
