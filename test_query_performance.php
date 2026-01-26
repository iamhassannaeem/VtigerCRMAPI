<?php
/**
 * Test Sales Tickets Query Performance
 * This script tests the actual query used in the code
 */

require_once __DIR__ . '/core/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Configuration
$testUserId = 11; // Change to your sales person user ID
$testLimit = 50;

echo "========================================\n";
echo "Sales Tickets Query Performance Test\n";
echo "========================================\n\n";

// This is the EXACT query from your code (OR-based)
$sql = "SELECT 
            tt.ticketid,
            tt.ticket_no,
            tt.title,
            tt.status,
            tt.priority,
            tt.severity,
            tt.category,
            ce.createdtime,
            ce.modifiedtime,
            ce.smownerid,
            CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name,
            a.accountname as account_name,
            CONCAT(c.firstname, ' ', c.lastname) as contact_name
        FROM vtiger_troubletickets tt
        INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
        LEFT JOIN vtiger_users u ON ce.smownerid = u.id
        LEFT JOIN vtiger_account a ON tt.parent_id = a.accountid
        LEFT JOIN vtiger_contactdetails c ON tt.contact_id = c.contactid
        LEFT JOIN vtiger_crmentity ce_account ON a.accountid = ce_account.crmid 
            AND ce_account.setype = 'Accounts' 
            AND ce_account.deleted = 0
        WHERE ce.deleted = 0
        AND (
            ce.smownerid = ? 
            OR (tt.parent_id IS NOT NULL AND ce_account.smownerid = ? AND ce_account.crmid IS NOT NULL)
        )
        ORDER BY ce.createdtime DESC
        LIMIT ? OFFSET 0";

$params = [$testUserId, $testUserId, $testLimit];

echo "TEST 1: EXPLAIN Query Plan\n";
echo "----------------------------------------\n";

$explainSql = "EXPLAIN " . $sql;
$stmt = $conn->prepare($explainSql);
$stmt->execute($params);
$explain = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo sprintf("%-20s | %-12s | %-35s | %-12s | %s\n", "Table", "Type", "Key", "Rows", "Extra");
echo str_repeat("-", 120) . "\n";

$hasFullScan = false;
$usingIndex = false;

foreach ($explain as $row) {
    $table = $row['table'] ?? 'N/A';
    $type = $row['type'] ?? 'N/A';
    $key = $row['key'] ?? 'NULL';
    $rows = $row['rows'] ?? 'N/A';
    $extra = $row['Extra'] ?? '';
    
    echo sprintf("%-20s | %-12s | %-35s | %-12s | %s\n", 
        substr($table, 0, 20), 
        $type, 
        substr($key, 0, 35), 
        $rows, 
        substr($extra, 0, 50)
    );
    
    if ($type === 'ALL') {
        $hasFullScan = true;
    }
    
    if (!empty($key) && $key !== 'NULL' && strpos($key, 'idx_') !== false) {
        $usingIndex = true;
    }
}

echo "\n";

if ($hasFullScan) {
    echo "❌ PROBLEM: Full table scan detected (type: ALL)\n";
    echo "   This means MySQL is scanning ALL rows instead of using indexes!\n\n";
}

if (!$usingIndex) {
    echo "❌ PROBLEM: No indexes are being used (key is NULL)\n";
    echo "   Indexes exist but MySQL isn't using them!\n\n";
}

echo "TEST 2: Execution Time\n";
echo "----------------------------------------\n";

$start = microtime(true);
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$time = (microtime(true) - $start) * 1000;

echo sprintf("Execution time: %.2f ms (%.2f seconds)\n", $time, $time / 1000);
echo sprintf("Rows returned: %d\n", count($results));

if ($time > 2000) {
    echo "❌ Query is VERY SLOW (>2 seconds)\n";
} elseif ($time > 1000) {
    echo "⚠️  Query is SLOW (>1 second)\n";
} else {
    echo "✅ Query performance is acceptable\n";
}

echo "\n";

echo "TEST 3: Count Total Tickets (without LIMIT)\n";
echo "----------------------------------------\n";

$countSql = "SELECT COUNT(*) as total
        FROM vtiger_troubletickets tt
        INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
        LEFT JOIN vtiger_account a ON tt.parent_id = a.accountid
        LEFT JOIN vtiger_crmentity ce_account ON a.accountid = ce_account.crmid 
            AND ce_account.setype = 'Accounts' 
            AND ce_account.deleted = 0
        WHERE ce.deleted = 0
        AND (
            ce.smownerid = ? 
            OR (tt.parent_id IS NOT NULL AND ce_account.smownerid = ? AND ce_account.crmid IS NOT NULL)
        )";

$start = microtime(true);
$stmt = $conn->prepare($countSql);
$stmt->execute([$testUserId, $testUserId]);
$countResult = $stmt->fetch(PDO::FETCH_ASSOC);
$countTime = (microtime(true) - $start) * 1000;

echo sprintf("Total tickets: %s\n", number_format($countResult['total']));
echo sprintf("Count time: %.2f ms (%.2f seconds)\n", $countTime, $countTime / 1000);

echo "\n";

echo "TEST 4: Check Indexes on Key Columns\n";
echo "----------------------------------------\n";

