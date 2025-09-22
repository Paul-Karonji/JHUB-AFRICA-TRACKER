<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Main API Router and Entry Point
 * 
 * This file routes all API requests to appropriate handlers and provides
 * RESTful endpoints for the JHUB AFRICA system.
 * 
 * @author JHUB AFRICA Development Team
 * @version 1.0
 * @since 2024
 */

// Initialize the application
require_once __DIR__ . '/../includes/init.php';

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * API Router Class
 * 
 * Handles routing and processing of all API requests
 */
class APIRouter {
    
    /** @var Auth Authentication instance */
    private $auth;
    
    /** @var Project Project management instance */
    private $project;
    /** @var Mentor */
    private $mentor;

    /** @var Comment */
    private $comment;

    /** @var Rating */
    private $rating;

    /** @var Notification */
    private $notification;

    
    /** @var array Rate limiting storage */
    private $rateLimits = [];
    
    /**
     * Constructor - Initialize API router
     */
    public function __construct() {
        $this->auth = new Auth();
        $this->project = new Project();
        $this->mentor = new Mentor();
        $this->comment = new Comment();
        $this->rating = new Rating();
        $this->notification = new Notification();
    }
    
    /**
     * Main routing method
     */
    public function route() {
        try {
            // Apply rate limiting
            if (!$this->checkRateLimit()) {
                $this->sendResponse(['error' => 'Rate limit exceeded'], 429);
                return;
            }
            
            // Get request information
            $method = $_SERVER['REQUEST_METHOD'];
            $path = $this->getRequestPath();
            $segments = explode('/', trim($path, '/'));
            
            // Remove 'api' from segments if present
            if (!empty($segments[0]) && $segments[0] === 'api') {
                array_shift($segments);
            }
            
            // Route to appropriate handler
            $resource = $segments[0] ?? '';
            $id = $segments[1] ?? null;
            $subResource = $segments[2] ?? '';
            
            switch ($resource) {
                case 'auth':
                    $this->handleAuth($method, $segments);
                    break;
                    
                case 'projects':
                    $this->handleProjects($method, $id, $subResource, array_slice($segments, 3));
                    break;
                    
                case 'mentors':
                    $this->handleMentors($method, $id, $subResource, array_slice($segments, 3));
                    break;
                    
                case 'comments':
                    $this->handleComments($method, $id, $subResource);
                    break;
                    
                case 'ratings':
                    $this->handleRatings($method, $id, $subResource);
                    break;
                    
                case 'notifications':
                    $this->handleNotifications($method, $id, $subResource);
                    break;
                    
                case 'stats':
                    $this->handleStats($method, $segments);
                    break;
                    
                case 'search':
                    $this->handleSearch($method, $segments);
                    break;
                    
                default:
                    $this->sendResponse(['error' => 'Endpoint not found'], 404);
            }
            
        } catch (Exception $e) {
            logActivity('ERROR', "API Error: " . $e->getMessage(), [
                'path' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'user_type' => $this->auth->getUserType()
            ]);
            
            $this->sendResponse([
                'error' => DEVELOPMENT_MODE ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
    
    /**
     * Handle authentication endpoints
     */
    private function handleAuth($method, $segments) {
        $action = $segments[1] ?? '';
        
        switch ($action) {
            case 'login':
                if ($method !== 'POST') {
                    $this->sendResponse(['error' => 'Method not allowed'], 405);
                    return;
                }
                $this->handleLogin();
                break;
                
            case 'logout':
                if ($method !== 'POST') {
                    $this->sendResponse(['error' => 'Method not allowed'], 405);
                    return;
                }
                $this->handleLogout();
                break;
                
            case 'me':
                if ($method !== 'GET') {
                    $this->sendResponse(['error' => 'Method not allowed'], 405);
                    return;
                }
                $this->handleGetCurrentUser();
                break;
                
            case 'refresh':
                if ($method !== 'POST') {
                    $this->sendResponse(['error' => 'Method not allowed'], 405);
                    return;
                }
                $this->handleRefreshSession();
                break;
                
            default:
                $this->sendResponse(['error' => 'Auth endpoint not found'], 404);
        }
    }
    
    /**
     * Handle project endpoints
     */
    private function handleProjects($method, $id, $subResource, $additionalSegments) {
        switch ($method) {
            case 'GET':
                if ($id === null) {
                    $this->handleGetProjects();
                } elseif ($subResource === '') {
                    $this->handleGetProject($id);
                } elseif ($subResource === 'team') {
                    $this->handleGetProjectTeam($id);
                } elseif ($subResource === 'mentors') {
                    $this->handleGetProjectMentors($id);
                } elseif ($subResource === 'dashboard') {
                    $this->requireProjectAccess($id);
                    $this->handleGetProjectDashboard($id);
                } elseif ($subResource === 'activity') {
                    $this->handleGetProjectActivity($id);
                } else {
                    $this->sendResponse(['error' => 'Resource not found'], 404);
                }
                break;
                
            case 'POST':
                if ($id === null) {
                    $this->handleCreateProject();
                } elseif ($subResource === 'team') {
                    $this->requireProjectAccess($id);
                    $this->handleAddInnovator($id);
                } elseif ($subResource === 'mentors') {
                    $this->requireAuthentication();
                    if ($this->auth->getUserType() === 'mentor') {
                        $this->handleJoinProjectAsMentor($id);
                    } else {
                        $this->sendResponse(['error' => 'Only mentors can join projects'], 403);
                    }
                } else {
                    $this->sendResponse(['error' => 'Resource not found'], 404);
                }
                break;
                
            case 'PUT':
                if ($id !== null && $subResource === '') {
                    $this->requireProjectAccess($id);
                    $this->handleUpdateProject($id);
                } else {
                    $this->sendResponse(['error' => 'Resource not found'], 404);
                }
                break;
                
            case 'DELETE':
                if ($id !== null && $subResource === 'team' && !empty($additionalSegments[0])) {
                    $this->requireProjectAccess($id);
                    $this->handleRemoveInnovator($id, $additionalSegments[0]);
                } elseif ($id !== null && $subResource === 'terminate') {
                    $this->requireAdminAccess();
                    $this->handleTerminateProject($id);
                } else {
                    $this->sendResponse(['error' => 'Resource not found'], 404);
                }
                break;
                
            default:
                $this->sendResponse(['error' => 'Method not allowed'], 405);
        }
    }
    
    /**
     * Handle mentor endpoints
     */
    private function handleMentors($method, $id, $subResource, $additionalSegments) {
        switch ($method) {
            case 'GET':
                if ($id === null) {
                    $this->requireAdminAccess();
                    $this->handleGetMentors();
                } elseif ($subResource === 'projects') {
                    $this->requireMentorAccess($id);
                    $this->handleGetMentorProjects($id);
                } elseif ($subResource === 'available-projects') {
                    $this->requireMentorAccess($id);
                    $this->handleGetAvailableProjects($id);
                } else {
                    $this->sendResponse(['error' => 'Resource not found'], 404);
                }
                break;
                
            case 'POST':
                if ($id === null) {
                    $this->requireAdminAccess();
                    $this->handleRegisterMentor();
                } else {
                    $this->sendResponse(['error' => 'Resource not found'], 404);
                }
                break;
                
            default:
                $this->sendResponse(['error' => 'Method not allowed'], 405);
        }
    }
    
    /**
     * Handle comment endpoints
     */
        private function handleComments($method, $id, $subResource) {
        switch ($method) {
            case 'GET':
                if ($id === null) {
                    $projectId = $_GET['project_id'] ?? null;
                    if (!$projectId) {
                        $this->sendResponse(['error' => 'project_id is required'], 400);
                        return;
                    }
                    $includeReplies = ($_GET['include_replies'] ?? '1') !== '0';
                    $limit = $_GET['limit'] ?? null;
                    $result = $this->comment->getProjectComments($projectId, $includeReplies, 'newest_first', $limit);
                    $this->sendResponse($result);
                } else {
                    $comment = $this->comment->getComment($id);
                    if (!$comment) {
                        $this->sendResponse(['error' => 'Comment not found'], 404);
                        return;
                    }
                    $this->sendResponse(['success' => true, 'comment' => $comment]);
                }
                break;
            case 'POST':
                $data = $this->getRequestData();
                try {
                    requireKeys($data, ['project_id', 'comment_text']);
                    $userType = 'public';
                    $userId = null;
                    $commenterName = $data['commenter_name'] ?? null;
                    if ($this->auth->isAuthenticated()) {
                        $userType = $this->auth->getUserType();
                        $userId = $this->auth->getUserId();
                        if ($userType === 'project') {
                            $commenterName = $_SESSION['project_name'] ?? 'Project Team';
                        } elseif ($userType === 'mentor') {
                            $commenterName = $_SESSION['name'] ?? 'Mentor';
                        } elseif ($userType === 'admin') {
                            $commenterName = $_SESSION['username'] ?? 'Admin';
                        }
                    } else {
                        $userType = $data['user_type'] ?? 'public';
                    }
                    $result = $this->comment->addComment(
                        $data['project_id'],
                        $userType,
                        $userId,
                        $data['comment_text'],
                        $data['parent_id'] ?? null,
                        $commenterName
                    );
                    $status = $result['success'] ? 201 : 400;
                    $this->sendResponse($result, $status);
                } catch (Exception $e) {
                    $this->sendResponse(['success' => false, 'error' => $e->getMessage()], 400);
                }
                break;
            case 'PUT':
                if ($id === null) {
                    $this->sendResponse(['error' => 'Comment ID required'], 400);
                    return;
                }
                if (!$this->auth->isAuthenticated()) {
                    $this->sendResponse(['error' => 'Authentication required'], 401);
                    return;
                }
                $data = $this->getRequestData();
                $userType = $this->auth->getUserType();
                $userId = $this->auth->getUserId();
                $result = $this->comment->updateComment($id, $data['comment_text'] ?? '', $userType, $userId);
                $this->sendResponse($result, $result['success'] ? 200 : 400);
                break;
            case 'DELETE':
                if ($id === null) {
                    $this->sendResponse(['error' => 'Comment ID required'], 400);
                    return;
                }
                if (!$this->auth->isAuthenticated()) {
                    $this->sendResponse(['error' => 'Authentication required'], 401);
                    return;
                }
                $userType = $this->auth->getUserType();
                $userId = $this->auth->getUserId();
                $result = $this->comment->deleteComment($id, $userType, $userId);
                $this->sendResponse($result, $result['success'] ? 200 : 400);
                break;
            default:
                $this->sendResponse(['error' => 'Method not allowed'], 405);
        }
    }
/**
     * Handle rating endpoints
     */
        private function handleRatings($method, $id, $subResource) {
        switch ($method) {
            case 'GET':
                if ($id !== null) {
                    if ($subResource === 'timeline') {
                        $result = $this->rating->getProjectTimeline($id);
                        $this->sendResponse($result);
                    } elseif ($subResource === 'latest') {
                        $result = $this->rating->getLatestRating($id);
                        $this->sendResponse(['success' => true, 'rating' => $result]);
                    } elseif ($subResource === 'can-rate') {
                        $this->requireMentorAccess($this->auth->getUserId());
                        $mentorId = $this->auth->getUserId();
                        $result = $this->rating->canMentorRateProject($id, $mentorId);
                        $this->sendResponse($result);
                    } else {
                        $result = $this->rating->getProjectRatings($id);
                        $this->sendResponse($result);
                    }
                } elseif (isset($_GET['mentor_id'])) {
                    $result = $this->rating->getRatingsByMentor($_GET['mentor_id']);
                    $this->sendResponse($result);
                } else {
                    $result = $this->rating->getSystemRatingStats();
                    $this->sendResponse(['success' => true, 'stats' => $result]);
                }
                break;
            case 'POST':
                $this->requireAuthentication();
                if ($this->auth->getUserType() !== 'mentor') {
                    $this->sendResponse(['error' => 'Only mentors can update ratings'], 403);
                    return;
                }
                $data = $this->getRequestData();
                try {
                    requireKeys($data, ['project_id', 'stage', 'percentage']);
                    $mentorId = $this->auth->getUserId();
                    $result = $this->rating->updateProjectRating(
                        $data['project_id'],
                        $mentorId,
                        (int) $data['stage'],
                        (int) $data['percentage'],
                        $data['notes'] ?? null
                    );
                    $status = $result['success'] ? 200 : 400;
                    $this->sendResponse($result, $status);
                } catch (Exception $e) {
                    $this->sendResponse(['success' => false, 'error' => $e->getMessage()], 400);
                }
                break;
            default:
                $this->sendResponse(['error' => 'Method not allowed'], 405);
        }
    }
/**
     * Handle notification endpoints
     */
        private function handleNotifications($method, $id, $subResource) {
        $this->requireAuthentication();
        $userType = $this->auth->getUserType();
        $userId = $this->auth->getUserId();

        switch ($method) {
            case 'GET':
                if ($subResource === 'unread-count') {
                    $count = $this->notification->countUnread($userType, $userId);
                    $this->sendResponse(['success' => true, 'unread' => $count]);
                    return;
                }
                $options = [
                    'limit' => (int) ($_GET['limit'] ?? 20),
                    'offset' => (int) ($_GET['offset'] ?? 0),
                    'unread_only' => ($_GET['unread_only'] ?? '0') === '1'
                ];
                $result = $this->notification->getNotifications($userType, $userId, $options);
                $this->sendResponse($result);
                break;
            case 'POST':
                $data = $this->getRequestData();
                if ($subResource === 'mark-read' || isset($data['notification_id']) || $id !== null) {
                    $notificationId = $data['notification_id'] ?? $id;
                    if (!$notificationId) {
                        $this->sendResponse(['error' => 'notification_id is required'], 400);
                        return;
                    }
                    $result = $this->notification->markAsRead($notificationId, $userType, $userId);
                    $this->sendResponse($result);
                } elseif ($subResource === 'mark-all-read') {
                    $result = $this->notification->markAllAsRead($userType, $userId);
                    $this->sendResponse($result);
                } else {
                    if ($userType !== 'admin') {
                        $this->sendResponse(['error' => 'Only administrators can create notifications'], 403);
                        return;
                    }
                    try {
                        requireKeys($data, ['recipient_type', 'title', 'message']);
                        $result = $this->notification->create($data);
                        $status = $result['success'] ? 201 : 400;
                        $this->sendResponse($result, $status);
                    } catch (Exception $e) {
                        $this->sendResponse(['success' => false, 'error' => $e->getMessage()], 400);
                    }
                }
                break;
            case 'DELETE':
                if (!$id) {
                    $this->sendResponse(['error' => 'Notification ID required'], 400);
                    return;
                }
                $result = $this->notification->delete($id, $userType, $userId);
                $this->sendResponse($result, $result['success'] ? 200 : 400);
                break;
            default:
                $this->sendResponse(['error' => 'Method not allowed'], 405);
        }
    }
/**
     * Handle statistics endpoints
     */
    private function handleStats($method, $segments) {
        if ($method !== 'GET') {
            $this->sendResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        $this->handleGetStats();
    }
    
    /**
     * Handle search endpoints
     */
    private function handleSearch($method, $segments) {
        if ($method !== 'GET' && $method !== 'POST') {
            $this->sendResponse(['error' => 'Method not allowed'], 405);
            return;
        }
        
        $this->handleSearchProjects();
    }
    
    // ================================================
    // AUTHENTICATION HANDLERS
    // ================================================
    
    private function handleLogin() {
        $data = $this->getRequestData();
        
        if (empty($data['login_type'])) {
            $this->sendResponse(['error' => 'Login type is required'], 400);
            return;
        }
        
        switch ($data['login_type']) {
            case 'admin':
                $result = $this->auth->loginAdmin($data['username'] ?? '', $data['password'] ?? '');
                break;
                
            case 'mentor':
                $result = $this->auth->loginMentor($data['email'] ?? '', $data['password'] ?? '');
                break;
                
            case 'project':
                $result = $this->auth->loginProject($data['profile_name'] ?? '', $data['password'] ?? '');
                break;
                
            default:
                $this->sendResponse(['error' => 'Invalid login type'], 400);
                return;
        }
        
        $this->sendResponse($result);
    }
    
    private function handleLogout() {
        $result = $this->auth->logout();
        $this->sendResponse($result);
    }
    
    private function handleGetCurrentUser() {
        if (!$this->auth->isAuthenticated()) {
            $this->sendResponse(['error' => 'Not authenticated'], 401);
            return;
        }
        
        $userInfo = $this->auth->getUserInfo();
        $this->sendResponse(['success' => true, 'user' => $userInfo]);
    }
    
    private function handleRefreshSession() {
        if (!$this->auth->isAuthenticated()) {
            $this->sendResponse(['error' => 'Not authenticated'], 401);
            return;
        }
        
        $refreshed = $this->auth->refreshUserSession();
        $this->sendResponse([
            'success' => $refreshed,
            'message' => $refreshed ? 'Session refreshed' : 'Failed to refresh session'
        ]);
    }
    
    // ================================================
    // PROJECT HANDLERS
    // ================================================
    
    private function handleGetProjects() {
        $filters = $_GET;
        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? AppConfig::PROJECTS_PER_PAGE);
        
        $result = $this->project->getAllProjects($filters, $page, $perPage);
        $this->sendResponse($result);
    }
    
    private function handleGetProject($id) {
        $includeTeam = $_GET['include_team'] ?? true;
        $includeMentors = $_GET['include_mentors'] ?? true;
        
        $result = $this->project->getProject($id, $includeTeam, $includeMentors);
        $this->sendResponse($result);
    }
    
    private function handleCreateProject() {
        $data = $this->getRequestData();
        $result = $this->project->createProject($data);
        $this->sendResponse($result, $result['success'] ? 201 : 400);
    }
    
    private function handleUpdateProject($id) {
        $data = $this->getRequestData();
        $updatedBy = $this->auth->getUserId();
        
        $result = $this->project->updateProject($id, $data, $updatedBy);
        $this->sendResponse($result);
    }
    
    private function handleGetProjectTeam($id) {
        $result = $this->project->getProjectTeam($id);
        $this->sendResponse($result);
    }
    
    private function handleAddInnovator($id) {
        $data = $this->getRequestData();
        $addedBy = $this->auth->getUserId();
        
        $result = $this->project->addInnovator($id, $data, $addedBy);
        $this->sendResponse($result, $result['success'] ? 201 : 400);
    }
    
    private function handleRemoveInnovator($projectId, $innovatorId) {
        $removedBy = $this->auth->getUserId();
        
        $result = $this->project->removeInnovator($projectId, $innovatorId, $removedBy);
        $this->sendResponse($result);
    }
    
    private function handleGetProjectMentors($id) {
        $result = $this->project->getProjectMentors($id);
        $this->sendResponse($result);
    }
    
    private function handleJoinProjectAsMentor($id) {
        $mentorId = $this->auth->getUserId();
        
        $result = $this->project->assignMentor($id, $mentorId, true);
        $this->sendResponse($result, $result['success'] ? 201 : 400);
    }
    
    private function handleGetProjectDashboard($id) {
        $result = $this->project->getProjectDashboardData($id);
        $this->sendResponse($result);
    }
    
    private function handleGetProjectActivity($id) {
        $limit = (int)($_GET['limit'] ?? 20);
        
        $result = $this->project->getProjectActivityLog($id, $limit);
        $this->sendResponse($result);
    }
    
    private function handleTerminateProject($id) {
        $data = $this->getRequestData();
        $adminId = $this->auth->getUserId();
        $reason = $data['reason'] ?? null;
        
        $result = $this->project->terminateProject($id, $adminId, $reason);
        $this->sendResponse($result);
    }
    
    // ================================================
    // MENTOR HANDLERS
    // ================================================
    
    private function handleGetMentors() {
        $filters = [
            'page' => (int) ($_GET['page'] ?? 1),
            'per_page' => (int) ($_GET['per_page'] ?? AppConfig::MENTORS_PER_PAGE),
            'search' => $_GET['search'] ?? null,
            'active_only' => ($_GET['active_only'] ?? '0') === '1'
        ];

        $result = $this->mentor->getMentors($filters);
        $this->sendResponse($result);
    }
private function handleRegisterMentor() {
        $data = $this->getRequestData();
        $adminId = $this->auth->getUserId();

        try {
            $result = $this->mentor->registerMentor($data, $adminId);
            $status = $result['success'] ? 201 : 400;
            $this->sendResponse($result, $status);
        } catch (Exception $e) {
            $this->sendResponse(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }
private function handleGetMentorProjects($mentorId) {
        $activeOnly = $_GET['active_only'] ?? true;
        
        $result = $this->project->getProjectsByMentor($mentorId, $activeOnly);
        $this->sendResponse($result);
    }
    
    private function handleGetAvailableProjects($mentorId) {
        $result = $this->project->getAvailableProjectsForMentor($mentorId);
        $this->sendResponse($result);
    }
    
    // ================================================
    // STATISTICS HANDLERS
    // ================================================
    
    private function handleGetStats() {
        $result = $this->project->getSystemStats();
        $this->sendResponse(['success' => true, 'stats' => $result]);
    }
    
    // ================================================
    // SEARCH HANDLERS
    // ================================================
    
    private function handleSearchProjects() {
        $criteria = $this->getRequestData();
        
        // Also check GET parameters
        if (empty($criteria) && !empty($_GET)) {
            $criteria = $_GET;
        }
        
        $result = $this->project->searchProjects($criteria);
        $this->sendResponse($result);
    }
    
    // ================================================
    // UTILITY METHODS
    // ================================================
    
    /**
     * Get request path
     */
    private function getRequestPath() {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($path, PHP_URL_PATH);
        return $path;
    }
    
    /**
     * Get request data from JSON or form
     */
    private function getRequestData() {
        $input = file_get_contents('php://input');
        
        if (!empty($input)) {
            $data = json_decode($input, true);
            if ($data !== null) {
                return $data;
            }
        }
        
        return $_POST;
    }
    
    /**
     * Send JSON response
     */
    private function sendResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    /**
     * Require authentication
     */
    private function requireAuthentication() {
        if (!$this->auth->isAuthenticated()) {
            $this->sendResponse(['error' => 'Authentication required'], 401);
            exit;
        }
    }
    
    /**
     * Require admin access
     */
    private function requireAdminAccess() {
        $this->requireAuthentication();
        
        if ($this->auth->getUserType() !== 'admin') {
            $this->sendResponse(['error' => 'Admin access required'], 403);
            exit;
        }
    }
    
    /**
     * Require mentor access
     */
    private function requireMentorAccess($mentorId = null) {
        $this->requireAuthentication();
        
        if ($this->auth->getUserType() !== 'mentor') {
            $this->sendResponse(['error' => 'Mentor access required'], 403);
            exit;
        }
        
        // If specific mentor ID provided, ensure it matches current user
        if ($mentorId !== null && $this->auth->getUserId() != $mentorId) {
            $this->sendResponse(['error' => 'Access denied'], 403);
            exit;
        }
    }
    
    /**
     * Require project access
     */
    private function requireProjectAccess($projectId = null) {
        $this->requireAuthentication();
        
        if ($this->auth->getUserType() !== 'project') {
            $this->sendResponse(['error' => 'Project access required'], 403);
            exit;
        }
        
        // If specific project ID provided, ensure it matches current user's project
        if ($projectId !== null && $this->auth->getProjectId() != $projectId) {
            $this->sendResponse(['error' => 'Access denied to this project'], 403);
            exit;
        }
    }
    
    /**
     * Check rate limiting
     */
    private function checkRateLimit() {
        if (!AppConfig::isFeatureEnabled('api_rate_limiting')) {
            return true;
        }
        
        $clientIp = getClientIP();
        $currentTime = time();
        $timeWindow = 3600; // 1 hour
        $maxRequests = 1000; // Per hour
        
        // Initialize rate limit data
        if (!isset($this->rateLimits[$clientIp])) {
            $this->rateLimits[$clientIp] = [];
        }
        
        // Clean old requests
        $this->rateLimits[$clientIp] = array_filter(
            $this->rateLimits[$clientIp],
            function($timestamp) use ($currentTime, $timeWindow) {
                return ($currentTime - $timestamp) < $timeWindow;
            }
        );
        
        // Check if limit exceeded
        if (count($this->rateLimits[$clientIp]) >= $maxRequests) {
            return false;
        }
        
        // Record this request
        $this->rateLimits[$clientIp][] = $currentTime;
        
        return true;
    }
}

// ================================================
// MAIN EXECUTION
// ================================================

try {
    $router = new APIRouter();
    $router->route();
} catch (Throwable $e) {
    logActivity('CRITICAL', "API Fatal Error: " . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => DEVELOPMENT_MODE ? $e->getMessage() : 'An unexpected error occurred'
    ]);
}





