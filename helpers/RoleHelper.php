<?php
/**
 * Role Helper
 * Maps role names directly to dashboard types
 */

class RoleHelper
{
    /**
     * Map role name directly to dashboard type
     * 
     * @param string $roleName Role name from database (e.g., "CEO", "Sales Manager")
     * @return array ['type' => 'Management', 'base_name' => 'CEO', 'full_name' => 'CEO']
     */
    public static function getRoleType($roleName)
    {
        if (empty($roleName)) {
            return [
                'type' => 'Others',
                'base_name' => '',
                'full_name' => ''
            ];
        }

        // Direct role name to dashboard type mapping
        $roleMapping = [
            // Management roles
            'Organization' => 'Management',
            'CEO' => 'Management',
            'Admin' => 'Management',
            
            // Sales roles
            'Sales Manager' => 'Sales',
            'Sales Person' => 'Sales',
            
            // NOC roles
            'Assistant Manager NOC' => 'NOC',
            'Internal Team Member' => 'NOC',
            'Networks Manager' => 'NOC',
            'Team Leads' => 'NOC',
            'Team Members' => 'NOC',
            
            // Field roles
            'Wireless Team Lead' => 'Field',
            'External Team Leads' => 'Field',
            'Fiber Manager - 1' => 'Field',
            'Fiber Team Lead - 1' => 'Field',
            'Fiber Manager - 2' => 'Field',
            'Fiber Team Lead - 2' => 'Field',
            
            // Others (default for all other roles)
            'Human Resource' => 'Others',
            'Accounts Team' => 'Others',
            'IPTV' => 'Others',
            'Office Manager' => 'Others',
            'Store Manager' => 'Others',
            'PBX/Voice' => 'Others',
            'GIS' => 'Others',
        ];

        // Normalize role name: trim and replace multiple spaces with single space
        $roleName = trim($roleName);
        $roleName = preg_replace('/\s+/', ' ', $roleName); // Normalize whitespace
        
        // Check for exact match first
        if (isset($roleMapping[$roleName])) {
            return [
                'type' => $roleMapping[$roleName],
                'base_name' => $roleName,
                'full_name' => $roleName
            ];
        }
        
        // Fallback: Try case-insensitive matching for Fiber Manager roles
        if (preg_match('/Fiber\s+Manager\s*[-]?\s*\d+/i', $roleName)) {
            return [
                'type' => 'Field',
                'base_name' => $roleName,
                'full_name' => $roleName
            ];
        }
        
        // Fallback: Try case-insensitive matching for Fiber Team Lead roles
        if (preg_match('/Fiber\s+Team\s+Lead\s*[-]?\s*\d+/i', $roleName)) {
            return [
                'type' => 'Field',
                'base_name' => $roleName,
                'full_name' => $roleName
            ];
        }

        // If no exact match found, default to Others
        return [
            'type' => 'Others',
            'base_name' => $roleName,
            'full_name' => $roleName
        ];
    }

    /**
     * Get all role types
     * 
     * @return array List of valid role types
     */
    public static function getValidRoleTypes()
    {
        return ['NOC', 'Sales', 'Management', 'Field', 'Others'];
    }

    /**
     * Validate if a role type is valid
     * 
     * @param string $roleType
     * @return bool
     */
    public static function isValidRoleType($roleType)
    {
        return in_array($roleType, self::getValidRoleTypes());
    }

    /**
     * Get team member user IDs for a manager role
     * 
     * @param string $managerRoleName Manager role name (e.g., "Fiber Manager - 1", "Fiber Manager-1", "Fiber Manager 1")
     * @param Database $db Database instance
     * @return array Array of user IDs belonging to the corresponding team lead role
     */
    public static function getTeamMemberUserIds($managerRoleName, $db)
    {
        if (empty($managerRoleName)) {
            return [];
        }
        
        // Normalize the role name: trim and handle variations
        $normalizedRole = trim($managerRoleName);
        $normalizedRole = preg_replace('/\s+/', ' ', $normalizedRole); // Normalize whitespace
        
        // Check if it's a Fiber Manager role using flexible matching
        // Handles: "Fiber Manager - 1", "Fiber Manager-1", "Fiber Manager 1", etc.
        if (preg_match('/Fiber\s+Manager\s*[-]?\s*(\d+)/i', $normalizedRole, $matches)) {
            $managerNumber = $matches[1]; // Extract the number (1 or 2)
            
            // Query to get all user IDs with the team lead role
            // Based on login response, the exact format is "Fiber Team Lead - 1" (with spaces around dash)
            $sql = "SELECT DISTINCT u.id 
                    FROM vtiger_users u
                    INNER JOIN vtiger_user2role u2r ON u.id = u2r.userid
                    INNER JOIN vtiger_role r ON u2r.roleid = r.roleid
                    WHERE (
                        r.rolename = ? 
                        OR r.rolename = ?
                        OR r.rolename = ?
                        OR r.rolename LIKE ?
                    ) 
                    AND u.deleted = 0 
                    AND u.status = 'Active'";
            
            // Try multiple variations of the team lead role name
            // Pass exactly 4 parameters to match the 4 placeholders in SQL
            $params = [
                "Fiber Team Lead - {$managerNumber}",  // Standard format (exact match from DB)
                "Fiber Team Lead-{$managerNumber}",    // No spaces around dash (fallback)
                "Fiber Team Lead {$managerNumber}",     // Space instead of dash (fallback)
                "Fiber Team Lead%{$managerNumber}%"     // LIKE pattern as final fallback
            ];
            
            $results = $db->fetchAll($sql, $params);
            return array_column($results, 'id');
        }
        
        // Fallback: Try exact match with normalized role name (for backward compatibility)
        $teamMapping = [
            'Fiber Manager - 1' => 'Fiber Team Lead - 1',
            'Fiber Manager - 2' => 'Fiber Team Lead - 2'
        ];
        
        if (isset($teamMapping[$normalizedRole])) {
            $teamLeadRole = $teamMapping[$normalizedRole];
            
            $sql = "SELECT DISTINCT u.id 
                    FROM vtiger_users u
                    INNER JOIN vtiger_user2role u2r ON u.id = u2r.userid
                    INNER JOIN vtiger_role r ON u2r.roleid = r.roleid
                    WHERE r.rolename = ? AND u.deleted = 0 AND u.status = 'Active'";
            
            $results = $db->fetchAll($sql, [$teamLeadRole]);
            return array_column($results, 'id');
        }
        
        return [];
    }

    /**
     * Get all user IDs with the same role (for peer-sharing roles)
     * 
     * @param string $roleName Role name (e.g., "Wireless Team Lead")
     * @param Database $db Database instance
     * @return array Array of user IDs with the same role
     */
    public static function getPeerUserIds($roleName, $db)
    {
        if (empty($roleName)) {
            return [];
        }
        
        // Query to get all user IDs with the same role
        $sql = "SELECT DISTINCT u.id 
                FROM vtiger_users u
                INNER JOIN vtiger_user2role u2r ON u.id = u2r.userid
                INNER JOIN vtiger_role r ON u2r.roleid = r.roleid
                WHERE r.rolename = ? AND u.deleted = 0 AND u.status = 'Active'";
        
        $results = $db->fetchAll($sql, [$roleName]);
        return array_column($results, 'id');
    }
}