<?php
/**
 * Picklist Model
 * Handles all picklist data retrieval
 */

class Picklist
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get ticket priorities
     */
    public function getPriorities()
    {
        $sql = "SELECT 
                    ticketpriorities_id as id,
                    ticketpriorities as value,
                    color,
                    sortorderid
                FROM vtiger_ticketpriorities
                WHERE presence = 1
                ORDER BY sortorderid ASC";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get Services Issue (custom picklist cf_869)
     */
    public function getServicesIssue()
    {
        $sql = "SELECT 
                    cf_869id as id,
                    cf_869 as value,
                    color,
                    sortorderid
                FROM vtiger_cf_869
                WHERE presence = 1
                ORDER BY sortorderid ASC";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get ticket statuses
     */
    public function getStatuses()
    {
        $sql = "SELECT 
                    ticketstatus_id as id,
                    ticketstatus as value,
                    color,
                    sortorderid
                FROM vtiger_ticketstatus
                WHERE presence = 0
                ORDER BY sortorderid ASC";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get active users (for assignment)
     */
    public function getUsers()
    {
        $sql = "SELECT 
                    id,
                    user_name as username,
                    CONCAT(first_name, ' ', last_name) as full_name,
                    first_name,
                    last_name,
                    email1,
                    department,
                    is_admin,
                    status
                FROM vtiger_users
                WHERE deleted = 0 
                AND status = 'Active'
                ORDER BY first_name ASC, last_name ASC";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get Issue End (custom picklist cf_871)
     */
    public function getIssueEnd()
    {
        $sql = "SELECT 
                    cf_871id as id,
                    cf_871 as value,
                    color,
                    sortorderid
                FROM vtiger_cf_871
                WHERE presence = 1
                ORDER BY sortorderid ASC";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get ticket categories
     */
    public function getCategories()
    {
        $sql = "SELECT 
                    ticketcategories_id as id,
                    ticketcategories as value,
                    color,
                    sortorderid
                FROM vtiger_ticketcategories
                WHERE presence = 0
                ORDER BY sortorderid ASC";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get ticket severities
     */
    public function getSeverities()
    {
        $sql = "SELECT 
                    ticketseverities_id as id,
                    ticketseverities as value,
                    color,
                    sortorderid
                FROM vtiger_ticketseverities
                WHERE presence = 1
                ORDER BY sortorderid ASC";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get organization priority (custom picklist cf_851)
     */
    public function getOrganizationPriority()
    {
        $sql = "SELECT 
                    cf_851id as id,
                    cf_851 as value,
                    color,
                    sortorderid
                FROM vtiger_cf_851
                WHERE presence = 1
                ORDER BY sortorderid ASC";

        return $this->db->fetchAll($sql);
    }
}


