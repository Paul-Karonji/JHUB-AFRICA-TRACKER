<?php
// api/comments/submit.php - Updated comment submission with approval logic
header('Content-Type: application/json');
require_once '../../includes/init.php';
require_once '../../includes/mentor-consensus-functions.php';

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

    // Validate CSRF token for authenticated users
    if ($auth->isLoggedIn() && (!isset($input['csrf_token']) || !$auth->validateCSRFToken($input['csrf_token']))) {
        throw new Exception('Invalid security token');
    }

    // Validate required fields
    if (empty($input['project_id']) || empty($input['comment_text'])) {
        throw new Exception('Project ID and comment text are required');
    }

    $projectId = intval($input['project_id']);
    $commentText = trim($input['comment_text']);

    // Validate comment length
    if (strlen($commentText) < 10) {
        throw new Exception('Comment must be at least 10 characters long');
    }

    if (strlen($commentText) > 2000) {
        throw new Exception('Comment must not exceed 2000 characters');
    }

    // Get project info
    $project = $database->getRow("
        SELECT project_id, project_name, status 
        FROM projects 
        WHERE project_id = ? AND status = 'active'
    ", [$projectId]);

    if (!$project) {
        throw new Exception('Project not found or not active');
    }

    // Determine commenter information and approval status
    if ($auth->isLoggedIn()) {
        $userId = $auth->getUserId();
        $userType = $auth->getUserType();
        
        if ($userType === USER_TYPE_ADMIN) {
            $user = $database->getRow("SELECT username FROM admins WHERE admin_id = ?", [$userId]);
            $commenterType = 'admin';
            $commenterName = $user['username'];
            $commenterEmail = null; // Protect admin email
            $commenterId = $userId;
            $requiresApproval = false; // Admin comments auto-approved
            
        } elseif ($userType === USER_TYPE_MENTOR) {
            $user = $database->getRow("SELECT name, email FROM mentors WHERE mentor_id = ?", [$userId]);
            $commenterType = 'mentor';
            $commenterName = $user['name'];
            $commenterEmail = $user['email'];
            $commenterId = $userId;
            $requiresApproval = false; // Mentor comments auto-approved
            
        } elseif ($userType === USER_TYPE_PROJECT) {
            $user = $database->getRow("SELECT project_name as name, project_lead_email as email FROM projects WHERE project_id = ?", [$userId]);
            $commenterType = 'innovator';
            $commenterName = $user['name'];
            $commenterEmail = $user['email'];
            $commenterId = $userId;
            $requiresApproval = false; // Innovator comments auto-approved
            
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
        $requiresApproval = true; // Public comments require approval
        
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
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
        'is_approved' => $requiresApproval ? 0 : 1,
        'approved_at' => $requiresApproval ? null : date('Y-m-d H:i:s')
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

    // Send notification to admins for pending comments
    if ($requiresApproval) {
        // Notify admins about pending comment
        $adminEmails = $database->getColumn("SELECT email FROM admins WHERE is_active = 1 AND email IS NOT NULL");
        
        foreach ($adminEmails as $adminEmail) {
            sendEmailNotification(
                $adminEmail,
                'New Public Comment Pending Approval',
                "A new public comment from {$commenterName} ({$commenterEmail}) is pending approval on project '{$project['project_name']}'.\n\nComment preview: " . substr($commentText, 0, 100) . "...\n\nPlease log in to review and approve/reject this comment.\n\nBest regards,\nJHUB AFRICA System",
                NOTIFY_SYSTEM_ALERT
            );
        }
        
        $message = 'Comment submitted successfully! It will be visible after admin approval.';
    } else {
        $message = 'Comment posted successfully!';
    }

    // Get the posted comment with formatted date (if not requiring approval)
    $responseData = [
        'success' => true,
        'message' => $message,
        'comment_id' => $commentId,
        'requires_approval' => $requiresApproval
    ];

    if (!$requiresApproval) {
        $postedComment = $database->getRow("SELECT * FROM comments WHERE comment_id = ?", [$commentId]);
        $postedComment['created_at_formatted'] = formatDate($postedComment['created_at']);
        $responseData['comment'] = $postedComment;
    }

    echo json_encode($responseData);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    error_log('Comment submission error: ' . $e->getMessage());
}

// ================================================================================
// templates/comments-section.php - Updated comments display with approval system
// ================================================================================

<?php
// This code should replace the existing templates/comments-section.php
require_once dirname(__FILE__) . '/../includes/mentor-consensus-functions.php';

// Get current user info for comment visibility
$viewerType = $auth->isLoggedIn() ? $auth->getUserType() : null;
$viewerId = $auth->isLoggedIn() ? $auth->getUserId() : null;

// Get visible comments based on approval and privacy rules
$comments = getVisibleProjectComments($projectId, $viewerType, $viewerId);
$commentsCount = count($comments);

// Get pending comments count for admins
$pendingCount = 0;
if ($viewerType === USER_TYPE_ADMIN) {
    $pendingCount = $database->count('comments', 
        'commenter_type = "investor" AND is_approved = 0 AND is_deleted = 0'
    );
}
?>

<!-- Comments Section -->
<div class="card shadow mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">
            <i class="fas fa-comments me-2"></i>Comments & Feedback 
            <span class="badge bg-light text-dark float-end"><?php echo $commentsCount; ?></span>
            <?php if ($pendingCount > 0 && $viewerType === USER_TYPE_ADMIN): ?>
                <a href="<?php echo BASE_URL; ?>/dashboards/admin/moderate-comments.php" 
                   class="badge bg-warning text-dark ms-2">
                    <?php echo $pendingCount; ?> Pending
                </a>
            <?php endif; ?>
        </h5>
    </div>
    <div class="card-body">
        
        <!-- Comment Form for Authenticated Users -->
        <?php if ($auth->isLoggedIn()): ?>
        <div class="mb-4 p-3 bg-light rounded">
            <h6 class="mb-3"><i class="fas fa-edit me-2"></i>Leave a Comment</h6>
            <form id="commentForm" onsubmit="submitComment(event)">
                <input type="hidden" id="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                <input type="hidden" id="project_id" value="<?php echo $projectId; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Your Comment <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="comment_text" rows="4" 
                              placeholder="Share your feedback, questions, or suggestions..." 
                              required minlength="10" maxlength="2000"></textarea>
                    <div class="form-text">Minimum 10 characters, maximum 2000 characters</div>
                </div>
                
                <div id="commentAlert"></div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Post Comment
                </button>
            </form>
        </div>
        
        <?php else: ?>
        <!-- Public Comment Form -->
        <div class="mb-4 p-3 bg-light rounded">
            <h6 class="mb-3"><i class="fas fa-edit me-2"></i>Leave a Comment</h6>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Public comments require admin approval before being visible.
            </div>
            <form id="publicCommentForm" onsubmit="submitPublicComment(event)">
                <input type="hidden" id="project_id_public" value="<?php echo $projectId; ?>">
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Your Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="commenter_name" 
                               placeholder="Enter your full name" required maxlength="100">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Your Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="commenter_email" 
                               placeholder="Enter your email address" required maxlength="255">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Your Comment <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="public_comment_text" rows="4" 
                              placeholder="Share your feedback, questions, or suggestions..." 
                              required minlength="10" maxlength="2000"></textarea>
                    <div class="form-text">Minimum 10 characters, maximum 2000 characters</div>
                </div>
                
                <div id="publicCommentAlert"></div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Submit for Review
                </button>
            </form>
        </div>
        <?php endif; ?>
        
        <hr>
        
        <!-- Comments List -->
        <div id="commentsList">
            <?php if (empty($comments)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-comment-slash fa-3x mb-3"></i>
                    <p class="lead">No comments yet.</p>
                    <p>Be the first to share your thoughts!</p>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                <div class="comment-item mb-4 p-3 border-start border-4 border-<?php 
                    echo $comment['commenter_type'] === 'admin' ? 'danger' : 
                        ($comment['commenter_type'] === 'mentor' ? 'primary' : 
                        ($comment['commenter_type'] === 'innovator' ? 'success' : 'info')); 
                ?> bg-light">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <strong class="text-<?php 
                                echo $comment['commenter_type'] === 'admin' ? 'danger' : 
                                    ($comment['commenter_type'] === 'mentor' ? 'primary' : 
                                    ($comment['commenter_type'] === 'innovator' ? 'success' : 'info')); 
                            ?>">
                                <?php echo e($comment['display_name']); ?>
                            </strong>
                            <span class="badge bg-<?php 
                                echo $comment['commenter_type'] === 'admin' ? 'danger' : 
                                    ($comment['commenter_type'] === 'mentor' ? 'primary' : 
                                    ($comment['commenter_type'] === 'innovator' ? 'success' : 'info')); 
                            ?> ms-2">
                                <?php 
                                switch($comment['commenter_type']) {
                                    case 'admin': echo 'Administrator'; break;
                                    case 'mentor': echo 'Mentor'; break;
                                    case 'innovator': echo 'Project Team'; break;
                                    default: echo 'Public'; break;
                                }
                                ?>
                            </span>
                        </div>
                        <small class="text-muted">
                            <?php echo formatDate($comment['created_at']); ?>
                        </small>
                    </div>
                    <div class="comment-content">
                        <?php echo nl2br(e($comment['comment_text'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Updated JavaScript for comment submission
function submitComment(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const alertDiv = document.getElementById('commentAlert');
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Posting...';
    
    const formData = {
        csrf_token: document.getElementById('csrf_token').value,
        project_id: document.getElementById('project_id').value,
        comment_text: document.getElementById('comment_text').value
    };
    
    fetch('<?php echo BASE_URL; ?>/api/comments/submit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alertDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            
            if (!data.requires_approval) {
                // Reload page to show new comment
                setTimeout(() => location.reload(), 1000);
            } else {
                // Clear form for pending approval
                form.reset();
            }
        } else {
            alertDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alertDiv.innerHTML = '<div class="alert alert-danger">An error occurred while posting your comment.</div>';
    })
    .finally(() => {
        // Reset button
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Post Comment';
        
        // Clear alert after 5 seconds
        setTimeout(() => alertDiv.innerHTML = '', 5000);
    });
}

function submitPublicComment(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const alertDiv = document.getElementById('publicCommentAlert');
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
    
    const formData = {
        project_id: document.getElementById('project_id_public').value,
        commenter_name: document.getElementById('commenter_name').value,
        commenter_email: document.getElementById('commenter_email').value,
        comment_text: document.getElementById('public_comment_text').value
    };
    
    fetch('<?php echo BASE_URL; ?>/api/comments/submit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alertDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            form.reset();
        } else {
            alertDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alertDiv.innerHTML = '<div class="alert alert-danger">An error occurred while submitting your comment.</div>';
    })
    .finally(() => {
        // Reset button
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i>Submit for Review';
        
        // Clear alert after 10 seconds
        setTimeout(() => alertDiv.innerHTML = '', 10000);
    });
}
</script>
?>