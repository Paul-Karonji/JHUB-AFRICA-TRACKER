<?php
// dashboards/admin/applications.php
// Admin Application Review Interface - COMPLETE WORKING VERSION
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_ADMIN);

$adminId = $auth->getUserId();
$viewApplication = null;
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$applicationId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get specific application for review
if ($applicationId) {
    $viewApplication = $database->getRow(
        "SELECT * FROM project_applications WHERE application_id = ?",
        [$applicationId]
    );
    
    if (!$viewApplication) {
        header('Location: applications.php');
        exit;
    }
}

// Get all applications with statistics
$applications = $database->getRows("
    SELECT * FROM project_applications 
    ORDER BY 
        CASE status 
            WHEN 'pending' THEN 1 
            WHEN 'approved' THEN 2 
            WHEN 'rejected' THEN 3 
        END,
        applied_at DESC
");

// Calculate statistics
$stats = [
    'total' => count($applications),
    'pending' => count(array_filter($applications, fn($a) => $a['status'] === 'pending')),
    'approved' => count(array_filter($applications, fn($a) => $a['status'] === 'approved')),
    'rejected' => count(array_filter($applications, fn($a) => $a['status'] === 'rejected')),
];

$pageTitle = $viewApplication ? "Review Application" : "Application Management";
include '../../templates/header.php';
?>

<div class="admin-dashboard">
    <?php if (!$viewApplication): ?>
    <!-- List View -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Application Management</h1>
            <p class="text-muted mb-0">Review and manage project applications</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Applications</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['total']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Review</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['pending']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Approved</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['approved']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-danger shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Rejected</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['rejected']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Applications Table -->
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Applications</h5>
            <div>
                <button class="btn btn-light btn-sm" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($applications)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No applications found</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Project Name</th>
                            <th>Project Lead</th>
                            <th>Email</th>
                            <th>Applied Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                        <tr>
                            <td>#<?php echo str_pad($app['application_id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <strong><?php echo e($app['project_name']); ?></strong>
                            </td>
                            <td><?php echo e($app['project_lead_name']); ?></td>
                            <td>
                                <small><?php echo e($app['project_lead_email']); ?></small>
                            </td>
                            <td>
                                <small><?php echo date('M j, Y', strtotime($app['applied_at'])); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $app['status'] === 'pending' ? 'warning' : 
                                        ($app['status'] === 'approved' ? 'success' : 'danger'); 
                                ?>">
                                    <?php echo ucfirst($app['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="?id=<?php echo $app['application_id']; ?>" 
                                   class="btn btn-sm btn-primary">
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

    <?php else: ?>
    <!-- Detail View -->
    <div class="mb-4">
        <a href="applications.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Back to Applications
        </a>
    </div>

    <div class="row">
        <!-- Application Details -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Application Details</h5>
                </div>
                <div class="card-body">
                    <!-- Status Badge -->
                    <div class="mb-4">
                        <span class="badge bg-<?php 
                            echo $viewApplication['status'] === 'pending' ? 'warning' : 
                                ($viewApplication['status'] === 'approved' ? 'success' : 'danger'); 
                        ?> fs-6 px-3 py-2">
                            Status: <?php echo ucfirst($viewApplication['status']); ?>
                        </span>
                    </div>

                    <!-- Project Information -->
                    <h5 class="border-bottom pb-2 mb-3">Project Information</h5>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <strong>Project Name:</strong><br>
                            <span class="text-primary fs-5"><?php echo e($viewApplication['project_name']); ?></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Start Date:</strong><br>
                            <?php echo date('F j, Y', strtotime($viewApplication['date'])); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Project Email:</strong><br>
                            <?php echo $viewApplication['project_email'] ? e($viewApplication['project_email']) : '<em class="text-muted">Not provided</em>'; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Project Website:</strong><br>
                            <?php if ($viewApplication['project_website']): ?>
                                <a href="<?php echo e($viewApplication['project_website']); ?>" target="_blank">
                                    <?php echo e($viewApplication['project_website']); ?>
                                    <i class="fas fa-external-link-alt ms-1"></i>
                                </a>
                            <?php else: ?>
                                <em class="text-muted">Not provided</em>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Project Description -->
                    <h5 class="border-bottom pb-2 mb-3">Project Description</h5>
                    <div class="mb-4">
                        <p class="text-justify"><?php echo nl2br(e($viewApplication['description'])); ?></p>
                    </div>

                    <!-- Project Lead Information -->
                    <h5 class="border-bottom pb-2 mb-3">Project Lead Information</h5>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <strong>Full Name:</strong><br>
                            <?php echo e($viewApplication['project_lead_name']); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Email Address:</strong><br>
                            <a href="mailto:<?php echo e($viewApplication['project_lead_email']); ?>">
                                <?php echo e($viewApplication['project_lead_email']); ?>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Profile Name (Username):</strong><br>
                            <code><?php echo e($viewApplication['profile_name']); ?></code>
                        </div>
                    </div>

                    <!-- Presentation File -->
                    <h5 class="border-bottom pb-2 mb-3">Project Presentation</h5>
                    <?php if ($viewApplication['presentation_file']): ?>
                        <?php 
                        $filePath = '../../assets/uploads/presentations/' . $viewApplication['presentation_file'];
                        $fileExists = file_exists($filePath);
                        ?>
                        <div class="alert alert-info">
                            <i class="fas fa-file-pdf fa-2x float-start me-3"></i>
                            <div>
                                <strong>Presentation File:</strong><br>
                                <small class="text-muted"><?php echo e($viewApplication['presentation_file']); ?></small>
                                <?php if (!$fileExists): ?>
                                    <br><small class="text-danger">⚠️ File not found on server</small>
                                <?php endif; ?>
                            </div>
                            <?php if ($fileExists): ?>
                            <a href="../../assets/uploads/presentations/<?php echo e($viewApplication['presentation_file']); ?>" 
                               target="_blank" 
                               download
                               class="btn btn-sm btn-primary mt-2">
                                <i class="fas fa-download me-1"></i> Download Presentation
                            </a>
                            <?php else: ?>
                            <button class="btn btn-sm btn-secondary mt-2" disabled>
                                <i class="fas fa-exclamation-triangle me-1"></i> File Missing
                            </button>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No presentation file uploaded</p>
                    <?php endif; ?>

                    <!-- Application Metadata -->
                    <h5 class="border-bottom pb-2 mb-3 mt-4">Application Metadata</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Application ID:</strong><br>
                            #<?php echo str_pad($viewApplication['application_id'], 6, '0', STR_PAD_LEFT); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Submitted:</strong><br>
                            <?php echo date('F j, Y \a\t g:i A', strtotime($viewApplication['applied_at'])); ?>
                        </div>
                        <?php if ($viewApplication['reviewed_at']): ?>
                        <div class="col-md-6 mb-3">
                            <strong>Reviewed:</strong><br>
                            <?php echo date('F j, Y \a\t g:i A', strtotime($viewApplication['reviewed_at'])); ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($viewApplication['ip_address']): ?>
                        <div class="col-md-6 mb-3">
                            <strong>IP Address:</strong><br>
                            <small class="text-muted"><?php echo e($viewApplication['ip_address']); ?></small>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($viewApplication['status'] === 'rejected' && $viewApplication['rejection_reason']): ?>
                    <!-- Rejection Reason -->
                    <div class="alert alert-danger mt-3">
                        <h6 class="alert-heading">Rejection Reason:</h6>
                        <p class="mb-0"><?php echo nl2br(e($viewApplication['rejection_reason'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Action Panel -->
        <div class="col-lg-4">
            <?php if ($viewApplication['status'] === 'pending'): ?>
            <!-- Review Actions -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Review Actions</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Take action on this application:</p>
                    
                    <!-- Approve Button -->
                    <button class="btn btn-success btn-lg w-100 mb-3" 
                            onclick="reviewApplication(<?php echo $viewApplication['application_id']; ?>, 'approve')">
                        <i class="fas fa-check me-2"></i> Approve Application
                    </button>

                    <!-- Reject Section -->
                    <button class="btn btn-outline-danger w-100 mb-2" type="button" 
                            data-bs-toggle="collapse" data-bs-target="#rejectSection">
                        <i class="fas fa-times me-2"></i> Reject Application
                    </button>

                    <div class="collapse mt-3" id="rejectSection">
                        <div class="alert alert-warning">
                            <small><strong>Note:</strong> Rejecting will notify the applicant via email.</small>
                        </div>
                        <label class="form-label">Rejection Reason<span class="text-danger">*</span></label>
                        <textarea class="form-control mb-2" id="rejectionReason" rows="4" 
                                  placeholder="Provide a clear reason for rejection..."></textarea>
                        <button class="btn btn-danger w-100" 
                                onclick="rejectWithReason(<?php echo $viewApplication['application_id']; ?>)">
                            <i class="fas fa-paper-plane me-2"></i> Send Rejection
                        </button>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Already Reviewed -->
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Status</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-<?php echo $viewApplication['status'] === 'approved' ? 'success' : 'danger'; ?> mb-0">
                        <h6 class="alert-heading">
                            <?php echo $viewApplication['status'] === 'approved' ? 'Application Approved' : 'Application Rejected'; ?>
                        </h6>
                        <p class="mb-0">
                            This application has already been reviewed on 
                            <?php echo date('F j, Y', strtotime($viewApplication['reviewed_at'])); ?>.
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Info -->
            <div class="card shadow">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Review Guidelines</h6>
                </div>
                <div class="card-body">
                    <ul class="small mb-0">
                        <li class="mb-2">Review the project presentation carefully</li>
                        <li class="mb-2">Assess innovation potential and feasibility</li>
                        <li class="mb-2">Check if project aligns with JHUB AFRICA's mission</li>
                        <li class="mb-2">Verify contact information is valid</li>
                        <li class="mb-2">If rejecting, provide constructive feedback</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function reviewApplication(applicationId, action) {
    if (!confirm(`Are you sure you want to ${action} this application?`)) {
        return;
    }

    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';

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
            window.location.href = 'applications.php';
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        alert('Network error: ' + error);
        btn.disabled = false;
        btn.innerHTML = originalText;
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

    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sending...';

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
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    })
    .catch(error => {
        alert('Network error: ' + error);
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}
</script>

<?php include '../../templates/footer.php'; ?>