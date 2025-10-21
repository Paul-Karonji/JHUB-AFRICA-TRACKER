<?php
/**
 * dashboards/admin/moderate-comments.php
 * Admin interface for moderating pending comments
 */

require_once '../../includes/init.php';

// Require admin authentication
if (!$auth->isValidSession() || $auth->getUserType() !== USER_TYPE_ADMIN) {
    header('Location: ' . SITE_URL . '/auth/login.php?type=admin');
    exit;
}

$adminId = $auth->getUserId();
$adminName = $_SESSION['admin_name'] ?? 'Admin';

// Get filter parameters
$filterProject = isset($_GET['project_id']) ? intval($_GET['project_id']) : null;
$filterType = isset($_GET['commenter_type']) ? $_GET['commenter_type'] : 'all';

// Build query for pending comments
$whereConditions = ["c.is_deleted = 0", "c.is_approved = 0"];
$params = [];

if ($filterProject) {
    $whereConditions[] = "c.project_id = ?";
    $params[] = $filterProject;
}

if ($filterType !== 'all') {
    $whereConditions[] = "c.commenter_type = ?";
    $params[] = $filterType;
}

$whereClause = implode(' AND ', $whereConditions);

// Get pending comments
$pendingComments = $database->getRows("
    SELECT 
        c.*,
        p.project_name,
        p.project_id
    FROM comments c
    INNER JOIN projects p ON c.project_id = p.project_id
    WHERE $whereClause
    ORDER BY c.created_at DESC
", $params);

// Get all projects for filter dropdown
$projects = $database->getRows("
    SELECT project_id, project_name 
    FROM projects 
    WHERE status = 'active'
    ORDER BY project_name ASC
");

// Get statistics
$stats = [
    'total_pending' => $database->count('comments', 'is_deleted = 0 AND is_approved = 0'),
    'investor_pending' => $database->count('comments', 'is_deleted = 0 AND is_approved = 0 AND commenter_type = ?', ['investor']),
    'today_pending' => $database->count('comments', 'is_deleted = 0 AND is_approved = 0 AND DATE(created_at) = CURDATE()'),
];

$pageTitle = 'Moderate Comments';
require_once '../../templates/header.php';
?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-gavel me-2"></i>Moderate Comments
            </h1>
            <p class="text-muted mb-0">Review and approve pending comments</p>
        </div>
        <div>
            <a href="<?php echo SITE_URL; ?>/check-comments.php" class="btn btn-info" target="_blank">
                <i class="fas fa-database"></i> View All Comments
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Pending
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['total_pending']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Investor Comments
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['investor_pending']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Today's Pending
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['today_pending']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter me-2"></i>Filters
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="form-label">Project</label>
                    <select name="project_id" class="form-select">
                        <option value="">All Projects</option>
                        <?php foreach ($projects as $proj): ?>
                        <option value="<?php echo $proj['project_id']; ?>" 
                                <?php echo $filterProject == $proj['project_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($proj['project_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label">Commenter Type</label>
                    <select name="commenter_type" class="form-select">
                        <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="investor" <?php echo $filterType === 'investor' ? 'selected' : ''; ?>>Investors</option>
                        <option value="admin" <?php echo $filterType === 'admin' ? 'selected' : ''; ?>>Admins</option>
                        <option value="mentor" <?php echo $filterType === 'mentor' ? 'selected' : ''; ?>>Mentors</option>
                        <option value="innovator" <?php echo $filterType === 'innovator' ? 'selected' : ''; ?>>Innovators</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions -->
    <?php if (!empty($pendingComments)): ?>
    <div class="alert alert-info">
        <strong>Bulk Actions:</strong>
        <button onclick="approveAll()" class="btn btn-sm btn-success ms-2">
            <i class="fas fa-check-double"></i> Approve All Visible
        </button>
        <button onclick="rejectAll()" class="btn btn-sm btn-danger ms-2">
            <i class="fas fa-times"></i> Reject All Visible
        </button>
    </div>
    <?php endif; ?>

    <!-- Pending Comments List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-comments me-2"></i>Pending Comments 
                <span class="badge bg-warning text-dark"><?php echo count($pendingComments); ?></span>
            </h6>
        </div>
        <div class="card-body">
            <?php if (empty($pendingComments)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-check-circle fa-4x mb-3 text-success"></i>
                    <h5>No Pending Comments!</h5>
                    <p>All comments have been reviewed.</p>
                </div>
            <?php else: ?>
                <div id="commentsList">
                    <?php foreach ($pendingComments as $comment): ?>
                    <div class="comment-moderation-card border rounded p-3 mb-3" id="comment-<?php echo $comment['comment_id']; ?>">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong class="text-primary"><?php echo htmlspecialchars($comment['commenter_name']); ?></strong>
                                        <span class="badge bg-<?php 
                                            echo $comment['commenter_type'] === 'investor' ? 'info' : 
                                                ($comment['commenter_type'] === 'mentor' ? 'primary' : 
                                                ($comment['commenter_type'] === 'admin' ? 'danger' : 'success')); 
                                        ?> ms-2">
                                            <?php echo ucfirst($comment['commenter_type']); ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('M j, Y g:i A', strtotime($comment['created_at'])); ?>
                                    </small>
                                </div>
                                
                                <p class="mb-2"><strong>Project:</strong> 
                                    <a href="<?php echo SITE_URL; ?>/dashboards/admin/projects.php?id=<?php echo $comment['project_id']; ?>">
                                        <?php echo htmlspecialchars($comment['project_name']); ?>
                                    </a>
                                </p>
                                
                                <div class="bg-light p-3 rounded mb-2">
                                    <strong>Comment:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?>
                                </div>
                                
                                <div class="text-muted small">
                                    <div><strong>Email:</strong> <?php echo htmlspecialchars($comment['commenter_email']); ?></div>
                                    <?php if ($comment['ip_address']): ?>
                                        <div><strong>IP Address:</strong> <?php echo htmlspecialchars($comment['ip_address']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($comment['user_agent']): ?>
                                        <div><strong>User Agent:</strong> <small><?php echo htmlspecialchars(substr($comment['user_agent'], 0, 100)); ?></small></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-4 d-flex flex-column justify-content-center">
                                <button onclick="moderateComment(<?php echo $comment['comment_id']; ?>, 'approve')" 
                                        class="btn btn-success btn-lg mb-2">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button onclick="moderateComment(<?php echo $comment['comment_id']; ?>, 'reject')" 
                                        class="btn btn-danger btn-lg">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function moderateComment(commentId, action) {
    const actionText = action === 'approve' ? 'approve' : 'reject';
    
    if (!confirm(`Are you sure you want to ${actionText} this comment?`)) {
        return;
    }
    
    fetch('<?php echo SITE_URL; ?>/api/comments/moderate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            csrf_token: '<?php echo $auth->generateCSRFToken(); ?>',
            comment_id: commentId,
            action: action
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the comment card with animation
            const card = document.getElementById('comment-' + commentId);
            card.style.transition = 'all 0.3s ease';
            card.style.opacity = '0';
            card.style.transform = 'translateX(100px)';
            
            setTimeout(() => {
                card.remove();
                
                // Check if there are no more comments
                if (document.querySelectorAll('.comment-moderation-card').length === 0) {
                    window.location.reload();
                }
            }, 300);
            
            // Show success message
            alert(data.message);
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Network error: ' + error.message);
    });
}

function approveAll() {
    const commentIds = Array.from(document.querySelectorAll('.comment-moderation-card'))
        .map(card => parseInt(card.id.replace('comment-', '')));
    
    if (commentIds.length === 0) return;
    
    if (!confirm(`Approve all ${commentIds.length} visible comment(s)?`)) {
        return;
    }
    
    fetch('<?php echo SITE_URL; ?>/api/comments/moderate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            csrf_token: '<?php echo $auth->generateCSRFToken(); ?>',
            comment_ids: commentIds,
            action: 'approve'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Network error: ' + error.message);
    });
}

function rejectAll() {
    const commentIds = Array.from(document.querySelectorAll('.comment-moderation-card'))
        .map(card => parseInt(card.id.replace('comment-', '')));
    
    if (commentIds.length === 0) return;
    
    if (!confirm(`REJECT all ${commentIds.length} visible comment(s)? This cannot be undone.`)) {
        return;
    }
    
    fetch('<?php echo SITE_URL; ?>/api/comments/moderate.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            csrf_token: '<?php echo $auth->generateCSRFToken(); ?>',
            comment_ids: commentIds,
            action: 'reject'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Network error: ' + error.message);
    });
}
</script>

<style>
.comment-moderation-card {
    transition: all 0.3s ease;
    background: #fff;
}

.comment-moderation-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}

.border-left-info {
    border-left: 4px solid #36b9cc !important;
}

.border-left-primary {
    border-left: 4px solid #4e73df !important;
}
</style>

<?php require_once '../../templates/footer.php'; ?>