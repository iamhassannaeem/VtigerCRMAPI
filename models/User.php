<?php
/**
 * User Model
 */

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Authenticate user by username and password
     */
    public function authenticate($username, $password)
    {
        // Sanitize input
        $username = trim($username);
        $password = trim($password);

        // First, get the user by username
        $sql = "SELECT u.*, r.rolename, r.roleid
                FROM vtiger_users u
                LEFT JOIN vtiger_user2role u2r ON u.id = u2r.userid
                LEFT JOIN vtiger_role r ON u2r.roleid = r.roleid
                WHERE u.user_name = ? 
                AND u.status = 'Active'
                AND u.deleted = 0";

        $user = $this->db->fetchOne($sql, [$username]);

        if (!$user) {
            return null;
        }

        // Verify password - Vtiger uses different password formats
        $storedPassword = $user['user_password'];
        $isValid = false;

        // Check if it's crypt format (starts with $1$, $2$, $5$, $6$, etc.)
        if (preg_match('/^\$[0-9a-z]+\$/', $storedPassword)) {
            // Use crypt() for MD5-crypt, bcrypt, etc.
            $isValid = (crypt($password, $storedPassword) === $storedPassword);
        } else {
            // Plain MD5 hash (legacy Vtiger)
            $hashedPassword = md5($password);
            $isValid = ($hashedPassword === $storedPassword);
        }

        if (!$isValid) {
            return null;
        }

        return $user;
    }

    /**
     * Get user by ID
     */
    public function getUserById($userId)
    {
        $sql = "SELECT u.*, r.rolename, r.roleid
                FROM vtiger_users u
                LEFT JOIN vtiger_user2role u2r ON u.id = u2r.userid
                LEFT JOIN vtiger_role r ON u2r.roleid = r.roleid
                WHERE u.id = ? AND u.deleted = 0";

        return $this->db->fetchOne($sql, [$userId]);
    }

    /**
     * Get user's assigned tickets count
     */
    public function getAssignedTicketsCount($userId)
    {
        $sql = "SELECT COUNT(*) as count
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.smownerid = ?
                AND ce.deleted = 0";

        $result = $this->db->fetchOne($sql, [$userId]);
        return $result['count'] ?? 0;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin($userId)
    {
        $sql = "SELECT is_admin FROM vtiger_users WHERE id = ?";
        $result = $this->db->fetchOne($sql, [$userId]);
        return $result && $result['is_admin'] === 'on';
    }

    /**
     * Get user full name
     */
    public function getFullName($userId)
    {
        $sql = "SELECT CONCAT(first_name, ' ', last_name) as full_name 
                FROM vtiger_users 
                WHERE id = ?";
        
        $result = $this->db->fetchOne($sql, [$userId]);
        return $result['full_name'] ?? '';
    }
}

