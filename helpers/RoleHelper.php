<?php
/**
 * Role Helper
 * Determines role type based on role name postfix
 */

class RoleHelper
{
    /**
     * Extract role type from role name based on postfix
     * 
     * @param string $roleName Role name from database (e.g., "Sales Manager_Sales")
     * @return array ['type' => 'Sales', 'base_name' => 'Sales Manager']
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

        // Define valid role types
        $validTypes = ['NOC', 'Sales', 'Management', 'Field', 'Others'];
        
        // Check for postfix pattern
        foreach ($validTypes as $type) {
            $postfix = '_' . $type;
            
            // Check if role name ends with this postfix
            if (substr($roleName, -strlen($postfix)) === $postfix) {
                return [
                    'type' => $type,
                    'base_name' => trim(substr($roleName, 0, -strlen($postfix))),
                    'full_name' => $roleName
                ];
            }
        }

        // No postfix found, default to Others
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