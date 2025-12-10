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
        $sql = "SELECT 
                    COUNT(CASE WHEN tt.status NOT IN ('Closed') THEN 1 END) as my_open_tickets,
                    COUNT(CASE WHEN tt.status = 'In Progress' THEN 1 END) as in_progress,
                    COUNT(CASE WHEN tt.priority IN ('High', 'Urgent') AND tt.status NOT IN ('Closed') THEN 1 END) as high_priority,
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
    public function getCriticalMetrics()
    {
        // OPTIMIZED: Combine 3 queries into 1
        $sql = "SELECT 
                    COUNT(*) as total_open_tickets,
                    COUNT(CASE WHEN ce.smownerid IS NULL OR ce.smownerid = 0 THEN 1 END) as unassigned_tickets,
                    COUNT(CASE WHEN tt.severity = 'Critical' THEN 1 END) as critical_issues
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0 
                AND tt.status NOT IN ('Closed')";
        
        $result = $this->db->fetchOne($sql);

        // Average Response Time (simplified - skip for now due to performance)
        $avgResponseTime = 0; // $this->calculateAverageResponseTime(); - Disabled for performance

        return [
            'total_open_tickets' => (int)($result['total_open_tickets'] ?? 0),
            'unassigned_tickets' => (int)($result['unassigned_tickets'] ?? 0),
            'critical_issues' => (int)($result['critical_issues'] ?? 0),
            'avg_response_time_hours' => $avgResponseTime
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
                    AND ce.createdtime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
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
     * @return array
     */
    public function getKPIs()
    {
        // OPTIMIZED: Combine multiple queries into one - Fixed for index usage
        $sql = "SELECT 
                    COUNT(*) as total_tickets,
                    COUNT(CASE WHEN tt.status NOT IN ('Closed') THEN 1 END) as open_tickets,
                    COUNT(CASE WHEN tt.status = 'Closed' AND ce.modifiedtime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as closed_last_30,
                    COUNT(CASE WHEN ce.createdtime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as created_last_30
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0
                AND ce.createdtime >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
        
        $result = $this->db->fetchOne($sql);
        
        $totalTickets = (int)($result['total_tickets'] ?? 0);
        $openTickets = (int)($result['open_tickets'] ?? 0);
        $closedLast30 = (int)($result['closed_last_30'] ?? 0);
        $createdLast30 = (int)($result['created_last_30'] ?? 0);

        // Resolution Rate (last 30 days)
        $resolutionRate = $createdLast30 > 0 
            ? round(($closedLast30 / $createdLast30) * 100, 2) 
            : 0;

        // Average Resolution Time (simplified)
        $avgResolutionTime = $this->calculateAverageResolutionTime();

        // Skip expensive customer satisfaction calculation
        $customerSatisfaction = [
            'score' => 'N/A',
            'percentage' => 0,
            'distribution' => [
                'high' => 0,
                'medium' => 0,
                'low' => 0
            ]
        ];

        return [
            'total_tickets' => $totalTickets,
            'open_tickets' => $openTickets,
            'resolution_rate' => $resolutionRate,
            'avg_resolution_time_hours' => $avgResolutionTime,
            'customer_satisfaction' => $customerSatisfaction
        ];
    }

    /**
     * Get trends data for Management role
     * @param int $days
     * @return array
     */
    public function getTrendsData($days = 30)
    {
        // OPTIMIZED: Limit to 7 days for faster response - Fixed for index usage
        $sql = "SELECT 
                    DATE(ce.createdtime) as date,
                    COUNT(CASE WHEN tt.status = 'Closed' THEN 1 END) as resolved,
                    COUNT(*) as created
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0
                AND ce.createdtime >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(ce.createdtime)
                ORDER BY date DESC
                LIMIT 30";

        return $this->db->fetchAll($sql, [min($days, 7)]);
    }

    /**
     * Get priority trends for Management role
     * @param int $days
     * @return array
     */
    public function getPriorityTrends($days = 30)
    {
        // OPTIMIZED: Skip this query for now - can be added later with caching
        // This is expensive with GROUP BY on both date and priority
        return [];
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
                    AND ce.createdtime >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
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
     * @return array
     */
    public function getCustomerInsights()
    {
        // OPTIMIZED: Only top customers, limited to 7 days for speed
        $sqlTopCustomers = "SELECT 
                                a.accountid,
                                a.accountname,
                                COUNT(tt.ticketid) as ticket_count,
                                COUNT(CASE WHEN tt.status NOT IN ('Closed') THEN 1 END) as open_tickets,
                                COUNT(CASE WHEN tt.priority IN ('High', 'Urgent') THEN 1 END) as high_priority_tickets
                            FROM vtiger_account a
                            LEFT JOIN vtiger_troubletickets tt ON a.accountid = tt.parent_id
                            LEFT JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid AND ce.deleted = 0
                            WHERE ce.createdtime >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                            GROUP BY a.accountid, a.accountname
                            HAVING ticket_count > 0
                            ORDER BY ticket_count DESC
                            LIMIT 10";
        $topCustomers = $this->db->fetchAll($sqlTopCustomers);

        // Skip category and severity for performance - can be added later with caching
        return [
            'top_customers' => $topCustomers,
            'tickets_by_category' => [],
            'tickets_by_severity' => []
        ];
    }

    // ==================== SALES ROLE METHODS ====================

    /**
     * Get sales overview
     * @return array
     */
    public function getSalesOverview()
    {
        // OPTIMIZED: Combine 4 queries into 1
        $sql = "SELECT 
                    COUNT(DISTINCT a.accountid) as total_customers,
                    COUNT(DISTINCT CASE WHEN MONTH(ace.createdtime) = MONTH(CURDATE()) AND YEAR(ace.createdtime) = YEAR(CURDATE()) THEN a.accountid END) as new_this_month,
                    COUNT(DISTINCT CASE WHEN tce.crmid IS NOT NULL THEN a.accountid END) as active_customers,
                    COUNT(DISTINCT CASE WHEN tt.status NOT IN ('Closed') THEN a.accountid END) as customers_with_open_tickets
                FROM vtiger_account a
                INNER JOIN vtiger_crmentity ace ON a.accountid = ace.crmid AND ace.deleted = 0
                LEFT JOIN vtiger_troubletickets tt ON a.accountid = tt.parent_id
                LEFT JOIN vtiger_crmentity tce ON tt.ticketid = tce.crmid AND tce.deleted = 0 
                    AND DATE(tce.createdtime) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        
        $result = $this->db->fetchOne($sql);

        return [
            'total_customers' => (int)($result['total_customers'] ?? 0),
            'new_this_month' => (int)($result['new_this_month'] ?? 0),
            'active_customers' => (int)($result['active_customers'] ?? 0),
            'customers_with_open_tickets' => (int)($result['customers_with_open_tickets'] ?? 0)
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

        // Skip at-risk customers for now - too expensive
        return [
            'high_priority_customers' => $highPriority,
            'customers_at_risk' => []
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
                WHERE ce.createdtime >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
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
                AND ce.createdtime >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
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
                WHERE ce.createdtime >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
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
    public function getMyTicketsSummary($userId)
    {
        $sql = "SELECT 
                    COUNT(CASE WHEN tt.status NOT IN ('Closed') THEN 1 END) as open_tickets,
                    COUNT(CASE WHEN tt.status = 'Closed' THEN 1 END) as closed_tickets,
                    COUNT(*) as total_tickets
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0 
                AND ce.smownerid = ?";

        return $this->db->fetchOne($sql, [$userId]) ?? [
            'open_tickets' => 0,
            'closed_tickets' => 0,
            'total_tickets' => 0
        ];
    }

    /**
     * Get status breakdown for Others role
     * @param int $userId
     * @return array
     */
    public function getStatusBreakdown($userId)
    {
        $sql = "SELECT 
                    tt.status,
                    COUNT(*) as count
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0 
                AND ce.smownerid = ?
                GROUP BY tt.status
                ORDER BY count DESC";

        return $this->db->fetchAll($sql, [$userId]);
    }

    /**
     * Get recent activity for Others role
     * @param int $userId
     * @return array
     */
    public function getRecentActivity($userId)
    {
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
                       ORDER BY ce.createdtime DESC
                       LIMIT 5";
        $recentTickets = $this->db->fetchAll($sqlTickets, [$userId]);

        // Recent comments
        $sqlComments = "SELECT 
                            tc.commentid,
                            tc.ticketid,
                            tc.comments,
                            tc.createdtime,
                            tt.ticket_no,
                            'comment' as type
                        FROM vtiger_ticketcomments tc
                        INNER JOIN vtiger_troubletickets tt ON tc.ticketid = tt.ticketid
                        INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                        WHERE ce.deleted = 0 
                        AND ce.smownerid = ?
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
    private function calculateAverageResolutionTime()
    {
        // OPTIMIZED: Only last 7 days instead of 30 to reduce calculation load - Fixed for index usage
        $sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, ce.createdtime, ce.modifiedtime)) as avg_resolution
                FROM vtiger_troubletickets tt
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0
                AND tt.status = 'Closed'
                AND ce.modifiedtime >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                LIMIT 1000";

        $result = $this->db->fetchOne($sql);
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

