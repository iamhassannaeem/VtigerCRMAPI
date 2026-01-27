<?php
/**
 * Notification Log Model
 */

require_once __DIR__ . '/../core/Database.php';

class NotificationLog
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Log notification attempt
     */
    public function log($userId, $ticketId, $notificationType, $title, $message, $fcmToken, $status, $errorMessage = null, $fcmMessageId = null)
    {
        $sql = "INSERT INTO notification_logs 
                (user_id, ticket_id, notification_type, title, message, fcm_token, status, error_message, fcm_message_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $this->db->execute($sql, [
            $userId,
            $ticketId,
            $notificationType,
            $title,
            $message,
            $fcmToken,
            $status,
            $errorMessage,
            $fcmMessageId
        ]);

        return $this->db->lastInsertId();
    }
}
