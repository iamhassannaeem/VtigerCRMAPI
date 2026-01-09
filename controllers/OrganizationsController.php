<?php
/**
 * Organizations Controller
 */

class OrganizationsController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get organizations list
     * GET /organizations
     */
    public function index()
    {
        // Get authenticated user
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            // Get query parameters
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            $search = $_GET['search'] ?? null;
            $accountType = $_GET['account_type'] ?? null;
            $industry = $_GET['industry'] ?? null;

            // Build SQL query
            $whereClauses = ["a.deleted = 0"];
            $params = [];

            if ($search) {
                $whereClauses[] = "(a.accountname LIKE ? OR a.account_no LIKE ?)";
                $searchTerm = '%' . $search . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            if ($accountType) {
                $whereClauses[] = "a.account_type = ?";
                $params[] = $accountType;
            }

            if ($industry) {
                $whereClauses[] = "a.industry = ?";
                $params[] = $industry;
            }

            $whereStr = implode(" AND ", $whereClauses);

            // Get total count
            $countSql = "SELECT COUNT(*) as count FROM vtiger_account a WHERE $whereStr";
            $countResult = $this->db->fetchOne($countSql, $params);
            $totalCount = $countResult['count'] ?? 0;

            // Get organizations
            $sql = "SELECT 
                        a.accountid,
                        a.accountname,
                        a.account_no,
                        a.phone,
                        a.email1,
                        a.industry,
                        a.account_type
                    FROM vtiger_account a
                    WHERE $whereStr
                    ORDER BY a.accountname ASC
                    LIMIT ? OFFSET ?";

            $params[] = $limit;
            $params[] = $offset;

            $organizations = $this->db->fetchAll($sql, $params);

            // Format response
            $formattedOrgs = array_map(function($org) {
                return [
                    'accountid' => (int)$org['accountid'],
                    'accountname' => $org['accountname'] ?? '',
                    'account_no' => $org['account_no'] ?? '',
                    'phone' => $org['phone'] ?? '',
                    'email1' => $org['email1'] ?? '',
                    'industry' => $org['industry'] ?? '',
                    'account_type' => $org['account_type'] ?? ''
                ];
            }, $organizations);

            echo json_encode([
                'success' => true,
                'count' => (int)$totalCount,
                'data' => $formattedOrgs
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
                'message' => 'Authentication failed. Please login again.'
            ]);
            return false;
        }

        $payload = JWT::validateToken($token);

        if (!$payload) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Authentication failed. Please login again.'
            ]);
            return false;
        }

        return $payload;
    }
}
