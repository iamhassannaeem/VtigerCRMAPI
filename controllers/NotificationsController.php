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

            // Try to query notification_logs table first (where FCM notifications are logged)
            try {
                $whereClauses = ["nl.user_id = ?"];
                $params = [$userId];

                if ($relatedType) {
                    $whereClauses[] = "nl.notification_type = ?";
                    $params[] = $relatedType;
                }

                // Filter by read status
                if ($isRead !== null) {
                    $whereClauses[] = "nl.is_read = ?";
                    $params[] = $isRead ? 1 : 0;
                }

                $whereStr = implode(" AND ", $whereClauses);

                // Get total count from notification_logs
                $countSql = "SELECT COUNT(*) as count FROM notification_logs nl WHERE $whereStr";
                $countResult = $this->db->fetchOne($countSql, $params);
                $totalCount = $countResult['count'] ?? 0;

                // Get notifications from notification_logs
                $sql = "SELECT 
                            nl.id,
                            nl.user_id,
                            nl.title,
                            nl.message,
                            nl.ticket_id as related_id,
                            nl.notification_type as related_type,
                            nl.is_read,
                            nl.created_at,
                            nl.updated_at,
                            nl.status,
                            nl.fcm_message_id
                        FROM notification_logs nl
                        WHERE $whereStr
                        ORDER BY nl.created_at DESC
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
                        'updated_at' => $notif['updated_at'] ?? null,
                        'status' => $notif['status'] ?? 'unknown',
                        'fcm_message_id' => $notif['fcm_message_id'] ?? null
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
                // Fallback: Try vtiger_notifications table if notification_logs doesn't exist
                try {
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

                } catch (Exception $e2) {
                    // If both tables don't exist, return empty result
                    echo json_encode([
                        'success' => true,
                        'count' => 0,
                        'total' => 0,
                        'data' => [],
                        'message' => 'No notifications found'
                    ]);
                }
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
            $notificationId = (int)$id;

            // Try notification_logs first
            $sql = "SELECT * FROM notification_logs 
                    WHERE id = ? AND user_id = ?";
            $notification = $this->db->fetchOne($sql, [$notificationId, $userId]);

            if ($notification) {
                // Update is_read field
                $updateSql = "UPDATE notification_logs 
                             SET is_read = 1, updated_at = NOW() 
                             WHERE id = ? AND user_id = ?";
                $this->db->execute($updateSql, [$notificationId, $userId]);

                // Get updated notification
                $updatedNotification = $this->db->fetchOne($sql, [$notificationId, $userId]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Notification marked as read',
                    'data' => [
                        'id' => (int)$updatedNotification['id'],
                        'user_id' => (int)$updatedNotification['user_id'],
                        'title' => $updatedNotification['title'],
                        'message' => $updatedNotification['message'],
                        'related_id' => $updatedNotification['ticket_id'] ?? null,
                        'related_type' => $updatedNotification['notification_type'],
                        'is_read' => 1,
                        'created_at' => $updatedNotification['created_at'],
                        'updated_at' => $updatedNotification['updated_at']
                    ]
                ]);
                return;
            }

            // Fallback to vtiger_notifications
            $sql = "SELECT * FROM vtiger_notifications 
                    WHERE id = ? AND user_id = ?";
            $notification = $this->db->fetchOne($sql, [$notificationId, $userId]);

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
            $this->db->execute($updateSql, [$notificationId, $userId]);

            // Get updated notification
            $updatedNotification = $this->db->fetchOne($sql, [$notificationId, $userId]);

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
