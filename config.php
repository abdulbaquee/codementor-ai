<?php

return [
    "rules" => [
        "ReviewSystem\Rules\CodeStyleRule",
        "ReviewSystem\Rules\LaravelBestPracticesRule",
        "ReviewSystem\Rules\NoMongoInControllerRule",
        "ReviewSystem\Rules\SecurityVulnerabilityRule",
    ],
    "cache" => [
        "enabled" => true,
        "ttl" => 3600,
    ],
    "reporting" => [
        "format" => "html",
        "output" => "reports/",
        "filters" => ["critical", "warning"],
    ],
    "quick_mode" => [
        "enabled" => true,
        "rules" => ["ReviewSystem\Rules\CodeStyleRule"],
        "max_files" => 50,
    ],
    "ai" => [
        "enabled" => true,
        "model_path" => "cache/ai_model.json",
        "learning_enabled" => true,
        "optimization_enabled" => true,
        "suggestion_confidence_threshold" => 0.7,
    ],
    "security" => [
        "enabled" => true,
        "strict_mode" => false,
        "custom_patterns" => [],
        "exclude_paths" => ["vendor/", "node_modules/"],
    ],
];
