<?php

namespace ReviewSystem\Engine;

class FileScanner
{
    private array $cache = [];
    private string $cacheFile;
    private int $maxFileSize;
    private bool $enableCaching;
    private array $performanceStats = [];
    private int $cacheHits = 0;
    private int $cacheMisses = 0;
    private int $cacheExpiryTime;
    private bool $useFileModTime;

    public function __construct(array $config = [])
    {
        $this->maxFileSize = $config['max_file_size'] ?? 10 * 1024 * 1024; // 10MB default
        $this->enableCaching = $config['enable_caching'] ?? true;
        $this->cacheFile = $config['cache_file'] ?? __DIR__ . '/../cache/file_scanner_cache.json';
        $this->cacheExpiryTime = $config['cache_expiry_time'] ?? 3600; // 1 hour default
        $this->useFileModTime = $config['use_file_mod_time'] ?? true; // Use file modification time by default
        
        if ($this->enableCaching) {
            $this->loadCache();
        }
    }

    /**
     * Scan directories for PHP files with performance optimizations
     */
    public function scan(array $paths): array
    {
        $startTime = microtime(true);
        $phpFiles = [];
        $scannedPaths = [];

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $scannedPaths[] = $path;
            $pathFiles = $this->scanDirectory($path);
            $phpFiles = array_merge($phpFiles, $pathFiles);
        }

        // Update cache if enabled
        if ($this->enableCaching) {
            $this->updateCache($scannedPaths, $phpFiles);
        }

        // Record performance stats
        $this->performanceStats = [
            'scan_time' => microtime(true) - $startTime,
            'files_found' => count($phpFiles),
            'paths_scanned' => count($scannedPaths),
            'cache_hits' => $this->cacheHits,
            'cache_misses' => $this->cacheMisses,
            'cache_hit_rate' => $this->getCacheHitRate(),
        ];

