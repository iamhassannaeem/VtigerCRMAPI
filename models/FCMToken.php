<?php
/**
 * FCM Token Model
 */

require_once __DIR__ . '/../core/Database.php';

class FCMToken
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get active FCM tokens for a user
     */
    public function getActiveTokensForUser($userId)
    {
        $sql = "SELECT fcm_token, device_type, device_id 
                FROM user_fcm_tokens 
                WHERE user_id = ? AND is_active = 1";
        
        return $this->db->fetchAll($sql, [$userId]);
    }

    /**
     * Register or update FCM token for a user
     */
    public function registerToken($userId, $fcmToken, $deviceType = null, $deviceId = null)
    {
        // Check if token already exists
        $sql = "SELECT id FROM user_fcm_tokens 
                WHERE user_id = ? AND fcm_token = ?";
        $existing = $this->db->fetchOne($sql, [$userId, $fcmToken]);

        if ($existing) {
            // Update existing token
            $sql = "UPDATE user_fcm_tokens 
                    SET device_type = ?, device_id = ?, is_active = 1, updated_at = NOW()
                    WHERE user_id = ? AND fcm_token = ?";
            $this->db->execute($sql, [$deviceType, $deviceId, $userId, $fcmToken]);
        } else {
            // Insert new token
            $sql = "INSERT INTO user_fcm_tokens 
                    (user_id, fcm_token, device_type, device_id, is_active) 
                    VALUES (?, ?, ?, ?, 1)";
            $this->db->execute($sql, [$userId, $fcmToken, $deviceType, $deviceId]);
        }

        return true;
    }

    /**
     * Deactivate a token (mark as inactive)
     */
    public function deactivateToken($fcmToken)
    {
        $sql = "UPDATE user_fcm_tokens 
                SET is_active = 0, updated_at = NOW()
                WHERE fcm_token = ?";
        
        $stmt = $this->db->execute($sql, [$fcmToken]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Remove token completely
     */
    public function removeToken($fcmToken)
    {
        $sql = "DELETE FROM user_fcm_tokens WHERE fcm_token = ?";
        $this->db->execute($sql, [$fcmToken]);
        return true;
    }
}
