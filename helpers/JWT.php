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

        // Check expiration
        if (!isset($payload['exp']) || $payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }

    /**
     * Extract token from Authorization header
     */
    public static function extractToken()
    {
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            return null;
        }

        // Bearer token format: "Bearer <token>"
        $authHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}



