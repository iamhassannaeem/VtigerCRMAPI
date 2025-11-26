<?php
/**
 * Contact Controller
 */

class ContactController
{
    private $contactModel;

    public function __construct()
    {
        $this->contactModel = new Contact();
    }

    /**
     * Get all contacts
     * GET /contacts
     */
    public function index()
    {
        // Get authenticated user
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            // Get query parameters
            $filters = [
                'account_id' => $_GET['account_id'] ?? null,
                'search' => $_GET['search'] ?? null,
                'limit' => $_GET['limit'] ?? 50,
                'offset' => $_GET['offset'] ?? 0
            ];

            // Get contacts
            $contacts = $this->contactModel->getContacts($filters);

            echo json_encode([
                'success' => true,
                'count' => count($contacts),
                'data' => $contacts
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
     * Get contact by ID
     * GET /contacts/{id}
     */
    public function show($contactId)
    {
        // Get authenticated user
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $contact = $this->contactModel->getContactById($contactId);

            if (!$contact) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Contact not found'
                ]);
                return;
            }

            echo json_encode([
                'success' => true,
                'data' => $contact
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


