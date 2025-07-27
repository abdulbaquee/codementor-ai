<?php

namespace ReviewSystem\Engine;

class ReportWriter
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Write HTML report with injected configuration
     */
    public function writeHtml(array $violations): string
    {
        $outputPath = $this->getConfigValue('reporting.output_path');
        $filenameFormat = $this->getConfigValue('reporting.filename_format');

        // Ensure output directory exists
        if (!is_dir($outputPath)) {
            mkdir($outputPath, 0755, true);
        }

        // Generate filename with timestamp
        $timestamp = date('Ymd_His');
        $filename = str_replace('{timestamp}', $timestamp, $filenameFormat);
        $fullPath = $outputPath . '/' . $filename;

        // Generate HTML content
        $html = $this->generateHtmlContent($violations);

        // Save the file
        file_put_contents($fullPath, $html);

        return $fullPath;
    }

    /**
     * Generate HTML content for the report
     */
    private function generateHtmlContent(array $violations): string
    {
        $title = $this->getConfigValue('reporting.html.title', 'Code Review Report');
        $includeCss = $this->getConfigValue('reporting.html.include_css', true);
        $cssPath = $this->getConfigValue('reporting.html.css_path', 'style.css');
        $showTimestamp = $this->getConfigValue('reporting.html.show_timestamp', true);
        $showViolationCount = $this->getConfigValue('reporting.html.show_violation_count', true);
        $enableFiltering = $this->getConfigValue('reporting.html.enable_filtering', true);

        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>';
        
        if ($includeCss) {
            $html .= '<link rel="stylesheet" href="' . htmlspecialchars($cssPath) . '">';
        }
        
        $html .= '<style>
            .filter-controls {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
                border: 1px solid #dee2e6;
            }
            .filter-group {
                display: inline-block;
                margin-right: 20px;
                margin-bottom: 10px;
            }
            .filter-group label {
                display: block;
                font-weight: bold;
                margin-bottom: 5px;
                color: #495057;
            }
            .filter-group select, .filter-group input {
                padding: 5px 10px;
                border: 1px solid #ced4da;
                border-radius: 3px;
                font-size: 14px;
            }
            .filter-group select {
                min-width: 120px;
            }
            .filter-group input {
                min-width: 200px;
            }
            .filter-actions {
                margin-top: 10px;
            }
            .filter-actions button {
                padding: 8px 16px;
                margin-right: 10px;
                border: none;
                border-radius: 3px;
                cursor: pointer;
                font-size: 14px;
            }
            .btn-primary {
                background: #007bff;
                color: white;
            }
            .btn-secondary {
                background: #6c757d;
                color: white;
            }
            .btn-success {
                background: #28a745;
                color: white;
            }
            .btn-warning {
                background: #ffc107;
                color: #212529;
            }
            .btn-danger {
                background: #dc3545;
                color: white;
            }
            .btn-info {
                background: #17a2b8;
                color: white;
            }
            .violation-row {
                transition: opacity 0.3s ease;
            }
            .violation-row.hidden {
                display: none;
            }
            .severity-error { border-left: 4px solid #dc3545; }
            .severity-warning { border-left: 4px solid #ffc107; }
            .severity-info { border-left: 4px solid #17a2b8; }
            .severity-suggestion { border-left: 4px solid #28a745; }
            .category-badge {
                display: inline-block;
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: bold;
                margin-right: 5px;
            }
            .tag-badge {
                display: inline-block;
                padding: 1px 6px;
                background: #e9ecef;
                border-radius: 10px;
                font-size: 11px;
                margin: 1px;
            }
            .stats-summary {
                background: #e9ecef;
                padding: 10px;
                border-radius: 5px;
                margin: 10px 0;
                font-size: 14px;
            }
            .stats-summary span {
                margin-right: 15px;
            }
            .stats-summary .count {
                font-weight: bold;
            }
        </style>';
        
        $html .= '</head>
<body>
    <div class="container">
        <h1>' . htmlspecialchars($title) . '</h1>';
        
        if ($showTimestamp || $showViolationCount) {
            $html .= '<div class="report-meta">';
            
            if ($showTimestamp) {
                $html .= '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
            }
            
            if ($showViolationCount) {
                $html .= '<p>Total violations found: <span class="violations-count">' . count($violations) . '</span></p>';
            }
            
            $html .= '</div>';
        }

        if (empty($violations)) {
            $html .= '<p style="color: green; font-weight: bold;">✅ No violations found!</p>';
        } else {
            // Add filtering controls
            if ($enableFiltering) {
                $html .= $this->generateFilterControls($violations);
            }
            
            // Add statistics summary
            $html .= $this->generateStatisticsSummary($violations);
            
            // Generate violations table
            $html .= $this->generateViolationsTable($violations);
        }

        $html .= '<div class="footer">
            <p>Generated by Review System - Laravel Code Quality Tool</p>
        </div>
    </div>';

        // Add JavaScript for filtering functionality
        if ($enableFiltering && !empty($violations)) {
            $html .= $this->generateFilterJavaScript($violations);
        }

        $html .= '</body>
</html>';

        return $html;
    }

    /**
     * Generate filter controls HTML
     */
    private function generateFilterControls(array $violations): string
    {
        // Extract unique values for filter options
        $severities = array_unique(array_filter(array_column($violations, 'severity')));
        $categories = array_unique(array_filter(array_column($violations, 'category')));
        $tags = $this->extractUniqueTags($violations);
        $files = array_unique(array_filter(array_column($violations, 'file')));

        $html = '<div class="filter-controls">
            <h3>🔍 Filter Violations</h3>
            
            <div class="filter-group">
                <label for="severity-filter">Severity:</label>
                <select id="severity-filter">
                    <option value="">All Severities</option>';
        
        foreach ($severities as $severity) {
            $displayName = ucfirst($severity);
            $html .= '<option value="' . htmlspecialchars($severity) . '">' . htmlspecialchars($displayName) . '</option>';
        }
        
        $html .= '</select>
            </div>
            
            <div class="filter-group">
                <label for="category-filter">Category:</label>
                <select id="category-filter">
                    <option value="">All Categories</option>';
        
        foreach ($categories as $category) {
            $displayName = ucfirst(str_replace('_', ' ', $category));
            $html .= '<option value="' . htmlspecialchars($category) . '">' . htmlspecialchars($displayName) . '</option>';
        }
        
        $html .= '</select>
            </div>
            
            <div class="filter-group">
                <label for="tag-filter">Tag:</label>
                <select id="tag-filter">
                    <option value="">All Tags</option>';
        
        foreach ($tags as $tag) {
            $html .= '<option value="' . htmlspecialchars($tag) . '">' . htmlspecialchars($tag) . '</option>';
        }
        
        $html .= '</select>
            </div>
            
            <div class="filter-group">
                <label for="file-filter">File:</label>
                <select id="file-filter">
                    <option value="">All Files</option>';
        
        foreach ($files as $file) {
            $shortName = basename($file);
            $html .= '<option value="' . htmlspecialchars($file) . '">' . htmlspecialchars($shortName) . '</option>';
        }
        
        $html .= '</select>
            </div>
            
            <div class="filter-group">
                <label for="search-filter">Search:</label>
                <input type="text" id="search-filter" placeholder="Search in messages...">
            </div>
            
            <div class="filter-actions">
                <button type="button" class="btn-primary" onclick="applyFilters()">Apply Filters</button>
                <button type="button" class="btn-secondary" onclick="clearFilters()">Clear All</button>
                <button type="button" class="btn-success" onclick="showAll()">Show All</button>
                <button type="button" class="btn-warning" onclick="showErrors()">Show Errors Only</button>
                <button type="button" class="btn-info" onclick="showWarnings()">Show Warnings Only</button>
            </div>
        </div>';

        return $html;
    }

    /**
     * Generate statistics summary HTML
     */
    private function generateStatisticsSummary(array $violations): string
    {
        $totalViolations = count($violations);
        $severityCounts = array_count_values(array_column($violations, 'severity'));
        $categoryCounts = array_count_values(array_column($violations, 'category'));
        
        $html = '<div class="stats-summary">
            <strong>📊 Summary:</strong>
            <span>Total: <span class="count">' . $totalViolations . '</span></span>';
        
        foreach ($severityCounts as $severity => $count) {
            $severityClass = 'severity-' . $severity;
            $html .= '<span>' . ucfirst($severity) . ': <span class="count ' . $severityClass . '">' . $count . '</span></span>';
        }
        
        $html .= '<span>Categories: <span class="count">' . count($categoryCounts) . '</span></span>
        </div>';

        return $html;
    }

    /**
     * Generate violations table HTML
     */
    private function generateViolationsTable(array $violations): string
    {
        $tableColumns = $this->getConfigValue('reporting.html.table_columns', [
            'file_path' => 'File Path',
            'message' => 'Violation Message',
            'bad_code' => 'Bad Code Sample',
            'suggested_fix' => 'Suggested Fix',
        ]);

        $html = '<table id="violations-table">
            <thead>
                <tr>';
        
        foreach ($tableColumns as $column) {
            $html .= '<th>' . htmlspecialchars($column) . '</th>';
        }
        
        $html .= '</tr>
            </thead>
            <tbody>';

        foreach ($violations as $index => $violation) {
            $severity = $violation['severity'] ?? 'warning';
            $category = $violation['category'] ?? 'general';
            $tags = $violation['tags'] ?? [];
            
            $html .= '<tr class="violation-row severity-' . htmlspecialchars($severity) . '" 
                           data-severity="' . htmlspecialchars($severity) . '"
                           data-category="' . htmlspecialchars($category) . '"
                           data-tags="' . htmlspecialchars(implode(',', $tags)) . '"
                           data-file="' . htmlspecialchars($violation['file'] ?? '') . '"
                           data-message="' . htmlspecialchars($violation['message'] ?? '') . '">';
            
            // Map violation data to table columns
            $columnData = [
                'file_path' => $this->formatFilePath($violation['file'] ?? 'N/A'),
                'message' => $this->formatMessage($violation['message'] ?? 'N/A', $severity, $category, $tags),
                'bad_code' => $violation['bad'] ?? 'N/A',
                'suggested_fix' => $violation['good'] ?? 'N/A',
            ];
            
            foreach ($columnData as $key => $value) {
                $cssClass = str_replace('_', '-', $key);
                $html .= '<td class="' . $cssClass . '">' . $value . '</td>';
            }
            
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        return $html;
    }

    /**
     * Format file path for display
     */
    private function formatFilePath(string $filePath): string
    {
        $shortPath = basename($filePath);
        return '<span title="' . htmlspecialchars($filePath) . '">' . htmlspecialchars($shortPath) . '</span>';
    }

    /**
     * Format message with severity and category badges
     */
    private function formatMessage(string $message, string $severity, string $category, array $tags): string
    {
        $html = htmlspecialchars($message);
        
        // Add severity badge
        $severityClass = 'btn-' . $severity;
        $html .= '<br><span class="category-badge ' . $severityClass . '">' . ucfirst($severity) . '</span>';
        
        // Add category badge
        $categoryDisplay = ucfirst(str_replace('_', ' ', $category));
        $html .= '<span class="category-badge btn-info">' . htmlspecialchars($categoryDisplay) . '</span>';
        
        // Add tag badges
        foreach ($tags as $tag) {
            $html .= '<span class="tag-badge">' . htmlspecialchars($tag) . '</span>';
        }
        
        return $html;
    }

    /**
     * Extract unique tags from violations
     */
    private function extractUniqueTags(array $violations): array
    {
        $allTags = [];
        foreach ($violations as $violation) {
            if (isset($violation['tags']) && is_array($violation['tags'])) {
                $allTags = array_merge($allTags, $violation['tags']);
            }
        }
        return array_unique($allTags);
    }

    /**
     * Generate JavaScript for filtering functionality
     */
    private function generateFilterJavaScript(array $violations): string
    {
        return '<script>
        function applyFilters() {
            const severityFilter = document.getElementById("severity-filter").value;
            const categoryFilter = document.getElementById("category-filter").value;
            const tagFilter = document.getElementById("tag-filter").value;
            const fileFilter = document.getElementById("file-filter").value;
            const searchFilter = document.getElementById("search-filter").value.toLowerCase();
            
            const rows = document.querySelectorAll("#violations-table tbody tr");
            let visibleCount = 0;
            
            rows.forEach(row => {
                let show = true;
                
                // Severity filter
                if (severityFilter && row.dataset.severity !== severityFilter) {
                    show = false;
                }
                
                // Category filter
                if (categoryFilter && row.dataset.category !== categoryFilter) {
                    show = false;
                }
                
                // Tag filter
                if (tagFilter && !row.dataset.tags.includes(tagFilter)) {
                    show = false;
                }
                
                // File filter
                if (fileFilter && row.dataset.file !== fileFilter) {
                    show = false;
                }
                
                // Search filter
                if (searchFilter) {
                    const message = row.dataset.message.toLowerCase();
                    const file = row.dataset.file.toLowerCase();
                    if (!message.includes(searchFilter) && !file.includes(searchFilter)) {
                        show = false;
                    }
                }
                
                if (show) {
                    row.classList.remove("hidden");
                    visibleCount++;
                } else {
                    row.classList.add("hidden");
                }
            });
            
            updateViolationCount(visibleCount);
        }
        
        function clearFilters() {
            document.getElementById("severity-filter").value = "";
            document.getElementById("category-filter").value = "";
            document.getElementById("tag-filter").value = "";
            document.getElementById("file-filter").value = "";
            document.getElementById("search-filter").value = "";
            showAll();
        }
        
        function showAll() {
            const rows = document.querySelectorAll("#violations-table tbody tr");
            rows.forEach(row => row.classList.remove("hidden"));
            updateViolationCount(rows.length);
        }
        
        function showErrors() {
            const rows = document.querySelectorAll("#violations-table tbody tr");
            let visibleCount = 0;
            
            rows.forEach(row => {
                if (row.dataset.severity === "error") {
                    row.classList.remove("hidden");
                    visibleCount++;
                } else {
                    row.classList.add("hidden");
                }
            });
            
            updateViolationCount(visibleCount);
        }
        
        function showWarnings() {
            const rows = document.querySelectorAll("#violations-table tbody tr");
            let visibleCount = 0;
            
            rows.forEach(row => {
                if (row.dataset.severity === "warning") {
                    row.classList.remove("hidden");
                    visibleCount++;
                } else {
                    row.classList.add("hidden");
                }
            });
            
            updateViolationCount(visibleCount);
        }
        
        function updateViolationCount(count) {
            const countElement = document.querySelector(".violations-count");
            if (countElement) {
                countElement.textContent = count;
            }
        }
        
        // Add event listeners for real-time filtering
        document.addEventListener("DOMContentLoaded", function() {
            const searchInput = document.getElementById("search-filter");
            if (searchInput) {
                searchInput.addEventListener("input", applyFilters);
            }
            
            const selectElements = document.querySelectorAll(".filter-group select");
            selectElements.forEach(select => {
                select.addEventListener("change", applyFilters);
            });
        });
        </script>';
    }

    /**
     * Static method for backward compatibility
     * @deprecated Use constructor injection instead
     */
    public static function writeHtmlStatic(array $violations): string
    {
        // Load config for backward compatibility
        $config = require __DIR__ . '/../config.php';
        
        // Create instance with injected config
        $writer = new self($config);
        
        return $writer->writeHtml($violations);
    }

    /**
     * Get configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Update configuration
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Get specific configuration value
     */
    public function getConfigValue(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }
}
