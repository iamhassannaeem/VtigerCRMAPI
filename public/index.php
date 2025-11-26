<?php
/**
 * Vtiger CRM REST API
 * Entry Point
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 1 for development, 0 for production

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Autoloader - Load all required files
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../helpers/JWT.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Organization.php';
require_once __DIR__ . '/../models/Contact.php';
require_once __DIR__ . '/../models/Picklist.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/TicketController.php';
require_once __DIR__ . '/../controllers/ProfileController.php';
require_once __DIR__ . '/../controllers/OrganizationController.php';
require_once __DIR__ . '/../controllers/ContactController.php';
require_once __DIR__ . '/../controllers/PicklistController.php';

// Initialize router
$router = new Router();

// ==================== ROUTES ====================

// Authentication Routes
$router->post('/auth/login', function() {
    $controller = new AuthController();
    $controller->login();
});

$router->get('/auth/verify', function() {
    $controller = new AuthController();
    $controller->verify();
});

// Dashboard Routes
$router->get('/dashboard', function() {
    $controller = new DashboardController();
    $controller->index();
});

// Ticket Routes
$router->get('/tickets', function() {
    $controller = new TicketController();
    $controller->index();
});

$router->get('/tickets/{id}', function($id) {
    $controller = new TicketController();
    $controller->show($id);
});

$router->post('/tickets', function() {
    $controller = new TicketController();
    $controller->create();
});

$router->post('/tickets/{id}/comments', function($id) {
    $controller = new TicketController();
    $controller->addComment($id);
});

// Profile Routes
$router->get('/me', function() {
    $controller = new ProfileController();
    $controller->me();
});

// Organization Routes
$router->get('/organizations', function() {
    $controller = new OrganizationController();
    $controller->index();
});

$router->get('/organizations/{id}', function($id) {
    $controller = new OrganizationController();
    $controller->show($id);
});

// Contact Routes
$router->get('/contacts', function() {
    $controller = new ContactController();
    $controller->index();
});

$router->get('/contacts/{id}', function($id) {
    $controller = new ContactController();
    $controller->show($id);
});

// Picklist Routes
$router->get('/picklists/priorities', function() {
    $controller = new PicklistController();
    $controller->priorities();
});

$router->get('/picklists/services-issue', function() {
    $controller = new PicklistController();
    $controller->servicesIssue();
});

$router->get('/picklists/statuses', function() {
    $controller = new PicklistController();
    $controller->statuses();
});

$router->get('/picklists/users', function() {
    $controller = new PicklistController();
    $controller->users();
});

$router->get('/picklists/issue-end', function() {
    $controller = new PicklistController();
    $controller->issueEnd();
});

$router->get('/picklists/categories', function() {
    $controller = new PicklistController();
    $controller->categories();
});

$router->get('/picklists/severities', function() {
    $controller = new PicklistController();
    $controller->severities();
});

$router->get('/picklists/organization-priority', function() {
    $controller = new PicklistController();
    $controller->organizationPriority();
});

// Health check route
$router->get('/', function() {
    echo json_encode([
        'success' => true,
        'message' => 'Vtiger CRM REST API',
        'version' => '1.0.0',
        'endpoints' => [
            'POST /auth/login' => 'User authentication',
            'GET /auth/verify' => 'Verify JWT token',
            'GET /dashboard' => 'Dashboard statistics',
            'GET /tickets' => 'List all tickets',
            'GET /tickets/{id}' => 'Get ticket details',
            'POST /tickets' => 'Create new ticket',
            'POST /tickets/{id}/comments' => 'Add comment to ticket',
            'GET /me' => 'Get user profile',
            'GET /organizations' => 'List all organizations',
            'GET /organizations/{id}' => 'Get organization details',
            'GET /contacts' => 'List all contacts',
            'GET /contacts/{id}' => 'Get contact details',
            'GET /picklists/priorities' => 'Get ticket priorities',
            'GET /picklists/services-issue' => 'Get services issue options',
            'GET /picklists/statuses' => 'Get ticket statuses',
            'GET /picklists/users' => 'Get active users',
            'GET /picklists/issue-end' => 'Get issue end options',
            'GET /picklists/categories' => 'Get ticket categories',
            'GET /picklists/severities' => 'Get ticket severities',
            'GET /picklists/organization-priority' => 'Get organization priority options'
        ]
    ]);
});

// 404 handler
$router->notFound(function() {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Endpoint not found'
    ]);
});

// ==================== EXCEPTION HANDLER ====================

set_exception_handler(function($exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $exception->getMessage()
    ]);
});

// ==================== DISPATCH ====================

try {
    $router->dispatch();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error',
        'error' => $e->getMessage()
    ]);
}



