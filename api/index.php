<?php
// api/index.php - Main API Router
header('Content-Type: application/json');

// CORS headers (if needed for external access)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../includes/init.php';

/**
 * API Router
 * Routes API requests to appropriate endpoints
 */

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Get route from query string or path
$route = $_GET['route'] ?? '';
$route = trim($route, '/');

// Parse route
$routeParts = explode('/', $route);
$resource = $routeParts[0] ?? '';
$action = $routeParts[1] ?? '';
$id = $routeParts[2] ?? null;

try {
    // Validate authentication for all API calls
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required',
            'error_code' => 'UNAUTHORIZED'
        ]);
        exit;
    }

    // Route to appropriate handler
    switch ($resource) {
        case 'applications':
            handleApplications($action, $id, $method);
            break;
            
        case 'projects':
            handleProjects($action, $id, $method);
            break;
            
        case 'mentors':
            handleMentors($action, $id, $method);
            break;
            
        case 'admins':
            handleAdmins($action, $id, $method);
            break;
            
        case 'system':
            handleSystem($action, $method);
            break;
            
        case 'health':
            // Health check endpoint
            echo json_encode([
                'success' => true,
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => SITE_VERSION ?? '1.0.0'
            ]);
            break;
            
        case '':
            // API documentation/welcome
            echo json_encode([
                'success' => true,
                'message' => 'JHUB AFRICA API',
                'version' => SITE_VERSION ?? '1.0.0',
                'documentation' => SITE_URL . '/docs/api',
                'endpoints' => [
                    'applications' => [
                        'POST /api/applications/review' => 'Review application (approve/reject)',
                        'POST /api/applications/submit' => 'Submit new application'
                    ],
                    'projects' => [
                        'GET /api/projects' => 'List all projects',
                        'GET /api/projects/{id}' => 'Get project details',
                        'POST /api/projects/remove-member' => 'Remove team member',
                        'POST /api/projects/terminate' => 'Terminate project'
                    ],
                    'mentors' => [
                        'GET /api/mentors' => 'List all mentors',
                        'POST /api/mentors/toggle-status' => 'Activate/deactivate mentor',
                        'POST /api/mentors/reset-password' => 'Reset mentor password'
                    ],
                    'admins' => [
                        'POST /api/admins/toggle-status' => 'Activate/deactivate admin',
                        'POST /api/admins/reset-password' => 'Reset admin password'
                    ],
                    'system' => [
                        'POST /api/system/test-email' => 'Send test email',
                        'POST /api/system/clear-logs' => 'Clear old activity logs'
                    ]
                ]
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Endpoint not found',
                'error_code' => 'ENDPOINT_NOT_FOUND',
                'requested_resource' => $resource
            ]);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => DEBUG_MODE ? $e->getMessage() : 'An error occurred',
        'error_code' => 'INTERNAL_ERROR'
    ]);
}

/**
 * Handle application endpoints
 */
function handleApplications($action, $id, $method) {
    global $auth;
    
    switch ($action) {
        case 'review':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            require_once __DIR__ . '/applications/review.php';
            break;
            
        case 'submit':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            require_once __DIR__ . '/applications/submit.php';
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Action not found']);
            break;
    }
}

/**
 * Handle project endpoints
 */
function handleProjects($action, $id, $method) {
    global $auth, $database;
    
    switch ($action) {
        case 'remove-member':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            require_once __DIR__ . '/projects/remove-member.php';
            break;
            
        case 'terminate':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            require_once __DIR__ . '/projects/terminate.php';
            break;
            
        case '':
        case 'list':
            // List all projects (GET)
            if ($method !== 'GET') {
                throw new Exception('Method not allowed');
            }
            
            $projects = $database->getRows("
                SELECT p.*, 
                       COUNT(DISTINCT pi.pi_id) as team_count,
                       COUNT(DISTINCT pm.mentor_id) as mentor_count
                FROM projects p
                LEFT JOIN project_innovators pi ON p.project_id = pi.project_id AND pi.is_active = 1
                LEFT JOIN project_mentors pm ON p.project_id = pm.project_id AND pm.is_active = 1
                GROUP BY p.project_id
                ORDER BY p.created_at DESC
                LIMIT 100
            ");
            
            echo json_encode([
                'success' => true,
                'data' => $projects,
                'count' => count($projects)
            ]);
            break;
            
        default:
            // Get single project by ID
            if ($id && $method === 'GET') {
                $project = $database->getRow("
                    SELECT p.*, 
                           COUNT(DISTINCT pi.pi_id) as team_count,
                           COUNT(DISTINCT pm.mentor_id) as mentor_count
                    FROM projects p
                    LEFT JOIN project_innovators pi ON p.project_id = pi.project_id AND pi.is_active = 1
                    LEFT JOIN project_mentors pm ON p.project_id = pm.project_id AND pm.is_active = 1
                    WHERE p.project_id = ?
                    GROUP BY p.project_id
                ", [intval($id)]);
                
                if ($project) {
                    echo json_encode([
                        'success' => true,
                        'data' => $project
                    ]);
                } else {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Project not found']);
                }
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Action not found']);
            }
            break;
    }
}

/**
 * Handle mentor endpoints
 */
function handleMentors($action, $id, $method) {
    global $auth, $database;
    
    switch ($action) {
        case 'toggle-status':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            require_once __DIR__ . '/mentors/toggle-status.php';
            break;
            
        case 'reset-password':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            require_once __DIR__ . '/mentors/reset-password.php';
            break;
            
        case '':
        case 'list':
            // List all mentors (GET)
            if ($method !== 'GET') {
                throw new Exception('Method not allowed');
            }
            
            $mentors = $database->getRows("
                SELECT m.*,
                       COUNT(DISTINCT pm.project_id) as project_count
                FROM mentors m
                LEFT JOIN project_mentors pm ON m.mentor_id = pm.mentor_id AND pm.is_active = 1
                WHERE m.is_active = 1
                GROUP BY m.mentor_id
                ORDER BY m.name ASC
            ");
            
            echo json_encode([
                'success' => true,
                'data' => $mentors,
                'count' => count($mentors)
            ]);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Action not found']);
            break;
    }
}

/**
 * Handle admin endpoints
 */
function handleAdmins($action, $id, $method) {
    switch ($action) {
        case 'toggle-status':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            require_once __DIR__ . '/admins/toggle-status.php';
            break;
            
        case 'reset-password':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            require_once __DIR__ . '/admins/reset-password.php';
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Action not found']);
            break;
    }
}

/**
 * Handle system endpoints
 */
function handleSystem($action, $method) {
    switch ($action) {
        case 'test-email':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            require_once __DIR__ . '/system/test-email.php';
            break;
            
        case 'clear-logs':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            require_once __DIR__ . '/system/clear-logs.php';
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Action not found']);
            break;
    }
}
?>