<?php
/**
 * Refresh Token Model
 */

require_once __DIR__ . '/../core/Database.php';

class RefreshToken
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate a refresh token
     */
    public function generateToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Store refresh token
     */
    public function storeToken($userId, $token, $expiresAt)
    {
        $sql = "INSERT INTO refresh_tokens 
                (user_id, token, expires_at) 
                VALUES (?, ?, ?)";
        
        $this->db->execute($sql, [$userId, $token, date('Y-m-d H:i:s', $expiresAt)]);
        return true;
    }

    /**
     * Validate refresh token
     */
    public function validateToken($token)
    {
        $sql = "SELECT user_id, expires_at, is_revoked 
                FROM refresh_tokens 
                WHERE token = ?";
        
        $result = $this->db->fetchOne($sql, [$token]);

        if (!$result) {
            return false;
        }

        if ($result['is_revoked'] == 1) {
            return false;
        }

        if (strtotime($result['expires_at']) < time()) {
            return false;
        }

        return [
            'user_id' => $result['user_id'],
            'valid' => true
        ];
    }

    /**
     * Revoke refresh token
     */
    public function revokeToken($token)
    {
        $sql = "UPDATE refresh_tokens 
                SET is_revoked = 1, revoked_at = NOW()
                WHERE token = ?";
        
        $stmt = $this->db->execute($sql, [$token]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Revoke all tokens for a user
     */
    public function revokeAllUserTokens($userId)
    {
        $sql = "UPDATE refresh_tokens 
                SET is_revoked = 1, revoked_at = NOW()
                WHERE user_id = ? AND is_revoked = 0";
        
        $this->db->execute($sql, [$userId]);
        return true;
    }

    /**
     * Clean up expired tokens
     */
    public function cleanupExpiredTokens()
    {
        $sql = "DELETE FROM refresh_tokens 
                WHERE expires_at < NOW() OR is_revoked = 1";
        
        $this->db->execute($sql);
        return true;
    }
}