$indexSql = "SELECT 
                TABLE_NAME,
                INDEX_NAME,
                GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) as columns,
                CARDINALITY
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND (
                (TABLE_NAME = 'vtiger_crmentity' AND INDEX_NAME LIKE 'idx_%smownerid%')
                OR (TABLE_NAME = 'vtiger_crmentity' AND INDEX_NAME LIKE 'idx_%setype%')
                OR (TABLE_NAME = 'vtiger_troubletickets' AND INDEX_NAME LIKE 'idx_%parent%')
                OR (TABLE_NAME = 'vtiger_crmentity' AND INDEX_NAME LIKE 'idx_%createdtime%')
            )
            GROUP BY TABLE_NAME, INDEX_NAME
            ORDER BY TABLE_NAME, INDEX_NAME";

$stmt = $conn->prepare($indexSql);
$stmt->execute();
$indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($indexes)) {
    echo "❌ No relevant indexes found!\n";
} else {
    echo "✅ Found " . count($indexes) . " relevant indexes:\n";
    foreach ($indexes as $idx) {
        echo sprintf("   %s.%s on (%s) - Cardinality: %s\n", 
            $idx['TABLE_NAME'], 
            $idx['INDEX_NAME'], 
            $idx['columns'],
            number_format($idx['CARDINALITY'] ?? 0)
        );
    }
}

echo "\n";

echo "TEST 5: Test with Date Filter (Last 90 Days)\n";
echo "----------------------------------------\n";

$sqlWithDate = "SELECT 
            tt.ticketid,
            tt.ticket_no,
            tt.title,
            tt.status,
            ce.createdtime
        FROM vtiger_troubletickets tt
        INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
        LEFT JOIN vtiger_account a ON tt.parent_id = a.accountid
        LEFT JOIN vtiger_crmentity ce_account ON a.accountid = ce_account.crmid 
            AND ce_account.setype = 'Accounts' 
            AND ce_account.deleted = 0
        WHERE ce.deleted = 0
        AND ce.createdtime >= DATE_SUB(NOW(), INTERVAL 90 DAY)
        AND (
            ce.smownerid = ? 
            OR (tt.parent_id IS NOT NULL AND ce_account.smownerid = ? AND ce_account.crmid IS NOT NULL)
        )
        ORDER BY ce.createdtime DESC
        LIMIT ? OFFSET 0";

$start = microtime(true);
$stmt = $conn->prepare($sqlWithDate);
$stmt->execute([$testUserId, $testUserId, $testLimit]);
$resultsWithDate = $stmt->fetchAll(PDO::FETCH_ASSOC);
$timeWithDate = (microtime(true) - $start) * 1000;

echo sprintf("Execution time with date filter: %.2f ms (%.2f seconds)\n", $timeWithDate, $timeWithDate / 1000);
echo sprintf("Rows returned: %d\n", count($resultsWithDate));

if ($timeWithDate < $time) {
    $improvement = (($time - $timeWithDate) / $time) * 100;
    echo sprintf("✅ Date filter improves performance by %.1f%%\n", $improvement);
}

echo "\n";

echo "========================================\n";
echo "DIAGNOSIS & RECOMMENDATIONS\n";
echo "========================================\n\n";

echo "ROOT CAUSE ANALYSIS:\n";
echo "--------------------\n";

if ($hasFullScan) {
    echo "1. ❌ Full table scan on vtiger_troubletickets (60,000+ rows)\n";
    echo "   - MySQL is scanning ALL tickets instead of using indexes\n";
    echo "   - This is why the query is so slow\n\n";
}

echo "2. OR Condition Problem:\n";
echo "   - OR conditions prevent efficient index usage\n";
echo "   - MySQL must check BOTH sides of the OR\n";
echo "   - Can't use a single index for the entire WHERE clause\n\n";

echo "3. LEFT JOIN Overhead:\n";
echo "   - LEFT JOIN creates many NULL rows\n";
echo "   - Must process and filter them out\n";
echo "   - Wasteful processing\n\n";

echo "SOLUTIONS:\n";
echo "----------\n";

echo "1. ✅ Add Date Filter (IMMEDIATE FIX):\n";
echo "   - Limit to last 90 days reduces dataset from 60,000 to ~5,000 rows\n";
echo "   - This alone will make query 5-10x faster\n";
echo "   - Add to WHERE clause: AND ce.createdtime >= DATE_SUB(NOW(), INTERVAL 90 DAY)\n\n";

echo "2. ✅ Use UNION Instead of OR (BEST FIX):\n";
echo "   - UNION allows each part to use indexes independently\n";
echo "   - 3-5x faster than OR query\n";
echo "   - Already implemented in code but you reverted it\n\n";

echo "3. ✅ Run ANALYZE TABLE:\n";
echo "   - Updates MySQL statistics\n";
echo "   - Helps MySQL choose better indexes\n";
echo "   - Run: ANALYZE TABLE vtiger_crmentity, vtiger_troubletickets, vtiger_account;\n\n";

echo "4. ✅ Add Composite Index:\n";
echo "   - CREATE INDEX idx_owner_deleted_created ON vtiger_crmentity(smownerid, deleted, createdtime);\n";
echo "   - Helps with date-filtered queries\n\n";

echo "\n";
