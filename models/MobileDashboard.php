<?php
/**
 * Mobile Dashboard Model
 * Provides role-specific dashboard data for mobile application
 */

class MobileDashboard
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ==================== FIELD ROLE METHODS ====================

    /**
     * Get quick stats for Field role
     * @param int $userId
     * @return array
     */
    public function getQuickStats($userId)
    {
        
        // OPTIMIZED: Combine 4 queries into 1 - Fixed to use index
        // Fixed: Escaped `high_priority` as it's a MySQL reserved word
        $sql = "SELECT 
                    COUNT(CASE WHEN tt.status NOT IN ('Closed') THEN 1 END) as my_open_tickets,
                    COUNT(CASE WHEN tt.status = 'In Progress' THEN 1 END) as in_progress,
                    COUNT(CASE WHEN tt.priority IN ('High', 'Urgent') AND tt.status NOT IN ('Closed') THEN 1 END) as `high_priority`,
                    COUNT(CASE WHEN tt.status = 'Closed' AND ce.modifiedtime >= CURDATE() AND ce.modifiedtime < DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 1 END) as closed_today
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0 
                AND ce.smownerid = ?";
        
        $result = $this->db->fetchOne($sql, [$userId]);

        return [
            'my_open_tickets' => (int)($result['my_open_tickets'] ?? 0),
            'in_progress' => (int)($result['in_progress'] ?? 0),
            'high_priority' => (int)($result['high_priority'] ?? 0),
            'closed_today' => (int)($result['closed_today'] ?? 0)
        ];
    }

    /**
     * Get KPIs for Field role
     * @param int $userId
     * @param string|null $startDate Date range start (Y-m-d format)
     * @param string|null $endDate Date range end (Y-m-d format)
     * @return array
     */
    public function getFieldKPIs($userId, $startDate = null, $endDate = null)
    {
        // Default to previous month if not provided
        if (!$startDate || !$endDate) {
            $startDate = date('Y-m-01', strtotime('first day of last month'));
            $endDate = date('Y-m-t', strtotime('last day of last month'));
        }
        
        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate = date('Y-m-d', strtotime($endDate));
        $endDateTime = $endDate . ' 23:59:59';
        
        // Get ticket counts by status for the date range
        $sql = "SELECT 
                    COUNT(CASE WHEN tt.status = 'Open' THEN 1 END) as open_tickets,
                    COUNT(CASE WHEN tt.status = 'In Progress' THEN 1 END) as in_progress_tickets,
                    COUNT(CASE WHEN tt.status IN ('Wait For Response', 'Waiting for Response') THEN 1 END) as wait_for_response_tickets,
                    COUNT(CASE WHEN tt.status = 'Closed' THEN 1 END) as closed_tickets
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0 
                AND ce.smownerid = ?
                AND ce.createdtime >= ?
                AND ce.createdtime <= ?";
        
        $result = $this->db->fetchOne($sql, [$userId, $startDate, $endDateTime]);

        return [
            'open_tickets' => (int)($result['open_tickets'] ?? 0),
            'in_progress_tickets' => (int)($result['in_progress_tickets'] ?? 0),
            'wait_for_response_tickets' => (int)($result['wait_for_response_tickets'] ?? 0),
            'closed_tickets' => (int)($result['closed_tickets'] ?? 0),
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ];
    }

    /**
     * Get today's schedule for Field role
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getTodaysSchedule($userId, $limit = 5)
    {
        $sql = "SELECT 
                    tt.ticketid,
                    tt.ticket_no,
                    tt.title,
                    tt.priority,
                    tt.status,
                    a.accountname as customer_name,
                    ab.bill_street,
                    ab.bill_city,
                    ab.bill_state,
                    CONCAT(ab.bill_street, ', ', ab.bill_city, ', ', ab.bill_state) as location,
                    ce.createdtime,
                    DATE_FORMAT(ce.createdtime, '%H:%i') as time_scheduled
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                LEFT JOIN vtiger_account a ON tt.parent_id = a.accountid
                LEFT JOIN vtiger_accountbillads ab ON a.accountid = ab.accountaddressid
                WHERE ce.deleted = 0 
                AND ce.smownerid = ?
                AND tt.status NOT IN ('Closed')
                AND ce.createdtime >= CURDATE() 
                AND ce.createdtime < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                ORDER BY ce.createdtime DESC
                LIMIT ?";

        return $this->db->fetchAll($sql, [$userId, (int)$limit]);
    }

    /**
     * Get recent comments for Field role
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getRecentCommentsForField($userId, $limit = 10)
    {
        $sql = "SELECT 
                    tc.commentid,
                    tc.ticketid,
                    tc.comments,
                    tc.createdtime,
                    CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                    tt.ticket_no,
                    tt.title as ticket_title
                FROM vtiger_ticketcomments tc
                LEFT JOIN vtiger_users u ON tc.ownerid = u.id
                INNER JOIN vtiger_troubletickets tt ON tc.ticketid = tt.ticketid
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0 
                AND ce.smownerid = ?
                ORDER BY tc.createdtime DESC
                LIMIT ?";

        return $this->db->fetchAll($sql, [$userId, (int)$limit]);
    }

    // ==================== NOC ROLE METHODS ====================

    /**
     * Get critical metrics for NOC role
     * @return array
     */
    /**
     * Get critical metrics for NOC role
     * @param string|null $startDate Date range start (Y-m-d format)
     * @param string|null $endDate Date range end (Y-m-d format)
     * @return array
     */
    public function getCriticalMetrics($startDate = null, $endDate = null)
    {
        // Default to previous month if not provided
        if (!$startDate || !$endDate) {
            $startDate = date('Y-m-01', strtotime('first day of last month'));
            $endDate = date('Y-m-t', strtotime('last day of last month'));
        }
        
        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate = date('Y-m-d', strtotime($endDate));
        $endDateTime = $endDate . ' 23:59:59';
        
        // Get ticket counts by status for the date range
        // Mapping:
        // unassigned_tickets -> in_progress_tickets (status = 'In Progress')
        // critical_issues -> wait_for_response_tickets (status = 'Wait For Response')
        // avg_response_time_hours -> closed_tickets (status = 'Closed')
        $sql = "SELECT 
                    COUNT(CASE WHEN tt.status NOT IN ('Closed') THEN 1 END) as total_open_tickets,
                    COUNT(CASE WHEN tt.status = 'In Progress' THEN 1 END) as in_progress_tickets,
                    COUNT(CASE WHEN tt.status IN ('Wait For Response', 'Waiting for Response') THEN 1 END) as wait_for_response_tickets,
                    COUNT(CASE WHEN tt.status = 'Closed' THEN 1 END) as closed_tickets
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0 
                AND ce.createdtime >= ?
                AND ce.createdtime <= ?";
        
        $result = $this->db->fetchOne($sql, [$startDate, $endDateTime]);

        return [
            'total_open_tickets' => (int)($result['total_open_tickets'] ?? 0),
            'in_progress_tickets' => (int)($result['in_progress_tickets'] ?? 0),  // In Progress tickets count
            'wait_for_response_tickets' => (int)($result['wait_for_response_tickets'] ?? 0),  // Wait For Response tickets count
            'closed_tickets' => (int)($result['closed_tickets'] ?? 0),  // Closed tickets count
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ];
    }

    /**
     * Get priority breakdown for NOC role
     * @return array
     */
    public function getPriorityBreakdown()
    {
        $sql = "SELECT 
                    tt.priority,
                    COUNT(*) as count
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0 
                AND tt.status NOT IN ('Closed')
                GROUP BY tt.priority
                ORDER BY 
                    CASE tt.priority
                        WHEN 'Urgent' THEN 1
                        WHEN 'High' THEN 2
                        WHEN 'Normal' THEN 3
                        WHEN 'Low' THEN 4
                        ELSE 5
                    END";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get severity breakdown for NOC role
     * @return array
     */
    public function getSeverityBreakdown()
    {
        $sql = "SELECT 
                    tt.severity,
                    COUNT(*) as count
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0 
                AND tt.status NOT IN ('Closed')
                GROUP BY tt.severity
                ORDER BY 
                    CASE tt.severity
                        WHEN 'Critical' THEN 1
                        WHEN 'Major' THEN 2
                        WHEN 'Minor' THEN 3
                        WHEN 'Feature' THEN 4
                        ELSE 5
                    END";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get team workload for NOC role
     * @return array
     */
    public function getTeamWorkload()
    {
        // OPTIMIZED: Only count recent tickets and limit to top 20 users
        $sql = "SELECT 
                    u.id as user_id,
                    CONCAT(u.first_name, ' ', u.last_name) as user_name,
                    u.department,
                    COUNT(CASE WHEN tt.status NOT IN ('Closed') THEN 1 END) as open_tickets,
                    COUNT(CASE WHEN tt.status = 'Closed' AND ce.modifiedtime >= CURDATE() AND ce.modifiedtime < DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 1 END) as closed_today,
                    CASE 
                        WHEN COUNT(CASE WHEN tt.status NOT IN ('Closed') THEN 1 END) = 0 THEN 'Light'
                        WHEN COUNT(CASE WHEN tt.status NOT IN ('Closed') THEN 1 END) < 5 THEN 'Normal'
                        WHEN COUNT(CASE WHEN tt.status NOT IN ('Closed') THEN 1 END) < 10 THEN 'Heavy'
                        ELSE 'Overloaded'
                    END as workload_indicator
                FROM vtiger_users u
                LEFT JOIN vtiger_crmentity ce ON u.id = ce.smownerid AND ce.deleted = 0
                    AND ce.createdtime >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
                LEFT JOIN vtiger_troubletickets tt ON ce.crmid = tt.ticketid
                WHERE u.deleted = 0 
                AND u.status = 'Active'
                GROUP BY u.id, u.first_name, u.last_name, u.department
                HAVING open_tickets > 0 OR closed_today > 0
                ORDER BY open_tickets DESC
                LIMIT 20";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get tickets waiting over 24 hours
     * @return array
     */
    public function getTicketsWaitingOver24h()
    {
        // OPTIMIZED: Use date comparison instead of TIMESTAMPDIFF for better performance
        $sql = "SELECT 
                    tt.ticketid,
                    tt.ticket_no,
                    tt.title,
                    tt.priority,
                    tt.status,
                    ce.createdtime,
                    TIMESTAMPDIFF(HOUR, ce.createdtime, NOW()) as hours_waiting,
                    CONCAT(u.first_name, ' ', u.last_name) as assigned_to,
                    a.accountname
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                LEFT JOIN vtiger_users u ON ce.smownerid = u.id
                LEFT JOIN vtiger_account a ON tt.parent_id = a.accountid
                WHERE ce.deleted = 0 
                AND tt.status NOT IN ('Closed')
                AND ce.createdtime <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY ce.createdtime ASC
                LIMIT 20";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get response time metrics
     * @return array
     * 
     * NOTE: This is a simplified version for performance
     * The original version with correlated subquery was too slow
     */
    public function getResponseTimeMetrics()
    {
        // OPTIMIZED: Disabled for performance - correlated subquery with MIN() is too expensive
        // Return mock data structure for now
        return [
            ['response_time_range' => '< 1 hour', 'count' => 0],
            ['response_time_range' => '1-4 hours', 'count' => 0],
            ['response_time_range' => '4-24 hours', 'count' => 0],
            ['response_time_range' => '1-3 days', 'count' => 0],
            ['response_time_range' => '> 3 days', 'count' => 0]
        ];
        
        /*
        // Original query - Too slow without indexes
        // Re-enable after adding index on vtiger_ticketcomments.ticketid
        $sql = "SELECT 
                    CASE 
                        WHEN response_hours < 1 THEN '< 1 hour'
                        WHEN response_hours < 4 THEN '1-4 hours'
                        WHEN response_hours < 24 THEN '4-24 hours'
                        WHEN response_hours < 72 THEN '1-3 days'
                        ELSE '> 3 days'
                    END as response_time_range,
                    COUNT(*) as count
                FROM (
                    SELECT 
                        tt.ticketid,
                        TIMESTAMPDIFF(HOUR, ce.createdtime, 
                            COALESCE(
                                (SELECT MIN(tc.createdtime) 
                                 FROM vtiger_ticketcomments tc 
                                 WHERE tc.ticketid = tt.ticketid),
                                NOW()
                            )
                        ) as response_hours
                    FROM vtiger_troubletickets tt
                    INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                    WHERE ce.deleted = 0
                    AND ce.createdtime >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    LIMIT 1000
                ) as response_data
                GROUP BY response_time_range
                ORDER BY 
                    CASE response_time_range
                        WHEN '< 1 hour' THEN 1
                        WHEN '1-4 hours' THEN 2
                        WHEN '4-24 hours' THEN 3
                        WHEN '1-3 days' THEN 4
                        ELSE 5
                    END";

        return $this->db->fetchAll($sql);
        */
    }

    // ==================== MANAGEMENT ROLE METHODS ====================

    /**
     * Get KPIs for Management role
     * @param string|null $startDate Date range start (Y-m-d format, defaults to previous month start)
     * @param string|null $endDate Date range end (Y-m-d format, defaults to previous month end)
     * @return array
     */
    public function getKPIs($startDate = null, $endDate = null)
    {
        // Default to previous month if not provided
        if (!$startDate || !$endDate) {
            $startDate = date('Y-m-01', strtotime('first day of last month'));
            $endDate = date('Y-m-t', strtotime('last day of last month'));
        }
        
        // Ensure dates are in correct format
        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate = date('Y-m-d', strtotime($endDate));
        
        // Add time to end date to include the full day
        $endDateTime = $endDate . ' 23:59:59';
        
        // Get 4 status counts based on date range
        // Filter: Tickets created in the date range, then count by current status
        // 1. open_tickets -> tickets with status = 'Open'
        // 2. in_progress_tickets -> tickets with status = 'In Progress'
        // 3. wait_for_response_tickets -> tickets with status = 'Wait For Response'
        // 4. closed_tickets -> tickets with status = 'Closed'
        $sql = "SELECT 
                    COUNT(CASE WHEN tt.status = 'Open' THEN 1 END) as open_tickets,
                    COUNT(CASE WHEN tt.status = 'In Progress' THEN 1 END) as in_progress_tickets,
                    COUNT(CASE WHEN tt.status IN ('Wait For Response', 'Waiting for Response') THEN 1 END) as wait_for_response_tickets,
                    COUNT(CASE WHEN tt.status = 'Closed' THEN 1 END) as closed_tickets
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0
                AND ce.createdtime >= ?
                AND ce.createdtime <= ?";
        
        $result = $this->db->fetchOne($sql, [$startDate, $endDateTime]);
        
        $openTickets = (int)($result['open_tickets'] ?? 0);
        $inProgressTickets = (int)($result['in_progress_tickets'] ?? 0);
        $waitForResponseTickets = (int)($result['wait_for_response_tickets'] ?? 0);
        $closedTickets = (int)($result['closed_tickets'] ?? 0);

        // Return with clear field names matching what they represent
        return [
            'open_tickets' => $openTickets,                    // Open tickets count
            'in_progress_tickets' => $inProgressTickets,       // In Progress tickets count
            'wait_for_response_tickets' => $waitForResponseTickets,  // Wait For Response tickets count
            'closed_tickets' => $closedTickets,                // Closed tickets count
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ];
    }

    /**
     * Get trends data for Management role
     * Shows created vs resolved tickets for the specified date range
     * @param string|null $startDate Date range start (Y-m-d format)
     * @param string|null $endDate Date range end (Y-m-d format)
     * @return array
     */
    public function getTrendsData($startDate = null, $endDate = null)
    {
        // Default to previous month if not provided
        if (!$startDate || !$endDate) {
            $startDate = date('Y-m-01', strtotime('first day of last month'));
            $endDate = date('Y-m-t', strtotime('last day of last month'));
        }
        
        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate = date('Y-m-d', strtotime($endDate));
        $endDateTime = $endDate . ' 23:59:59';
        
        // Get created tickets per day (within date range)
        $sqlCreated = "SELECT 
                    DATE(ce.createdtime) as date,
                    COUNT(*) as created
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0
                AND ce.createdtime >= ?
                AND ce.createdtime <= ?
                GROUP BY DATE(ce.createdtime)
                ORDER BY date DESC";

        $createdData = $this->db->fetchAll($sqlCreated, [$startDate, $endDateTime]);
        
        // Get resolved tickets per day (tickets closed within date range)
        $sqlResolved = "SELECT 
                    DATE(ce.modifiedtime) as date,
                    COUNT(*) as resolved
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0
                AND tt.status = 'Closed'
                AND ce.modifiedtime >= ?
                AND ce.modifiedtime <= ?
                GROUP BY DATE(ce.modifiedtime)
                ORDER BY date DESC";

        $resolvedData = $this->db->fetchAll($sqlResolved, [$startDate, $endDateTime]);
        
        // Combine created and resolved data by date
        $combined = [];
        
        // Add created tickets
        foreach ($createdData as $row) {
            $date = $row['date'];
            if (!isset($combined[$date])) {
                $combined[$date] = [
                    'date' => $date,
                    'created' => 0,
                    'resolved' => 0
                ];
            }
            $combined[$date]['created'] = (int)$row['created'];
        }
        
        // Add resolved tickets
        foreach ($resolvedData as $row) {
            $date = $row['date'];
            if (!isset($combined[$date])) {
                $combined[$date] = [
                    'date' => $date,
                    'created' => 0,
                    'resolved' => 0
                ];
            }
            $combined[$date]['resolved'] = (int)$row['resolved'];
        }
        
        // Sort by date descending and return as array
        krsort($combined);
        return array_values($combined);
    }

    /**
     * Get priority trends for Management role
     * @param int $days
     * @return array
     */
    public function getPriorityTrends($days = 30)
    {
        // Get priority trends for last 30 days - Now optimized with proper indexes
        $sql = "SELECT 
                    DATE(ce.createdtime) as date,
                    tt.priority,
                    COUNT(*) as count
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0
                AND ce.createdtime >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(ce.createdtime), tt.priority
                ORDER BY date DESC, priority
                LIMIT 300";
        
        return $this->db->fetchAll($sql, [min($days, 30)]);
    }

    /**
     * Get team performance for Management role
     * @return array
     */
    public function getTeamPerformance()
    {
        // OPTIMIZED: Removed TIMESTAMPDIFF calculation, limited to 7 days
        $sql = "SELECT 
                    u.id as user_id,
                    CONCAT(u.first_name, ' ', u.last_name) as user_name,
                    u.department,
                    COUNT(tt.ticketid) as total_assigned,
                    COUNT(CASE WHEN tt.status = 'Closed' THEN 1 END) as total_resolved,
                    COUNT(CASE WHEN tt.status NOT IN ('Closed') THEN 1 END) as currently_open,
                    ROUND(
                        COUNT(CASE WHEN tt.status = 'Closed' THEN 1 END) * 100.0 / 
                        NULLIF(COUNT(tt.ticketid), 0), 2
                    ) as resolution_percentage
                FROM vtiger_users u
                LEFT JOIN vtiger_crmentity ce ON u.id = ce.smownerid AND ce.deleted = 0
                    AND ce.createdtime >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                LEFT JOIN vtiger_troubletickets tt ON ce.crmid = tt.ticketid
                WHERE u.deleted = 0 
                AND u.status = 'Active'
                GROUP BY u.id, u.first_name, u.last_name, u.department
                HAVING total_assigned > 0
                ORDER BY total_resolved DESC
                LIMIT 20";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get customer insights for Management role
     * @param string|null $startDate Date range start
     * @param string|null $endDate Date range end
     * @return array
     */
    public function getCustomerInsights($startDate = null, $endDate = null)
    {
        // Default to previous month if not provided
        if (!$startDate || !$endDate) {
            $startDate = date('Y-m-01', strtotime('first day of last month'));
            $endDate = date('Y-m-t', strtotime('last day of last month'));
        }
        
        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate = date('Y-m-d', strtotime($endDate));
        $endDateTime = $endDate . ' 23:59:59';
        
        // Only show customers that have OPEN tickets (not closed)
        $sqlTopCustomers = "SELECT 
                                a.accountid,
                                a.accountname,
                                COUNT(tt.ticketid) as ticket_count,
                                COUNT(CASE WHEN tt.status NOT IN ('Closed') THEN 1 END) as open_tickets,
                                COUNT(CASE WHEN tt.priority IN ('High', 'Urgent') AND tt.status NOT IN ('Closed') THEN 1 END) as high_priority_tickets
                            FROM vtiger_account a
                            INNER JOIN vtiger_troubletickets tt ON a.accountid = tt.parent_id
                            INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                            WHERE ce.deleted = 0
                            AND ce.createdtime >= ?
                            AND ce.createdtime <= ?
                            AND tt.status NOT IN ('Closed')
                            GROUP BY a.accountid, a.accountname
                            HAVING open_tickets > 0
                            ORDER BY open_tickets DESC
                            LIMIT 10";
        $topCustomers = $this->db->fetchAll($sqlTopCustomers, [$startDate, $endDateTime]);

        // Get tickets by category - Fixed: Use date range and ensure category field is correct
        $sqlCategory = "SELECT 
                            tt.category,
                            COUNT(*) as count
                        FROM vtiger_troubletickets tt
                        INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                        WHERE ce.deleted = 0
                        AND ce.createdtime >= ?
                        AND ce.createdtime <= ?
                        AND tt.category IS NOT NULL
                        AND tt.category != ''
                        GROUP BY tt.category
                        ORDER BY count DESC
                        LIMIT 10";
        $ticketsByCategory = $this->db->fetchAll($sqlCategory, [$startDate, $endDateTime]);

        // Get tickets by severity - Fixed: Use date range and ensure severity field is correct
        $sqlSeverity = "SELECT 
                            tt.severity,
                            COUNT(*) as count
                        FROM vtiger_troubletickets tt
                        INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                        WHERE ce.deleted = 0
                        AND ce.createdtime >= ?
                        AND ce.createdtime <= ?
                        AND tt.severity IS NOT NULL
                        AND tt.severity != ''
                        GROUP BY tt.severity
                        ORDER BY count DESC";
        $ticketsBySeverity = $this->db->fetchAll($sqlSeverity, [$startDate, $endDateTime]);

        return [
            'top_customers' => $topCustomers,
            'tickets_by_category' => $ticketsByCategory,
            'tickets_by_severity' => $ticketsBySeverity
        ];
    }

    // ==================== SALES ROLE METHODS ====================

    /**
     * Get sales overview
     * @return array
     */
    /**
     * Get Sales overview - Shows ticket counts by status
     * @param string|null $startDate Date range start (Y-m-d format)
     * @param string|null $endDate Date range end (Y-m-d format)
     * @return array
     */
    public function getSalesOverview($startDate = null, $endDate = null)
    {
        // Default to previous month if not provided
        if (!$startDate || !$endDate) {
            $startDate = date('Y-m-01', strtotime('first day of last month'));
            $endDate = date('Y-m-t', strtotime('last day of last month'));
        }
        
        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate = date('Y-m-d', strtotime($endDate));
        $endDateTime = $endDate . ' 23:59:59';
        
        // Get ticket counts by status for the date range
        // Mapping:
        // total_customers -> open tickets count
        // active_customers -> in progress tickets count
        // new_this_month -> wait for response tickets count
        // customers_with_open_tickets -> closed tickets count
        $sql = "SELECT 
                    COUNT(CASE WHEN tt.status = 'Open' THEN 1 END) as open_tickets,
                    COUNT(CASE WHEN tt.status = 'In Progress' THEN 1 END) as in_progress_tickets,
                    COUNT(CASE WHEN tt.status IN ('Wait For Response', 'Waiting for Response') THEN 1 END) as wait_for_response_tickets,
                    COUNT(CASE WHEN tt.status = 'Closed' THEN 1 END) as closed_tickets
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0
                AND ce.createdtime >= ?
                AND ce.createdtime <= ?";
        
        $result = $this->db->fetchOne($sql, [$startDate, $endDateTime]);
        
        $openTickets = (int)($result['open_tickets'] ?? 0);
        $inProgressTickets = (int)($result['in_progress_tickets'] ?? 0);
        $waitForResponseTickets = (int)($result['wait_for_response_tickets'] ?? 0);
        $closedTickets = (int)($result['closed_tickets'] ?? 0);

        return [
            'open_tickets' => $openTickets,                    // Open tickets count
            'in_progress_tickets' => $inProgressTickets,     // In Progress tickets count
            'wait_for_response_tickets' => $waitForResponseTickets,  // Wait For Response tickets count
            'closed_tickets' => $closedTickets,              // Closed tickets count
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ];
    }

    /**
     * Get customer health dashboard
     * @return array
     */
    public function getCustomerHealth()
    {
        // OPTIMIZED: Simplified to only high priority customers, limit 10
        $sqlHighPriority = "SELECT 
                                a.accountid,
                                a.accountname,
                                COUNT(CASE WHEN tt.priority IN ('High', 'Urgent') THEN 1 END) as high_priority_count,
                                COUNT(CASE WHEN tt.status NOT IN ('Closed') THEN 1 END) as open_tickets
                            FROM vtiger_account a
                            INNER JOIN vtiger_troubletickets tt ON a.accountid = tt.parent_id
                            INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                            WHERE ce.deleted = 0
                            AND tt.status NOT IN ('Closed')
                            AND tt.priority IN ('High', 'Urgent')
                            GROUP BY a.accountid, a.accountname
                            ORDER BY high_priority_count DESC
                            LIMIT 10";
        $highPriority = $this->db->fetchAll($sqlHighPriority);

        // Get customers at risk - Now enabled (multiple open tickets, unresolved for >7 days)
        $sqlAtRisk = "SELECT 
                        a.accountname,
                        COUNT(DISTINCT tt.ticketid) as open_tickets,
                        MAX(DATEDIFF(CURDATE(), ce.createdtime)) as oldest_ticket_days,
                        COUNT(CASE WHEN tt.priority IN ('High', 'Urgent') THEN 1 END) as critical_count
                    FROM vtiger_account a
                    INNER JOIN vtiger_troubletickets tt ON a.accountid = tt.parent_id
                    INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                    WHERE ce.deleted = 0
                    AND tt.status NOT IN ('Closed')
                    AND ce.createdtime <= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    GROUP BY a.accountid, a.accountname
                    HAVING open_tickets >= 3 OR oldest_ticket_days >= 14
                    ORDER BY open_tickets DESC, oldest_ticket_days DESC
                    LIMIT 10";
        $customersAtRisk = $this->db->fetchAll($sqlAtRisk);

        return [
            'high_priority_customers' => $highPriority,
            'customers_at_risk' => $customersAtRisk
        ];
    }

    /**
     * Get tickets by organization
     * @return array
     */
    public function getTicketsByOrganization()
    {
        // OPTIMIZED: Limit to 7 days and top 10 only
        $sql = "SELECT 
                    a.accountid,
                    a.accountname,
                    a.account_type,
                    a.industry,
                    COUNT(tt.ticketid) as total_tickets,
                    COUNT(CASE WHEN tt.status NOT IN ('Closed') THEN 1 END) as open_tickets,
                    COUNT(CASE WHEN tt.status = 'Closed' THEN 1 END) as closed_tickets,
                    COUNT(CASE WHEN tt.priority IN ('High', 'Urgent') THEN 1 END) as high_priority_tickets,
                    MAX(ce.createdtime) as last_ticket_date
                FROM vtiger_account a
                INNER JOIN vtiger_troubletickets tt ON a.accountid = tt.parent_id
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid AND ce.deleted = 0
                WHERE ce.createdtime >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                GROUP BY a.accountid, a.accountname, a.account_type, a.industry
                HAVING total_tickets > 0
                ORDER BY total_tickets DESC
                LIMIT 10";

        return $this->db->fetchAll($sql);
    }

    /**
     * Get customer activity feed
     * @param int $limit
     * @return array
     */
    public function getCustomerActivity($limit = 10)
    {
        $sql = "SELECT 
                    tt.ticketid,
                    tt.ticket_no,
                    tt.title,
                    tt.status,
                    tt.priority,
                    a.accountid,
                    a.accountname,
                    ce.createdtime,
                    CONCAT(u.first_name, ' ', u.last_name) as assigned_to,
                    CASE 
                        WHEN tt.priority = 'Urgent' AND tt.status NOT IN ('Closed') THEN 'Critical'
                        WHEN tt.priority IN ('High', 'Urgent') AND tt.status NOT IN ('Closed') THEN 'Warning'
                        WHEN tt.status = 'Closed' THEN 'Good'
                        ELSE 'Normal'
                    END as health_indicator
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                LEFT JOIN vtiger_account a ON tt.parent_id = a.accountid
                LEFT JOIN vtiger_users u ON ce.smownerid = u.id
                WHERE ce.deleted = 0
                AND ce.createdtime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ORDER BY ce.createdtime DESC
                LIMIT ?";

        return $this->db->fetchAll($sql, [(int)$limit]);
    }

    /**
     * Get high value customers
     * @return array
     */
    public function getHighValueCustomers()
    {
        $sql = "SELECT 
                    a.accountid,
                    a.accountname,
                    a.account_type,
                    a.industry,
                    a.rating,
                    COUNT(tt.ticketid) as ticket_activity_score,
                    COUNT(CASE WHEN tt.status NOT IN ('Closed') THEN 1 END) as open_tickets,
                    CASE 
                        WHEN COUNT(CASE WHEN tt.priority = 'Urgent' AND tt.status NOT IN ('Closed') THEN 1 END) > 0 THEN 'Critical'
                        WHEN COUNT(CASE WHEN tt.priority IN ('High', 'Urgent') AND tt.status NOT IN ('Closed') THEN 1 END) > 0 THEN 'At Risk'
                        WHEN COUNT(CASE WHEN tt.status NOT IN ('Closed') THEN 1 END) > 3 THEN 'Needs Attention'
                        ELSE 'Healthy'
                    END as status
                FROM vtiger_account a
                LEFT JOIN vtiger_troubletickets tt ON a.accountid = tt.parent_id
                LEFT JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid AND ce.deleted = 0
                WHERE ce.createdtime >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)
                AND (a.rating = 'Active' OR a.account_type = 'Customer')
                GROUP BY a.accountid, a.accountname, a.account_type, a.industry, a.rating
                HAVING ticket_activity_score > 0
                ORDER BY ticket_activity_score DESC
                LIMIT 15";

        return $this->db->fetchAll($sql);
    }

    // ==================== OTHERS/NORMAL ROLE METHODS ====================

    /**
     * Get my tickets summary for Others role
     * @param int $userId
     * @return array
     */
    /**
     * Get my tickets summary for Others role
     * @param int $userId
     * @param string|null $startDate Date range start (Y-m-d format)
     * @param string|null $endDate Date range end (Y-m-d format)
     * @return array
     */
    public function getMyTicketsSummary($userId, $startDate = null, $endDate = null)
    {
        // Default to previous month if not provided
        if (!$startDate || !$endDate) {
            $startDate = date('Y-m-01', strtotime('first day of last month'));
            $endDate = date('Y-m-t', strtotime('last day of last month'));
        }
        
        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate = date('Y-m-d', strtotime($endDate));
        $endDateTime = $endDate . ' 23:59:59';
        
        $sql = "SELECT 
                    COUNT(CASE WHEN tt.status = 'Open' THEN 1 END) as open_tickets,
                    COUNT(CASE WHEN tt.status = 'In Progress' THEN 1 END) as in_progress_tickets,
                    COUNT(CASE WHEN tt.status IN ('Wait For Response', 'Waiting for Response') THEN 1 END) as wait_for_response_tickets,
                    COUNT(CASE WHEN tt.status = 'Closed' THEN 1 END) as closed_tickets
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0 
                AND ce.smownerid = ?
                AND ce.createdtime >= ?
                AND ce.createdtime <= ?";

        $result = $this->db->fetchOne($sql, [$userId, $startDate, $endDateTime]);

        return [
            'open_tickets' => (int)($result['open_tickets'] ?? 0),
            'in_progress_tickets' => (int)($result['in_progress_tickets'] ?? 0),
            'wait_for_response_tickets' => (int)($result['wait_for_response_tickets'] ?? 0),
            'closed_tickets' => (int)($result['closed_tickets'] ?? 0),
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ];
    }

    /**
     * Get status breakdown for Others role
     * @param int $userId
     * @param string|null $startDate Date range start (Y-m-d format)
     * @param string|null $endDate Date range end (Y-m-d format)
     * @return array
     */
    public function getStatusBreakdown($userId, $startDate = null, $endDate = null)
    {
        // Default to previous month if not provided
        if (!$startDate || !$endDate) {
            $startDate = date('Y-m-01', strtotime('first day of last month'));
            $endDate = date('Y-m-t', strtotime('last day of last month'));
        }
        
        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate = date('Y-m-d', strtotime($endDate));
        $endDateTime = $endDate . ' 23:59:59';
        
        $sql = "SELECT 
                    tt.status,
                    COUNT(*) as count
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0 
                AND ce.smownerid = ?
                AND ce.createdtime >= ?
                AND ce.createdtime <= ?
                GROUP BY tt.status
                ORDER BY count DESC";

        $result = $this->db->fetchAll($sql, [$userId, $startDate, $endDateTime]);
        
        // Ensure "In Progress" status is included even if count is 0
        $statuses = [];
        $hasInProgress = false;
        foreach ($result as $row) {
            $statuses[] = $row;
            if ($row['status'] === 'In Progress') {
                $hasInProgress = true;
            }
        }
        
        // If "In Progress" is not in the results, add it with count 0
        if (!$hasInProgress) {
            $statuses[] = [
                'status' => 'In Progress',
                'count' => 0
            ];
        }
        
        return $statuses;
    }

    /**
     * Get recent activity for Others role
     * @param int $userId
     * @param string|null $startDate Date range start (Y-m-d format)
     * @param string|null $endDate Date range end (Y-m-d format)
     * @return array
     */
    public function getRecentActivity($userId, $startDate = null, $endDate = null)
    {
        // Default to previous month if not provided
        if (!$startDate || !$endDate) {
            $startDate = date('Y-m-01', strtotime('first day of last month'));
            $endDate = date('Y-m-t', strtotime('last day of last month'));
        }
        
        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate = date('Y-m-d', strtotime($endDate));
        $endDateTime = $endDate . ' 23:59:59';
        
        // Recent tickets
        $sqlTickets = "SELECT 
                           tt.ticketid,
                           tt.ticket_no,
                           tt.title,
                           tt.status,
                           tt.priority,
                           ce.createdtime,
                           'ticket' as type
                       FROM vtiger_troubletickets tt
                       INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                       WHERE ce.deleted = 0 
                       AND ce.smownerid = ?
                       AND ce.createdtime >= ?
                       AND ce.createdtime <= ?
                       ORDER BY ce.createdtime DESC
                       LIMIT 5";
        $recentTickets = $this->db->fetchAll($sqlTickets, [$userId, $startDate, $endDateTime]);

        // Recent comments - Show recent comments on tickets assigned to user (last 30 days, not filtered by date range)
        // This shows the most recent activity regardless of the date range filter
        $sqlComments = "SELECT 
                            tc.commentid,
                            tc.ticketid,
                            tc.comments,
                            tc.createdtime,
                            tc.ownerid,
                            tt.ticket_no,
                            CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as author_name,
                            'comment' as type
                        FROM vtiger_ticketcomments tc
                        INNER JOIN vtiger_troubletickets tt ON tc.ticketid = tt.ticketid
                        INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                        LEFT JOIN vtiger_users u ON tc.ownerid = u.id
                        WHERE ce.deleted = 0 
                        AND ce.smownerid = ?
                        AND tc.createdtime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                        ORDER BY tc.createdtime DESC
                        LIMIT 5";
        $recentComments = $this->db->fetchAll($sqlComments, [$userId]);

        return [
            'recent_tickets' => $recentTickets,
            'recent_comments' => $recentComments
        ];
    }

    // ==================== HELPER METHODS ====================

    /**
     * Calculate average response time (time to first comment)
     * @return float Average response time in hours
     * 
     * NOTE: Disabled for performance - correlated subquery too expensive
     */
    private function calculateAverageResponseTime()
    {
        // DISABLED FOR PERFORMANCE - Correlated subquery with MIN() is too slow
        return 0;
        
        /*
        // Original query - Too slow without indexes
        $sql = "SELECT AVG(response_hours) as avg_response
                FROM (
                    SELECT 
                        TIMESTAMPDIFF(HOUR, ce.createdtime, 
                            (SELECT MIN(tc.createdtime) 
                             FROM vtiger_ticketcomments tc 
                             WHERE tc.ticketid = tt.ticketid)
                        ) as response_hours
                    FROM vtiger_troubletickets tt
                    INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                    WHERE ce.deleted = 0
                    AND EXISTS (
                        SELECT 1 FROM vtiger_ticketcomments tc 
                        WHERE tc.ticketid = tt.ticketid
                    )
                    AND ce.createdtime >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    LIMIT 500
                ) as response_data";

        $result = $this->db->fetchOne($sql);
        return round($result['avg_response'] ?? 0, 2);
        */
    }

    /**
     * Calculate average resolution time
     * @return float Average resolution time in hours
     */
    private function calculateAverageResolutionTime($startDate = null, $endDate = null)
    {
        // Default to previous month if not provided
        if (!$startDate || !$endDate) {
            $startDate = date('Y-m-01', strtotime('first day of last month'));
            $endDate = date('Y-m-t', strtotime('last day of last month'));
        }
        
        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate = date('Y-m-d', strtotime($endDate));
        $endDateTime = $endDate . ' 23:59:59';
        
        // Calculate average resolution time for closed tickets in date range
        $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, ce.createdtime, ce.modifiedtime)) as avg_resolution
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0
                AND tt.status = 'Closed'
                AND ce.createdtime >= ?
                AND ce.createdtime <= ?
                LIMIT 5000";

        $result = $this->db->fetchOne($sql, [$startDate, $endDateTime]);
        return round($result['avg_resolution'] ?? 0, 2);
    }

    /**
     * Calculate customer satisfaction based on resolution time
     * @return array Customer satisfaction metrics
     * 
     * NOTE: This method is disabled for performance reasons
     * TIMESTAMPDIFF in subquery is too expensive without indexes
     * Can be re-enabled once proper indexes are added
     */
    private function calculateCustomerSatisfaction()
    {
        // DISABLED FOR PERFORMANCE - Too expensive without indexes
        return [
            'score' => 'N/A',
            'percentage' => 0,
            'distribution' => [
                'high' => 0,
                'medium' => 0,
                'low' => 0
            ]
        ];
        
        /*
        // Original query - re-enable after adding indexes
        $sql = "SELECT 
                    COUNT(CASE WHEN resolution_hours < 24 THEN 1 END) as fast_resolution,
                    COUNT(CASE WHEN resolution_hours BETWEEN 24 AND 72 THEN 1 END) as medium_resolution,
                    COUNT(CASE WHEN resolution_hours > 72 THEN 1 END) as slow_resolution,
                    COUNT(*) as total
                FROM (
                    SELECT TIMESTAMPDIFF(HOUR, ce.createdtime, ce.modifiedtime) as resolution_hours
                    FROM vtiger_troubletickets tt
                    INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                    WHERE ce.deleted = 0
                    AND tt.status = 'Closed'
                    AND ce.modifiedtime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                ) as resolution_data";

        $result = $this->db->fetchOne($sql);
        
        if (!$result || $result['total'] == 0) {
            return [
                'score' => 'N/A',
                'percentage' => 0,
                'distribution' => ['high' => 0, 'medium' => 0, 'low' => 0]
            ];
        }

        $fast = (int)$result['fast_resolution'];
        $medium = (int)$result['medium_resolution'];
        $slow = (int)$result['slow_resolution'];
        $total = (int)$result['total'];

        $score = (($fast * 100) + ($medium * 70) + ($slow * 40)) / $total;

        return [
            'score' => $score >= 80 ? 'High' : ($score >= 60 ? 'Medium' : 'Low'),
            'percentage' => round($score, 2),
            'distribution' => ['high' => $fast, 'medium' => $medium, 'low' => $slow]
        ];
        */
    }
}

