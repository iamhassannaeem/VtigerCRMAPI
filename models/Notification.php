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
        $where = ["user_id = ?"];
        $params = [$userId];

        // Filter by related_type
        if (!empty($filters['related_type'])) {
            $where[] = "related_type = ?";
            $params[] = $filters['related_type'];
        }

        // Filter by is_read
        if (isset($filters['is_read']) && $filters['is_read'] !== '') {
            $where[] = "is_read = ?";
            $params[] = (int)$filters['is_read'];
        }

        $whereClause = implode(' AND ', $where);

        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM vtiger_notifications WHERE {$whereClause}";
        $totalResult = $this->db->fetchOne($countSql, $params);
        $total = (int)($totalResult['total'] ?? 0);

        // Get paginated results
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

    /**
     * Mark notification as read
     * @param int $notificationId Notification ID
     * @param int $userId User ID (for security)
     * @return bool
     */
    public function markAsRead($notificationId, $userId)
    {
        $sql = "UPDATE vtiger_notifications 
                SET is_read = 1, updated_at = NOW()
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->execute($sql, [$notificationId, $userId]);
        return $stmt->rowCount() > 0;
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
        $sql = "UPDATE vtiger_notifications 
                SET is_read = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->execute($sql, [$isRead, $notificationId, $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Get unread count for a user
     * @param int $userId User ID
     * @return int Unread count
     */
    public function getUnreadCount($userId)
    {
        $sql = "SELECT COUNT(*) as count FROM vtiger_notifications 
                WHERE user_id = ? AND is_read = 0";
        
        $result = $this->db->fetchOne($sql, [$userId]);
        return (int)($result['count'] ?? 0);
    }
}

