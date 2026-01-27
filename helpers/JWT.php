<?php
/**
 * JWT Helper Class
 */

class JWT
{
    private static $config;

    private static function getConfig()
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../config/jwt.php';
        }
        return self::$config;
    }

    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode($data)
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Generate JWT token
     */
    public static function generateToken($payload)
    {
        $config = self::getConfig();
        
        // Header
        $header = [
            'typ' => 'JWT',
            'alg' => $config['algorithm']
        ];

        // Add expiration to payload
        $payload['iat'] = time();
        $payload['exp'] = time() + $config['expiration'];

        // Encode header and payload
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        // Create signature
        $signature = hash_hmac(
            'sha256',
            "$headerEncoded.$payloadEncoded",
            $config['secret_key'],
            true
        );
        $signatureEncoded = self::base64UrlEncode($signature);

        // Return JWT
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }

    /**
     * Validate JWT token
     */
    public static function validateToken($token)
    {
        $config = self::getConfig();

        // Split token
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            error_log("JWT::validateToken: Invalid token format - expected 3 parts, got " . count($parts));
            return false;
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        // Verify signature
        $signature = self::base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac(
            'sha256',
            "$headerEncoded.$payloadEncoded",
            $config['secret_key'],
            true
        );

        if (!hash_equals($signature, $expectedSignature)) {
            error_log("JWT::validateToken: Signature verification failed");
            error_log("JWT::validateToken: Secret key length: " . strlen($config['secret_key']));
            return false;
        }

        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        
        if (!$payload) {
            error_log("JWT::validateToken: Failed to decode payload");
            return false;
        }

        // Check expiration
        $currentTime = time();
        $expTime = $payload['exp'] ?? 0;
        
        if (!isset($payload['exp'])) {
            error_log("JWT::validateToken: Token missing expiration (exp) claim");
            return false;
        }
        
        if ($expTime < $currentTime) {
            error_log("JWT::validateToken: Token expired. Exp: {$expTime}, Current: {$currentTime}, Diff: " . ($currentTime - $expTime) . " seconds");
            return false;
        }

        error_log("JWT::validateToken: Token validated successfully for user_id: " . ($payload['user_id'] ?? 'unknown'));
        return $payload;
    }

    /**
     * Extract token from Authorization header
     */
    public static function extractToken()
    {
        // Try multiple methods to get headers (for compatibility with different PHP setups)
        $authHeader = null;
        
        // Method 1: getallheaders() (works on Apache, not always on Nginx/FastCGI)
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (isset($headers['Authorization'])) {
                $authHeader = $headers['Authorization'];
            } elseif (isset($headers['authorization'])) {
                $authHeader = $headers['authorization'];
            }
        }
        
        // Method 2: $_SERVER (fallback for when getallheaders() doesn't work)
        if (!$authHeader) {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['Authorization'])) {
                $authHeader = $_SERVER['Authorization'];
            } elseif (function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
                if (isset($headers['Authorization'])) {
                    $authHeader = $headers['Authorization'];
                } elseif (isset($headers['authorization'])) {
                    $authHeader = $headers['authorization'];
                }
            }
        }
        
        if (!$authHeader) {
            error_log("JWT::extractToken: No Authorization header found");
            return null;
        }

        // Bearer token format: "Bearer <token>"
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = trim($matches[1]);
            error_log("JWT::extractToken: Token extracted, length: " . strlen($token));
            return $token;
        }

        error_log("JWT::extractToken: Authorization header found but doesn't match Bearer format");
        return null;
    }

    /**
     * Generate refresh token (longer expiration)
     */
    public static function generateRefreshToken($payload)
    {
        $config = self::getConfig();
        
        // Header
        $header = [
            'typ' => 'JWT',
            'alg' => $config['algorithm']
        ];

        // Add expiration to payload (longer for refresh token)
        $payload['iat'] = time();
        $payload['exp'] = time() + ($config['refresh_expiration'] ?? 604800); // Default 7 days
        $payload['type'] = 'refresh'; // Mark as refresh token

        // Encode header and payload
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        // Create signature
        $signature = hash_hmac(
            'sha256',
            "$headerEncoded.$payloadEncoded",
            $config['secret_key'],
            true
        );
        $signatureEncoded = self::base64UrlEncode($signature);

        // Return JWT
        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }

    /**
     * Validate refresh token (allows expired tokens to be checked)
     */
    public static function validateRefreshToken($token, $checkExpiration = true)
    {
        $config = self::getConfig();

        // Split token
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        // Verify signature
        $signature = self::base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac(
            'sha256',
            "$headerEncoded.$payloadEncoded",
            $config['secret_key'],
            true
        );

        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }

        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        
        if (!$payload) {
            return false;
        }

        // Check if it's a refresh token
        if (($payload['type'] ?? '') !== 'refresh') {
            return false;
        }

        // Check expiration if required
        if ($checkExpiration) {
            $currentTime = time();
            $expTime = $payload['exp'] ?? 0;
            
            if (!isset($payload['exp']) || $expTime < $currentTime) {
                return false;
            }
        }

        return $payload;
    }
}



