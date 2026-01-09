<?php
/**
 * Notifications Controller
 */

class NotificationsController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get notifications list
     * GET /notifications
     */
    public function index()
    {
        // Get authenticated user
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $userId = $auth['user_id'];

            // Get query parameters
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $relatedType = $_GET['related_type'] ?? null;
            $isRead = isset($_GET['is_read']) ? filter_var($_GET['is_read'], FILTER_VALIDATE_BOOLEAN) : null;

            // Check if notifications table exists
            try {
                // Try to query notifications table
                $whereClauses = ["n.user_id = ?"];
                $params = [$userId];

                if ($relatedType) {
                    $whereClauses[] = "n.related_type = ?";
                    $params[] = $relatedType;
                }

                if ($isRead !== null) {
                    $whereClauses[] = "n.is_read = ?";
                    $params[] = $isRead ? 1 : 0;
                }

                $whereStr = implode(" AND ", $whereClauses);

                // Get total count
                $countSql = "SELECT COUNT(*) as count FROM vtiger_notifications n WHERE $whereStr";
                $countResult = $this->db->fetchOne($countSql, $params);
                $totalCount = $countResult['count'] ?? 0;

                // Get notifications
                $sql = "SELECT 
                            n.id,
                            n.user_id,
                            n.title,
                            n.message,
                            n.related_id,
                            n.related_type,
                            n.is_read,
                            n.created_at,
                            n.updated_at
                        FROM vtiger_notifications n
                        WHERE $whereStr
                        ORDER BY n.created_at DESC
                        LIMIT ? OFFSET ?";

                $params[] = $limit;
                $params[] = $offset;

                $notifications = $this->db->fetchAll($sql, $params);

                // Format response
                $notificationData = array_map(function($notif) {
                    return [
                        'id' => (int)$notif['id'],
                        'user_id' => (int)$notif['user_id'],
                        'title' => $notif['title'] ?? '',
                        'message' => $notif['message'] ?? '',
                        'related_id' => $notif['related_id'] ?? null,
                        'related_type' => $notif['related_type'] ?? '',
                        'is_read' => (int)($notif['is_read'] ?? 0),
                        'created_at' => $notif['created_at'] ?? null,
                        'updated_at' => $notif['updated_at'] ?? null
                    ];
                }, $notifications);

                echo json_encode([
                    'success' => true,
                    'count' => count($notificationData),
                    'total' => (int)$totalCount,
                    'data' => $notificationData,
                    'message' => 'Notifications retrieved successfully'
                ]);

            } catch (Exception $e) {
                // If notifications table doesn't exist, return empty result
                echo json_encode([
                    'success' => true,
                    'count' => 0,
                    'total' => 0,
                    'data' => [],
                    'message' => 'Notifications feature not yet implemented'
                ]);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Mark notification as read
     * PUT /notifications/{id}
     */
    public function update($id)
    {
        // Get authenticated user
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $userId = $auth['user_id'];

            // Get notification
            $sql = "SELECT * FROM vtiger_notifications 
                    WHERE id = ? AND user_id = ?";
            $notification = $this->db->fetchOne($sql, [$id, $userId]);

            if (!$notification) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Notification not found'
                ]);
                return;
            }

            // Update notification
            $updateSql = "UPDATE vtiger_notifications 
                         SET is_read = 1, updated_at = NOW() 
                         WHERE id = ? AND user_id = ?";
            $this->db->execute($updateSql, [$id, $userId]);

            // Get updated notification
            $updatedNotification = $this->db->fetchOne($sql, [$id, $userId]);

            echo json_encode([
                'success' => true,
                'message' => 'Notification marked as read',
                'data' => [
                    'id' => (int)$updatedNotification['id'],
                    'user_id' => (int)$updatedNotification['user_id'],
                    'title' => $updatedNotification['title'],
                    'message' => $updatedNotification['message'],
                    'related_id' => $updatedNotification['related_id'] ?? null,
                    'related_type' => $updatedNotification['related_type'],
                    'is_read' => (int)$updatedNotification['is_read'],
                    'created_at' => $updatedNotification['created_at'],
                    'updated_at' => $updatedNotification['updated_at']
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
                'message' => 'Authentication failed. Please login again.'
            ]);
            return false;
        }

        $payload = JWT::validateToken($token);

        if (!$payload) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Authentication failed. Please login again.'
            ]);
            return false;
        }

        return $payload;
    }
}
