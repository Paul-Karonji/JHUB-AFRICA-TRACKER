<?php
/**
 * templates/comments-section.php
 * UPDATED: Universal Comments Section with Approval Filtering
 * 
 * Required variables:
 * - $projectId: The project ID
 * - $auth: Auth instance
 * - $database: Database instance
 * 
 * CHANGES:
 * - Filters comments by approval status
 * - Shows only investor comments to public
 * - Shows all approved comments to authenticated users
 * - Admins see a link to moderate pending comments
 */

if (!isset($projectId)) {
    die('Project ID is required for comments section');
}

// Determine comment visibility based on user type
$isAdmin = $auth->isLoggedIn() && $auth->getUserType() === USER_TYPE_ADMIN;
$isAuthenticated = $auth->isLoggedIn();

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
        <?php if ($isAdmin && $pendingCount > 0): ?>
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
            <h6 class="mb-3"><i class="fas fa-edit me-2"></i>Leave a Comment</h6>
            <form id="commentForm" onsubmit="submitComment(event)">
                <input type="hidden" id="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                <input type="hidden" id="project_id" value="<?php echo $projectId; ?>">
                
                <div class="mb-3">
                    <label class="form-label">Your Comment <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="comment_text" rows="4" 
                              placeholder="Share your feedback, questions, or suggestions..." 
                              required minlength="10"></textarea>
                    <div class="form-text">Minimum 10 characters</div>
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
                ?> bg-light">
                    <div class="d-flex justify-content-between align-items-start mb-2">
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
        alertDiv.innerHTML = '<div class="alert alert-danger">Comment must be at least 10 characters long.</div>';
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        return;
    }
    
    // Submit via AJAX
    fetch('<?php echo SITE_URL; ?>/api/comments/submit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const alertClass = data.message_type === 'info' ? 'alert-info' : 'alert-success';
            alertDiv.innerHTML = `<div class="alert ${alertClass}"><i class="fas fa-check-circle me-2"></i>${data.message}</div>`;
            
            // Clear form
            document.getElementById('comment_text').value = '';
            
            // Reload page after 1.5 seconds to show new comment (if auto-approved)
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>' + data.message + '</div>';
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    })
    .catch(error => {
        alertDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle me-2"></i>Network error: ' + error.message + '</div>';
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
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.comment-text {
    line-height: 1.6;
}
</style>