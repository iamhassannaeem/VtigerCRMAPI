<?php
/**
 * Firebase Cloud Messaging Helper
 */

class Firebase
{
    private static $config;
    private static $serviceAccount = null;
    private static $accessToken = null;
    private static $accessTokenExpiry = 0;

    private static function getConfig()
    {
        if (self::$config === null) {
            $configPath = __DIR__ . '/../config/firebase.php';
            if (file_exists($configPath)) {
                self::$config = require $configPath;
            } else {
                throw new Exception('Firebase configuration file not found. Please copy config/firebase.example.php to config/firebase.php');
            }
        }
        return self::$config;
    }

    /**
     * Get service account data from JSON file
     */
    private static function getServiceAccount()
    {
        if (self::$serviceAccount === null) {
            $config = self::getConfig();
            
            if (empty($config['service_account_path'])) {
                throw new Exception('Firebase service account path not configured. Please set service_account_path in config/firebase.php');
            }

            $serviceAccountPath = $config['service_account_path'];
            
            if (!file_exists($serviceAccountPath)) {
                throw new Exception("Firebase service account file not found at: {$serviceAccountPath}. Please download your service account JSON file from Firebase Console.");
            }

            $serviceAccountJson = file_get_contents($serviceAccountPath);
            self::$serviceAccount = json_decode($serviceAccountJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in service account file: ' . json_last_error_msg());
            }

            if (empty(self::$serviceAccount['project_id']) || empty(self::$serviceAccount['private_key']) || empty(self::$serviceAccount['client_email'])) {
                throw new Exception('Invalid service account file. Missing required fields: project_id, private_key, or client_email');
            }
        }

        return self::$serviceAccount;
    }

    /**
     * Get OAuth 2.0 access token using service account
     */
    private static function getAccessToken()
    {
        $now = time();
        
        // Return cached token if still valid (with 5 minute buffer)
        if (self::$accessToken !== null && self::$accessTokenExpiry > ($now + 300)) {
            return self::$accessToken;
        }

        $serviceAccount = self::getServiceAccount();
        
        $tokenUrl = 'https://oauth2.googleapis.com/token';
        
        // Create JWT assertion
        $now = time();
        $jwtHeader = [
            'alg' => 'RS256',
            'typ' => 'JWT'
        ];
        
        $jwtPayload = [
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $tokenUrl,
            'exp' => $now + 3600,
            'iat' => $now
        ];
        
        $jwtHeaderEncoded = self::base64UrlEncode(json_encode($jwtHeader));
        $jwtPayloadEncoded = self::base64UrlEncode(json_encode($jwtPayload));
        
        $signatureInput = $jwtHeaderEncoded . '.' . $jwtPayloadEncoded;
        
        // Sign with private key
        $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
        if (!$privateKey) {
            throw new Exception('Failed to load private key: ' . openssl_error_string());
        }
        
        openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKey);
        
        $signatureEncoded = self::base64UrlEncode($signature);
        $jwt = $signatureInput . '.' . $signatureEncoded;
        
        // Request access token
        $postData = http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tokenUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('OAuth token request failed: ' . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception('OAuth token request failed with HTTP ' . $httpCode . ': ' . $response);
        }
        
        $tokenData = json_decode($response, true);
        
        if (empty($tokenData['access_token'])) {
            throw new Exception('No access token in OAuth response');
        }
        
        self::$accessToken = $tokenData['access_token'];
        self::$accessTokenExpiry = $now + ($tokenData['expires_in'] ?? 3600);
        
        return self::$accessToken;
    }

    /**
     * Base64 URL encode (without padding)
     */
    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Send FCM notification using HTTP v1 API (service account)
     */
    public static function sendNotification($token, $title, $body, $data = [])
    {
        try {
            $serviceAccount = self::getServiceAccount();
            $accessToken = self::getAccessToken();
            
            $projectId = $serviceAccount['project_id'];
            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";
            
            // Prepare notification payload for HTTP v1 API
            $message = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body
                    ],
                    'data' => array_map('strval', $data),
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'notification_count' => 1
                        ]
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10'
                        ],
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1
                            ]
                        ]
                    ]
                ]
            ];
            
            $headers = [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                throw new Exception('FCM request failed: ' . $error);
            }
            
            $result = json_decode($response, true);
            
            if ($httpCode !== 200) {
                $errorMsg = $result['error']['message'] ?? 'Unknown error';
                $errorCode = $result['error']['code'] ?? 'UNKNOWN';
                throw new Exception("FCM API error ({$errorCode}): {$errorMsg}");
            }
            
            // Extract message ID from response
            $messageId = $result['name'] ?? null;
            
            return [
                'success' => true,
                'message_id' => $messageId,
                'error' => null,
                'response' => $result
            ];
            
        } catch (Exception $e) {
            // Check for specific error codes that indicate invalid token
            $errorMessage = $e->getMessage();
            $isInvalidToken = (
                strpos($errorMessage, 'INVALID_ARGUMENT') !== false ||
                strpos($errorMessage, 'NOT_FOUND') !== false ||
                strpos($errorMessage, 'invalid-registration-token') !== false ||
                strpos($errorMessage, 'registration-token-not-registered') !== false
            );
            
            return [
                'success' => false,
                'message_id' => null,
                'error' => $errorMessage,
                'is_invalid_token' => $isInvalidToken,
                'response' => null
            ];
        }
    }

    /**
     * Send FCM notification using legacy API (server key)
     * @deprecated This method redirects to sendNotification() which uses HTTP v1 API
     */
    public static function sendNotificationLegacy($token, $title, $body, $data = [])
    {
        // Redirect to new HTTP v1 API method
        return self::sendNotification($token, $title, $body, $data);
    }
}
