<?php
/**
 * Picklist Controller
 * Handles all picklist/dropdown data endpoints
 */

class PicklistController
{
    private $picklistModel;

    public function __construct()
    {
        $this->picklistModel = new Picklist();
    }

    /**
     * Get ticket priorities
     * GET /picklists/priorities
     */
    public function priorities()
    {
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $data = $this->picklistModel->getPriorities();

            echo json_encode([
                'success' => true,
                'count' => count($data),
                'data' => $data
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
     * Get Services Issue picklist
     * GET /picklists/services-issue
     */
    public function servicesIssue()
    {
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $data = $this->picklistModel->getServicesIssue();

            echo json_encode([
                'success' => true,
                'count' => count($data),
                'data' => $data
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
     * Get ticket statuses
     * GET /picklists/statuses
     */
    public function statuses()
    {
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $data = $this->picklistModel->getStatuses();

            echo json_encode([
                'success' => true,
                'count' => count($data),
                'data' => $data
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
     * Get active users
     * GET /picklists/users
     */
    public function users()
    {
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $data = $this->picklistModel->getUsers();

            echo json_encode([
                'success' => true,
                'count' => count($data),
                'data' => $data
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
     * Get Issue End picklist
     * GET /picklists/issue-end
     */
    public function issueEnd()
    {
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $data = $this->picklistModel->getIssueEnd();

            echo json_encode([
                'success' => true,
                'count' => count($data),
                'data' => $data
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
     * Get ticket categories
     * GET /picklists/categories
     */
    public function categories()
    {
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $data = $this->picklistModel->getCategories();

            echo json_encode([
                'success' => true,
                'count' => count($data),
                'data' => $data
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
     * Get ticket severities
     * GET /picklists/severities
     */
    public function severities()
    {
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $data = $this->picklistModel->getSeverities();

            echo json_encode([
                'success' => true,
                'count' => count($data),
                'data' => $data
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
     * Get organization priority
     * GET /picklists/organization-priority
     */
    public function organizationPriority()
    {
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $data = $this->picklistModel->getOrganizationPriority();

            echo json_encode([
                'success' => true,
                'count' => count($data),
                'data' => $data
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

