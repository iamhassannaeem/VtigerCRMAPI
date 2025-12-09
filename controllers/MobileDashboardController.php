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

            // Cache duration based on role (Management needs longer cache due to complexity)
            $cacheTime = ($roleType === 'Management' || $roleType === 'NOC') ? 600 : 300; // 10 or 5 minutes
            $cacheKey = "dashboard_{$roleType}_{$userId}";
            
            // Check cache first
            $cachedData = CacheHelper::get($cacheKey, $cacheTime);
            if ($cachedData !== null) {
                $elapsed = round((microtime(true) - $startTime) * 1000, 2);
                echo json_encode([
                    'success' => true,
                    'role_type' => $roleType,
                    'data' => $cachedData,
                    'cached' => true,
                    'elapsed_ms' => $elapsed
                ]);
                return;
            }

            // Log start of data fetch
            error_log("Dashboard: Fetching {$roleType} data for user {$userId}");
            $fetchStart = microtime(true);
            
            // Get fresh dashboard data
            $dashboardData = $this->getDashboardByRoleType($roleType, $userId, $isAdmin);
            
            $fetchTime = round((microtime(true) - $fetchStart) * 1000, 2);
            error_log("Dashboard: {$roleType} data fetched in {$fetchTime}ms");
            
            // Cache the result
            CacheHelper::set($cacheKey, $dashboardData);

            $elapsed = round((microtime(true) - $startTime) * 1000, 2);
            echo json_encode([
                'success' => true,
                'role_type' => $roleType,
                'data' => $dashboardData,
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
     * @return array
     */
    private function getDashboardByRoleType($roleType, $userId, $isAdmin)
    {
        switch ($roleType) {
            case 'Field':
                return $this->getFieldDashboard($userId);
            
            case 'NOC':
                return $this->getNocDashboard($userId, $isAdmin);
            
            case 'Management':
                return $this->getManagementDashboard($userId, $isAdmin);
            
            case 'Sales':
                return $this->getSalesDashboard($userId, $isAdmin);
            
            case 'Others':
            default:
                return $this->getOthersDashboard($userId);
        }
    }

    /**
     * Get Field role dashboard
     * @param int $userId
     * @return array
     */
    private function getFieldDashboard($userId)
    {
        return [
            'quick_stats' => $this->dashboardModel->getQuickStats($userId),
            'todays_schedule' => $this->dashboardModel->getTodaysSchedule($userId, 5),
            'recent_comments' => $this->dashboardModel->getRecentCommentsForField($userId, 10)
        ];
    }

    /**
     * Get NOC role dashboard
     * @param int $userId
     * @param bool $isAdmin
     * @return array
     */
    private function getNocDashboard($userId, $isAdmin)
    {
        return [
            'critical_metrics' => $this->dashboardModel->getCriticalMetrics(),
            'priority_breakdown' => $this->dashboardModel->getPriorityBreakdown(),
            'severity_breakdown' => $this->dashboardModel->getSeverityBreakdown(),
            'team_workload' => $this->dashboardModel->getTeamWorkload(),
            'tickets_waiting_24h' => $this->dashboardModel->getTicketsWaitingOver24h(),
            'response_time_metrics' => $this->dashboardModel->getResponseTimeMetrics()
        ];
    }

    /**
     * Get Management role dashboard
     * @param int $userId
     * @param bool $isAdmin
     * @return array
     */
    private function getManagementDashboard($userId, $isAdmin)
    {
        return [
            'kpis' => $this->dashboardModel->getKPIs(),
            'trends' => [
                'tickets_created_vs_resolved' => $this->dashboardModel->getTrendsData(30),
                'priority_trends' => $this->dashboardModel->getPriorityTrends(30)
            ],
            'team_performance' => $this->dashboardModel->getTeamPerformance(),
            'customer_insights' => $this->dashboardModel->getCustomerInsights()
        ];
    }

    /**
     * Get Sales role dashboard
     * @param int $userId
     * @param bool $isAdmin
     * @return array
     */
    private function getSalesDashboard($userId, $isAdmin)
    {
        return [
            'sales_overview' => $this->dashboardModel->getSalesOverview(),
            'customer_health' => $this->dashboardModel->getCustomerHealth(),
            'tickets_by_organization' => $this->dashboardModel->getTicketsByOrganization(),
            'customer_activity' => $this->dashboardModel->getCustomerActivity(10),
            'high_value_customers' => $this->dashboardModel->getHighValueCustomers()
        ];
    }

    /**
     * Get Others/Normal role dashboard
     * @param int $userId
     * @return array
     */
    private function getOthersDashboard($userId)
    {
        return [
            'my_tickets_summary' => $this->dashboardModel->getMyTicketsSummary($userId),
            'status_breakdown' => $this->dashboardModel->getStatusBreakdown($userId),
            'recent_activity' => $this->dashboardModel->getRecentActivity($userId)
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

