<?php
/**
 * Profile Controller
 */

class ProfileController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Get logged-in user profile
     * GET /me
     */
    public function me()
    {
        // Get authenticated user
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $userId = $auth['user_id'];

            // Get user details
            $user = $this->userModel->getUserById($userId);

            if (!$user) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]);
                return;
            }

            // Get assigned tickets count
            $assignedTicketsCount = $this->userModel->getAssignedTicketsCount($userId);

            // Format response
            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $user['id'],
                    'username' => $user['user_name'],
                    'full_name' => trim($user['first_name'] . ' ' . $user['last_name']),
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email1'],
                    'phone' => $user['phone_work'] ?? $user['phone_mobile'] ?? '',
                    'department' => $user['department'] ?? '',
                    'title' => $user['title'] ?? '',
                    'role' => $user['rolename'] ?? 'User',
                    'is_admin' => ($user['is_admin'] === 'on'),
                    'assigned_tickets_count' => $assignedTicketsCount,
                    'status' => $user['status']
                ]
            ]);

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



