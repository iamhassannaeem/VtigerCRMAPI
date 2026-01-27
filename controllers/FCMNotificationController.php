<?php
/**
 * FCM Notification Controller
 */

require_once __DIR__ . '/../models/FCMToken.php';
require_once __DIR__ . '/../models/NotificationLog.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Firebase.php';

class FCMNotificationController
{
    private $fcmTokenModel;
    private $notificationLogModel;
    private $userModel;

    public function __construct()
    {
        $this->fcmTokenModel = new FCMToken();
        $this->notificationLogModel = new NotificationLog();
        $this->userModel = new User();
    }

    /**
     * Send FCM notification
     * POST /api/notifications/send
     */
    public function send()
    {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        $required = ['user_id', 'title', 'message', 'ticket_id', 'ticket_number', 'type'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => "Missing required field: {$field}",
                    'code' => 'MISSING_FIELDS'
                ]);
                return;
            }
        }

        $userId = (int)$input['user_id'];
        $title = trim($input['title']);
        $message = trim($input['message']);
        $ticketId = (int)$input['ticket_id'];
        $ticketNumber = trim($input['ticket_number']);
        $type = trim($input['type']);

        // Validate notification type
        $allowedTypes = ['ticket_assigned', 'ticket_updated'];
        if (!in_array($type, $allowedTypes)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid notification type. Must be one of: ' . implode(', ', $allowedTypes),
                'code' => 'INVALID_TYPE'
            ]);
            return;
        }

        try {
            // Verify user exists
            $user = $this->userModel->getUserById($userId);
            if (!$user) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'User not found',
                    'code' => 'USER_NOT_FOUND'
                ]);
                return;
            }

            // Get FCM tokens for user
            $tokens = $this->fcmTokenModel->getActiveTokensForUser($userId);

            if (empty($tokens)) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'No FCM tokens found for user',
                    'code' => 'NO_TOKENS'
                ]);
                return;
            }

            // Prepare notification data
            $notificationData = [
                'ticket_id' => (string)$ticketId,
                'ticket_number' => $ticketNumber,
                'type' => $type,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
            ];

            // Send to all user's devices
            $results = [];
            $hasSuccess = false;
            $firstMessageId = null;

            foreach ($tokens as $tokenData) {
                $fcmToken = $tokenData['fcm_token'];
                
                try {
                    $response = Firebase::sendNotification(
                        $fcmToken,
                        $title,
                        $message,
                        $notificationData
                    );

                    if ($response['success']) {
                        $hasSuccess = true;
                        if (!$firstMessageId && $response['message_id']) {
                            $firstMessageId = $response['message_id'];
                        }

                        $results[] = [
                            'token' => substr($fcmToken, 0, 20) . '...',
                            'success' => true,
                            'messageId' => $response['message_id']
                        ];

                        // Log success
                        $this->notificationLogModel->log(
                            $userId,
                            $ticketId,
                            $type,
                            $title,
                            $message,
                            $fcmToken,
                            'sent',
                            null,
                            $response['message_id']
                        );
                    } else {
                        throw new Exception($response['error'] ?? 'Unknown error');
                    }
                } catch (Exception $e) {
                    $errorMessage = $e->getMessage();

                    $results[] = [
                        'token' => substr($fcmToken, 0, 20) . '...',
                        'success' => false,
                        'error' => $errorMessage
                    ];

                    // Log failure
                    $this->notificationLogModel->log(
                        $userId,
                        $ticketId,
                        $type,
                        $title,
                        $message,
                        $fcmToken,
                        'failed',
                        $errorMessage,
                        null
                    );

                    // Handle invalid tokens
                    if (isset($response['is_invalid_token']) && $response['is_invalid_token']) {
                        $this->fcmTokenModel->deactivateToken($fcmToken);
                    } elseif (strpos($errorMessage, 'InvalidRegistration') !== false || 
                        strpos($errorMessage, 'NotRegistered') !== false ||
                        strpos($errorMessage, 'invalid-registration-token') !== false ||
                        strpos($errorMessage, 'registration-token-not-registered') !== false ||
                        strpos($errorMessage, 'INVALID_ARGUMENT') !== false ||
                        strpos($errorMessage, 'NOT_FOUND') !== false) {
                        $this->fcmTokenModel->deactivateToken($fcmToken);
                    }
                }
            }

            if ($hasSuccess) {
                http_response_code(200);
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification sent successfully',
                    'notification_id' => $firstMessageId,
                    'results' => $results
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to send notification to any device',
                    'code' => 'SEND_FAILED',
                    'results' => $results
                ]);
            }

        } catch (Exception $e) {
            error_log('FCM Notification API Error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Internal server error',
                'code' => 'INTERNAL_ERROR',
                'message' => $e->getMessage()
            ]);
        }
    }
}
