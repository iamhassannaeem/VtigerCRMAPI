<?php
/**
 * Simple Logger Helper
 */

class Logger
{
    private static $logFile = __DIR__ . '/../logs/api.log';
    private static $timers = [];
    
    /**
     * Log a message
     */
    public static function log($message, $level = 'INFO')
    {
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";
        
        file_put_contents(self::$logFile, $logMessage, FILE_APPEND);
        
        // Also log to PHP error log for debugging
        error_log($logMessage);
    }
    
    /**
     * Start a timer
     */
    public static function startTimer($name)
    {
        self::$timers[$name] = microtime(true);
        self::log("TIMER START: {$name}", 'DEBUG');
    }
    
    /**
     * End a timer and log duration
     */
    public static function endTimer($name)
    {
        if (!isset(self::$timers[$name])) {
            return 0;
        }
        
        $duration = microtime(true) - self::$timers[$name];
        $durationMs = round($duration * 1000, 2);
        
        self::log("TIMER END: {$name} took {$durationMs}ms ({$duration}s)", 'PERF');
        
        unset(self::$timers[$name]);
        return $duration;
    }
    
    /**
     * Log memory usage
     */
    public static function logMemory($label = '')
    {
        $memory = round(memory_get_usage() / 1024 / 1024, 2);
        $peak = round(memory_get_peak_usage() / 1024 / 1024, 2);
        
        self::log("MEMORY {$label}: Current={$memory}MB, Peak={$peak}MB", 'DEBUG');
    }
    
    /**
     * Log an error
     */
    public static function error($message, $exception = null)
    {
        $logMessage = $message;
        
        if ($exception instanceof Exception) {
            $logMessage .= "\nException: " . $exception->getMessage();
            $logMessage .= "\nFile: " . $exception->getFile() . ":" . $exception->getLine();
            $logMessage .= "\nTrace: " . $exception->getTraceAsString();
        }
        
        self::log($logMessage, 'ERROR');
    }
}

