<?php
/**
 * Ticket Controller
 */

class TicketController
{
    private $ticketModel;
    private $commentModel;

    public function __construct()
    {
        $this->ticketModel = new Ticket();
        $this->commentModel = new Comment();
    }

    /**
     * Get all tickets
     * GET /tickets
     */
    public function index()
    {
        // Get authenticated user
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $userId = $auth['user_id'];
            $isAdmin = $auth['is_admin'];

            // Get query parameters
            $filters = [
                'status' => $_GET['status'] ?? null,
                'assigned_to' => $_GET['assigned_to'] ?? null,
                'search' => $_GET['search'] ?? null,
                'limit' => $_GET['limit'] ?? 50,
                'offset' => $_GET['offset'] ?? 0
            ];

            // Get tickets
            $tickets = $this->ticketModel->getTickets($filters, $userId, $isAdmin);

            echo json_encode([
                'success' => true,
                'count' => count($tickets),
                'data' => $tickets
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
     * Get ticket by ID
     * GET /tickets/{id}
     */
    public function show($ticketId)
    {
        // Get authenticated user
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $userId = $auth['user_id'];
            $isAdmin = $auth['is_admin'];

            // Get ticket details
            $ticket = $this->ticketModel->getTicketById($ticketId, $userId, $isAdmin);

            if (!$ticket) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Ticket not found or access denied'
                ]);
                return;
            }

            // Get comments
            $comments = $this->commentModel->getCommentsByTicketId($ticketId);
            $lastComment = $comments[0] ?? null;

            // Format response
            $response = [
                'success' => true,
                'data' => [
                    'summary' => [
                        'ticket_id' => $ticket['ticketid'],
                        'ticket_no' => $ticket['ticket_no'],
                        'title' => $ticket['title'],
                        'status' => $ticket['status'],
                        'priority' => $ticket['priority'],
                        'severity' => $ticket['severity'],
                        'category' => $ticket['category'],
                        'assigned_to' => $ticket['assigned_to_name'],
                        'created_by' => $ticket['created_by_name'],
                        'account' => $ticket['account_name'],
                        'contact' => $ticket['contact_name'],
                        'created_time' => $ticket['createdtime'],
                        'modified_time' => $ticket['modifiedtime']
                    ],
                    'details' => [
                        'description' => $ticket['description'] ?? '',
                        'solution' => $ticket['solution'] ?? '',
                        'hours' => $ticket['hours'] ?? 0,
                        'days' => $ticket['days'] ?? 0,
                        'custom_fields' => $this->extractCustomFields($ticket)
                    ],
                    'last_comment' => $lastComment,
                    'all_comments' => $comments
                ]
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Create new ticket
     * POST /tickets
     */
    public function create()
    {
        // Get authenticated user
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $userId = $auth['user_id'];

            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);

            // Validate required fields
            if (empty($input['title'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Title is required'
                ]);
                return;
            }

            // Prepare data - all form fields
            $data = [
                // Basic fields
                'title' => $input['title'],
                'description' => $input['description'] ?? '',
                'solution' => $input['solution'] ?? '',
                
                // Standard ticket fields
                'priority' => $input['priority'] ?? 'Normal',
                'category' => $input['category'] ?? '',
                'status' => $input['status'] ?? 'Open',
                'severity' => $input['severity'] ?? '',
                
                // Relationships
                'account_id' => $input['account_id'] ?? null,  // Organization
                'contact_id' => $input['contact_id'] ?? null,  // Contact
                'assigned_to' => $input['assigned_to'] ?? $userId,
                
                // Time tracking
                'hours' => $input['hours'] ?? null,
                'days' => $input['days'] ?? null,
                'total_time_minutes' => $input['total_time_minutes'] ?? null,
                
                // Custom fields
                'organization_priority' => $input['organization_priority'] ?? '',  // cf_867
                'services_issue' => $input['services_issue'] ?? '',  // cf_869
                'issue_end' => $input['issue_end'] ?? '',  // cf_871
                'end_time' => $input['end_time'] ?? null,  // cf_873 (time field)
                'closing_time' => $input['closing_time'] ?? '',  // cf_879
                
                // Checkboxes (1 = checked, 0 = unchecked)
                'send_on_confirmation' => $input['send_on_confirmation'] ?? '0',  // cf_875
                'email_sms_assigned_to' => $input['email_sms_assigned_to'] ?? '0',  // cf_877
                'email_sms_to_customer' => $input['email_sms_to_customer'] ?? '0',  // cf_897
                
                // Additional field
                'from_portal' => $input['from_portal'] ?? '0'
            ];

            // Create ticket
            $result = $this->ticketModel->createTicket($data, $userId);

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Ticket created successfully',
                'data' => $result
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
     * Add comment to ticket
     * POST /tickets/{id}/comments
     */
    public function addComment($ticketId)
    {
        // Get authenticated user
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $userId = $auth['user_id'];
            $isAdmin = $auth['is_admin'];

            // Verify ticket exists and user has access
            $ticket = $this->ticketModel->getTicketById($ticketId, $userId, $isAdmin);

            if (!$ticket) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Ticket not found or access denied'
                ]);
                return;
            }

            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);

            // Validate comment
            if (empty($input['comment'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Comment is required'
                ]);
                return;
            }

            // Add comment
            $comments = $this->commentModel->addComment($ticketId, $input['comment'], $userId);

            echo json_encode([
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => $comments
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
     * Extract custom fields from ticket data
     */
    private function extractCustomFields($ticket)
    {
        $customFields = [];
        
        foreach ($ticket as $key => $value) {
            if (strpos($key, 'cf_') === 0) {
                $customFields[$key] = $value;
            }
        }

        return $customFields;
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



