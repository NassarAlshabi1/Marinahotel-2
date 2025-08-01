<?php
/**
 * Cache Manager Class
 * Provides file-based caching system for Marina Hotel
 */

class CacheManager {
    private $cache_dir;
    private $default_ttl = 300; // 5 minutes
    
    public function __construct($cache_directory = null) {
        $this->cache_dir = $cache_directory ?? dirname(__DIR__) . '/cache';
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
        
        // Create .htaccess to protect cache directory
        $htaccess_file = $this->cache_dir . '/.htaccess';
        if (!file_exists($htaccess_file)) {
            file_put_contents($htaccess_file, "Deny from all\n");
        }
    }
    
    /**
     * Generate cache key from string
     */
    private function generateKey($key) {
        return md5($key) . '.cache';
    }
    
    /**
     * Get cache file path
     */
    private function getCacheFilePath($key) {
        return $this->cache_dir . '/' . $this->generateKey($key);
    }
    
    /**
     * Set cache data
     */
    public function set($key, $data, $ttl = null) {
        $ttl = $ttl ?? $this->default_ttl;
        $cache_file = $this->getCacheFilePath($key);
        
        $cache_data = [
            'data' => $data,
            'expires' => time() + $ttl,
            'created' => time()
        ];
        
        $serialized_data = serialize($cache_data);
        
        // Use atomic write to prevent corruption
        $temp_file = $cache_file . '.tmp';
        if (file_put_contents($temp_file, $serialized_data, LOCK_EX) !== false) {
            return rename($temp_file, $cache_file);
        }
        
        return false;
    }
    
    /**
     * Get cache data
     */
    public function get($key) {
        $cache_file = $this->getCacheFilePath($key);
        
        if (!file_exists($cache_file)) {
            return null;
        }
        
        $cache_content = file_get_contents($cache_file);
        if ($cache_content === false) {
            return null;
        }
        
        $cache_data = unserialize($cache_content);
        if ($cache_data === false) {
            // Corrupted cache file, delete it
            unlink($cache_file);
            return null;
        }
        
        // Check if cache has expired
        if (time() > $cache_data['expires']) {
            unlink($cache_file);
            return null;
        }
        
        return $cache_data['data'];
    }
    
    /**
     * Check if cache exists and is valid
     */
    public function has($key) {
        return $this->get($key) !== null;
    }
    
    /**
     * Delete specific cache entry
     */
    public function delete($key) {
        $cache_file = $this->getCacheFilePath($key);
        
        if (file_exists($cache_file)) {
            return unlink($cache_file);
        }
        
        return true;
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        $files = glob($this->cache_dir . '/*.cache');
        $deleted = 0;
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }
        
        return $deleted;
    }
    
    /**
     * Clear expired cache entries
     */
    public function clearExpired() {
        $files = glob($this->cache_dir . '/*.cache');
        $deleted = 0;
        
        foreach ($files as $file) {
            $cache_content = file_get_contents($file);
            if ($cache_content !== false) {
                $cache_data = unserialize($cache_content);
                if ($cache_data !== false && time() > $cache_data['expires']) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Get cache statistics
     */
    public function getStats() {
        $files = glob($this->cache_dir . '/*.cache');
        $total_files = count($files);
        $total_size = 0;
        $expired_files = 0;
        
        foreach ($files as $file) {
            $total_size += filesize($file);
            
            $cache_content = file_get_contents($file);
            if ($cache_content !== false) {
                $cache_data = unserialize($cache_content);
                if ($cache_data !== false && time() > $cache_data['expires']) {
                    $expired_files++;
                }
            }
        }
        
        return [
            'total_files' => $total_files,
            'total_size' => $total_size,
            'total_size_mb' => round($total_size / 1024 / 1024, 2),
            'expired_files' => $expired_files,
            'cache_dir' => $this->cache_dir
        ];
    }
    
    /**
     * Cache a callback function result
     */
    public function remember($key, $callback, $ttl = null) {
        $cached_data = $this->get($key);
        
        if ($cached_data !== null) {
            return $cached_data;
        }
        
        $data = call_user_func($callback);
        $this->set($key, $data, $ttl);
        
        return $data;
    }
    
    /**
     * Cache database query results
     */
    public function cacheQuery($conn, $query, $params = [], $ttl = null) {
        $cache_key = 'query_' . md5($query . serialize($params));
        
        return $this->remember($cache_key, function() use ($conn, $query, $params) {
            if (empty($params)) {
                $result = $conn->query($query);
            } else {
                $stmt = $conn->prepare($query);
                if (!empty($params)) {
                    $types = str_repeat('s', count($params)); // Assume all strings for simplicity
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
            }
            
            $data = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            
            if (isset($stmt)) {
                $stmt->close();
            }
            
            return $data;
        }, $ttl);
    }
    
    /**
     * Invalidate cache by pattern
     */
    public function invalidatePattern($pattern) {
        $files = glob($this->cache_dir . '/*.cache');
        $deleted = 0;
        
        foreach ($files as $file) {
            $filename = basename($file, '.cache');
            if (fnmatch($pattern, $filename)) {
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Warm up cache with common queries
     */
    public function warmUp($conn) {
        $common_queries = [
            'dashboard_stats' => "
                SELECT 
                    (SELECT COUNT(*) FROM bookings WHERE status = 'محجوزة' AND actual_checkout IS NULL) as occupied_rooms,
                    (SELECT COUNT(*) FROM rooms WHERE status = 'شاغرة') as available_rooms,
                    (SELECT COUNT(*) FROM bookings WHERE DATE(checkin_date) = CURDATE()) as today_checkins,
                    (SELECT COALESCE(SUM(amount), 0) FROM payment WHERE DATE(payment_date) = CURDATE()) as today_revenue
            ",
            'room_list' => "SELECT room_number, type, price, status FROM rooms ORDER BY room_number",
            'active_bookings_count' => "SELECT COUNT(*) as count FROM bookings WHERE status = 'محجوزة' AND actual_checkout IS NULL"
        ];
        
        $warmed = 0;
        foreach ($common_queries as $key => $query) {
            try {
                $this->cacheQuery($conn, $query, [], 600); // Cache for 10 minutes
                $warmed++;
            } catch (Exception $e) {
                error_log("Cache warm-up failed for $key: " . $e->getMessage());
            }
        }
        
        return $warmed;
    }
}

/**
 * Global cache instance
 */
function getCache() {
    static $cache = null;
    if ($cache === null) {
        $cache = new CacheManager();
    }
    return $cache;
}

/**
 * Helper function to cache database queries
 */
function cacheQuery($conn, $query, $params = [], $ttl = 300) {
    return getCache()->cacheQuery($conn, $query, $params, $ttl);
}

/**
 * Helper function to remember callback results
 */
function remember($key, $callback, $ttl = 300) {
    return getCache()->remember($key, $callback, $ttl);
}
?>