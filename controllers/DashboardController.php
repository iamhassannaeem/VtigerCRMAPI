<?php
/**
 * Dashboard Controller
 */

class DashboardController
{
    private $ticketModel;
    private $commentModel;

    public function __construct()
    {
        $this->ticketModel = new Ticket();
        $this->commentModel = new Comment();
    }

    /**
     * Get dashboard data
     * GET /dashboard
     */
    public function index()
    {
        // Get authenticated user
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $userId = $auth['user_id'];
            $isAdmin = $auth['is_admin'];
            $roleType = $auth['role_type'] ?? 'Others';

            // Get ticket statistics - pass roleType for Sales filtering
            $stats = $this->ticketModel->getTicketStats($userId, $isAdmin, $roleType);

            // Get recent comments
            $recentComments = $this->commentModel->getRecentComments($userId, $isAdmin, 5);

            // Format response
            $response = [
                'success' => true,
                'data' => [
                    'total_open_tickets' => $stats['total_open'],
                    'total_closed_tickets' => $stats['total_closed'],
                    'tickets_by_status' => $stats['by_status'],
                    'recent_comments' => $recentComments
                ]
            ];

            // Add my_assigned only for non-admin users
            if (!$isAdmin) {
                $response['data']['my_assigned_tickets'] = $stats['total_open'];
            }

            echo json_encode($response);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Authenticate and extract user from JWT
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



