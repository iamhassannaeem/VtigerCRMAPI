<?php
/**
 * Contact Model
 */

class Contact
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all contacts with optional filters
     */
    public function getContacts($filters = [])
    {
        $sql = "SELECT 
                    c.contactid,
                    c.contact_no,
                    c.firstname,
                    c.lastname,
                    CONCAT(c.firstname, ' ', c.lastname) as full_name,
                    c.email,
                    c.phone,
                    c.mobile,
                    c.title,
                    c.department,
                    c.accountid,
                    a.accountname,
                    ca.mailingcity,
                    ca.mailingstate,
                    ca.mailingcountry,
                    ce.createdtime,
                    ce.modifiedtime,
                    CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name
                FROM vtiger_contactdetails c
                INNER JOIN vtiger_crmentity ce ON c.contactid = ce.crmid
                LEFT JOIN vtiger_account a ON c.accountid = a.accountid
                LEFT JOIN vtiger_contactaddress ca ON c.contactid = ca.contactaddressid
                LEFT JOIN vtiger_users u ON ce.smownerid = u.id
                WHERE ce.deleted = 0";

        $params = [];

        // Filter by organization/account ID
        if (!empty($filters['account_id'])) {
            $sql .= " AND c.accountid = ?";
            $params[] = $filters['account_id'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $sql .= " AND (c.firstname LIKE ? OR c.lastname LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.mobile LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY c.lastname ASC, c.firstname ASC";

        // Pagination
        $limit = $filters['limit'] ?? 50;
        $offset = $filters['offset'] ?? 0;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get contact by ID
     */
    public function getContactById($contactId)
    {
        $sql = "SELECT 
                    c.*,
                    ca.*,
                    cs.*,
                    ccf.*,
                    a.accountname,
                    ce.createdtime,
                    ce.modifiedtime,
                    CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name,
                    CONCAT(creator.first_name, ' ', creator.last_name) as created_by_name
                FROM vtiger_contactdetails c
                INNER JOIN vtiger_crmentity ce ON c.contactid = ce.crmid
                LEFT JOIN vtiger_account a ON c.accountid = a.accountid
                LEFT JOIN vtiger_contactaddress ca ON c.contactid = ca.contactaddressid
                LEFT JOIN vtiger_contactsubdetails cs ON c.contactid = cs.contactsubscriptionid
                LEFT JOIN vtiger_contactscf ccf ON c.contactid = ccf.contactid
                LEFT JOIN vtiger_users u ON ce.smownerid = u.id
                LEFT JOIN vtiger_users creator ON ce.smcreatorid = creator.id
                WHERE c.contactid = ? 
                AND ce.deleted = 0";

        return $this->db->fetchOne($sql, [$contactId]);
    }
}






