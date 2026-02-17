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

        // Trim role name and check for exact match
        $roleName = trim($roleName);
        
        // Check for exact match first
        if (isset($roleMapping[$roleName])) {
            return [
                'type' => $roleMapping[$roleName],
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
     * @param string $managerRoleName Manager role name (e.g., "Fiber Manager - 1")
     * @param Database $db Database instance
     * @return array Array of user IDs belonging to the corresponding team lead role
     */
    public static function getTeamMemberUserIds($managerRoleName, $db)
    {
        // Map manager role to team lead role
        $teamMapping = [
            'Fiber Manager - 1' => 'Fiber Team Lead - 1',
            'Fiber Manager - 2' => 'Fiber Team Lead - 2'
        ];
        
        if (!isset($teamMapping[$managerRoleName])) {
            return [];
        }
        
        $teamLeadRole = $teamMapping[$managerRoleName];
        
        // Query to get all user IDs with the team lead role
        $sql = "SELECT DISTINCT u.id 
                FROM vtiger_users u
                INNER JOIN vtiger_user2role u2r ON u.id = u2r.userid
                INNER JOIN vtiger_role r ON u2r.roleid = r.roleid
                WHERE r.rolename = ? AND u.deleted = 0 AND u.status = 'Active'";
        
        $results = $db->fetchAll($sql, [$teamLeadRole]);
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