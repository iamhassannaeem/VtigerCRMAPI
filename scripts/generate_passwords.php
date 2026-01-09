<?php
/**
 * Password Generation Script
 * 
 * This script generates password hashes for all users mentioned in the requirement.
 * Run this script from command line: php scripts/generate_passwords.php
 * 
 * Usage:
 *   php scripts/generate_passwords.php > passwords.txt
 */

// Load core classes
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../helpers/PasswordHelper.php';
require_once __DIR__ . '/../models/User.php';

// Users to generate passwords for
$users = [
    'admin' => 'admin',
    'adeel.zahoor' => 'adeel.zahoor',
    'Khawar' => 'Khawar', // username is 'Khawar'
    'Kamran' => 'Kamran', // username is 'Kamran'
    'qasim.ali' => 'qasim.ali'
];

// Default password (change this to your desired default password)
$defaultPassword = 'Password123!';

echo "=== Password Generation for Vtiger Users ===\n\n";
echo "Default Password: {$defaultPassword}\n";
echo "IMPORTANT: Change these passwords after first login!\n\n";
echo str_repeat("=", 70) . "\n\n";

$db = Database::getInstance();

foreach ($users as $displayName => $username) {
    // Find user in database
    $sql = "SELECT id, user_name, first_name, last_name FROM vtiger_users WHERE user_name = ?";
    $user = $db->fetchOne($sql, [$username]);
    
    if (!$user) {
        echo "⚠️  User '{$username}' not found in database!\n\n";
        continue;
    }
    
    // Generate password hash
    $passwordData = PasswordHelper::generatePasswordHash($defaultPassword);
    
    // Display information
    echo "User: {$displayName}\n";
    echo "  Username: {$user['user_name']}\n";
    echo "  Full Name: {$user['first_name']} {$user['last_name']}\n";
    echo "  User ID: {$user['id']}\n";
    echo "  Password: {$defaultPassword}\n";
    echo "  Hash (user_password): {$passwordData['user_password']}\n";
    echo "  Hash (user_hash): {$passwordData['user_hash']}\n\n";
    
    // Generate UPDATE query
    echo "  SQL UPDATE Query:\n";
    echo "  UPDATE vtiger_users SET\n";
    echo "    user_password = '{$passwordData['user_password']}',\n";
    echo "    user_hash = '{$passwordData['user_hash']}',\n";
    echo "    confirm_password = '{$passwordData['confirm_password']}',\n";
    echo "    crypt_type = '{$passwordData['format']}'\n";
    echo "  WHERE id = {$user['id']};\n\n";
    
    echo str_repeat("-", 70) . "\n\n";
}

echo "=== Password Update Queries (Combined) ===\n\n";

// Generate all UPDATE queries
foreach ($users as $displayName => $username) {
    $user = $db->fetchOne("SELECT id FROM vtiger_users WHERE user_name = ?", [$username]);
    if ($user) {
        $passwordData = PasswordHelper::generatePasswordHash($defaultPassword);
        echo "-- {$displayName} (id: {$user['id']})\n";
        echo "UPDATE vtiger_users SET\n";
        echo "  user_password = '{$passwordData['user_password']}',\n";
        echo "  user_hash = '{$passwordData['user_hash']}',\n";
        echo "  confirm_password = '{$passwordData['confirm_password']}',\n";
        echo "  crypt_type = '{$passwordData['format']}'\n";
        echo "WHERE id = {$user['id']};\n\n";
    }
}

echo "\n=== End of Password Generation ===\n";

