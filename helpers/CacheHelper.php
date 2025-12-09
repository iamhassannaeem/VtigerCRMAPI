<?php
/**
 * Simple File-based Cache Helper
 * Used to cache dashboard data to improve performance
 */

class CacheHelper
{
    private static $cacheDir = __DIR__ . '/../cache/';
    
    /**
     * Get cached data if available and not expired
     * @param string $key Cache key
     * @param int $maxAge Maximum age in seconds (default 300 = 5 minutes)
     * @return mixed|null Returns cached data or null if not found/expired
     */
    public static function get($key, $maxAge = 300)
    {
        try {
            $cacheFile = self::$cacheDir . md5($key) . '.cache';
            
            // Check if cache file exists
            if (!file_exists($cacheFile)) {
                return null;
            }
            
            // Check if cache is expired
            $fileTime = filemtime($cacheFile);
            if (time() - $fileTime > $maxAge) {
                @unlink($cacheFile); // Delete expired cache
                return null;
            }
            
            // Read and unserialize cached data
            $data = @file_get_contents($cacheFile);
            if ($data === false) {
                return null;
            }
            
            return unserialize($data);
            
        } catch (Exception $e) {
            // Silently fail - don't break the API
            return null;
        }
    }
    
    /**
     * Set/save data to cache
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @return bool Success status
     */
    public static function set($key, $data)
    {
        try {
            // Create cache directory if it doesn't exist
            if (!is_dir(self::$cacheDir)) {
                @mkdir(self::$cacheDir, 0777, true);
            }
            
            $cacheFile = self::$cacheDir . md5($key) . '.cache';
            
            // Serialize and write data
            $serialized = serialize($data);
            $result = @file_put_contents($cacheFile, $serialized, LOCK_EX);
            
            return $result !== false;
            
        } catch (Exception $e) {
            // Silently fail - don't break the API
            return false;
        }
    }
    
    /**
     * Clear all cache files
     * @return bool Success status
     */
    public static function clear()
    {
        try {
            if (!is_dir(self::$cacheDir)) {
                return true;
            }
            
            $files = glob(self::$cacheDir . '*.cache');
            foreach ($files as $file) {
                @unlink($file);
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Delete a specific cache entry
     * @param string $key Cache key
     * @return bool Success status
     */
    public static function delete($key)
    {
        try {
            $cacheFile = self::$cacheDir . md5($key) . '.cache';
            
            if (file_exists($cacheFile)) {
                return @unlink($cacheFile);
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get cache statistics
     * @return array Cache stats (file count, total size, etc.)
     */
    public static function getStats()
    {
        try {
            if (!is_dir(self::$cacheDir)) {
                return [
                    'file_count' => 0,
                    'total_size' => 0,
                    'total_size_mb' => 0
                ];
            }
            
            $files = glob(self::$cacheDir . '*.cache');
            $totalSize = 0;
            
            foreach ($files as $file) {
                $totalSize += filesize($file);
            }
            
            return [
                'file_count' => count($files),
                'total_size' => $totalSize,
                'total_size_mb' => round($totalSize / 1024 / 1024, 2)
            ];
            
        } catch (Exception $e) {
            return [
                'file_count' => 0,
                'total_size' => 0,
                'total_size_mb' => 0
            ];
        }
    }
}

