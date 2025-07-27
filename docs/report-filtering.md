# Report Filtering System

## Overview

The Review System now features comprehensive filtering capabilities in HTML reports, allowing users to easily filter violations by severity, category, tags, files, and search terms. This interactive filtering system provides a much better user experience when dealing with large numbers of violations.

## Features

### 🔍 **Multi-Criteria Filtering**
- **Severity Filter**: Filter by error, warning, info, or suggestion
- **Category Filter**: Filter by security, performance, architecture, etc.
- **Tag Filter**: Filter by specific tags (laravel, mongodb, security, etc.)
- **File Filter**: Filter by specific files
- **Search Filter**: Real-time search in violation messages and file names

### ⚡ **Interactive Controls**
- **Real-time Filtering**: Instant results as you type or select options
- **Quick Action Buttons**: One-click filters for common scenarios
- **Clear All**: Reset all filters with a single click
- **Show All**: Display all violations regardless of filters

### 📊 **Visual Enhancements**
- **Statistics Summary**: Real-time count updates showing filtered results
- **Color-coded Severity**: Visual indicators for different severity levels
- **Category Badges**: Clear identification of violation categories
- **Tag Badges**: Visual representation of violation tags
- **Responsive Design**: Works on different screen sizes

## Filter Controls

### Severity Filter
```html
<select id="severity-filter">
    <option value="">All Severities</option>
    <option value="error">Error</option>
    <option value="warning">Warning</option>
    <option value="info">Info</option>
    <option value="suggestion">Suggestion</option>
</select>
```

**Usage**: Select a specific severity level to show only violations of that type.

### Category Filter
```html
<select id="category-filter">
    <option value="">All Categories</option>
    <option value="security">Security</option>
    <option value="performance">Performance</option>
    <option value="architecture">Architecture</option>
    <option value="maintainability">Maintainability</option>
    <option value="documentation">Documentation</option>
    <option value="testing">Testing</option>
    <option value="style">Style</option>
</select>
```

**Usage**: Filter violations by their category to focus on specific areas of concern.

### Tag Filter
```html
<select id="tag-filter">
    <option value="">All Tags</option>
    <option value="laravel">laravel</option>
    <option value="mongodb">mongodb</option>
    <option value="security">security</option>
    <option value="sql-injection">sql-injection</option>
    <option value="performance">performance</option>
    <option value="architecture">architecture</option>
</select>
```

**Usage**: Filter by specific tags to see violations related to particular technologies or concepts.

### File Filter
```html
<select id="file-filter">
    <option value="">All Files</option>
    <option value="/path/to/UserController.php">UserController.php</option>
    <option value="/path/to/AuthController.php">AuthController.php</option>
    <option value="/path/to/ProductController.php">ProductController.php</option>
</select>
```

**Usage**: Focus on violations in specific files by selecting from the dropdown.

### Search Filter
```html
<input type="text" id="search-filter" placeholder="Search in messages...">
```

**Usage**: Type any text to search within violation messages and file names in real-time.

## Quick Action Buttons

### Apply Filters
```html
<button type="button" class="btn-primary" onclick="applyFilters()">Apply Filters</button>
```
**Action**: Applies all selected filters to the violation list.

### Clear All
```html
<button type="button" class="btn-secondary" onclick="clearFilters()">Clear All</button>
```
**Action**: Resets all filters and shows all violations.

### Show All
```html
<button type="button" class="btn-success" onclick="showAll()">Show All</button>
```
**Action**: Displays all violations regardless of current filter state.

### Show Errors Only
```html
<button type="button" class="btn-warning" onclick="showErrors()">Show Errors Only</button>
```
**Action**: Quickly filter to show only critical error-level violations.

### Show Warnings Only
```html
<button type="button" class="btn-info" onclick="showWarnings()">Show Warnings Only</button>
```
**Action**: Quickly filter to show only warning-level violations.

## Visual Indicators

