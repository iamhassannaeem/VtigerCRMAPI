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
     * Get team member user IDs for a manager role (DYNAMIC VERSION)
     * Uses role hierarchy from database instead of hardcoded patterns
     * 
     * @param string $managerRoleName Manager role name (e.g., "Fiber Manager - 1")
     * @param Database $db Database instance
     * @return array Array of user IDs belonging to child roles under this manager
     */
    public static function getTeamMemberUserIds($managerRoleName, $db)
    {
        if (empty($managerRoleName)) {
            return [];
        }
        
        // Normalize the role name
        $normalizedRole = trim($managerRoleName);
        $normalizedRole = preg_replace('/\s+/', ' ', $normalizedRole);
        
        // Step 1: Get the manager's roleid from rolename
        $sql = "SELECT roleid FROM vtiger_role WHERE rolename = ? LIMIT 1";
        $managerRole = $db->fetchOne($sql, [$normalizedRole]);
        
        if (!$managerRole || !isset($managerRole['roleid'])) {
            return [];
        }
        
        $managerRoleId = $managerRole['roleid'];
        
        // Step 2: Find ALL child roles where parentrole contains the manager's roleid
        // The parentrole column uses :: delimiter format (e.g., "H1::H2::H10")
        // We need to find roles where the manager's roleid appears in the path
        // This handles both immediate children and nested descendants
        
        // Using LIKE to find roles where manager's roleid is in the parentrole path
        // Pattern: parentrole LIKE '%::H10' OR parentrole LIKE 'H10::%' OR parentrole = 'H10'
        $sql = "SELECT roleid, rolename 
                FROM vtiger_role 
                WHERE (
                    parentrole LIKE ? 
                    OR parentrole LIKE ?
                    OR parentrole = ?
                )";
        
        $params = [
            "%::{$managerRoleId}",      // Paths ending with ::managerRoleId (immediate children)
            "{$managerRoleId}::%",      // Paths starting with managerRoleId:: (if manager is root)
            $managerRoleId              // Direct parent (if any)
        ];
        
        $childRoles = $db->fetchAll($sql, $params);
        
        if (empty($childRoles)) {
            return [];
        }
        
        // Step 3: Extract all roleids of child roles
        $childRoleIds = array_column($childRoles, 'roleid');
        
        // Step 4: Get all user IDs with any of these child roles
        if (empty($childRoleIds)) {
            return [];
        }
        
        $placeholders = implode(',', array_fill(0, count($childRoleIds), '?'));
        $sql = "SELECT DISTINCT u.id 
                FROM vtiger_users u
                INNER JOIN vtiger_user2role u2r ON u.id = u2r.userid
                WHERE u2r.roleid IN ($placeholders)
                AND u.deleted = 0 
                AND u.status = 'Active'";
        
        $results = $db->fetchAll($sql, $childRoleIds);
        return array_column($results, 'id');
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