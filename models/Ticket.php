<?php
/**
 * Ticket Model
 */

class Ticket
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get tickets with filters
     */
    public function getTickets($filters = [], $userId = null, $isAdmin = false, $roleType = 'Others')
    {
        $sql = "SELECT 
                    tt.ticketid,
                    tt.ticket_no,
                    tt.title,
                    tt.status,
                    tt.priority,
                    tt.severity,
                    tt.category,
                    ce.createdtime,
                    ce.modifiedtime,
                    ce.smownerid,
                    CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name,
                    a.accountname as account_name,
                    CONCAT(c.firstname, ' ', c.lastname) as contact_name
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                LEFT JOIN vtiger_users u ON ce.smownerid = u.id
                LEFT JOIN vtiger_account a ON tt.parent_id = a.accountid
                LEFT JOIN vtiger_contactdetails c ON tt.contact_id = c.contactid";

        // Add account entity join for Sales filtering
        if ($roleType === 'Sales' && $userId) {
            $sql .= " LEFT JOIN vtiger_crmentity ce_account ON a.accountid = ce_account.crmid 
                      AND ce_account.setype = 'Accounts' 
                      AND ce_account.deleted = 0";
        }

        $sql .= " WHERE ce.deleted = 0";

        $params = [];

        // Sales users: see tickets assigned to them OR tickets from their customers' accounts
        if ($roleType === 'Sales' && $userId) {
            // Support both start_date/end_date (new) and date_range_days (backward compatibility)
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                // Use date range (start_date and end_date)
                $sql .= " AND ce.createdtime >= ? AND ce.createdtime <= ?
                AND (
                    ce.smownerid = ? 
                    OR (tt.parent_id IS NOT NULL AND ce_account.smownerid = ? AND ce_account.crmid IS NOT NULL)
                )";
                $params[] = $filters['start_date'];
                $params[] = $filters['end_date'];
                $params[] = $userId;
                $params[] = $userId;
            } elseif (!empty($filters['date_range_days'])) {
                // Use days from now (backward compatibility)
                $dateFilterDays = (int)$filters['date_range_days'];
                $sql .= " AND ce.createdtime >= DATE_SUB(NOW(), INTERVAL ? DAY)
                AND (
                    ce.smownerid = ? 
                    OR (tt.parent_id IS NOT NULL AND ce_account.smownerid = ? AND ce_account.crmid IS NOT NULL)
                )";
                $params[] = $dateFilterDays;
                $params[] = $userId;
                $params[] = $userId;
            } else {
                // Default: last 30 days for Sales (performance optimization)
                $sql .= " AND ce.createdtime >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND (
                    ce.smownerid = ? 
                    OR (tt.parent_id IS NOT NULL AND ce_account.smownerid = ? AND ce_account.crmid IS NOT NULL)
                )";
                $params[] = $userId;
                $params[] = $userId;
            }
        }
        // If not admin and not Sales, restrict to user's assigned tickets
        elseif (!$isAdmin && $userId) {
            $sql .= " AND ce.smownerid = ?";
            $params[] = $userId;
        }

        // Apply date filtering for all users (optional - only if frontend passes date parameters)
        // This applies AFTER role-based filtering
        // Note: Sales users already have date filtering applied above, so skip for them
        if ($roleType !== 'Sales') {
            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
                // Use date range (start_date and end_date)
                $sql .= " AND ce.createdtime >= ? AND ce.createdtime <= ?";
                $params[] = $filters['start_date'];
                $params[] = $filters['end_date'];
            } elseif (!empty($filters['date_range_days'])) {
                // Use days from now
                $dateFilterDays = (int)$filters['date_range_days'];
                $sql .= " AND ce.createdtime >= DATE_SUB(NOW(), INTERVAL ? DAY)";
                $params[] = $dateFilterDays;
            }
        }

        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND tt.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['assigned_to']) && $isAdmin) {
            $sql .= " AND ce.smownerid = ?";
            $params[] = $filters['assigned_to'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (tt.title LIKE ? OR tt.ticket_no LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY ce.createdtime DESC";

        // Pagination
        $limit = $filters['limit'] ?? 50;
        $offset = $filters['offset'] ?? 0;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get ticket by ID
     */
    public function getTicketById($ticketId, $userId = null, $isAdmin = false, $roleType = 'Others')
    {
        $sql = "SELECT 
                    tt.*,
                    tcf.*,
                    ce.crmid,
                    ce.smownerid,
                    ce.description,
                    ce.createdtime,
                    ce.modifiedtime,
                    CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name,
                    CONCAT(creator.first_name, ' ', creator.last_name) as created_by_name,
                    a.accountname as account_name,
                    a.phone as account_phone,
                    a.email1 as account_email,
                    a.website as account_website,
                    ab.bill_street as account_bill_street,
                    ab.bill_city as account_bill_city,
                    ab.bill_state as account_bill_state,
                    ab.bill_country as account_bill_country,
                    ab.bill_code as account_bill_code,
                    ash.ship_street as account_ship_street,
                    ash.ship_city as account_ship_city,
                    ash.ship_state as account_ship_state,
                    ash.ship_country as account_ship_country,
                    ash.ship_code as account_ship_code,
                    CONCAT(c.firstname, ' ', c.lastname) as contact_name,
                    c.email as contact_email,
                    c.phone as contact_phone,
                    c.mobile as contact_mobile
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                LEFT JOIN vtiger_ticketcf tcf ON tt.ticketid = tcf.ticketid
                LEFT JOIN vtiger_users u ON ce.smownerid = u.id
                LEFT JOIN vtiger_users creator ON ce.smcreatorid = creator.id
                LEFT JOIN vtiger_account a ON tt.parent_id = a.accountid
                LEFT JOIN vtiger_accountbillads ab ON a.accountid = ab.accountaddressid
                LEFT JOIN vtiger_accountshipads ash ON a.accountid = ash.accountaddressid
                LEFT JOIN vtiger_contactdetails c ON tt.contact_id = c.contactid";

        // Add account entity join for Sales filtering
        if ($roleType === 'Sales' && $userId) {
            $sql .= " LEFT JOIN vtiger_crmentity ce_account ON a.accountid = ce_account.crmid 
                      AND ce_account.setype = 'Accounts' 
                      AND ce_account.deleted = 0";
        }

        $sql .= " WHERE tt.ticketid = ? 
                AND ce.deleted = 0";

        $params = [$ticketId];

        // Sales users: see tickets assigned to them OR tickets from their customers' accounts
        if ($roleType === 'Sales' && $userId) {
            $sql .= " AND (
                ce.smownerid = ? 
                OR (tt.parent_id IS NOT NULL AND ce_account.smownerid = ? AND ce_account.crmid IS NOT NULL)
            )";
            $params[] = $userId;
            $params[] = $userId;
        }
        // If not admin and not Sales, restrict to user's assigned tickets
        elseif (!$isAdmin && $userId) {
            $sql .= " AND ce.smownerid = ?";
            $params[] = $userId;
        }

        return $this->db->fetchOne($sql, $params);
    }

    /**
     * Create new ticket
     */
    public function createTicket($data, $userId)
    {
        try {
            $this->db->beginTransaction();

            // Get next entity ID from sequence table (Vtiger standard)
            $crmid = $this->getNextEntityId();

            // Get next ticket number
            $ticketNo = $this->getNextTicketNumber();

            // Insert into vtiger_crmentity
            $sqlEntity = "INSERT INTO vtiger_crmentity 
                         (crmid, smcreatorid, smownerid, modifiedby, setype, description, 
                          createdtime, modifiedtime, deleted, source, label)
                         VALUES 
                         (?, ?, ?, ?, 'HelpDesk', ?, NOW(), NOW(), 0, 'CRM', ?)";

            $assignedTo = $data['assigned_to'] ?? $userId;
            $this->db->execute($sqlEntity, [
                $crmid,
                $userId,
                $assignedTo,
                $userId,
                $data['description'] ?? '',
                $data['title']
            ]);

            // Insert into vtiger_troubletickets
            $sqlTicket = "INSERT INTO vtiger_troubletickets 
                         (ticketid, ticket_no, title, priority, category, status, severity, 
                          parent_id, contact_id, solution, hours, days)
                         VALUES 
                         (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $this->db->execute($sqlTicket, [
                $crmid,
                $ticketNo,
                $data['title'],
                $data['priority'] ?? 'Normal',
                $data['category'] ?? '',
                $data['status'] ?? 'Open',
                $data['severity'] ?? '',
                $data['account_id'] ?? null,
                $data['contact_id'] ?? null,
                $data['solution'] ?? '',
                $data['hours'] ?? null,
                $data['days'] ?? null
            ]);

            // Insert into vtiger_ticketcf (custom fields table)
            $sqlCf = "INSERT INTO vtiger_ticketcf 
                     (ticketid, from_portal, cf_867, cf_869, cf_871, cf_873, cf_875, cf_877, cf_879, cf_897)
                     VALUES 
                     (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->execute($sqlCf, [
                $crmid,
                $data['from_portal'] ?? '0',
                $data['organization_priority'] ?? '',  // cf_867
                $data['services_issue'] ?? '',  // cf_869
                $data['issue_end'] ?? '',  // cf_871
                $data['end_time'] ?? null,  // cf_873
                $data['send_on_confirmation'] ?? '0',  // cf_875
                $data['email_sms_assigned_to'] ?? '0',  // cf_877
                $data['closing_time'] ?? '',  // cf_879
                $data['email_sms_to_customer'] ?? '0'  // cf_897
            ]);

            $this->db->commit();

            return [
                'success' => true,
                'ticket_id' => $crmid,
                'ticket_no' => $ticketNo
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get next entity ID from Vtiger sequence table
     */
    private function getNextEntityId()
    {
        // Get the maximum ID from vtiger_crmentity to ensure sequence is correct
        $sqlMax = "SELECT MAX(crmid) as max_id FROM vtiger_crmentity";
        $maxResult = $this->db->fetchOne($sqlMax);
        $maxExistingId = $maxResult['max_id'] ?? 0;
        
        // Get current ID from sequence table
        $sql = "SELECT id FROM vtiger_crmentity_seq FOR UPDATE";
        $result = $this->db->fetchOne($sql);
        
        if (!$result) {
            throw new Exception("Unable to get next entity ID from sequence table");
        }
        
        $sequenceId = $result['id'];
        
        // Ensure sequence is ahead of existing data
        $nextId = max($sequenceId, $maxExistingId + 1);
        
        // Update sequence table with next ID
        $sqlUpdate = "UPDATE vtiger_crmentity_seq SET id = ?";
        $this->db->execute($sqlUpdate, [$nextId + 1]);
        
        return $nextId;
    }

    /**
     * Get next ticket number
     */
    private function getNextTicketNumber()
    {
        $sql = "SELECT ticket_no FROM vtiger_troubletickets 
                ORDER BY ticketid DESC LIMIT 1";
        $result = $this->db->fetchOne($sql);

        if ($result) {
            // Extract number from format like "TT0001"
            preg_match('/\d+/', $result['ticket_no'], $matches);
            $nextNum = isset($matches[0]) ? intval($matches[0]) + 1 : 1;
        } else {
            $nextNum = 1;
        }

        return 'TT' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get ticket statistics for dashboard
     */
    public function getTicketStats($userId = null, $isAdmin = false, $roleType = 'Others')
    {
        $whereClauses = ["ce.deleted = 0"];
        $params = [];

        // Sales users: see tickets assigned to them OR tickets from their customers' accounts
        if ($roleType === 'Sales' && $userId) {
            $whereClauses[] = "(
                ce.smownerid = ? 
                OR (tt.parent_id IS NOT NULL AND ce_account.smownerid = ? AND ce_account.crmid IS NOT NULL)
            )";
            $params[] = $userId;
            $params[] = $userId;
        }
        // If not admin and not Sales, restrict to user's assigned tickets
        elseif (!$isAdmin && $userId) {
            $whereClauses[] = "ce.smownerid = ?";
            $params[] = $userId;
        }

        $whereStr = implode(" AND ", $whereClauses);

        // Add account entity join for Sales filtering in stats queries
        $accountJoin = "";
        if ($roleType === 'Sales' && $userId) {
            $accountJoin = " LEFT JOIN vtiger_account a ON tt.parent_id = a.accountid
                            LEFT JOIN vtiger_crmentity ce_account ON a.accountid = ce_account.crmid
                                AND ce_account.setype = 'Accounts'
                                AND ce_account.deleted = 0";
        }

        // Total open tickets
        $sqlOpen = "SELECT COUNT(*) as count
                    FROM vtiger_troubletickets tt
                    INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                    $accountJoin
                    WHERE $whereStr AND tt.status IN ('Open', 'In Progress', 'Wait For Response')";
        $openResult = $this->db->fetchOne($sqlOpen, $params);

        // Total closed tickets
        $sqlClosed = "SELECT COUNT(*) as count
                      FROM vtiger_troubletickets tt
                      INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                      $accountJoin
                      WHERE $whereStr AND tt.status = 'Closed'";
        $closedResult = $this->db->fetchOne($sqlClosed, $params);

        // Tickets by status
        $sqlByStatus = "SELECT tt.status, COUNT(*) as count
                        FROM vtiger_troubletickets tt
                        INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                        $accountJoin
                        WHERE $whereStr
                        GROUP BY tt.status";
        $byStatus = $this->db->fetchAll($sqlByStatus, $params);

        return [
            'total_open' => $openResult['count'] ?? 0,
            'total_closed' => $closedResult['count'] ?? 0,
            'my_assigned' => !$isAdmin ? ($openResult['count'] ?? 0) : null,
            'by_status' => $byStatus
        ];
    }
}