### Severity Color Coding
```css
.severity-error { border-left: 4px solid #dc3545; }
.severity-warning { border-left: 4px solid #ffc107; }
.severity-info { border-left: 4px solid #17a2b8; }
.severity-suggestion { border-left: 4px solid #28a745; }
```

Each violation row has a colored left border indicating its severity level.

### Category Badges
```html
<span class="category-badge btn-warning">Warning</span>
<span class="category-badge btn-info">Architecture</span>
```

Severity and category are displayed as colored badges for quick identification.

### Tag Badges
```html
<span class="tag-badge">laravel</span>
<span class="tag-badge">mongodb</span>
<span class="tag-badge">architecture</span>
```

Tags are displayed as small badges for additional categorization.

## Statistics Summary

### Real-time Count Updates
```html
<div class="stats-summary">
    <strong>📊 Summary:</strong>
    <span>Total: <span class="count">10</span></span>
    <span>Error: <span class="count severity-error">3</span></span>
    <span>Warning: <span class="count severity-warning">4</span></span>
    <span>Info: <span class="count severity-info">2</span></span>
    <span>Suggestion: <span class="count severity-suggestion">1</span></span>
    <span>Categories: <span class="count">7</span></span>
</div>
```

The summary section shows:
- Total violation count
- Count by severity level
- Number of categories represented
- Updates in real-time as filters are applied

## JavaScript Functionality

### Core Filtering Function
```javascript
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
        
        // Apply each filter
        if (severityFilter && row.dataset.severity !== severityFilter) {
            show = false;
        }
        
        if (categoryFilter && row.dataset.category !== categoryFilter) {
            show = false;
        }
        
        if (tagFilter && !row.dataset.tags.includes(tagFilter)) {
            show = false;
        }
        
        if (fileFilter && row.dataset.file !== fileFilter) {
            show = false;
        }
        
        if (searchFilter) {
            const message = row.dataset.message.toLowerCase();
            const file = row.dataset.file.toLowerCase();
            if (!message.includes(searchFilter) && !file.includes(searchFilter)) {
                show = false;
            }
        }
        
        // Show/hide row and update count
        if (show) {
            row.classList.remove("hidden");
            visibleCount++;
        } else {
            row.classList.add("hidden");
        }
    });
    
    updateViolationCount(visibleCount);
}
```

### Real-time Event Listeners
```javascript
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
```

## Configuration Options

### Enable/Disable Filtering
```php
// In config/review-system.php
'html' => [
    'enable_filtering' => env('REVIEW_HTML_ENABLE_FILTERING', true),
    'filter_options' => [
        'enable_severity_filter' => env('REVIEW_HTML_ENABLE_SEVERITY_FILTER', true),
        'enable_category_filter' => env('REVIEW_HTML_ENABLE_CATEGORY_FILTER', true),
        'enable_tag_filter' => env('REVIEW_HTML_ENABLE_TAG_FILTER', true),
        'enable_file_filter' => env('REVIEW_HTML_ENABLE_FILE_FILTER', true),
        'enable_search_filter' => env('REVIEW_HTML_ENABLE_SEARCH_FILTER', true),
        'real_time_filtering' => env('REVIEW_HTML_REAL_TIME_FILTERING', true),
    ],
],
```

### Environment Variables
```bash
# Enable/disable filtering features
REVIEW_HTML_ENABLE_FILTERING=true
REVIEW_HTML_ENABLE_SEVERITY_FILTER=true
REVIEW_HTML_ENABLE_CATEGORY_FILTER=true
REVIEW_HTML_ENABLE_TAG_FILTER=true
REVIEW_HTML_ENABLE_FILE_FILTER=true
REVIEW_HTML_ENABLE_SEARCH_FILTER=true
REVIEW_HTML_REAL_TIME_FILTERING=true
```

## Usage Examples

