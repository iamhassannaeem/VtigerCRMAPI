<?php
/**
 * Mobile Dashboard Controller
 * Provides role-specific dashboard data for mobile application
 */

class MobileDashboardController
{
    private $dashboardModel;

    public function __construct()
    {
        $this->dashboardModel = new MobileDashboard();
    }

    /**
     * Get dashboard data based on user's role type
     * GET /mobile/dashboard
     */
    public function index()
    {
        $startTime = microtime(true);
        
        // Get authenticated user
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $userId = $auth['user_id'];
            $isAdmin = $auth['is_admin'] ?? false;
            $roleType = $auth['role_type'] ?? 'Others';

            // Get date range parameters (for Management dashboard filtering)
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            // Validate and set default to previous month if not provided
            if ($startDate && $endDate) {
                $startDate = date('Y-m-d', strtotime($startDate));
                $endDate = date('Y-m-d', strtotime($endDate));
            } else {
                // Default to previous month
                $startDate = date('Y-m-01', strtotime('first day of last month'));
                $endDate = date('Y-m-t', strtotime('last day of last month'));
            }

            // Cache duration based on role (Management needs longer cache due to complexity)
            $cacheTime = ($roleType === 'Management' || $roleType === 'NOC') ? 600 : 300; // 10 or 5 minutes
            $cacheKey = "dashboard_{$roleType}_{$userId}_" . md5($startDate . $endDate);
            
            // Check cache first
            $cachedData = CacheHelper::get($cacheKey, $cacheTime);
            if ($cachedData !== null) {
                $elapsed = round((microtime(true) - $startTime) * 1000, 2);
                echo json_encode([
                    'success' => true,
                    'role_type' => $roleType,
                    'data' => $cachedData,
                    'date_range' => ['start_date' => $startDate, 'end_date' => $endDate],
                    'cached' => true,
                    'elapsed_ms' => $elapsed
                ]);
                return;
            }

            // Log start of data fetch
            error_log("Dashboard: Fetching {$roleType} data for user {$userId} (date range: {$startDate} to {$endDate})");
            $fetchStart = microtime(true);
            
            // Get fresh dashboard data
            $dashboardData = $this->getDashboardByRoleType($roleType, $userId, $isAdmin, $startDate, $endDate);
            
            $fetchTime = round((microtime(true) - $fetchStart) * 1000, 2);
            error_log("Dashboard: {$roleType} data fetched in {$fetchTime}ms");
            
            // Cache the result
            CacheHelper::set($cacheKey, $dashboardData);

            $elapsed = round((microtime(true) - $startTime) * 1000, 2);
            echo json_encode([
                'success' => true,
                'role_type' => $roleType,
                'data' => $dashboardData,
                'date_range' => ['start_date' => $startDate, 'end_date' => $endDate],
                'cached' => false,
                'elapsed_ms' => $elapsed,
                'fetch_time_ms' => $fetchTime
            ]);

        } catch (Exception $e) {
            $elapsed = round((microtime(true) - $startTime) * 1000, 2);
            error_log("Dashboard ERROR after {$elapsed}ms: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'elapsed_ms' => $elapsed,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * TEST ENDPOINT - Returns immediately to test timeouts
     * GET /mobile/dashboard/test
     */
    public function test()
    {
        $auth = $this->authenticate();
        if (!$auth) return;
        
        echo json_encode([
            'success' => true,
            'message' => 'Test endpoint works!',
            'role_type' => $auth['role_type'] ?? 'Unknown',
            'user_id' => $auth['user_id'],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Route to appropriate dashboard based on role type
     * @param string $roleType
     * @param int $userId
     * @param bool $isAdmin
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    private function getDashboardByRoleType($roleType, $userId, $isAdmin, $startDate = null, $endDate = null)
    {
        // ALL FEATURES ENABLED - Excellent performance achieved!
        // All queries use indexes efficiently after optimization
        $enabledQueries = [
            'kpis' => true,                      // ✅ 806ms - excellent!
            'trends' => true,                    // ✅ Fast with indexes
            'team_performance' => true,          // ✅ Optimized
            'customer_insights' => true,         // ✅ Enable all features
            'critical_metrics' => true,          // ✅ NOC dashboard
            'priority_breakdown' => true,        // ✅ Simple GROUP BY
            'severity_breakdown' => true,        // ✅ Simple GROUP BY
            'team_workload' => true,             // ✅ Now fast with indexes
            'tickets_waiting_24h' => true,       // ✅ Optimized
            'response_time_metrics' => false,    // ❌ Returns mock data (disabled for performance)
            'quick_stats' => true,               // ✅ Field - optimized
            'todays_schedule' => true,           // ✅ Limited to today
            'recent_comments' => true,           // ✅ Has limit
            'sales_overview' => true,            // ✅ Sales - optimized
            'customer_health' => true,           // ✅ Enable for Sales
            'tickets_by_organization' => true,   // ✅ Enable for Sales
            'customer_activity' => true,         // ✅ Fast queries
            'high_value_customers' => true       // ✅ Enable all
        ];
        
        switch ($roleType) {
            case 'Field':
                return $this->getFieldDashboard($userId, $enabledQueries, $startDate, $endDate);
            
            case 'NOC':
                return $this->getNocDashboard($userId, $isAdmin, $enabledQueries, $startDate, $endDate);
            
            case 'Management':
                return $this->getManagementDashboard($userId, $isAdmin, $enabledQueries, $startDate, $endDate);
            
            case 'Sales':
                return $this->getSalesDashboard($userId, $isAdmin, $enabledQueries, $startDate, $endDate);
            
            case 'Others':
            default:
                return $this->getOthersDashboard($userId, $startDate, $endDate);
        }
    }
    
    /**
     * Fast mode dashboard - returns minimal mock data
     * @param string $roleType
     * @param int $userId
     * @return array
     */
    private function getFastModeDashboard($roleType, $userId)
    {
        switch ($roleType) {
            case 'Field':
                return [
                    'kpis' => [
                        'open_tickets' => 5,
                        'in_progress_tickets' => 2,
                        'wait_for_response_tickets' => 1,
                        'closed_tickets' => 10,
                        'date_range' => [
                            'start_date' => date('Y-m-01', strtotime('first day of last month')),
                            'end_date' => date('Y-m-t', strtotime('last day of last month'))
                        ]
                    ],
                    'quick_stats' => [
                        'my_open_tickets' => 5,
                        'in_progress' => 2,
                        'high_priority' => 1,
                        'closed_today' => 3
                    ],
                    'todays_schedule' => [],
                    'recent_comments' => []
                ];
            
            case 'NOC':
                return [
                    'critical_metrics' => [
                        'total_open_tickets' => 25,
                        'in_progress_tickets' => 5,
                        'wait_for_response_tickets' => 2,
                        'closed_tickets' => 50,
                        'date_range' => [
                            'start_date' => date('Y-m-01', strtotime('first day of last month')),
                            'end_date' => date('Y-m-t', strtotime('last day of last month'))
                        ]
                    ],
                    'priority_breakdown' => [],
                    'severity_breakdown' => [],
                    'team_workload' => [],
                    'tickets_waiting_24h' => [],
                    'response_time_metrics' => []
                ];
            
            case 'Management':
                return [
                    'kpis' => [
                        'open_tickets' => 45,
                        'in_progress_tickets' => 30,
                        'wait_for_response_tickets' => 15,
                        'closed_tickets' => 150,
                        'date_range' => [
                            'start_date' => date('Y-m-01', strtotime('first day of last month')),
                            'end_date' => date('Y-m-t', strtotime('last day of last month'))
                        ]
                    ],
                    'trends' => [
                        'tickets_created_vs_resolved' => [],
                        'priority_trends' => []
                    ],
                    'team_performance' => [],
                    'customer_insights' => [
                        'top_customers' => [],
                        'tickets_by_category' => [],
                        'tickets_by_severity' => []
                    ]
                ];
            
            case 'Sales':
                return [
                    'sales_overview' => [
                        'open_tickets' => 15,
                        'in_progress_tickets' => 5,
                        'wait_for_response_tickets' => 8,
                        'closed_tickets' => 100,
                        'date_range' => [
                            'start_date' => date('Y-m-01', strtotime('first day of last month')),
                            'end_date' => date('Y-m-t', strtotime('last day of last month'))
                        ]
                    ],
                    'customer_health' => [
                        'high_priority_customers' => [],
                        'customers_at_risk' => []
                    ],
                    'tickets_by_organization' => [],
                    'customer_activity' => [],
                    'high_value_customers' => []
                ];
            
            case 'Others':
            default:
                return [
                    'my_tickets_summary' => [
                        'open_tickets' => 5,
                        'in_progress_tickets' => 3,
                        'wait_for_response_tickets' => 2,
                        'closed_tickets' => 10,
                        'date_range' => [
                            'start_date' => date('Y-m-01', strtotime('first day of last month')),
                            'end_date' => date('Y-m-t', strtotime('last day of last month'))
                        ]
                    ],
                    'status_breakdown' => [
                        ['status' => 'Open', 'count' => 5],
                        ['status' => 'In Progress', 'count' => 3],
                        ['status' => 'Wait For Response', 'count' => 2],
                        ['status' => 'Closed', 'count' => 10]
                    ],
                    'recent_activity' => [
                        'recent_tickets' => [],
                        'recent_comments' => []
                    ]
                ];
        }
    }

    /**
     * Get Field role dashboard
     * @param int $userId
     * @param array $enabled
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    private function getFieldDashboard($userId, $enabled = [], $startDate = null, $endDate = null)
    {
        $data = [];
        
        // KPIs - Ticket counts by status (always included)
        try {
            $data['kpis'] = $this->dashboardModel->getFieldKPIs($userId, $startDate, $endDate);
            error_log("Field KPIs: Successfully fetched for user {$userId}");
        } catch (Exception $e) {
            error_log("Field KPIs error: " . $e->getMessage());
            error_log("Field KPIs stack trace: " . $e->getTraceAsString());
            $data['kpis'] = [
                'open_tickets' => 0,
                'in_progress_tickets' => 0,
                'wait_for_response_tickets' => 0,
                'closed_tickets' => 0,
                'date_range' => [
                    'start_date' => $startDate ?? date('Y-m-01', strtotime('first day of last month')),
                    'end_date' => $endDate ?? date('Y-m-t', strtotime('last day of last month'))
                ]
            ];
        }
        
        if ($enabled['quick_stats'] ?? false) {
            try {
                $data['quick_stats'] = $this->dashboardModel->getQuickStats($userId);
            } catch (Exception $e) {
                error_log("Field quick_stats error: " . $e->getMessage());
                $data['quick_stats'] = ['my_open_tickets' => 0, 'in_progress' => 0, 'high_priority' => 0, 'closed_today' => 0];
            }
        } else {
            $data['quick_stats'] = ['my_open_tickets' => 0, 'in_progress' => 0, 'high_priority' => 0, 'closed_today' => 0];
        }
        
        if ($enabled['todays_schedule'] ?? false) {
            try {
                $data['todays_schedule'] = $this->dashboardModel->getTodaysSchedule($userId, 5);
            } catch (Exception $e) {
                error_log("Field todays_schedule error: " . $e->getMessage());
                $data['todays_schedule'] = [];
            }
        } else {
            $data['todays_schedule'] = [];
        }
        
        if ($enabled['recent_comments'] ?? false) {
            try {
                $data['recent_comments'] = $this->dashboardModel->getRecentCommentsForField($userId, 10);
            } catch (Exception $e) {
                error_log("Field recent_comments error: " . $e->getMessage());
                $data['recent_comments'] = [];
            }
        } else {
            $data['recent_comments'] = [];
        }
        
        return $data;
    }

    /**
     * Get NOC role dashboard
     * @param int $userId
     * @param bool $isAdmin
     * @param array $enabled
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    private function getNocDashboard($userId, $isAdmin, $enabled = [], $startDate = null, $endDate = null)
    {
        $data = [];
        
        $data['critical_metrics'] = ($enabled['critical_metrics'] ?? false) 
            ? $this->dashboardModel->getCriticalMetrics($startDate, $endDate)
            : [
                'total_open_tickets' => 0, 
                'in_progress_tickets' => 0, 
                'wait_for_response_tickets' => 0, 
                'closed_tickets' => 0,
                'date_range' => [
                    'start_date' => $startDate ?? date('Y-m-01', strtotime('first day of last month')),
                    'end_date' => $endDate ?? date('Y-m-t', strtotime('last day of last month'))
                ]
            ];
        
        $data['priority_breakdown'] = ($enabled['priority_breakdown'] ?? false) 
            ? $this->dashboardModel->getPriorityBreakdown() : [];
        
        $data['severity_breakdown'] = ($enabled['severity_breakdown'] ?? false) 
            ? $this->dashboardModel->getSeverityBreakdown() : [];
        
        $data['team_workload'] = ($enabled['team_workload'] ?? false) 
            ? $this->dashboardModel->getTeamWorkload() : [];
        
        $data['tickets_waiting_24h'] = ($enabled['tickets_waiting_24h'] ?? false) 
            ? $this->dashboardModel->getTicketsWaitingOver24h() : [];
        
        $data['response_time_metrics'] = ($enabled['response_time_metrics'] ?? false) 
            ? $this->dashboardModel->getResponseTimeMetrics() : [];
        
        return $data;
    }

    /**
     * Get Management role dashboard
     * @param int $userId
     * @param bool $isAdmin
     * @param array $enabled
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    private function getManagementDashboard($userId, $isAdmin, $enabled = [], $startDate = null, $endDate = null)
    {
        $data = [];
        
        // KPIs - with date range filtering
        if ($enabled['kpis'] ?? false) {
            try {
                $start = microtime(true);
                $data['kpis'] = $this->dashboardModel->getKPIs($startDate, $endDate);
                $elapsed = round((microtime(true) - $start) * 1000, 2);
                error_log("Management getKPIs took {$elapsed}ms");
            } catch (Exception $e) {
                error_log("Management KPIs error: " . $e->getMessage());
                $data['kpis'] = [
                    'open_tickets' => 0,
                    'in_progress_tickets' => 0,
                    'wait_for_response_tickets' => 0,
                    'closed_tickets' => 0,
                    'date_range' => [
                        'start_date' => $startDate ?? date('Y-m-01', strtotime('first day of last month')),
                        'end_date' => $endDate ?? date('Y-m-t', strtotime('last day of last month'))
                    ]
                ];
            }
        } else {
            $data['kpis'] = [
                'open_tickets' => 0,
                'in_progress_tickets' => 0,
                'wait_for_response_tickets' => 0,
                'closed_tickets' => 0,
                'date_range' => [
                    'start_date' => $startDate ?? date('Y-m-01', strtotime('first day of last month')),
                    'end_date' => $endDate ?? date('Y-m-t', strtotime('last day of last month'))
                ]
            ];
        }
        
        // Trends - Filtered by date range
        $data['trends'] = [
            'tickets_created_vs_resolved' => ($enabled['trends'] ?? false) ? $this->dashboardModel->getTrendsData($startDate, $endDate) : [],
            'priority_trends' => [] // Always disabled - too slow
        ];
        
        $data['team_performance'] = ($enabled['team_performance'] ?? false) 
            ? $this->dashboardModel->getTeamPerformance() : [];
        
        // Customer insights - Only customers with open tickets
        $data['customer_insights'] = ($enabled['customer_insights'] ?? false) 
            ? $this->dashboardModel->getCustomerInsights($startDate, $endDate) 
            : ['top_customers' => [], 'tickets_by_category' => [], 'tickets_by_severity' => []];
        
        return $data;
    }

    /**
     * Get Sales role dashboard
     * @param int $userId
     * @param bool $isAdmin
     * @param array $enabled
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    private function getSalesDashboard($userId, $isAdmin, $enabled = [], $startDate = null, $endDate = null)
    {
        $data = [];
        
        $data['sales_overview'] = ($enabled['sales_overview'] ?? false) 
            ? $this->dashboardModel->getSalesOverview($startDate, $endDate)
            : [
                'open_tickets' => 0, 
                'in_progress_tickets' => 0, 
                'wait_for_response_tickets' => 0, 
                'closed_tickets' => 0,
                'date_range' => [
                    'start_date' => $startDate ?? date('Y-m-01', strtotime('first day of last month')),
                    'end_date' => $endDate ?? date('Y-m-t', strtotime('last day of last month'))
                ]
            ];
        
        $data['customer_health'] = ($enabled['customer_health'] ?? false) 
            ? $this->dashboardModel->getCustomerHealth()
            : ['high_priority_customers' => [], 'customers_at_risk' => []];
        
        $data['tickets_by_organization'] = ($enabled['tickets_by_organization'] ?? false) 
            ? $this->dashboardModel->getTicketsByOrganization() : [];
        
        $data['customer_activity'] = ($enabled['customer_activity'] ?? false) 
            ? $this->dashboardModel->getCustomerActivity(10) : [];
        
        $data['high_value_customers'] = ($enabled['high_value_customers'] ?? false) 
            ? $this->dashboardModel->getHighValueCustomers() : [];
        
        return $data;
    }

    /**
     * Get Others/Normal role dashboard
     * @param int $userId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     */
    private function getOthersDashboard($userId, $startDate = null, $endDate = null)
    {
        return [
            'my_tickets_summary' => $this->dashboardModel->getMyTicketsSummary($userId, $startDate, $endDate),
            'status_breakdown' => $this->dashboardModel->getStatusBreakdown($userId, $startDate, $endDate),
            'recent_activity' => $this->dashboardModel->getRecentActivity($userId, $startDate, $endDate)
        ];
    }

    /**
     * Authenticate and extract user from JWT
     * @return array|false
     */
    private function authenticate()
    {
        $token = JWT::extractToken();

        if (!$token) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'No token provided'
            ]);
            return false;
        }

        $payload = JWT::validateToken($token);

        if (!$payload) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid or expired token'
            ]);
            return false;
        }

        return $payload;
    }
}

