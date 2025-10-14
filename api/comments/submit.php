<?php
// api/comments/submit.php
// Universal Comment Submission API (Works for Admin, Mentor, Project, Investor)

header('Content-Type: application/json');
require_once '../../includes/init.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid request data');
    }

    // Validate CSRF token
    if (!isset($input['csrf_token']) || !$auth->validateCSRFToken($input['csrf_token'])) {
        throw new Exception('Invalid security token');
    }

    // Validate required fields
    if (empty($input['project_id'])) {
        throw new Exception('Project ID is required');
    }

    if (empty($input['comment_text'])) {
        throw new Exception('Comment text is required');
    }

    $projectId = intval($input['project_id']);
    $commentText = trim($input['comment_text']);

    // Validate comment length
    if (strlen($commentText) < 10) {
        throw new Exception('Comment must be at least 10 characters long');
    }

    // Get project
    $project = $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);
    
    if (!$project) {
        throw new Exception('Project not found');
    }

    // Determine commenter type and details
    if ($auth->isLoggedIn()) {
        // Authenticated user (admin, mentor, or project)
        $userType = $auth->getUserType();
        $userId = $auth->getUserId();
        
        // Get user details based on type
        if ($userType === USER_TYPE_ADMIN) {
            $user = $database->getRow("SELECT admin_name as name, username as email FROM admins WHERE admin_id = ?", [$userId]);
            $commenterType = 'admin';
            $commenterName = $user['name'] ?? $user['email'];
            $commenterEmail = $user['email'];
            $commenterId = $userId;
            
        } elseif ($userType === USER_TYPE_MENTOR) {
            $user = $database->getRow("SELECT name, email FROM mentors WHERE mentor_id = ?", [$userId]);
            $commenterType = 'mentor';
            $commenterName = $user['name'];
            $commenterEmail = $user['email'];
            $commenterId = $userId;
            
        } elseif ($userType === USER_TYPE_PROJECT) {
            $user = $database->getRow("SELECT project_name as name, project_lead_email as email FROM projects WHERE project_id = ?", [$userId]);
            $commenterType = 'innovator';
            $commenterName = $user['name'];
            $commenterEmail = $user['email'];
            $commenterId = $userId;
            
        } else {
            throw new Exception('Invalid user type');
        }
        
    } else {
        // Public user (investor)
        if (empty($input['commenter_name']) || empty($input['commenter_email'])) {
            throw new Exception('Name and email are required for public comments');
        }
        
        $commenterType = 'investor';
        $commenterName = trim($input['commenter_name']);
        $commenterEmail = trim($input['commenter_email']);
        $commenterId = null;
        
        // Validate email
        if (!filter_var($commenterEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }
    }

    // Prepare comment data
    $commentData = [
        'project_id' => $projectId,
        'commenter_type' => $commenterType,
        'commenter_name' => $commenterName,
        'commenter_email' => $commenterEmail,
        'commenter_id' => $commenterId,
        'comment_text' => $commentText,
        'parent_comment_id' => !empty($input['parent_comment_id']) ? intval($input['parent_comment_id']) : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
    ];

    // Insert comment
    $commentId = $database->insert('comments', $commentData);

    if (!$commentId) {
        throw new Exception('Failed to post comment');
    }

    // Log activity for authenticated users
    if ($auth->isLoggedIn()) {
        logActivity(
            $userType,
            $userId,
            'comment_posted',
            "Posted comment on project: {$project['project_name']}",
            $projectId,
            ['comment_id' => $commentId]
        );
    }

    // Get the posted comment with formatted date
    $postedComment = $database->getRow("SELECT * FROM comments WHERE comment_id = ?", [$commentId]);
    $postedComment['created_at_formatted'] = formatDate($postedComment['created_at']);

    echo json_encode([
        'success' => true,
        'message' => 'Comment posted successfully!',
        'comment' => $postedComment
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    error_log('Comment submission error: ' . $e->getMessage());
}
?>