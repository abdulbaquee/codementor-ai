<?php

return [
    "rules" => [
        "ReviewSystem\Rules\CodeStyleRule",
        "ReviewSystem\Rules\LaravelBestPracticesRule",
        "ReviewSystem\Rules\NoMongoInControllerRule",
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
];
