<?php

namespace ReviewSystem\Engine;

class ProgressIndicator
{
    private int $totalSteps = 0;
    private int $currentStep = 0;
    private int $lastProgress = 0;
    private bool $isInteractive;
    private int $terminalWidth;
    private array $statusMessages = [];
    private float $startTime;

    public function __construct()
    {
        $this->isInteractive = $this->isInteractiveTerminal();
        $this->terminalWidth = $this->getTerminalWidth();
        $this->startTime = microtime(true);
    }

    /**
     * Initialize the progress indicator with total steps
     */
    public function initialize(int $totalSteps, string $title = 'Processing'): void
    {
        $this->totalSteps = $totalSteps;
        $this->currentStep = 0;
        $this->lastProgress = 0;
        
        echo "\n🚀 {$title}\n";
        if ($this->isInteractive) {
            $this->displayProgressBar();
        } else {
            echo "Progress: 0/{$totalSteps} (0%)\n";
        }
    }

    /**
     * Update progress with a status message
     */
    public function update(int $step, string $status = ''): void
    {
        $this->currentStep = $step;
        
        if ($status) {
            $this->statusMessages[] = $status;
        }
        
        if ($this->isInteractive) {
            $this->updateProgressBar($status);
        } else {
            $percentage = $this->totalSteps > 0 ? round(($step / $this->totalSteps) * 100) : 0;
            echo "Progress: {$step}/{$this->totalSteps} ({$percentage}%)";
            if ($status) {
                echo " - {$status}";
            }
            echo "\n";
        }
    }

    /**
     * Advance progress by one step
     */
    public function advance(string $status = ''): void
    {
        $this->update($this->currentStep + 1, $status);
    }

    /**
     * Complete the progress indicator
     */
    public function complete(string $message = 'Completed'): void
    {
        $totalTime = microtime(true) - $this->startTime;
        
        if ($this->isInteractive) {
            $this->completeProgressBar($message);
        } else {
            $percentage = $this->totalSteps > 0 ? round(($this->currentStep / $this->totalSteps) * 100) : 0;
            echo "Progress: {$this->currentStep}/{$this->totalSteps} ({$percentage}%) - {$message}\n";
        }
        
        echo "⏱️  Total time: " . round($totalTime, 3) . "s\n\n";
    }

    /**
     * Display a simple status message
     */
    public function status(string $message): void
    {
        echo "ℹ️  {$message}\n";
    }

    /**
     * Display a warning message
     */
    public function warning(string $message): void
    {
        echo "⚠️  {$message}\n";
    }

    /**
     * Display an error message
     */
    public function error(string $message): void
    {
        echo "❌ {$message}\n";
    }

    /**
     * Display a success message
     */
    public function success(string $message): void
    {
        echo "✅ {$message}\n";
    }

    /**
     * Display the initial progress bar
     */
    private function displayProgressBar(): void
    {
        $barWidth = min(50, $this->terminalWidth - 20);
        $progress = str_repeat('░', $barWidth);
        echo "\r[{$progress}] 0%";
    }

    /**
     * Update the progress bar
     */
    private function updateProgressBar(string $status = ''): void
    {
        if ($this->totalSteps <= 0) {
            return;
        }

        $percentage = round(($this->currentStep / $this->totalSteps) * 100);
        
        // Only update if percentage changed to avoid flickering
        if ($percentage === $this->lastProgress) {
            return;
        }
        
        $this->lastProgress = $percentage;
        
        $barWidth = min(50, $this->terminalWidth - 20);
        $filledWidth = round(($percentage / 100) * $barWidth);
        $emptyWidth = $barWidth - $filledWidth;
        
        $filled = str_repeat('█', $filledWidth);
        $empty = str_repeat('░', $emptyWidth);
        
        $progressBar = "[{$filled}{$empty}]";
        $percentageText = " {$percentage}%";
        
        // Calculate available space for status
        $usedSpace = strlen($progressBar) + strlen($percentageText) + 2; // +2 for padding
        $availableSpace = $this->terminalWidth - $usedSpace;
        
        $statusText = '';
        if ($status && $availableSpace > 10) {
            $statusText = ' ' . substr($status, 0, $availableSpace - 3);
            if (strlen($status) > $availableSpace - 3) {
                $statusText .= '...';
            }
        }
        
        echo "\r{$progressBar}{$percentageText}{$statusText}";
    }

    /**
     * Complete the progress bar
     */
    private function completeProgressBar(string $message): void
    {
        $barWidth = min(50, $this->terminalWidth - 20);
        $filled = str_repeat('█', $barWidth);
        $progressBar = "[{$filled}]";
        
        echo "\r{$progressBar} 100% - {$message}\n";
    }

    /**
     * Check if the terminal supports interactive features
     */
    private function isInteractiveTerminal(): bool
    {
        // Check if we're in a terminal and not redirected
        if (!posix_isatty(STDOUT)) {
            return false;
        }
        
        // Check for common CI environments
        $ciEnvironments = ['CI', 'TRAVIS', 'CIRCLECI', 'JENKINS', 'GITHUB_ACTIONS'];
        foreach ($ciEnvironments as $env) {
            if (getenv($env)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get the terminal width
     */
    private function getTerminalWidth(): int
    {
        // Try to get terminal width from environment
        if ($width = getenv('COLUMNS')) {
            return (int) $width;
        }
        
        // Try to get from stty
        if (function_exists('exec')) {
            $output = [];
            exec('stty size 2>/dev/null', $output);
            if (!empty($output[0])) {
                $parts = explode(' ', $output[0]);
                if (isset($parts[1])) {
                    return (int) $parts[1];
                }
            }
        }
        
        // Default fallback
        return 80;
    }

    /**
     * Get recent status messages
     */
    public function getRecentStatusMessages(int $count = 5): array
    {
        return array_slice($this->statusMessages, -$count);
    }

    /**
     * Display a summary of the progress
     */
    public function displaySummary(): void
    {
        $totalTime = microtime(true) - $this->startTime;
        $rate = $this->totalSteps > 0 ? $this->totalSteps / $totalTime : 0;
        
        echo "📊 Summary:\n";
        echo "   • Total steps: {$this->totalSteps}\n";
        echo "   • Completed: {$this->currentStep}\n";
        echo "   • Total time: " . round($totalTime, 3) . "s\n";
        echo "   • Rate: " . round($rate, 2) . " steps/second\n";
        
        if (!empty($this->statusMessages)) {
            echo "   • Recent activity:\n";
            $recent = $this->getRecentStatusMessages(3);
            foreach ($recent as $message) {
                echo "     - {$message}\n";
            }
        }
    }
} 