        return $phpFiles;
    }

    /**
     * Scan a single directory for PHP files
     */
    private function scanDirectory(string $path): array
    {
        $phpFiles = [];
        $pathHash = md5($path);

        // Check cache first
        if ($this->enableCaching && $this->isCacheValid($pathHash)) {
            $this->cacheHits++;
            return $this->cache[$pathHash]['files'] ?? [];
        }

        $this->cacheMisses++;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($this->shouldIncludeFile($file)) {
                    $phpFiles[] = $file->getPathname();
                }
            }

            // Cache the results with file modification times
            if ($this->enableCaching) {
                $this->cache[$pathHash] = [
                    'files' => $phpFiles,
                    'timestamp' => time(),
                    'path' => $path,
                    'file_mod_times' => $this->getFileModificationTimes($phpFiles),
                    'directory_mod_time' => $this->getDirectoryModificationTime($path),
                ];
            }
        } catch (\Exception $e) {
            error_log("Error scanning directory {$path}: " . $e->getMessage());
        }

        return $phpFiles;
    }

    /**
     * Determine if a file should be included in the scan
     */
    private function shouldIncludeFile(\SplFileInfo $file): bool
    {
        // Must be a file
        if (!$file->isFile()) {
            return false;
        }

        // Must be a PHP file
        if ($file->getExtension() !== 'php') {
            return false;
        }

        // Skip hidden/system files
        if (strpos($file->getFilename(), '.') === 0) {
            return false;
        }

        // Skip files that are too large
        if ($file->getSize() > $this->maxFileSize) {
            error_log("Skipping large file: {$file->getPathname()} ({$file->getSize()} bytes)");
            return false;
        }

        // Skip vendor and cache directories
        $path = $file->getPathname();
        if (str_contains($path, '/vendor/') || 
            str_contains($path, '/cache/') || 
            str_contains($path, '/storage/') ||
            str_contains($path, '/node_modules/')) {
            return false;
        }

        return true;
    }

    /**
     * Read file contents with streaming for large files
     */
    public function readFileContents(string $filePath): string
    {
        if (!file_exists($filePath)) {
            return '';
        }

        $fileSize = filesize($filePath);
        
        // For small files, use file_get_contents (faster)
        if ($fileSize < 1024 * 1024) { // 1MB
            return file_get_contents($filePath) ?: '';
        }

        // For large files, use streaming
        return $this->readFileStreaming($filePath);
    }

    /**
     * Read file contents using streaming to avoid memory issues
     */
    private function readFileStreaming(string $filePath): string
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return '';
        }

        $content = '';
        $chunkSize = 8192; // 8KB chunks

        while (!feof($handle)) {
            $chunk = fread($handle, $chunkSize);
            if ($chunk === false) {
                break;
            }
            $content .= $chunk;
        }

        fclose($handle);
        return $content;
    }

    /**
     * Check if a file is readable and not too large
     */
    public function isFileProcessable(string $filePath): bool
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        $fileSize = filesize($filePath);
        return $fileSize <= $this->maxFileSize;
    }

    /**
     * Load cache from file
     */
    private function loadCache(): void
    {
        if (!file_exists($this->cacheFile)) {
            $this->cache = [];
            return;
        }

        try {
            $cacheData = json_decode(file_get_contents($this->cacheFile), true);
            $this->cache = is_array($cacheData) ? $cacheData : [];
        } catch (\Exception $e) {
            error_log("Error loading file scanner cache: " . $e->getMessage());
            $this->cache = [];
        }
    }

    /**
     * Save cache to file
     */
    private function saveCache(): void
    {
        if (!$this->enableCaching) {
            return;
        }

        try {
            $cacheDir = dirname($this->cacheFile);
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }

            file_put_contents($this->cacheFile, json_encode($this->cache, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            error_log("Error saving file scanner cache: " . $e->getMessage());
        }
    }

    /**
     * Update cache with new scan results
     */
    private function updateCache(array $paths, array $files): void
    {
        foreach ($paths as $path) {
            $pathHash = md5($path);
            $pathFiles = array_filter($files, function($file) use ($path) {
                return strpos($file, $path) === 0;
            });

            $this->cache[$pathHash] = [
                'files' => array_values($pathFiles),
                'timestamp' => time(),
                'path' => $path,
            ];
        }

        $this->saveCache();
    }

    /**
     * Check if cache is valid for a path
     */
    private function isCacheValid(string $pathHash): bool
    {
        if (!isset($this->cache[$pathHash])) {
            return false;
        }

        $cacheEntry = $this->cache[$pathHash];
        $cacheAge = time() - $cacheEntry['timestamp'];
        
        // Check time-based expiry first
        if ($cacheAge >= $this->cacheExpiryTime) {
            return false;
        }

        // If file modification time checking is enabled, check for file changes
        if ($this->useFileModTime) {
            return !$this->hasFilesChanged($pathHash);
        }

        return true;
    }

    /**
     * Clear the cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    /**
     * Get performance statistics
     */
    public function getPerformanceStats(): array
    {
        return $this->performanceStats;
    }



    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return [
            'total_entries' => count($this->cache),
            'cache_hits' => $this->cacheHits,
            'cache_misses' => $this->cacheMisses,
            'cache_hit_rate' => $this->getCacheHitRate(),
            'cache_file' => $this->cacheFile,
            'cache_enabled' => $this->enableCaching,
            'use_file_mod_time' => $this->useFileModTime,
            'cache_expiry_time' => $this->cacheExpiryTime,
        ];
    }

    /**
     * Get cache hit rate as percentage
     */
    public function getCacheHitRate(): float
    {
        $total = $this->cacheHits + $this->cacheMisses;
        if ($total === 0) {
            return 0.0;
        }
        return round(($this->cacheHits / $total) * 100, 2);
    }

    /**
     * Get file modification times for all files in a directory
     */
    private function getFileModificationTimes(array $files): array
    {
        if (!$this->useFileModTime) {
            return [];
        }

        $modTimes = [];
        foreach ($files as $file) {
            if (file_exists($file)) {
                $modTimes[$file] = filemtime($file);
            }
        }
        return $modTimes;
    }

    /**
     * Get directory modification time (latest file modification time)
     */
    private function getDirectoryModificationTime(string $path): int
    {
        if (!$this->useFileModTime) {
            return time();
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            $latestTime = 0;
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $modTime = $file->getMTime();
                    if ($modTime > $latestTime) {
                        $latestTime = $modTime;
                    }
                }
            }
            return $latestTime;
        } catch (\Exception $e) {
            return time();
        }
    }

    /**
     * Check if any files in the directory have been modified since last scan
     */
    private function hasFilesChanged(string $pathHash): bool
    {
        if (!$this->useFileModTime || !isset($this->cache[$pathHash])) {
            return false;
        }

        $cacheEntry = $this->cache[$pathHash];
        $path = $cacheEntry['path'];

        // Check if directory modification time has changed
        $currentDirModTime = $this->getDirectoryModificationTime($path);
        if ($currentDirModTime > ($cacheEntry['directory_mod_time'] ?? 0)) {
            return true;
        }

        // Check individual file modification times
        $cachedModTimes = $cacheEntry['file_mod_times'] ?? [];
        foreach ($cachedModTimes as $file => $cachedTime) {
            if (file_exists($file)) {
                $currentTime = filemtime($file);
                if ($currentTime > $cachedTime) {
                    return true;
                }
            } else {
                // File was deleted
                return true;
            }
        }

        return false;
    }

    /**
     * Reset cache statistics
     */
    public function resetCacheStats(): void
    {
        $this->cacheHits = 0;
        $this->cacheMisses = 0;
    }

    /**
     * Get cache hits count
     */
    public function getCacheHits(): int
    {
        return $this->cacheHits;
    }

    /**
     * Get cache misses count
     */
    public function getCacheMisses(): int
    {
        return $this->cacheMisses;
    }
}
