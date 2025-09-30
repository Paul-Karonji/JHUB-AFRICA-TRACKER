<?php
// dashboards/admin/applications.php - Application Management
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_ADMIN);

$adminId = $auth->getUserId();

// Handle view single application
$viewApplication = null;
if (isset($_GET['id'])) {
    $viewApplication = $database->getRow("
        SELECT * FROM project_applications 
        WHERE application_id = ?
    ", [intval($_GET['id'])]);
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery = $_GET['search'] ?? '';

// Build query
$whereConditions = [];
$params = [];

if ($statusFilter !== 'all') {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
}

if (!empty($searchQuery)) {
    $whereConditions[] = "(project_name LIKE ? OR project_lead_name LIKE ? OR project_lead_email LIKE ?)";
    $searchTerm = "%{$searchQuery}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get all applications
$applications = $database->getRows("
    SELECT pa.*, a.username as reviewer_username
    FROM project_applications pa
    LEFT JOIN admins a ON pa.reviewed_by = a.admin_id
    {$whereClause}
    ORDER BY 
        CASE pa.status 
            WHEN 'pending' THEN 1 
            WHEN 'approved' THEN 2 
            WHEN 'rejected' THEN 3 
        END,
        pa.applied_at DESC
", $params);

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM project_applications
";
$appStats = $database->getRow($statsQuery);

$pageTitle = "Application Management";
include '../../templates/header.php';
?>

<div class="applications-management">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Application Management</h1>
            <p class="text-muted">Review and process project applications</p>
        </div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Applications</div>
                    <div class="h5 mb-0 font-weight-bold"><?php echo $appStats['total']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning shadow">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending</div>
                    <div class="h5 mb-0 font-weight-bold"><?php echo $appStats['pending']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success shadow">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Approved</div>
                    <div class="h5 mb-0 font-weight-bold"><?php echo $appStats['approved']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-danger shadow">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Rejected</div>
                    <div class="h5 mb-0 font-weight-bold"><?php echo $appStats['rejected']; ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($viewApplication): ?>
    <!-- Single Application View -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Application Details</h6>
            <a href="applications.php" class="btn btn-sm btn-secondary">
                <i class="fas fa-times me-1"></i> Close
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h4><?php echo e($viewApplication['project_name']); ?></h4>
                    <p class="text-muted">
                        Applied: <?php echo formatDate($viewApplication['applied_at']); ?>
                        <span class="ms-3">
                            Status: 
                            <span class="badge bg-<?php 
                                echo $viewApplication['status'] === 'pending' ? 'warning' : 
                                    ($viewApplication['status'] === 'approved' ? 'success' : 'danger'); 
                            ?>">
                                <?php echo ucfirst($viewApplication['status']); ?>
                            </span>
                        </span>
                    </p>

                    <div class="mt-4">
                        <h5>Project Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th width="200">Project Name</th>
                                <td><?php echo e($viewApplication['project_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <td><?php echo formatDate($viewApplication['date']); ?></td>
                            </tr>
                            <tr>
                                <th>Project Email</th>
                                <td><?php echo e($viewApplication['project_email'] ?? 'N/A'); ?></td>
                            </tr>
                            <tr>
                                <th>Project Website</th>
                                <td><?php echo $viewApplication['project_website'] ? '<a href="' . e($viewApplication['project_website']) . '" target="_blank">' . e($viewApplication['project_website']) . '</a>' : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Description</th>
                                <td><?php echo nl2br(e($viewApplication['description'])); ?></td>
                            </tr>
                        </table>

                        <h5 class="mt-4">Project Lead Information</h5>
                        <table class="table table-bordered">
                            <tr>
                                <th width="200">Lead Name</th>
                                <td><?php echo e($viewApplication['project_lead_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Lead Email</th>
                                <td><?php echo e($viewApplication['project_lead_email']); ?></td>
                            </tr>
                            <tr>
                                <th>Profile Name (Username)</th>
                                <td><?php echo e($viewApplication['profile_name']); ?></td>
                            </tr>
                        </table>

                        <?php if ($viewApplication['presentation_file']): ?>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-file-pdf me-2"></i>
                            Presentation File: 
                            <a href="../../assets/uploads/presentations/<?php echo e($viewApplication['presentation_file']); ?>" 
                               target="_blank" class="alert-link">
                                Download Presentation
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if ($viewApplication['status'] === 'rejected' && $viewApplication['rejection_reason']): ?>
                        <div class="alert alert-danger mt-3">
                            <h6>Rejection Reason:</h6>
                            <p class="mb-0"><?php echo nl2br(e($viewApplication['rejection_reason'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <?php if ($viewApplication['status'] === 'pending'): ?>
                    <!-- Action Panel -->
                    <div class="card border-success mb-3">
                        <div class="card-header bg-success text-white">
                            <i class="fas fa-check-circle me-2"></i>Approve Application
                        </div>
                        <div class="card-body">
                            <p>This will:</p>
                            <ul>
                                <li>Create an active project</li>
                                <li>Send approval email to applicant</li>
                                <li>Complete Stage 1 automatically</li>
                            </ul>
                            <button class="btn btn-success w-100" onclick="reviewApplication(<?php echo $viewApplication['application_id']; ?>, 'approve')">
                                <i class="fas fa-check me-1"></i> Approve Application
                            </button>
                        </div>
                    </div>

                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <i class="fas fa-times-circle me-2"></i>Reject Application
                        </div>
                        <div class="card-body">
                            <form id="rejectForm">
                                <div class="mb-3">
                                    <label class="form-label">Rejection Reason *</label>
                                    <textarea class="form-control" id="rejectionReason" rows="4" required 
                                              placeholder="Provide a clear reason for rejection..."></textarea>
                                </div>
                                <button type="button" class="btn btn-danger w-100" onclick="rejectWithReason(<?php echo $viewApplication['application_id']; ?>)">
                                    <i class="fas fa-times me-1"></i> Reject Application
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <h6>Application Status</h6>
                        <p>This application has been <strong><?php echo $viewApplication['status']; ?></strong>.</p>
                        <?php if ($viewApplication['reviewed_at']): ?>
                        <small>
                            Reviewed: <?php echo formatDate($viewApplication['reviewed_at']); ?><br>
                            By: <?php echo e($viewApplication['reviewer_username'] ?? 'System'); ?>
                        </small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- Applications List -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">All Applications</h6>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label">Status Filter</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by project name, lead name, or email..."
                           value="<?php echo e($searchQuery); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                </div>
            </form>

            <!-- Applications Table -->
            <?php if (empty($applications)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <p class="text-muted">No applications found</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Project Name</th>
                            <th>Project Lead</th>
                            <th>Applied Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                        <tr>
                            <td>
                                <strong><?php echo e($app['project_name']); ?></strong><br>
                                <small class="text-muted"><?php echo truncateText(e($app['description']), 80); ?></small>
                            </td>
                            <td>
                                <?php echo e($app['project_lead_name']); ?><br>
                                <small class="text-muted"><?php echo e($app['project_lead_email']); ?></small>
                            </td>
                            <td><?php echo formatDate($app['applied_at']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $app['status'] === 'pending' ? 'warning' : 
                                        ($app['status'] === 'approved' ? 'success' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($app['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="?id=<?php echo $app['application_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye me-1"></i> Review
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function reviewApplication(applicationId, action) {
    if (!confirm(`Are you sure you want to ${action} this application?`)) {
        return;
    }

    const formData = {
        application_id: applicationId,
        action: action,
        csrf_token: '<?php echo $auth->generateCSRFToken(); ?>'
    };

    fetch('../../api/applications/review.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
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
        alert('Network error: ' + error);
    });
}

function rejectWithReason(applicationId) {
    const reason = document.getElementById('rejectionReason').value;
    if (!reason || reason.trim() === '') {
        alert('Please provide a rejection reason');
        return;
    }

    if (!confirm('Are you sure you want to reject this application?')) {
        return;
    }

    const formData = {
        application_id: applicationId,
        action: 'reject',
        rejection_reason: reason,
        csrf_token: '<?php echo $auth->generateCSRFToken(); ?>'
    };

    fetch('../../api/applications/review.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = 'applications.php';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Network error: ' + error);
    });
}
</script>

<?php include '../../templates/footer.php'; ?>