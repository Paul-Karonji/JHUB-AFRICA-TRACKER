<?php
/**
 * api/comments/submit.php
 * UPDATED: Universal Comment Submission API with Approval Logic
 * 
 * Changes:
 * - Admin/Mentor/Innovator comments are auto-approved
 * - Investor (public) comments require admin approval
 * - Different success messages based on approval status
 */

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
    $isAutoApproved = false;
    $approvedBy = null;
    $approvedAt = null;
    
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
            $isAutoApproved = true; // Auto-approve admin comments
            $approvedBy = $userId;
            $approvedAt = date('Y-m-d H:i:s');
            
        } elseif ($userType === USER_TYPE_MENTOR) {
            $user = $database->getRow("SELECT name, email FROM mentors WHERE mentor_id = ?", [$userId]);
            $commenterType = 'mentor';
            $commenterName = $user['name'];
            $commenterEmail = $user['email'];
            $commenterId = $userId;
            $isAutoApproved = true; // Auto-approve mentor comments
            // For mentors, we'll set approved_by to NULL since they approved their own
            $approvedAt = date('Y-m-d H:i:s');
            
        } elseif ($userType === USER_TYPE_PROJECT) {
            $user = $database->getRow("SELECT project_name as name, project_lead_email as email FROM projects WHERE project_id = ?", [$userId]);
            $commenterType = 'innovator';
            $commenterName = $user['name'];
            $commenterEmail = $user['email'];
            $commenterId = $userId;
            $isAutoApproved = true; // Auto-approve innovator comments
            $approvedAt = date('Y-m-d H:i:s');
            
        } else {
            throw new Exception('Invalid user type');
        }
        
    } else {
        // Public user (investor) - requires approval
        if (empty($input['commenter_name']) || empty($input['commenter_email'])) {
            throw new Exception('Name and email are required for public comments');
        }
        
        $commenterType = 'investor';
        $commenterName = trim($input['commenter_name']);
        $commenterEmail = trim($input['commenter_email']);
        $commenterId = null;
        $isAutoApproved = false; // Investor comments need approval
        
        // Validate email
        if (!filter_var($commenterEmail, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }
    }

    // Prepare comment data with approval logic
    $commentData = [
        'project_id' => $projectId,
        'commenter_type' => $commenterType,
        'commenter_name' => $commenterName,
        'commenter_email' => $commenterEmail,
        'commenter_id' => $commenterId,
        'comment_text' => $commentText,
        'is_approved' => $isAutoApproved ? 1 : 0,
        'approved_by' => $approvedBy,
        'approved_at' => $approvedAt,
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
            ['comment_id' => $commentId, 'is_auto_approved' => $isAutoApproved]
        );
    }

    // If investor comment (needs approval), notify admins
    if (!$isAutoApproved) {
        // Get all active admins
        $admins = $database->getRows("SELECT admin_id, admin_name, username FROM admins WHERE is_active = 1");
        
        foreach ($admins as $admin) {
            // Create notification for each admin
            $database->insert('notifications', [
                'user_id' => $admin['admin_id'],
                'user_type' => 'admin',
                'title' => 'New Comment Pending Approval',
                'message' => "A new public comment from {$commenterName} needs approval on project: {$project['project_name']}",
                'notification_type' => 'warning',
                'action_url' => '/dashboards/admin/moderate-comments.php',
                'metadata' => json_encode([
                    'comment_id' => $commentId,
                    'project_id' => $projectId,
                    'commenter_email' => $commenterEmail
                ])
            ]);
        }
    }

    // Get the posted comment with formatted date
    $postedComment = $database->getRow("SELECT * FROM comments WHERE comment_id = ?", [$commentId]);
    $postedComment['created_at_formatted'] = formatDate($postedComment['created_at']);

    // Different messages based on approval status
    if ($isAutoApproved) {
        $message = 'Comment posted successfully!';
        $messageType = 'success';
    } else {
        $message = 'Comment submitted successfully! It will be visible after admin approval.';
        $messageType = 'info';
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'message_type' => $messageType,
        'is_approved' => $isAutoApproved,
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