<?php
/**
 * templates/comments-section.php
 * FIXED: Universal Comments Section with Better Debugging
 * 
 * Required variables:
 * - $projectId: The project ID
 * - $auth: Auth instance
 * - $database: Database instance
 */

if (!isset($projectId)) {
    die('Project ID is required for comments section');
}

// Determine comment visibility based on user type
$isAdmin = $auth->isLoggedIn() && $auth->getUserType() === USER_TYPE_ADMIN;
$isMentor = $auth->isLoggedIn() && $auth->getUserType() === USER_TYPE_MENTOR;
$isProject = $auth->isLoggedIn() && $auth->getUserType() === USER_TYPE_PROJECT;
$isAuthenticated = $auth->isLoggedIn();

// DEBUG: Log current user info
if ($isAuthenticated) {
    error_log("Comments Debug - User Type: " . $auth->getUserType() . ", User ID: " . $auth->getUserId());
}

if ($isAdmin) {
    // Admins see ALL approved comments + indicator for pending ones
    $comments = $database->getRows("
        SELECT * FROM comments 
        WHERE project_id = ? 
          AND is_deleted = 0 
          AND parent_comment_id IS NULL
          AND is_approved = 1
        ORDER BY created_at DESC
    ", [$projectId]);
    
    // Get pending count for admin indicator
    $pendingCount = $database->count(
        'comments',
        'project_id = ? AND is_deleted = 0 AND is_approved = 0',
        [$projectId]
    );
    
    // DEBUG
    error_log("Admin viewing comments. Approved: " . count($comments) . ", Pending: " . $pendingCount);
    
} elseif ($isAuthenticated) {
    // Authenticated users (mentor/innovator) see all approved comments
    $comments = $database->getRows("
        SELECT * FROM comments 
        WHERE project_id = ? 
          AND is_deleted = 0 
          AND parent_comment_id IS NULL
          AND is_approved = 1
        ORDER BY created_at DESC
    ", [$projectId]);
    
    // DEBUG
    error_log("Authenticated user (" . $auth->getUserType() . ") viewing " . count($comments) . " approved comments");
    
} else {
    // Public users see ONLY approved investor comments
    $comments = $database->getRows("
        SELECT * FROM comments 
        WHERE project_id = ? 
          AND is_deleted = 0 
          AND parent_comment_id IS NULL
          AND is_approved = 1
          AND commenter_type = 'investor'
        ORDER BY created_at DESC
    ", [$projectId]);
}

$commentsCount = count($comments);
?>

<!-- Comments Section -->
<div class="card shadow mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">
            <i class="fas fa-comments me-2"></i>Comments & Feedback 
            <span class="badge bg-light text-dark float-end"><?php echo $commentsCount; ?></span>
        </h5>
    </div>
    <div class="card-body">
        
        <!-- Admin Pending Comments Notice -->
        <?php if ($isAdmin && isset($pendingCount) && $pendingCount > 0): ?>
        <div class="alert alert-warning mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong><?php echo $pendingCount; ?></strong> comment<?php echo $pendingCount > 1 ? 's' : ''; ?> 
            pending approval.
            <a href="<?php echo SITE_URL; ?>/dashboards/admin/moderate-comments.php?project_id=<?php echo $projectId; ?>" 
               class="btn btn-sm btn-warning ms-2">
                <i class="fas fa-gavel"></i> Review Now
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Comment Form for Authenticated Users -->
        <?php if ($auth->isLoggedIn()): ?>
        <div class="mb-4 p-3 bg-light rounded">
            <h6 class="mb-3">
                <i class="fas fa-edit me-2"></i>Leave a Comment
                <?php if ($isAdmin): ?>
                    <span class="badge bg-danger ms-2">Admin</span>
                <?php elseif ($isMentor): ?>
                    <span class="badge bg-primary ms-2">Mentor</span>
                <?php elseif ($isProject): ?>
                    <span class="badge bg-success ms-2">Innovator</span>
                <?php endif; ?>
            </h6>
            
            <div class="alert alert-info mb-3">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> Your comment will be automatically approved and visible immediately.
            </div>
            
            <form id="commentForm" onsubmit="submitComment(event)">
                <input type="hidden" id="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                <input type="hidden" id="project_id" value="<?php echo $projectId; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Your Comment <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="comment_text" rows="4" 
                              placeholder="Share your feedback, questions, or suggestions..." 
                              required minlength="10"></textarea>
                    <div class="form-text">Minimum 10 characters. Your comment will be posted as: 
                        <strong>
                        <?php 
                        if ($isAdmin) {
                            $user = $database->getRow("SELECT admin_name FROM admins WHERE admin_id = ?", [$auth->getUserId()]);
                            echo htmlspecialchars($user['admin_name'] ?? 'Admin');
                        } elseif ($isMentor) {
                            $user = $database->getRow("SELECT name FROM mentors WHERE mentor_id = ?", [$auth->getUserId()]);
                            echo htmlspecialchars($user['name'] ?? 'Mentor');
                        } elseif ($isProject) {
                            $user = $database->getRow("SELECT project_lead_name FROM projects WHERE project_id = ?", [$auth->getUserId()]);
                            echo htmlspecialchars($user['project_lead_name'] ?? 'Innovator');
                        }
                        ?>
                        </strong>
                    </div>
                </div>
                
                <div id="commentAlert"></div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Post Comment
                </button>
            </form>
        </div>
        <?php endif; ?>
        
        <hr>
        
        <!-- Public Comment Notice -->
        <?php if (!$isAuthenticated): ?>
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i>
            Public comments are welcome! However, they will only be visible after admin approval to maintain quality and prevent spam.
        </div>
        <?php endif; ?>
        
        <!-- Comments List -->
        <div id="commentsList">
            <?php if (empty($comments)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-comment-slash fa-3x mb-3"></i>
                    <p class="lead">No comments yet.</p>
                    <?php if ($auth->isLoggedIn()): ?>
                    <p>Be the first to share your thoughts!</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                <div class="comment-item mb-4 p-3 border-start border-4 border-<?php 
                    echo $comment['commenter_type'] === 'admin' ? 'danger' : 
                        ($comment['commenter_type'] === 'mentor' ? 'primary' : 
                        ($comment['commenter_type'] === 'innovator' ? 'success' : 'info')); 
                ?> bg-white shadow-sm rounded">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong class="text-<?php 
                                echo $comment['commenter_type'] === 'admin' ? 'danger' : 
                                    ($comment['commenter_type'] === 'mentor' ? 'primary' : 
                                    ($comment['commenter_type'] === 'innovator' ? 'success' : 'info')); 
                            ?>">
                                <?php echo htmlspecialchars($comment['commenter_name']); ?>
                            </strong>
                            <span class="badge bg-<?php 
                                echo $comment['commenter_type'] === 'admin' ? 'danger' : 
                                    ($comment['commenter_type'] === 'mentor' ? 'primary' : 
                                    ($comment['commenter_type'] === 'innovator' ? 'success' : 'info')); 
                            ?> ms-2">
                                <?php echo ucfirst($comment['commenter_type']); ?>
                            </span>
                            <?php if ($isAdmin): ?>
                                <span class="badge bg-success ms-1" title="Approved">
                                    <i class="fas fa-check"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted">
                            <?php echo formatDate($comment['created_at']); ?>
                        </small>
                    </div>
                    
                    <div class="comment-text mt-2">
                        <?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?>
                    </div>
                    
                    <?php if ($comment['is_edited']): ?>
                    <div class="mt-2">
                        <small class="text-muted"><i class="fas fa-edit"></i> Edited</small>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function submitComment(event) {
    event.preventDefault();
    
    const submitBtn = event.target.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    const alertDiv = document.getElementById('commentAlert');
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Posting...';
    alertDiv.innerHTML = '';
    
    // Get form data
    const formData = {
        csrf_token: document.getElementById('csrf_token').value,
        project_id: parseInt(document.getElementById('project_id').value),
        comment_text: document.getElementById('comment_text').value.trim()
    };
    
    // Validate
    if (formData.comment_text.length < 10) {
        alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Comment must be at least 10 characters long.</div>';
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        return;
    }
    
    console.log('Submitting comment:', formData); // DEBUG
    
    // Submit via AJAX
    fetch('<?php echo SITE_URL; ?>/api/comments/submit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => {
        console.log('Response status:', response.status); // DEBUG
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data); // DEBUG
        
        if (data.success) {
            const alertClass = data.message_type === 'info' ? 'alert-info' : 'alert-success';
            alertDiv.innerHTML = `<div class="alert ${alertClass}"><i class="fas fa-check-circle me-2"></i>${data.message}</div>`;
            
            // Clear form
            document.getElementById('comment_text').value = '';
            
            // Show additional info if auto-approved
            if (data.is_approved) {
                alertDiv.innerHTML += '<div class="alert alert-success mt-2"><i class="fas fa-check-double me-2"></i>Your comment has been automatically approved and is now visible!</div>';
            }
            
            // Reload page after 2 seconds to show new comment
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>' + data.message + '</div>';
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    })
    .catch(error => {
        console.error('Error:', error); // DEBUG
        alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Network error: ' + error.message + '. Please check the console for details.</div>';
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
}
</script>

<style>
.comment-item {
    transition: all 0.2s;
}

.comment-item:hover {
    background-color: #f8f9fa !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    transform: translateY(-2px);
}

.comment-text {
    line-height: 1.6;
    color: #495057;
}

#commentForm textarea:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>