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
     * Uses role hierarchy from database to dynamically find all child roles
     * Follows the hierarchy: Manager -> Team Leads -> Team Members
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
        
        try {
            // Normalize the role name
            $normalizedRole = trim($managerRoleName);
            $normalizedRole = preg_replace('/\s+/', ' ', $normalizedRole);
            
            // Step 1: Get the manager's roleid from rolename
            $sql = "SELECT roleid FROM vtiger_role WHERE rolename = ? LIMIT 1";
            $managerRole = $db->fetchOne($sql, [$normalizedRole]);
            
            if (!$managerRole || !isset($managerRole['roleid']) || empty($managerRole['roleid'])) {
                error_log("RoleHelper: Manager role not found: {$normalizedRole}");
                return [];
            }
            
            $managerRoleId = $managerRole['roleid'];
            
            // Step 2: Find ALL child roles where parentrole contains the manager's roleid
            // The parentrole column uses :: delimiter format (e.g., "H1::H2::H10")
            // We need to find roles where the manager's roleid appears in the path
            // This handles both immediate children and nested descendants
            // 
            // Example hierarchy:
            // - Fiber Manager (H10): parentrole = 'H1::H2::H10'
            // - Fiber Team Lead (H11): parentrole = 'H1::H2::H10::H11'
            // Pattern '%::H10::%' will match Team Lead's parentrole
            
            // Escape special characters for LIKE patterns (% and _ are wildcards in SQL LIKE)
            $escapedRoleId = str_replace(['%', '_'], ['\%', '\_'], $managerRoleId);
            
            // Using LIKE to find roles where manager's roleid is in the parentrole path
            // Patterns:
            // - '%::H10::%' matches roles where H10 appears in the middle (e.g., "H1::H2::H10::H11")
            // - '%::H10' matches roles ending with ::H10 (but we exclude manager itself)
            // - 'H10::%' matches roles starting with H10:: (if manager is root level)
            $sql = "SELECT roleid, rolename 
                    FROM vtiger_role 
                    WHERE (
                        parentrole LIKE ? 
                        OR parentrole LIKE ?
                        OR parentrole LIKE ?
                    )
                    AND roleid != ?";  // Exclude the manager role itself
            
            $params = [
                "%::{$escapedRoleId}::%",  // Paths with manager roleid in the middle (e.g., "H1::H2::H10::H11")
                "%::{$escapedRoleId}",     // Paths ending with ::managerRoleId (immediate children)
                "{$escapedRoleId}::%",     // Paths starting with managerRoleId:: (if manager is root)
                $managerRoleId             // Exclude manager itself
            ];
            
            $childRoles = $db->fetchAll($sql, $params);
            
            if (empty($childRoles)) {
                error_log("RoleHelper: No child roles found for manager role: {$normalizedRole} (roleid: {$managerRoleId})");
                return [];
            }
            
            // Step 3: Extract all roleids of child roles
            $childRoleIds = array_column($childRoles, 'roleid');
            
            // Filter out any null or empty values
            $childRoleIds = array_filter($childRoleIds, function($id) {
                return !empty($id);
            });
            
            // Step 4: Get all user IDs with any of these child roles
            if (empty($childRoleIds)) {
                error_log("RoleHelper: No valid child role IDs found for manager: {$normalizedRole}");
                return [];
            }
            
            // Ensure we have sequential array keys for proper parameter binding
            $childRoleIds = array_values($childRoleIds);
            
            $placeholders = implode(',', array_fill(0, count($childRoleIds), '?'));
            $sql = "SELECT DISTINCT u.id 
                    FROM vtiger_users u
                    INNER JOIN vtiger_user2role u2r ON u.id = u2r.userid
                    WHERE u2r.roleid IN ($placeholders)
                    AND u.deleted = 0 
                    AND u.status = 'Active'";
            
            $results = $db->fetchAll($sql, $childRoleIds);
            
            if (empty($results)) {
                error_log("RoleHelper: No active users found for child roles of manager: {$normalizedRole}");
                return [];
            }
            
            $userIds = array_column($results, 'id');
            
            // Filter out any null values
            $userIds = array_filter($userIds, function($id) {
                return !empty($id);
            });
            
            error_log("RoleHelper: Found " . count($userIds) . " team member user IDs for manager: {$normalizedRole}");
            return array_values($userIds);
            
        } catch (Exception $e) {
            // Log the error but don't crash - return empty array
            error_log("RoleHelper::getTeamMemberUserIds ERROR for role '{$managerRoleName}': " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        } catch (Error $e) {
            // Catch fatal errors as well
            error_log("RoleHelper::getTeamMemberUserIds FATAL ERROR for role '{$managerRoleName}': " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [];
        }
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