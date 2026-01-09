<?php
/**
 * Password Helper - Generate password hashes compatible with Vtiger system
 */

class PasswordHelper
{
    /**
     * Generate MD5 hash for password (legacy Vtiger format)
     * 
     * @param string $password Plain text password
     * @return string MD5 hash
     */
    public static function generateMD5Hash($password)
    {
        return md5($password);
    }

    /**
     * Generate crypt hash for password (PHP5.3MD5 format - Vtiger standard)
     * This creates a hash compatible with crypt() function
     * 
     * @param string $password Plain text password
     * @param string $salt Optional salt (if not provided, generates random)
     * @return string Crypt hash (format: $1$salt$hash)
     */
    public static function generateCryptHash($password, $salt = null)
    {
        if ($salt === null) {
            // Generate random salt (12 characters for MD5-crypt)
            $salt = substr(str_replace('+', '.', base64_encode(random_bytes(9))), 0, 12);
        }
        
        // Generate MD5-crypt hash ($1$ indicates MD5-crypt algorithm)
        return crypt($password, '$1$' . $salt);
    }

    /**
     * Generate password hash compatible with Vtiger system
     * Uses crypt format by default (more secure)
     * 
     * @param string $password Plain text password
     * @return array ['hash' => string, 'user_hash' => string, 'format' => string]
     */
    public static function generatePasswordHash($password)
    {
        $cryptHash = self::generateCryptHash($password);
        $md5Hash = self::generateMD5Hash($password);
        
        return [
            'user_password' => $cryptHash,  // For user_password column
            'user_hash' => $md5Hash,        // For user_hash column (MD5)
            'confirm_password' => $cryptHash, // For confirm_password column
            'format' => 'PHP5.3MD5'
        ];
    }

    /**
     * Update user password in database
     * 
     * @param int $userId User ID
     * @param string $newPassword Plain text password
     * @return bool Success status
     */
    public static function updateUserPassword($userId, $newPassword)
    {
        $db = Database::getInstance();
        $passwordData = self::generatePasswordHash($newPassword);
        
        $sql = "UPDATE vtiger_users 
                SET user_password = :user_password,
                    user_hash = :user_hash,
                    confirm_password = :confirm_password,
                    crypt_type = :crypt_type
                WHERE id = :user_id";
        
        return $db->execute($sql, [
            ':user_password' => $passwordData['user_password'],
            ':user_hash' => $passwordData['user_hash'],
            ':confirm_password' => $passwordData['confirm_password'],
            ':crypt_type' => $passwordData['format'],
            ':user_id' => $userId
        ]);
    }

    /**
     * Generate random password
     * 
     * @param int $length Password length (default: 12)
     * @return string Random password
     */
    public static function generateRandomPassword($length = 12)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }
}

