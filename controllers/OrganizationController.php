<?php
/**
 * Organization (Account) Controller
 */

class OrganizationController
{
    private $organizationModel;

    public function __construct()
    {
        $this->organizationModel = new Organization();
    }

    /**
     * Get all organizations
     * GET /organizations
     */
    public function index()
    {
        // Get authenticated user
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            // Get query parameters
            $filters = [
                'search' => $_GET['search'] ?? null,
                'account_type' => $_GET['account_type'] ?? null,
                'industry' => $_GET['industry'] ?? null,
                'limit' => $_GET['limit'] ?? 50,
                'offset' => $_GET['offset'] ?? 0
            ];

            // Get organizations
            $organizations = $this->organizationModel->getOrganizations($filters);

            echo json_encode([
                'success' => true,
                'count' => count($organizations),
                'data' => $organizations
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
     * Get organization by ID
     * GET /organizations/{id}
     */
    public function show($accountId)
    {
        // Get authenticated user
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $organization = $this->organizationModel->getOrganizationById($accountId);

            if (!$organization) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Organization not found'
                ]);
                return;
            }

            echo json_encode([
                'success' => true,
                'data' => $organization
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
                'message' => 'No token provided'
            ]);
            return false;
        }

        $payload = JWT::validateToken($token);

        if (!$payload) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid or expired token'
            ]);
            return false;
        }

        return $payload;
    }
}






