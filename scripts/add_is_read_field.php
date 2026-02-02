<?php
/**
 * Add is_read field to notification_logs table
 */

require_once __DIR__ . '/../core/Database.php';

$config = require __DIR__ . '/../config/database.php';

try {
    $dsn = sprintf(
        "mysql:host=%s;port=%d;dbname=%s;charset=%s",
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    $pdo = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );

    echo "Adding is_read and updated_at fields to notification_logs table...\n";

    // Check if is_read column already exists
    $checkSql = "SHOW COLUMNS FROM notification_logs LIKE 'is_read'";
    $result = $pdo->query($checkSql);
    
    if ($result->rowCount() == 0) {
        // Add is_read column
        $pdo->exec("ALTER TABLE notification_logs ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER status");
        echo "✓ Added is_read column\n";
    } else {
        echo "✓ is_read column already exists\n";
    }

    // Check if updated_at column already exists
    $checkSql = "SHOW COLUMNS FROM notification_logs LIKE 'updated_at'";
    $result = $pdo->query($checkSql);
    
    if ($result->rowCount() == 0) {
        // Add updated_at column
        $pdo->exec("ALTER TABLE notification_logs ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at");
        echo "✓ Added updated_at column\n";
    } else {
        echo "✓ updated_at column already exists\n";
    }

    // Add index if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE notification_logs ADD INDEX idx_is_read (is_read)");
        echo "✓ Added index on is_read\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "✓ Index already exists\n";
        } else {
            throw $e;
        }
    }

    echo "\nMigration completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
