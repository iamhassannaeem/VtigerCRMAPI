<?php
/**
 * Organization (Account) Model
 */

class Organization
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all organizations with optional search
     */
    public function getOrganizations($filters = [])
    {
        $sql = "SELECT 
                    a.accountid,
                    a.account_no,
                    a.accountname,
                    a.phone,
                    a.email1,
                    a.email2,
                    a.website,
                    a.account_type,
                    a.industry,
                    a.rating,
                    a.employees,
                    ab.bill_city,
                    ab.bill_state,
                    ab.bill_country,
                    ab.bill_street,
                    ce.createdtime,
                    ce.modifiedtime,
                    CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name
                FROM vtiger_account a
                INNER JOIN vtiger_crmentity ce ON a.accountid = ce.crmid
                LEFT JOIN vtiger_accountbillads ab ON a.accountid = ab.accountaddressid
                LEFT JOIN vtiger_users u ON ce.smownerid = u.id
                WHERE ce.deleted = 0";

        $params = [];

        // Search filter
        if (!empty($filters['search'])) {
            $sql .= " AND (a.accountname LIKE ? OR a.account_no LIKE ? OR a.email1 LIKE ? OR a.phone LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Filter by account type
        if (!empty($filters['account_type'])) {
            $sql .= " AND a.account_type = ?";
            $params[] = $filters['account_type'];
        }

        // Filter by industry
        if (!empty($filters['industry'])) {
            $sql .= " AND a.industry = ?";
            $params[] = $filters['industry'];
        }

        $sql .= " ORDER BY a.accountname ASC";

        // Pagination
        $limit = $filters['limit'] ?? 50;
        $offset = $filters['offset'] ?? 0;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get organization by ID
     */
    public function getOrganizationById($accountId)
    {
        $sql = "SELECT 
                    a.*,
                    ab.bill_city,
                    ab.bill_code,
                    ab.bill_state,
                    ab.bill_country,
                    ab.bill_street,
                    ab.bill_pobox,
                    ash.ship_city,
                    ash.ship_code,
                    ash.ship_state,
                    ash.ship_country,
                    ash.ship_street,
                    ash.ship_pobox,
                    acf.*,
                    ce.createdtime,
                    ce.modifiedtime,
                    CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name,
                    CONCAT(creator.first_name, ' ', creator.last_name) as created_by_name
                FROM vtiger_account a
                INNER JOIN vtiger_crmentity ce ON a.accountid = ce.crmid
                LEFT JOIN vtiger_accountbillads ab ON a.accountid = ab.accountaddressid
                LEFT JOIN vtiger_accountshipads ash ON a.accountid = ash.accountaddressid
                LEFT JOIN vtiger_accountscf acf ON a.accountid = acf.accountid
                LEFT JOIN vtiger_users u ON ce.smownerid = u.id
                LEFT JOIN vtiger_users creator ON ce.smcreatorid = creator.id
                WHERE a.accountid = ? 
                AND ce.deleted = 0";

        return $this->db->fetchOne($sql, [$accountId]);
    }
}

