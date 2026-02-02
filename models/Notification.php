<?php

require_once __DIR__ . '/../core/Database.php';

class Notification
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new notification
     * @param array $data Notification data
     * @return array Created notification
     */
    public function create($data)
    {
        $sql = "INSERT INTO vtiger_notifications 
                (user_id, title, message, related_id, related_type, is_read, created_at)
                VALUES (?, ?, ?, ?, ?, 0, NOW())";

        $params = [
            $data['user_id'],
            $data['title'],
            $data['message'],
            $data['related_id'] ?? null,
            $data['related_type']
        ];

        $this->db->execute($sql, $params);
        $notificationId = $this->db->lastInsertId();

        return $this->getById($notificationId);
    }

    /**
     * Get notification by ID
     * @param int $id Notification ID
     * @return array|null
     */
    public function getById($id)
    {
        // Try notification_logs first
        try {
            $sql = "SELECT 
                        nl.id,
                        nl.user_id,
                        nl.title,
                        nl.message,
                        nl.ticket_id as related_id,
                        nl.notification_type as related_type,
                        nl.is_read,
                        nl.created_at,
                        nl.updated_at
                    FROM notification_logs nl
                    WHERE nl.id = ?";
            $result = $this->db->fetchOne($sql, [$id]);
            if ($result) {
                return [
                    'id' => (int)$result['id'],
                    'user_id' => (int)$result['user_id'],
                    'title' => $result['title'],
                    'message' => $result['message'],
                    'related_id' => $result['related_id'],
                    'related_type' => $result['related_type'],
                    'is_read' => (int)$result['is_read'],
                    'created_at' => $result['created_at'],
                    'updated_at' => $result['updated_at']
                ];
            }
        } catch (Exception $e) {
            // Continue to fallback
        }
        
        // Fallback to vtiger_notifications
        $sql = "SELECT * FROM vtiger_notifications WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }

    /**
     * Get notifications for a user with filters and pagination
     * @param int $userId User ID
     * @param array $filters Filters (related_type, is_read)
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Notifications and total count
     */
    public function getNotifications($userId, $filters = [], $limit = 20, $offset = 0)
    {
        // Query notification_logs table first (where FCM push notifications are stored)
        try {
            $where = ["nl.user_id = ?"];
            $params = [$userId];

            // Filter by related_type (maps to notification_type in notification_logs)
            if (!empty($filters['related_type'])) {
                $where[] = "nl.notification_type = ?";
                $params[] = $filters['related_type'];
            }

            // Filter by is_read
            if (isset($filters['is_read']) && $filters['is_read'] !== '') {
                $where[] = "nl.is_read = ?";
                $params[] = (int)$filters['is_read'];
            }

            $whereClause = implode(' AND ', $where);

            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM notification_logs nl WHERE {$whereClause}";
            $totalResult = $this->db->fetchOne($countSql, $params);
            $total = (int)($totalResult['total'] ?? 0);

            // Get paginated results
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
                    WHERE {$whereClause}
                    ORDER BY nl.created_at DESC
                    LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $notifications = $this->db->fetchAll($sql, $params);

            // Format notifications to match expected structure
            $formattedNotifications = array_map(function($notif) {
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

            return [
                'notifications' => $formattedNotifications,
                'total' => $total
            ];
        } catch (Exception $e) {
            // Fallback to vtiger_notifications if notification_logs doesn't exist
            $where = ["user_id = ?"];
            $params = [$userId];

            if (!empty($filters['related_type'])) {
                $where[] = "related_type = ?";
                $params[] = $filters['related_type'];
            }

            if (isset($filters['is_read']) && $filters['is_read'] !== '') {
                $where[] = "is_read = ?";
                $params[] = (int)$filters['is_read'];
            }

            $whereClause = implode(' AND ', $where);

            $countSql = "SELECT COUNT(*) as total FROM vtiger_notifications WHERE {$whereClause}";
            $totalResult = $this->db->fetchOne($countSql, $params);
            $total = (int)($totalResult['total'] ?? 0);

            $sql = "SELECT * FROM vtiger_notifications 
                    WHERE {$whereClause}
                    ORDER BY created_at DESC
                    LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            
            $notifications = $this->db->fetchAll($sql, $params);

            return [
                'notifications' => $notifications,
                'total' => $total
            ];
        }
    }

    /**
     * Mark notification as read
     * @param int $notificationId Notification ID
     * @param int $userId User ID (for security)
     * @return bool
     */
    public function markAsRead($notificationId, $userId)
    {
        // Try notification_logs first
        try {
            // First check if notification exists in notification_logs
            $checkSql = "SELECT id FROM notification_logs WHERE id = ? AND user_id = ?";
            $exists = $this->db->fetchOne($checkSql, [$notificationId, $userId]);
            
            if ($exists) {
                $sql = "UPDATE notification_logs 
                        SET is_read = 1, updated_at = NOW()
                        WHERE id = ? AND user_id = ?";
                $this->db->execute($sql, [$notificationId, $userId]);
                return true; // If it exists and we executed, consider it successful
            }
        } catch (Exception $e) {
            // Continue to fallback
        }
        
        // Fallback to vtiger_notifications
        try {
            $checkSql = "SELECT id FROM vtiger_notifications WHERE id = ? AND user_id = ?";
            $exists = $this->db->fetchOne($checkSql, [$notificationId, $userId]);
            
            if ($exists) {
                $sql = "UPDATE vtiger_notifications 
                        SET is_read = 1, updated_at = NOW()
                        WHERE id = ? AND user_id = ?";
                $this->db->execute($sql, [$notificationId, $userId]);
                return true;
            }
        } catch (Exception $e) {
            // Continue
        }
        
        return false; // Notification not found in either table
    }

    /**
     * Update notification read status
     * @param int $notificationId Notification ID
     * @param int $userId User ID (for security)
     * @param int $isRead Read status (0 or 1)
     * @return bool
     */
    public function updateReadStatus($notificationId, $userId, $isRead)
    {
        // Try notification_logs first
        try {
            // First check if notification exists in notification_logs
            $checkSql = "SELECT id FROM notification_logs WHERE id = ? AND user_id = ?";
            $exists = $this->db->fetchOne($checkSql, [$notificationId, $userId]);
            
            if ($exists) {
                $sql = "UPDATE notification_logs 
                        SET is_read = ?, updated_at = NOW()
                        WHERE id = ? AND user_id = ?";
                $this->db->execute($sql, [$isRead, $notificationId, $userId]);
                return true; // If it exists and we executed, consider it successful
            }
        } catch (Exception $e) {
            // Continue to fallback
        }
        
        // Fallback to vtiger_notifications
        try {
            $checkSql = "SELECT id FROM vtiger_notifications WHERE id = ? AND user_id = ?";
            $exists = $this->db->fetchOne($checkSql, [$notificationId, $userId]);
            
            if ($exists) {
                $sql = "UPDATE vtiger_notifications 
                        SET is_read = ?, updated_at = NOW()
                        WHERE id = ? AND user_id = ?";
                $this->db->execute($sql, [$isRead, $notificationId, $userId]);
                return true;
            }
        } catch (Exception $e) {
            // Continue
        }
        
        return false; // Notification not found in either table
    }

    /**
     * Get unread count for a user
     * @param int $userId User ID
     * @return int Unread count
     */
    public function getUnreadCount($userId)
    {
        // Try notification_logs first
        try {
            $sql = "SELECT COUNT(*) as count FROM notification_logs 
                    WHERE user_id = ? AND is_read = 0";
            $result = $this->db->fetchOne($sql, [$userId]);
            if ($result !== null) {
                return (int)($result['count'] ?? 0);
            }
        } catch (Exception $e) {
            // Continue to fallback
        }
        
        // Fallback to vtiger_notifications
        $sql = "SELECT COUNT(*) as count FROM vtiger_notifications 
                WHERE user_id = ? AND is_read = 0";
        
        $result = $this->db->fetchOne($sql, [$userId]);
        return (int)($result['count'] ?? 0);
    }
}

