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
    public function getTickets($filters = [], $userId = null, $isAdmin = false)
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
                LEFT JOIN vtiger_contactdetails c ON tt.contact_id = c.contactid
                WHERE ce.deleted = 0";

        $params = [];

        // If not admin, restrict to user's assigned tickets
        if (!$isAdmin && $userId) {
            $sql .= " AND ce.smownerid = ?";
            $params[] = $userId;
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
    public function getTicketById($ticketId, $userId = null, $isAdmin = false)
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
                    CONCAT(c.firstname, ' ', c.lastname) as contact_name
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                LEFT JOIN vtiger_ticketcf tcf ON tt.ticketid = tcf.ticketid
                LEFT JOIN vtiger_users u ON ce.smownerid = u.id
                LEFT JOIN vtiger_users creator ON ce.smcreatorid = creator.id
                LEFT JOIN vtiger_account a ON tt.parent_id = a.accountid
                LEFT JOIN vtiger_contactdetails c ON tt.contact_id = c.contactid
                WHERE tt.ticketid = ? 
                AND ce.deleted = 0";

        $params = [$ticketId];

        // If not admin, restrict to user's assigned tickets
        if (!$isAdmin && $userId) {
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
    public function getTicketStats($userId = null, $isAdmin = false)
    {
        $whereClauses = ["ce.deleted = 0"];
        $params = [];

        if (!$isAdmin && $userId) {
            $whereClauses[] = "ce.smownerid = ?";
            $params[] = $userId;
        }

        $whereStr = implode(" AND ", $whereClauses);

        // Total open tickets
        $sqlOpen = "SELECT COUNT(*) as count
                    FROM vtiger_troubletickets tt
                    INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                    WHERE $whereStr AND tt.status IN ('Open', 'In Progress', 'Wait For Response')";
        $openResult = $this->db->fetchOne($sqlOpen, $params);

        // Total closed tickets
        $sqlClosed = "SELECT COUNT(*) as count
                      FROM vtiger_troubletickets tt
                      INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                      WHERE $whereStr AND tt.status = 'Closed'";
        $closedResult = $this->db->fetchOne($sqlClosed, $params);

        // Tickets by status
        $sqlByStatus = "SELECT tt.status, COUNT(*) as count
                        FROM vtiger_troubletickets tt
                        INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
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



