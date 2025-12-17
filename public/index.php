<?php
/**
 * Vtiger CRM REST API
 * Entry Point
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 1 for development, 0 for production

// Increase timeout for dashboard APIs (aggressive)
set_time_limit(180); // 3 minutes
ini_set('max_execution_time', '180');
ini_set('memory_limit', '256M'); // Increase memory limit

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
require_once __DIR__ . '/../helpers/RoleHelper.php';
require_once __DIR__ . '/../helpers/CacheHelper.php';

// Load Logger with error handling
try {
    require_once __DIR__ . '/../helpers/Logger.php';
} catch (Exception $e) {
    error_log("Failed to load Logger: " . $e->getMessage());
}

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Ticket.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Organization.php';
require_once __DIR__ . '/../models/Contact.php';
require_once __DIR__ . '/../models/Picklist.php';
require_once __DIR__ . '/../models/MobileDashboard.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/TicketController.php';
require_once __DIR__ . '/../controllers/ProfileController.php';
require_once __DIR__ . '/../controllers/OrganizationController.php';
require_once __DIR__ . '/../controllers/ContactController.php';
require_once __DIR__ . '/../controllers/PicklistController.php';
require_once __DIR__ . '/../controllers/MobileDashboardController.php';
require_once __DIR__ . '/../controllers/NotificationController.php';

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

// Mobile Dashboard Routes (Role-based)
$router->get('/mobile/dashboard', function() {
    $controller = new MobileDashboardController();
    $controller->index();
});

// Test endpoint
$router->get('/mobile/dashboard/test', function() {
    $controller = new MobileDashboardController();
    $controller->test();
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

$router->get('/tickets/{id}/comments', function($id) {
    $controller = new TicketController();
    $controller->getComments($id);
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

// Notification Routes
$router->post('/notifications', function() {
    $controller = new NotificationController();
    $controller->create();
});

$router->get('/notifications', function() {
    $controller = new NotificationController();
    $controller->index();
});

$router->put('/notifications/{id}', function($id) {
    $controller = new NotificationController();
    $controller->update($id);
});

$router->get('/picklists/severities', function() {
    $controller = new PicklistController();
    $controller->severities();
});

$router->get('/picklists/organization-priority', function() {
    $controller = new PicklistController();
    $controller->organizationPriority();
});

// DEBUG: JWT decode endpoint
$router->get('/debug/jwt', function() {
    $token = JWT::extractToken();
    if (!$token) {
        echo json_encode(['success' => false, 'message' => 'No token provided']);
        return;
    }
    $payload = JWT::validateToken($token);
    echo json_encode([
        'success' => true,
        'token' => substr($token, 0, 50) . '...',
        'payload' => $payload
    ]);
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
            'GET /debug/jwt' => 'Debug JWT contents',
            'GET /dashboard' => 'Dashboard statistics',
            'GET /mobile/dashboard' => 'Mobile role-based dashboard',
            'GET /tickets' => 'List all tickets',
            'GET /tickets/{id}' => 'Get ticket details',
            'POST /tickets' => 'Create new ticket',
            'GET /tickets/{id}/comments' => 'Get ticket comments',
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
            'GET /picklists/organization-priority' => 'Get organization priority options',
            'GET /debug/logs' => 'View API logs (Debug only)',
            'GET /debug/logs/clear' => 'Clear API logs (Debug only)',
            'GET /debug/logs/download' => 'Download API logs (Debug only)'
        ]
    ]);
});

// ==================== DEBUG ENDPOINTS ====================
// WARNING: DISABLE IN PRODUCTION OR RESTRICT ACCESS!

// View recent logs
$router->get('/debug/logs', function() {
    header('Content-Type: text/html; charset=utf-8');
    
    $logFile = __DIR__ . '/../logs/api.log';
    
    // Get filter and lines parameters
    $filter = $_GET['filter'] ?? '';
    $lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 200;
    $refresh = isset($_GET['refresh']) ? (int)$_GET['refresh'] : 0;
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>API Logs Viewer</title>
        <style>
            body {
                font-family: 'Courier New', monospace;
                background: #1e1e1e;
                color: #d4d4d4;
                margin: 0;
                padding: 20px;
            }
            .header {
                background: #2d2d30;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 5px;
            }
            .controls {
                margin-bottom: 10px;
            }
            .controls input, .controls select, .controls button {
                padding: 8px 12px;
                margin-right: 10px;
                background: #3e3e42;
                border: 1px solid #555;
                color: #d4d4d4;
                border-radius: 3px;
            }
            .controls button {
                cursor: pointer;
                background: #0e639c;
            }
            .controls button:hover {
                background: #1177bb;
            }
            .log-container {
                background: #1e1e1e;
                padding: 15px;
                border: 1px solid #3e3e42;
                border-radius: 5px;
                overflow-x: auto;
            }
            .log-line {
                margin: 2px 0;
                padding: 2px 0;
                white-space: pre-wrap;
                word-wrap: break-word;
            }
            .log-line.ERROR { color: #f48771; font-weight: bold; }
            .log-line.WARN { color: #dcdcaa; }
            .log-line.INFO { color: #4fc1ff; }
            .log-line.DEBUG { color: #b5cea8; }
            .log-line.PERF { color: #c586c0; }
            .log-line.TIMER { color: #9cdcfe; }
            .log-line:hover { background: #2d2d30; }
            .stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 10px;
                margin-top: 10px;
            }
            .stat-box {
                background: #2d2d30;
                padding: 10px;
                border-radius: 5px;
                text-align: center;
            }
            .stat-label {
                font-size: 12px;
                color: #858585;
            }
            .stat-value {
                font-size: 20px;
                font-weight: bold;
                margin-top: 5px;
            }
            .no-logs {
                color: #858585;
                text-align: center;
                padding: 40px;
            }
        </style>
        <?php if ($refresh > 0): ?>
        <meta http-equiv="refresh" content="<?= $refresh ?>">
        <?php endif; ?>
    </head>
    <body>
        <div class="header">
            <h2>🔍 API Logs Viewer</h2>
            <div class="controls">
                <form method="GET" style="display: inline;">
                    <input type="text" name="filter" placeholder="Filter logs..." value="<?= htmlspecialchars($filter) ?>">
                    <select name="lines">
                        <option value="100" <?= $lines == 100 ? 'selected' : '' ?>>Last 100 lines</option>
                        <option value="200" <?= $lines == 200 ? 'selected' : '' ?>>Last 200 lines</option>
                        <option value="500" <?= $lines == 500 ? 'selected' : '' ?>>Last 500 lines</option>
                        <option value="1000" <?= $lines == 1000 ? 'selected' : '' ?>>Last 1000 lines</option>
                    </select>
                    <select name="refresh">
                        <option value="0" <?= $refresh == 0 ? 'selected' : '' ?>>No auto-refresh</option>
                        <option value="5" <?= $refresh == 5 ? 'selected' : '' ?>>Refresh every 5s</option>
                        <option value="10" <?= $refresh == 10 ? 'selected' : '' ?>>Refresh every 10s</option>
                        <option value="30" <?= $refresh == 30 ? 'selected' : '' ?>>Refresh every 30s</option>
                    </select>
                    <button type="submit">Apply</button>
                    <button type="button" onclick="location.href='?'">Clear Filters</button>
                </form>
            </div>
            
            <?php
            if (file_exists($logFile)) {
                $fileSize = round(filesize($logFile) / 1024, 2);
                $allLines = file($logFile);
                $totalLines = count($allLines);
                
                // Count log levels
                $errorCount = $warnCount = $infoCount = $perfCount = 0;
                foreach ($allLines as $line) {
                    if (strpos($line, '[ERROR]') !== false) $errorCount++;
                    if (strpos($line, '[WARN]') !== false) $warnCount++;
                    if (strpos($line, '[INFO]') !== false) $infoCount++;
                    if (strpos($line, '[PERF]') !== false) $perfCount++;
                }
                
                echo "<div class='stats'>";
                echo "<div class='stat-box'><div class='stat-label'>File Size</div><div class='stat-value'>{$fileSize} KB</div></div>";
                echo "<div class='stat-box'><div class='stat-label'>Total Lines</div><div class='stat-value'>{$totalLines}</div></div>";
                echo "<div class='stat-box'><div class='stat-label'>Errors</div><div class='stat-value' style='color: #f48771'>{$errorCount}</div></div>";
                echo "<div class='stat-box'><div class='stat-label'>Warnings</div><div class='stat-value' style='color: #dcdcaa'>{$warnCount}</div></div>";
                echo "<div class='stat-box'><div class='stat-label'>Info</div><div class='stat-value' style='color: #4fc1ff'>{$infoCount}</div></div>";
                echo "<div class='stat-box'><div class='stat-label'>Performance</div><div class='stat-value' style='color: #c586c0'>{$perfCount}</div></div>";
                echo "</div>";
            }
            ?>
        </div>
        
        <div class="log-container">
            <?php
            if (file_exists($logFile)) {
                $allLines = file($logFile);
                $lastLines = array_slice($allLines, -$lines);
                
                // Apply filter if provided
                if (!empty($filter)) {
                    $lastLines = array_filter($lastLines, function($line) use ($filter) {
                        return stripos($line, $filter) !== false;
                    });
                }
                
                if (empty($lastLines)) {
                    echo "<div class='no-logs'>No logs found matching your criteria</div>";
                } else {
                    foreach ($lastLines as $line) {
                        $line = htmlspecialchars($line);
                        
                        // Determine log level
                        $class = '';
                        if (strpos($line, '[ERROR]') !== false) $class = 'ERROR';
                        elseif (strpos($line, '[WARN]') !== false) $class = 'WARN';
                        elseif (strpos($line, '[INFO]') !== false) $class = 'INFO';
                        elseif (strpos($line, '[DEBUG]') !== false) $class = 'DEBUG';
                        elseif (strpos($line, '[PERF]') !== false) $class = 'PERF';
                        
                        echo "<div class='log-line {$class}'>{$line}</div>";
                    }
                }
            } else {
                echo "<div class='no-logs'>No log file found at: {$logFile}<br><br>Logs will be created when you make API requests.</div>";
            }
            ?>
        </div>
        
        <div style="text-align: center; margin-top: 20px; color: #858585; font-size: 12px;">
            Last updated: <?= date('Y-m-d H:i:s') ?>
            <?php if ($refresh > 0): ?>
                | Auto-refreshing every <?= $refresh ?> seconds
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
});

// Clear logs endpoint
$router->get('/debug/logs/clear', function() {
    $logFile = __DIR__ . '/../logs/api.log';
    
    if (file_exists($logFile)) {
        file_put_contents($logFile, '');
        echo json_encode([
            'success' => true,
            'message' => 'Logs cleared successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Log file not found'
        ]);
    }
});

// Download logs
$router->get('/debug/logs/download', function() {
    $logFile = __DIR__ . '/../logs/api.log';
    
    if (file_exists($logFile)) {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="api-logs-' . date('Y-m-d-His') . '.txt"');
        readfile($logFile);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Log file not found'
        ]);
    }
});

// ==================== END DEBUG ENDPOINTS ====================

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



