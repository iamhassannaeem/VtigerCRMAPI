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
            'Wireless Team Lead' => 'NOC',
            'Assistant Manager NOC' => 'NOC',
            'Internal Team Member' => 'NOC',
            'Networks Manager' => 'NOC',
            'Team Leads' => 'NOC',
            'Team Members' => 'NOC',
            
            // Field roles
            'External Team Leads' => 'Field',
            
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
}