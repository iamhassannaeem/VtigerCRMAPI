<?php
/**
 * Authentication Controller
 */

class AuthController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
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

            $token = JWT::generateToken($payload);

            // Return success response
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'token' => $token,
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
}



