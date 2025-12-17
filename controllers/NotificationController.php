<?php

require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../helpers/JWT.php';

class NotificationController
{
    private $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new Notification();
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

        try {
            $decoded = JWT::decode($token);
            return $decoded;
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid token: ' . $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create a new notification
     * POST /notifications
     */
    public function create()
    {
        $auth = $this->authenticate();
        if (!$auth) return;

        // Get request data
        $data = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        $required = ['user_id', 'title', 'message', 'related_type'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => "Field '{$field}' is required"
                ]);
                return;
            }
        }

        // Validate related_type
        $allowedTypes = ['ticket', 'comment', 'other'];
        if (!in_array($data['related_type'], $allowedTypes)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "related_type must be one of: " . implode(', ', $allowedTypes)
            ]);
            return;
        }

        // Validate user_id exists
        require_once __DIR__ . '/../models/User.php';
        $userModel = new User();
        $user = $userModel->getUserById($data['user_id']);
        if (!$user) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
            return;
        }

        try {
            $notification = $this->notificationModel->create([
                'user_id' => (int)$data['user_id'],
                'title' => trim($data['title']),
                'message' => trim($data['message']),
                'related_id' => isset($data['related_id']) ? (int)$data['related_id'] : null,
                'related_type' => $data['related_type']
            ]);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Notification created successfully',
                'data' => $notification
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create notification: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get notifications for authenticated user
     * GET /notifications
     */
    public function index()
    {
        $auth = $this->authenticate();
        if (!$auth) return;

        $userId = $auth['user_id'];

        // Get query parameters
        $filters = [];
        if (isset($_GET['related_type'])) {
            $filters['related_type'] = $_GET['related_type'];
        }
        if (isset($_GET['is_read'])) {
            $filters['is_read'] = $_GET['is_read'];
        }

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        // Validate limit and offset
        if ($limit < 1 || $limit > 100) {
            $limit = 20;
        }
        if ($offset < 0) {
            $offset = 0;
        }

        try {
            $result = $this->notificationModel->getNotifications($userId, $filters, $limit, $offset);

            echo json_encode([
                'success' => true,
                'count' => count($result['notifications']),
                'total' => $result['total'],
                'data' => $result['notifications']
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch notifications: ' . $e->getMessage()
            ]);
        }
    }
}

