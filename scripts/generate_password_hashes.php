<?php
/**
 * Standalone Password Hash Generator
 * 
 * This script generates password hashes without requiring database connection.
 * Run from command line: php scripts/generate_password_hashes.php
 * 
 * Usage:
 *   php scripts/generate_password_hashes.php > password_hashes.txt
 */

// Password helper functions (inline)
function generateMD5Hash($password) {
    return md5($password);
}

function generateCryptHash($password, $salt = null) {
    if ($salt === null) {
        // Use first 3 characters of username + '000000' for consistency with Vtiger
        $salt = substr(str_replace('+', '.', base64_encode(random_bytes(9))), 0, 12);
    }
    return crypt($password, '$1$' . $salt);
}

// Default password (change this if needed)
$defaultPassword = 'Password123!';

// Users with their usernames and user IDs
$users = [
    ['username' => 'admin', 'id' => 1, 'salt_prefix' => 'ad000000'],
    ['username' => 'adeel.zahoor', 'id' => 36, 'salt_prefix' => 'ad000000'],
    ['username' => 'Khawar', 'id' => 83, 'salt_prefix' => 'Kh000000'],
    ['username' => 'Kamran', 'id' => 134, 'salt_prefix' => 'Ka000000'],
    ['username' => 'qasim.ali', 'id' => 11, 'salt_prefix' => 'qa000000'],
];

echo "=== Password Hash Generation for Vtiger Users ===\n\n";
echo "Default Password: {$defaultPassword}\n";
echo "⚠️  IMPORTANT: Change these passwords after first login!\n\n";
echo str_repeat("=", 80) . "\n\n";

foreach ($users as $user) {
    $username = $user['username'];
    $userId = $user['id'];
    $saltPrefix = $user['salt_prefix'];
    
    // Generate crypt hash with consistent salt (Vtiger style)
    $cryptHash = crypt($defaultPassword, '$1$' . $saltPrefix);
    $md5Hash = generateMD5Hash($defaultPassword);
    
    echo "User: {$username}\n";
    echo "  User ID: {$userId}\n";
    echo "  Password: {$defaultPassword}\n";
    echo "  Crypt Hash (user_password): {$cryptHash}\n";
    echo "  MD5 Hash (user_hash): {$md5Hash}\n";
    echo "\n";
    
    echo "  SQL UPDATE Query:\n";
    echo "  -- Update password for {$username} (ID: {$userId})\n";
    echo "  UPDATE vtiger_users SET\n";
    echo "    user_password = '{$cryptHash}',\n";
    echo "    user_hash = '{$md5Hash}',\n";
    echo "    confirm_password = '{$cryptHash}',\n";
    echo "    crypt_type = 'PHP5.3MD5'\n";
    echo "  WHERE id = {$userId};\n\n";
    
    echo str_repeat("-", 80) . "\n\n";
}

echo "\n=== Combined SQL UPDATE Queries ===\n\n";

foreach ($users as $user) {
    $username = $user['username'];
    $userId = $user['id'];
    $saltPrefix = $user['salt_prefix'];
    
    $cryptHash = crypt($defaultPassword, '$1$' . $saltPrefix);
    $md5Hash = generateMD5Hash($defaultPassword);
    
    echo "-- {$username} (ID: {$userId})\n";
    echo "UPDATE vtiger_users SET\n";
    echo "  user_password = '{$cryptHash}',\n";
    echo "  user_hash = '{$md5Hash}',\n";
    echo "  confirm_password = '{$cryptHash}',\n";
    echo "  crypt_type = 'PHP5.3MD5'\n";
    echo "WHERE id = {$userId};\n\n";
}

echo "\n=== Password Summary ===\n\n";
echo "All users have been assigned the default password: {$defaultPassword}\n";
echo "Passwords are stored using PHP5.3MD5 crypt format.\n\n";
echo "To test a password login:\n";
echo "1. Use username and password: {$defaultPassword}\n";
echo "2. The system will verify using crypt() function\n\n";
echo "=== End ===\n";

