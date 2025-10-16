<?php
// dashboards/admin/moderate-comments.php
// Admin interface for moderating public comments
require_once '../../includes/init.php';
require_once '../../includes/mentor-consensus-functions.php';

$auth->requireUserType(USER_TYPE_ADMIN);

$adminId = $auth->getUserId();
$errors = [];
$success = '';

// Get pending comments
$pendingComments = getPendingComments(100);
$pendingCount = count($pendingComments);

// Get some statistics
$totalPendingCount = $database->count('comments', 
    'commenter_type = "investor" AND is_approved = 0 AND is_deleted = 0'
);
$approvedTodayCount = $database->count('comments',
    'commenter_type = "investor" AND is_approved = 1 AND DATE(approved_at) = CURDATE()'
);
$rejectedTodayCount = $database->count('comments',
    'commenter_type = "investor" AND is_deleted = 1 AND DATE(approved_at) = CURDATE()'
);

$pageTitle = 'Comment Moderation';
$breadcrumbs = [
    ['title' => 'Admin Dashboard', 'url' => BASE_URL . '/dashboards/admin/'],
    ['title' => 'Comment Moderation', 'url' => '']
];

include '../../templates/header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-comments-dollar text-info me-2"></i>Comment Moderation
        </h1>
        <div class="btn-group">
            <button type="button" class="btn btn-success btn-sm" onclick="bulkApprove()">
                <i class="fas fa-check-double me-1"></i>Bulk Approve
            </button>
            <button type="button" class="btn btn-danger btn-sm" onclick="bulkReject()">
                <i class="fas fa-ban me-1"></i>Bulk Reject
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Review</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalPendingCount; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Approved Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $approvedTodayCount; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Rejected Today</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $rejectedTodayCount; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ban fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Processing</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $approvedTodayCount + $rejectedTodayCount; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Area -->
    <div id="alertArea"></div>

    <!-- Pending Comments -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    Pending Comments (<?php echo $pendingCount; ?>)
                </h6>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                    <label class="form-check-label" for="selectAll">Select All</label>
                </div>
            </div>
        </div>
        <div class="card-body">
            
            <?php if (empty($pendingComments)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5>No Pending Comments</h5>
                    <p class="text-muted">All public comments have been reviewed!</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="masterCheckbox" onchange="toggleSelectAll()">
                                </th>
                                <th>Commenter</th>
                                <th>Project</th>
                                <th>Comment Preview</th>
                                <th>Date</th>
                                <th width="200">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingComments as $comment): ?>
                            <tr id="comment-row-<?php echo $comment['comment_id']; ?>">
                                <td>
                                    <input type="checkbox" class="comment-checkbox" 
                                           value="<?php echo $comment['comment_id']; ?>">
                                </td>
                                <td>
                                    <strong><?php echo e($comment['commenter_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo e($comment['commenter_email']); ?></small>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/public/project-details.php?id=<?php echo $comment['project_id']; ?>" 
                                       class="text-decoration-none" target="_blank">
                                        <?php echo e($comment['project_name']); ?>
                                        <i class="fas fa-external-link-alt fa-sm ms-1"></i>
                                    </a>
                                </td>
                                <td>
                                    <div class="comment-preview">
                                        <?php 
                                        $preview = strlen($comment['comment_text']) > 100 
                                            ? substr($comment['comment_text'], 0, 100) . '...' 
                                            : $comment['comment_text']; 
                                        echo e($preview);
                                        ?>
                                    </div>
                                    <button type="button" class="btn btn-link btn-sm p-0" 
                                            onclick="showFullComment(<?php echo $comment['comment_id']; ?>)">
                                        View Full Comment
                                    </button>
                                </td>
                                <td>
                                    <small><?php echo formatDate($comment['created_at']); ?></small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-success" 
                                                onclick="approveComment(<?php echo $comment['comment_id']; ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger" 
                                                onclick="rejectComment(<?php echo $comment['comment_id']; ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                        <button type="button" class="btn btn-info" 
                                                onclick="showFullComment(<?php echo $comment['comment_id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Full Comment Modal -->
<div class="modal fade" id="fullCommentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Full Comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="fullCommentContent"></div>
                <div class="mt-3">
                    <label class="form-label">Admin Notes (Optional)</label>
                    <textarea class="form-control" id="adminNotes" rows="3" 
                              placeholder="Add any notes about this comment..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="modalApproveBtn">
                    <i class="fas fa-check me-2"></i>Approve
                </button>
                <button type="button" class="btn btn-danger" id="modalRejectBtn">
                    <i class="fas fa-times me-2"></i>Reject
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Reject Reason Modal -->
<div class="modal fade" id="bulkRejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Bulk Reject Comments</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Rejection Reason</label>
                    <textarea class="form-control" id="bulkRejectReason" rows="3" 
                              placeholder="Enter reason for rejection..." required></textarea>
                </div>
                <p class="text-muted">This reason will be applied to all selected comments.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" onclick="confirmBulkReject()">
                    <i class="fas fa-ban me-2"></i>Reject Selected
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentCommentId = null;
const selectedComments = new Set();

// Toggle select all
function toggleSelectAll() {
    const masterCheckbox = document.getElementById('masterCheckbox');
    const checkboxes = document.querySelectorAll('.comment-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = masterCheckbox.checked;
        updateSelection(checkbox.value, checkbox.checked);
    });
}

// Update selection tracking
function updateSelection(commentId, isSelected) {
    if (isSelected) {
        selectedComments.add(commentId);
    } else {
        selectedComments.delete(commentId);
    }
}

// Show full comment in modal
function showFullComment(commentId) {
    currentCommentId = commentId;
    
    // Find comment in the table
    const row = document.getElementById(`comment-row-${commentId}`);
    const commenterName = row.cells[1].querySelector('strong').textContent;
    const commenterEmail = row.cells[1].querySelector('small').textContent;
    const projectName = row.cells[2].querySelector('a').textContent.trim();
    
    // Get full comment text from PHP
    fetch(`<?php echo BASE_URL; ?>/api/comments/get-comment.php?id=${commentId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('fullCommentContent').innerHTML = `
                <div class="mb-3">
                    <strong>Commenter:</strong> ${commenterName} (${commenterEmail})<br>
                    <strong>Project:</strong> ${projectName}<br>
                    <strong>Date:</strong> ${new Date(data.comment.created_at).toLocaleString()}
                </div>
                <div class="border p-3 bg-light">
                    ${data.comment.comment_text.replace(/\n/g, '<br>')}
                </div>
            `;
            
            // Set up modal buttons
            document.getElementById('modalApproveBtn').onclick = () => approveCommentFromModal();
            document.getElementById('modalRejectBtn').onclick = () => rejectCommentFromModal();
            
            // Show modal
            new bootstrap.Modal(document.getElementById('fullCommentModal')).show();
        } else {
            showAlert('error', 'Failed to load comment details');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while loading comment details');
    });
}

// Approve comment
function approveComment(commentId, adminNotes = '') {
    const data = {
        csrf_token: '<?php echo $auth->generateCSRFToken(); ?>',
        action: 'approve',
        comment_id: commentId,
        admin_notes: adminNotes
    };
    
    fetch('<?php echo BASE_URL; ?>/api/admin/moderate-comments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            removeCommentRow(commentId);
            updateStats();
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while approving the comment');
    });
}

// Reject comment
function rejectComment(commentId, reason = '') {
    if (!reason) {
        reason = prompt('Enter rejection reason:');
        if (!reason) return;
    }
    
    const data = {
        csrf_token: '<?php echo $auth->generateCSRFToken(); ?>',
        action: 'reject',
        comment_id: commentId,
        reason: reason
    };
    
    fetch('<?php echo BASE_URL; ?>/api/admin/moderate-comments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            removeCommentRow(commentId);
            updateStats();
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred while rejecting the comment');
    });
}

// Approve from modal
function approveCommentFromModal() {
    const adminNotes = document.getElementById('adminNotes').value;
    approveComment(currentCommentId, adminNotes);
    bootstrap.Modal.getInstance(document.getElementById('fullCommentModal')).hide();
}

// Reject from modal
function rejectCommentFromModal() {
    const reason = prompt('Enter rejection reason:');
    if (reason) {
        rejectComment(currentCommentId, reason);
        bootstrap.Modal.getInstance(document.getElementById('fullCommentModal')).hide();
    }
}

// Bulk approve
function bulkApprove() {
    if (selectedComments.size === 0) {
        showAlert('warning', 'Please select comments to approve');
        return;
    }
    
    if (!confirm(`Are you sure you want to approve ${selectedComments.size} comments?`)) {
        return;
    }
    
    const data = {
        csrf_token: '<?php echo $auth->generateCSRFToken(); ?>',
        action: 'bulk_approve',
        comment_ids: Array.from(selectedComments),
        admin_notes: 'Bulk approved'
    };
    
    fetch('<?php echo BASE_URL; ?>/api/admin/moderate-comments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            selectedComments.forEach(commentId => removeCommentRow(commentId));
            selectedComments.clear();
            updateStats();
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred during bulk approval');
    });
}

// Bulk reject
function bulkReject() {
    if (selectedComments.size === 0) {
        showAlert('warning', 'Please select comments to reject');
        return;
    }
    
    new bootstrap.Modal(document.getElementById('bulkRejectModal')).show();
}

// Confirm bulk reject
function confirmBulkReject() {
    const reason = document.getElementById('bulkRejectReason').value.trim();
    if (!reason) {
        showAlert('error', 'Please enter a rejection reason');
        return;
    }
    
    const data = {
        csrf_token: '<?php echo $auth->generateCSRFToken(); ?>',
        action: 'bulk_reject',
        comment_ids: Array.from(selectedComments),
        reason: reason
    };
    
    fetch('<?php echo BASE_URL; ?>/api/admin/moderate-comments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            selectedComments.forEach(commentId => removeCommentRow(commentId));
            selectedComments.clear();
            bootstrap.Modal.getInstance(document.getElementById('bulkRejectModal')).hide();
            document.getElementById('bulkRejectReason').value = '';
            updateStats();
        } else {
            showAlert('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('error', 'An error occurred during bulk rejection');
    });
}

// Remove comment row from table
function removeCommentRow(commentId) {
    const row = document.getElementById(`comment-row-${commentId}`);
    if (row) {
        row.remove();
        selectedComments.delete(commentId);
    }
}

// Show alert
function showAlert(type, message) {
    const alertArea = document.getElementById('alertArea');
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 'alert-warning';
    
    alertArea.innerHTML = `
        <div class="alert ${alertClass} alert-dismissible fade show">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = alertArea.querySelector('.alert');
        if (alert) {
            bootstrap.Alert.getInstance(alert).close();
        }
    }, 5000);
}

// Update statistics
function updateStats() {
    // Reload page to update stats (could be done via AJAX for better UX)
    setTimeout(() => location.reload(), 1000);
}

// Set up checkbox change listeners
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.comment-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelection(this.value, this.checked);
        });
    });
});
</script>

<?php include '../../templates/footer.php'; ?>