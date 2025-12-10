# Dashboard Performance Optimization Guide

## 🚀 Quick Wins (Implement First)

### 1. Add Database Indexes ⚡ **HIGHEST IMPACT**
Run the SQL indexes provided. This alone can reduce query time by 70-90%.

**Expected Improvement:** 16s → 2-3s

### 2. Use Summary Tables (Materialized Views)
Create pre-aggregated tables that update every 5-15 minutes.

```sql
-- Create summary table for dashboard stats
CREATE TABLE dashboard_stats_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_type VARCHAR(50),
    user_id INT,
    stat_type VARCHAR(100),
    stat_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    INDEX idx_lookup (role_type, user_id, stat_type, expires_at)
);

-- Example: Store NOC critical metrics
INSERT INTO dashboard_stats_cache (role_type, stat_type, stat_data, expires_at)
VALUES ('NOC', 'critical_metrics', 
    JSON_OBJECT(
        'total_open_tickets', 25,
        'unassigned_tickets', 5,
        'critical_issues', 2
    ),
    DATE_ADD(NOW(), INTERVAL 5 MINUTE)
);
```

**Expected Improvement:** 2-3s → 0.5-1s

### 3. Implement Redis Caching
Replace file-based cache with Redis for faster access.

```php
class RedisCache {
    private $redis;
    
    public function __construct() {
        $this->redis = new Redis();
        $this->redis->connect('127.0.0.1', 6379);
    }
    
    public function get($key) {
        $data = $this->redis->get($key);
        return $data ? json_decode($data, true) : null;
    }
    
    public function set($key, $value, $ttl = 300) {
        $this->redis->setex($key, $ttl, json_encode($value));
    }
}
```

**Expected Improvement:** 0.5-1s → 0.2-0.5s

---

## 🔧 Advanced Optimizations

### 4. Query Optimization Patterns

#### ❌ AVOID: Correlated Subqueries
```sql
-- BAD - Runs subquery for each row
SELECT tt.*, 
    (SELECT MIN(createdtime) FROM vtiger_ticketcomments 
     WHERE ticketid = tt.ticketid) as first_comment
FROM vtiger_troubletickets tt;
```

#### ✅ USE: LEFT JOIN Instead
```sql
-- GOOD - Single join operation
SELECT tt.*, tc.first_comment
FROM vtiger_troubletickets tt
LEFT JOIN (
    SELECT ticketid, MIN(createdtime) as first_comment
    FROM vtiger_ticketcomments
    GROUP BY ticketid
) tc ON tt.ticketid = tc.ticketid;
```

### 5. Pagination Everything
Never load unlimited results.

```php
// ALWAYS add LIMIT
$sql = "SELECT ... ORDER BY created DESC LIMIT 20";
```

### 6. Use Connection Pooling
```php
class DatabasePool {
    private static $connections = [];
    private static $maxConnections = 10;
    
    public static function getConnection() {
        // Reuse existing connections
        foreach (self::$connections as $conn) {
            if (!$conn->inUse) {
                $conn->inUse = true;
                return $conn;
            }
        }
        
        // Create new if under limit
        if (count(self::$connections) < self::$maxConnections) {
            $conn = new mysqli(...);
            $conn->inUse = true;
            self::$connections[] = $conn;
            return $conn;
        }
        
        // Wait for available connection
        return self::waitForConnection();
    }
}
```

---

## 📊 Monitoring & Debugging

### Add Query Performance Logging
```php
class PerformanceLogger {
    public static function logQuery($queryName, $duration, $role) {
        if ($duration > 1000) { // Over 1 second
            error_log(sprintf(
                "[SLOW QUERY] %s for %s took %.2fms",
                $queryName, $role, $duration
            ));
        }
    }
}

// Usage
$start = microtime(true);
$result = $this->db->query($sql);
$duration = (microtime(true) - $start) * 1000;
PerformanceLogger::logQuery('getKPIs', $duration, 'Management');
```

### Enable MySQL Slow Query Log
```sql
-- In my.cnf
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow-query.log
long_query_time = 1  -- Log queries over 1 second
log_queries_not_using_indexes = 1
```

---

## 🎯 Implementation Priority

| Priority | Task | Expected Improvement | Difficulty |
|----------|------|---------------------|------------|
| **P0** | Add database indexes | 70-90% faster | Easy |
| **P0** | Enable Redis cache | 50-70% faster | Medium |
| **P1** | Create summary tables | 60-80% faster | Medium |
| **P1** | Add LIMIT to all queries | 30-50% faster | Easy |
| **P2** | Implement connection pooling | 20-30% faster | Hard |
| **P2** | Use async queries | 40-60% faster | Hard |
| **P3** | Add query monitoring | N/A | Easy |

---

## 🔬 Load Testing Script

```bash
#!/bin/bash
# Test dashboard performance

echo "Testing dashboard endpoints..."

for i in {1..10}; do
    echo "Request $i:"
    curl -w "\nTime: %{time_total}s\n" \
        -H "Authorization: Bearer YOUR_TOKEN" \
        http://localhost/mobile/dashboard
    echo "---"
done
```

---

## 📈 Expected Results

### Before Optimization
- **Management Dashboard:** 16-20 seconds
- **NOC Dashboard:** 8-12 seconds
- **Field Dashboard:** 5-8 seconds

### After Basic Optimization (Indexes + Cache)
- **Management Dashboard:** 1-2 seconds
- **NOC Dashboard:** 0.5-1 seconds
- **Field Dashboard:** 0.3-0.5 seconds

### After Full Optimization (All Strategies)
- **Management Dashboard:** 0.2-0.5 seconds
- **NOC Dashboard:** 0.1-0.3 seconds
- **Field Dashboard:** 0.1-0.2 seconds

---

## 🚨 Common Mistakes to Avoid

1. **Don't fetch all data at once** - Use lazy loading
2. **Don't use `SELECT *`** - Select only needed columns
3. **Don't run queries in loops** - Batch operations
4. **Don't forget to close connections** - Always cleanup
5. **Don't ignore the query cache** - Use `SQL_CACHE` hint when appropriate
6. **Don't over-normalize in production** - Some denormalization is OK for reads

---

## 🔄 Background Job for Heavy Calculations

Create a cron job to pre-calculate expensive metrics:

```php
// cron_dashboard_cache.php - Run every 5 minutes
class DashboardCacheUpdater {
    public function updateAllDashboards() {
        $roles = ['Field', 'NOC', 'Management', 'Sales'];
        
        foreach ($roles as $role) {
            try {
                $data = $this->calculateDashboardData($role);
                $this->storeSummaryTable($role, $data);
                echo "Updated {$role} dashboard cache\n";
            } catch (Exception $e) {
                error_log("Failed to update {$role}: " . $e->getMessage());
            }
        }
    }
}

$updater = new DashboardCacheUpdater();
$updater->updateAllDashboards();
```

Add to crontab:
```bash
*/5 * * * * php /path/to/cron_dashboard_cache.php
```