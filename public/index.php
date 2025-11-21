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
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/TicketController.php';
require_once __DIR__ . '/../controllers/ProfileController.php';

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
            'GET /me' => 'Get user profile'
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

