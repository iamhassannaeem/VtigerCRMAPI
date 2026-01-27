<?php
/**
 * Authentication Controller
 */

require_once __DIR__ . '/../models/RefreshToken.php';

class AuthController
{
    private $userModel;
    private $refreshTokenModel;

    public function __construct()
    {
        $this->userModel = new User();
        $this->refreshTokenModel = new RefreshToken();
    }

    /**
     * Login endpoint
     * POST /auth/login
     */
    public function login()
    {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        // Validate input
        if (empty($input['username']) || empty($input['password'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Username and password are required'
            ]);
            return;
        }

        try {
            // Authenticate user
            $user = $this->userModel->authenticate($input['username'], $input['password']);

            if (!$user) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ]);
                return;
            }

            // Check if user is admin
            $isAdmin = ($user['is_admin'] === 'on');

           // ✨ NEW: Extract role type from role name
           $roleInfo = RoleHelper::getRoleType($user['rolename'] ?? '');

            // Generate JWT token
            $payload = [
                'user_id' => $user['id'],
                'username' => $user['user_name'],
                'is_admin' => $isAdmin,
                'role_type' => $roleInfo['type']  // ✨ NEW: Include role_type in JWT
      
            ];

            $accessToken = JWT::generateToken($payload);

            // Generate refresh token
            $refreshTokenString = $this->refreshTokenModel->generateToken();
            $refreshTokenExpiry = time() + (7 * 24 * 60 * 60); // 7 days
            
            // Store refresh token in database
            $this->refreshTokenModel->storeToken($user['id'], $refreshTokenString, $refreshTokenExpiry);

            // Return success response
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'token' => $accessToken,
                'refresh_token' => $refreshTokenString,
                'expires_in' => 86400, // 24 hours
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['user_name'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name'],
                    'email' => $user['email1'],
                    'is_admin' => $isAdmin,
                    'role' => $user['rolename'] ?? 'User',
                    'role_info' => [
                        'full_name' => $roleInfo['full_name'],
                        'base_name' => $roleInfo['base_name'],
                        'type' => $roleInfo['type']
                    ],
                    'department' => $user['department'] ?? ''
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
     * Verify token (optional endpoint for token validation)
     * GET /auth/verify
     */
    public function verify()
    {
        $token = JWT::extractToken();

        if (!$token) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'No token provided'
            ]);
            return;
        }

        $payload = JWT::validateToken($token);

        if (!$payload) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid or expired token'
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Token is valid',
            'user_id' => $payload['user_id']
        ]);
    }

    /**
     * Refresh access token
     * POST /auth/refresh
     */
    public function refresh()
    {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['refresh_token'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Refresh token is required'
            ]);
            return;
        }

        try {
            // Validate refresh token
            $tokenData = $this->refreshTokenModel->validateToken($input['refresh_token']);

            if (!$tokenData || !$tokenData['valid']) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid or expired refresh token'
                ]);
                return;
            }

            // Get user data
            $user = $this->userModel->getUserById($tokenData['user_id']);

            if (!$user) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]);
                return;
            }

            // Check if user is admin
            $isAdmin = ($user['is_admin'] === 'on');

            // Extract role type
            $roleInfo = RoleHelper::getRoleType($user['rolename'] ?? '');

            // Generate new access token
            $payload = [
                'user_id' => $user['id'],
                'username' => $user['user_name'],
                'is_admin' => $isAdmin,
                'role_type' => $roleInfo['type']
            ];

            $accessToken = JWT::generateToken($payload);

            // Return new access token
            echo json_encode([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'token' => $accessToken,
                'expires_in' => 86400 // 24 hours
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
     * Logout - revoke refresh token
     * POST /auth/logout
     */
    public function logout()
    {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        if (!empty($input['refresh_token'])) {
            $this->refreshTokenModel->revokeToken($input['refresh_token']);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}



