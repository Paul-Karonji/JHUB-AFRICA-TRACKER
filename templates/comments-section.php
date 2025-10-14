<?php
/**
 * templates/comments-section.php
 * Universal Comments Section Component
 * Can be included in admin, mentor, or project dashboards
 * 
 * Required variables:
 * - $projectId: The project ID
 * - $auth: Auth instance (already available from init.php)
 * - $database: Database instance (already available from init.php)
 */

if (!isset($projectId)) {
    die('Project ID is required for comments section');
}

// Get all comments for this project
$comments = $database->getRows("
    SELECT * FROM comments 
    WHERE project_id = ? AND is_deleted = 0 AND parent_comment_id IS NULL
    ORDER BY created_at DESC
", [$projectId]);

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
                                <i class="fas fa-<?php 
                                    echo $comment['commenter_type'] === 'admin' ? 'user-shield' : 
                                        ($comment['commenter_type'] === 'mentor' ? 'user-tie' : 
                                        ($comment['commenter_type'] === 'innovator' ? 'user-cog' : 'user')); 
                                ?> me-1"></i>
                                <?php echo htmlspecialchars($comment['commenter_name']); ?>
                            </strong>
                            <span class="badge bg-secondary ms-2"><?php echo ucfirst($comment['commenter_type']); ?></span>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo formatDate($comment['created_at']); ?>
                        </small>
                    </div>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function submitComment(event) {
    event.preventDefault();
    
    const form = document.getElementById('commentForm');
    const submitBtn = form.querySelector('button[type="submit"]');
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
            alertDiv.innerHTML = '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' + data.message + '</div>';
            
            // Clear form
            document.getElementById('comment_text').value = '';
            
            // Reload page after 1 second to show new comment
            setTimeout(() => {
                window.location.reload();
            }, 1000);
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
</style>