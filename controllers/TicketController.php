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
            $roleType = $auth['role_type'] ?? 'Others';

            // Get query parameters
            $filters = [
                'status' => $_GET['status'] ?? null,
                'assigned_to' => $_GET['assigned_to'] ?? null,
                'search' => $_GET['search'] ?? null,
                'limit' => $_GET['limit'] ?? 50,
                'offset' => $_GET['offset'] ?? 0,
                // Date filtering parameters
                'start_date' => $_GET['start_date'] ?? null,
                'end_date' => $_GET['end_date'] ?? null,
                'date_range_days' => $_GET['date_range_days'] ?? null
            ];

            // NOC and Management users can see all tickets
            // Sales users will be filtered by their customers' accounts
            $canSeeAllTickets = $isAdmin || in_array($roleType, ['NOC', 'Management']);

            // Get tickets - pass roleType for Sales filtering
            $tickets = $this->ticketModel->getTickets($filters, $userId, $canSeeAllTickets, $roleType);

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
            $roleType = $auth['role_type'] ?? 'Others';

            // NOC and Management can see all tickets
            // Sales users will be filtered by their customers' accounts
            $canSeeAllTickets = $isAdmin || in_array($roleType, ['NOC', 'Management']);

            // Get ticket details - pass roleType for Sales filtering
            $ticket = $this->ticketModel->getTicketById($ticketId, $userId, $canSeeAllTickets, $roleType);

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
                    'organization_details' => [
                        'name' => $ticket['account_name'],
                        'phone' => $ticket['account_phone'] ?? '',
                        'email' => $ticket['account_email'] ?? '',
                        'website' => $ticket['account_website'] ?? '',
                        'billing_address' => [
                            'street' => $ticket['account_bill_street'] ?? '',
                            'city' => $ticket['account_bill_city'] ?? '',
                            'state' => $ticket['account_bill_state'] ?? '',
                            'country' => $ticket['account_bill_country'] ?? '',
                            'postal_code' => $ticket['account_bill_code'] ?? ''
                        ],
                        'shipping_address' => [
                            'street' => $ticket['account_ship_street'] ?? '',
                            'city' => $ticket['account_ship_city'] ?? '',
                            'state' => $ticket['account_ship_state'] ?? '',
                            'country' => $ticket['account_ship_country'] ?? '',
                            'postal_code' => $ticket['account_ship_code'] ?? ''
                        ]
                    ],
                    'contact_details' => [
                        'name' => $ticket['contact_name'],
                        'email' => $ticket['contact_email'] ?? '',
                        'phone' => $ticket['contact_phone'] ?? '',
                        'mobile' => $ticket['contact_mobile'] ?? ''
                    ],
                    'details' => [
                        'description' => $ticket['description'] ?? '',
                        'solution' => $ticket['solution'] ?? '',
                        'hours' => $ticket['hours'] ?? 0,
                        'days' => $ticket['days'] ?? 0,
                        'custom_fields' => $this->extractCustomFields($ticket),
                        'custom_fields_labeled' => [
                            'organization_priority' => [
                                'field_name' => 'cf_867',
                                'label' => 'Organization Priority',
                                'value' => $ticket['cf_867'] ?? ''
                            ],
                            'services_issue' => [
                                'field_name' => 'cf_869',
                                'label' => 'Services Issue',
                                'value' => $ticket['cf_869'] ?? ''
                            ],
                            'issue_end' => [
                                'field_name' => 'cf_871',
                                'label' => 'Issue End',
                                'value' => $ticket['cf_871'] ?? ''
                            ],
                            'end_time' => [
                                'field_name' => 'cf_873',
                                'label' => 'End Time',
                                'value' => $ticket['cf_873'] ?? null
                            ],
                            'send_on_confirmation' => [
                                'field_name' => 'cf_875',
                                'label' => 'Send On Confirmation',
                                'value' => $ticket['cf_875'] ?? '0'
                            ],
                            'email_sms_assigned_to' => [
                                'field_name' => 'cf_877',
                                'label' => 'Email And SMS Assigned To',
                                'value' => $ticket['cf_877'] ?? '0'
                            ],
                            'closing_time' => [
                                'field_name' => 'cf_879',
                                'label' => 'Closing Time',
                                'value' => $ticket['cf_879'] ?? ''
                            ],
                            'email_sms_to_customer' => [
                                'field_name' => 'cf_897',
                                'label' => 'Email And SMS to Customer',
                                'value' => $ticket['cf_897'] ?? '0'
                            ]
                        ]
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

            // Create notification entry in database for in-app notifications
            if (!empty($result['ticket_id'])) {
                require_once __DIR__ . '/../models/NotificationLog.php';
                
                $notificationLog = new NotificationLog();
                $assignedUserId = $data['assigned_to'] ?? $userId;
                
                // Create in-app notification entry
                $notificationLog->log(
                    $assignedUserId,
                    $result['ticket_id'],
                    'ticket_assigned',
                    "New Ticket Assigned",
                    $data['title'] ?? "Ticket #{$result['ticket_no']}",
                    null, // FCM token - will be null if push is sent separately
                    'sent', // Status
                    null,
                    null
                );
            }

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
     * Update existing ticket
     * PUT /tickets/{id}
     * Only Admin and NOC roles can update tickets
     */
    public function update($id)
    {
        // Get authenticated user
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $userId = $auth['user_id'];
            $isAdmin = $auth['is_admin'];
            $roleType = $auth['role_type'] ?? 'Others';
            $ticketId = (int)$id;

            // Check if user has permission to edit tickets
            // Only Admin (Management) and NOC roles can edit
            $canEdit = $isAdmin || in_array($roleType, ['Management', 'NOC']);

            if (!$canEdit) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'You do not have permission to edit tickets. Only Admin and NOC roles can update tickets.'
                ]);
                return;
            }

            // Verify ticket exists and user can access it
            $canSeeAllTickets = $isAdmin || in_array($roleType, ['NOC', 'Management']);
            $ticket = $this->ticketModel->getTicketById($ticketId, $userId, $canSeeAllTickets, $roleType);

            if (!$ticket) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Ticket not found or you do not have access to it'
                ]);
                return;
            }

            // Get JSON input
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'No data provided for update'
                ]);
                return;
            }

            // Prepare update data (only include fields that are provided)
            $data = [];
            
            // Basic fields
            if (isset($input['title'])) $data['title'] = $input['title'];
            if (isset($input['description'])) $data['description'] = $input['description'];
            if (isset($input['solution'])) $data['solution'] = $input['solution'];
            
            // Standard ticket fields
            if (isset($input['priority'])) $data['priority'] = $input['priority'];
            if (isset($input['category'])) $data['category'] = $input['category'];
            if (isset($input['status'])) $data['status'] = $input['status'];
            if (isset($input['severity'])) $data['severity'] = $input['severity'];
            
            // Relationships
            if (isset($input['account_id'])) $data['account_id'] = $input['account_id'];
            if (isset($input['contact_id'])) $data['contact_id'] = $input['contact_id'];
            if (isset($input['assigned_to'])) $data['assigned_to'] = $input['assigned_to'];
            
            // Time tracking
            if (isset($input['hours'])) $data['hours'] = $input['hours'];
            if (isset($input['days'])) $data['days'] = $input['days'];
            
            // Custom fields
            if (isset($input['organization_priority'])) $data['organization_priority'] = $input['organization_priority'];
            if (isset($input['services_issue'])) $data['services_issue'] = $input['services_issue'];
            if (isset($input['issue_end'])) $data['issue_end'] = $input['issue_end'];
            if (isset($input['end_time'])) $data['end_time'] = $input['end_time'];
            if (isset($input['closing_time'])) $data['closing_time'] = $input['closing_time'];
            if (isset($input['send_on_confirmation'])) $data['send_on_confirmation'] = $input['send_on_confirmation'];
            if (isset($input['email_sms_assigned_to'])) $data['email_sms_assigned_to'] = $input['email_sms_assigned_to'];
            if (isset($input['email_sms_to_customer'])) $data['email_sms_to_customer'] = $input['email_sms_to_customer'];
            if (isset($input['from_portal'])) $data['from_portal'] = $input['from_portal'];

            // Track if assignee changed for notification
            $oldAssignee = $ticket['smownerid'] ?? null;
            $newAssignee = $data['assigned_to'] ?? $oldAssignee;

            // Update ticket
            $updatedTicket = $this->ticketModel->updateTicket($ticketId, $data, $userId);

            // Send notification if assignee changed
            if (isset($data['assigned_to']) && $newAssignee != $oldAssignee && $newAssignee != $userId) {
                require_once __DIR__ . '/../models/NotificationLog.php';
                
                $notificationLog = new NotificationLog();
                $notificationLog->log(
                    $newAssignee,
                    $ticketId,
                    'ticket_assigned',
                    "Ticket Assigned to You",
                    $data['title'] ?? $ticket['title'] ?? "Ticket #{$ticket['ticket_no']}",
                    null,
                    'sent',
                    null,
                    null
                );
            }

            // Also notify if status changed to closed
            if (isset($data['status']) && $data['status'] === 'Closed' && $ticket['status'] !== 'Closed') {
                require_once __DIR__ . '/../models/NotificationLog.php';
                
                $notificationLog = new NotificationLog();
                $assignedUserId = $newAssignee ?? $oldAssignee ?? $userId;
                
                $notificationLog->log(
                    $assignedUserId,
                    $ticketId,
                    'ticket_updated',
                    "Ticket Closed",
                    $data['title'] ?? $ticket['title'] ?? "Ticket #{$ticket['ticket_no']}",
                    null,
                    'sent',
                    null,
                    null
                );
            }

            // Format response similar to show() method
            $response = [
                'success' => true,
                'message' => 'Ticket updated successfully',
                'data' => [
                    'summary' => [
                        'ticket_id' => $updatedTicket['ticketid'],
                        'ticket_no' => $updatedTicket['ticket_no'],
                        'title' => $updatedTicket['title'],
                        'status' => $updatedTicket['status'],
                        'priority' => $updatedTicket['priority'],
                        'severity' => $updatedTicket['severity'],
                        'category' => $updatedTicket['category'],
                        'assigned_to' => $updatedTicket['assigned_to_name'],
                        'created_by' => $updatedTicket['created_by_name'],
                        'account' => $updatedTicket['account_name'],
                        'contact' => $updatedTicket['contact_name'],
                        'created_time' => $updatedTicket['createdtime'],
                        'modified_time' => $updatedTicket['modifiedtime']
                    ],
                    'organization_details' => [
                        'name' => $updatedTicket['account_name'],
                        'phone' => $updatedTicket['account_phone'] ?? '',
                        'email' => $updatedTicket['account_email'] ?? '',
                        'website' => $updatedTicket['account_website'] ?? '',
                        'billing_address' => [
                            'street' => $updatedTicket['account_bill_street'] ?? '',
                            'city' => $updatedTicket['account_bill_city'] ?? '',
                            'state' => $updatedTicket['account_bill_state'] ?? '',
                            'country' => $updatedTicket['account_bill_country'] ?? '',
                            'postal_code' => $updatedTicket['account_bill_code'] ?? ''
                        ],
                        'shipping_address' => [
                            'street' => $updatedTicket['account_ship_street'] ?? '',
                            'city' => $updatedTicket['account_ship_city'] ?? '',
                            'state' => $updatedTicket['account_ship_state'] ?? '',
                            'country' => $updatedTicket['account_ship_country'] ?? '',
                            'postal_code' => $updatedTicket['account_ship_code'] ?? ''
                        ]
                    ],
                    'contact_details' => [
                        'name' => $updatedTicket['contact_name'],
                        'email' => $updatedTicket['contact_email'] ?? '',
                        'phone' => $updatedTicket['contact_phone'] ?? '',
                        'mobile' => $updatedTicket['contact_mobile'] ?? ''
                    ],
                    'details' => [
                        'description' => $updatedTicket['description'] ?? '',
                        'solution' => $updatedTicket['solution'] ?? '',
                        'hours' => $updatedTicket['hours'] ?? 0,
                        'days' => $updatedTicket['days'] ?? 0,
                        'custom_fields' => $this->extractCustomFields($updatedTicket),
                        'custom_fields_labeled' => [
                            'organization_priority' => [
                                'field_name' => 'cf_867',
                                'label' => 'Organization Priority',
                                'value' => $updatedTicket['cf_867'] ?? ''
                            ],
                            'services_issue' => [
                                'field_name' => 'cf_869',
                                'label' => 'Services Issue',
                                'value' => $updatedTicket['cf_869'] ?? ''
                            ],
                            'issue_end' => [
                                'field_name' => 'cf_871',
                                'label' => 'Issue End',
                                'value' => $updatedTicket['cf_871'] ?? ''
                            ],
                            'end_time' => [
                                'field_name' => 'cf_873',
                                'label' => 'End Time',
                                'value' => $updatedTicket['cf_873'] ?? null
                            ],
                            'send_on_confirmation' => [
                                'field_name' => 'cf_875',
                                'label' => 'Send On Confirmation',
                                'value' => $updatedTicket['cf_875'] ?? '0'
                            ],
                            'email_sms_assigned_to' => [
                                'field_name' => 'cf_877',
                                'label' => 'Email And SMS Assigned To',
                                'value' => $updatedTicket['cf_877'] ?? '0'
                            ],
                            'closing_time' => [
                                'field_name' => 'cf_879',
                                'label' => 'Closing Time',
                                'value' => $updatedTicket['cf_879'] ?? ''
                            ],
                            'email_sms_to_customer' => [
                                'field_name' => 'cf_897',
                                'label' => 'Email And SMS to Customer',
                                'value' => $updatedTicket['cf_897'] ?? '0'
                            ]
                        ]
                    ]
                ]
            ];

            http_response_code(200);
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
     * Get ticket comments
     * GET /tickets/{id}/comments
     */
    public function getComments($ticketId)
    {
        // Get authenticated user
        $auth = $this->authenticate();
        if (!$auth) return;

        try {
            $userId = $auth['user_id'];
            $isAdmin = $auth['is_admin'];
            $roleType = $auth['role_type'] ?? 'Others';

            // NOC and Management can see all tickets
            // Sales users will be filtered by their customers' accounts
            $canSeeAllTickets = $isAdmin || in_array($roleType, ['NOC', 'Management']);

            // Verify ticket exists and user has access - pass roleType for Sales filtering
            $ticket = $this->ticketModel->getTicketById($ticketId, $userId, $canSeeAllTickets, $roleType);

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

            echo json_encode([
                'success' => true,
                'count' => count($comments),
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
            $roleType = $auth['role_type'] ?? 'Others';

            // NOC and Management can see all tickets
            // Sales users will be filtered by their customers' accounts
            $canSeeAllTickets = $isAdmin || in_array($roleType, ['NOC', 'Management']);

            // Verify ticket exists and user has access - pass roleType for Sales filtering
            $ticket = $this->ticketModel->getTicketById($ticketId, $userId, $canSeeAllTickets, $roleType);

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