### Filter by Security Issues Only
1. Select "Security" from the Category dropdown
2. Click "Apply Filters" or wait for real-time update
3. Only security-related violations will be displayed

### Find MongoDB-Related Issues
1. Type "MongoDB" in the Search field
2. Results will show violations containing "MongoDB" in the message
3. Alternatively, select "mongodb" from the Tag dropdown

### Focus on Critical Issues
1. Click "Show Errors Only" button
2. Only error-level violations will be displayed
3. The count will update to show the number of errors

### Combine Multiple Filters
1. Select "Security" from Category
2. Select "error" from Severity
3. Type "injection" in Search
4. Results will show only security errors containing "injection"

### Filter by Specific File
1. Select a specific file from the File dropdown
2. Only violations in that file will be displayed
3. Useful for focusing on issues in a particular component

## Benefits

### 🎯 **For Developers**
- **Quick Focus**: Easily focus on specific types of issues
- **Efficient Review**: Filter out noise to concentrate on important violations
- **Better Organization**: Group related violations for easier analysis
- **Time Saving**: Find specific issues quickly without scrolling

### 🏢 **For Teams**
- **Prioritized Review**: Focus on high-priority issues first
- **Collaborative Analysis**: Share filtered views for specific concerns
- **Progress Tracking**: Monitor improvements in specific areas
- **Knowledge Sharing**: Use filters to educate team members about specific issues

### 🔧 **For System Administrators**
- **Compliance Monitoring**: Focus on security and critical issues
- **Performance Analysis**: Filter performance-related violations
- **Maintenance Planning**: Group violations by category for planning
- **Reporting**: Generate focused reports for stakeholders

## Best Practices

### 1. **Start with High-Priority Filters**
```javascript
// Focus on critical issues first
showErrors(); // Show only errors
// Then refine with category
selectCategory('security'); // Focus on security errors
```

### 2. **Use Search for Specific Terms**
```javascript
// Search for specific patterns
searchFilter.value = 'SQL injection';
// Search for file patterns
searchFilter.value = 'Controller.php';
```

### 3. **Combine Filters for Precision**
```javascript
// Multiple criteria for precise results
severityFilter.value = 'error';
categoryFilter.value = 'security';
tagFilter.value = 'authentication';
```

### 4. **Use Quick Actions for Common Scenarios**
```javascript
// Common workflow patterns
showErrors(); // Review critical issues first
showWarnings(); // Then review warnings
showAll(); // Finally review all issues
```

### 5. **Monitor Statistics**
```javascript
// Watch the count updates to understand impact
// Total violations: 10 → 3 (after filtering)
// This shows 70% of violations are in the filtered category
```

## Technical Implementation

### Data Attributes
Each violation row includes data attributes for filtering:
```html
<tr data-severity="error" 
    data-category="security" 
    data-tags="security,sql-injection,authentication"
    data-file="/path/to/file.php"
    data-message="SQL injection vulnerability detected.">
```

### CSS Classes
```css
.violation-row.hidden { display: none; }
.severity-error { border-left: 4px solid #dc3545; }
.category-badge { /* styling for category badges */ }
.tag-badge { /* styling for tag badges */ }
```

### Performance Considerations
- **Client-side Filtering**: All filtering happens in the browser for instant results
- **Efficient DOM Manipulation**: Uses CSS classes for show/hide operations
- **Event Delegation**: Efficient event handling for dynamic content
- **Minimal Reflows**: Optimized for smooth user experience

## Future Enhancements

### Planned Features
- **Filter Presets**: Save and load common filter combinations
- **Export Filtered Results**: Export filtered violations to CSV/JSON
- **Advanced Search**: Regex support and complex search queries
- **Filter History**: Track and restore previous filter states
- **Bulk Actions**: Apply actions to filtered violations
- **Filter Analytics**: Track which filters are most commonly used
- **Custom Filter Rules**: User-defined filter combinations
- **Filter Sharing**: Share filter URLs with team members 