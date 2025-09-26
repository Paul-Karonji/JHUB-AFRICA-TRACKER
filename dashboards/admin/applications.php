<?php
/**
 * Admin Application Management Page
 * Location: dashboards/admin/applications.php
 * Purpose: Review and manage project applications
 */

require_once '../../includes/init.php';

// Require admin authentication
$auth->requireUserType(USER_TYPE_ADMIN);

// Get filter from query string
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query based on filter
$whereClause = '';
$params = [];

if ($statusFilter !== 'all') {
    $whereClause = 'WHERE status = ?';
    $params[] = $statusFilter;
}

// Get applications
$applications = $database->getRows(
    "SELECT * FROM project_applications {$whereClause} ORDER BY applied_at DESC",
    $params
);

// Calculate statistics
$stats = [
    'pending' => $database->count('project_applications', 'status = ?', ['pending']),
    'approved' => $database->count('project_applications', 'status = ?', ['approved']),
    'rejected' => $database->count('project_applications', 'status = ?', ['rejected']),
    'total' => $database->count('project_applications')
];

$pageTitle = "Application Management";
$additionalCSS = [BASE_PATH . '/assets/css/admin.css'];
include '../../templates/header.php';
?>

<style>
.applications-management {
    padding: 20px;
}
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
    margin-bottom: 1.5rem;
}
.border-left-warning { border-left: 4px solid #f6c23e; }
.border-left-success { border-left: 4px solid #1cc88a; }
.border-left-danger { border-left: 4px solid #e74a3b; }
.border-left-info { border-left: 4px solid #36b9cc; }
.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,.025);
    cursor: pointer;
}
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
    font-weight: 600;
}
.days-pending {
    font-size: 0.875rem;
}
.action-buttons {
    display: flex;
    gap: 0.5rem;
}
</style>

<div class="applications-management">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Application Management</h1>
            <p class="text-muted mb-0">Review and manage project applications</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" onclick="refreshApplications()">
                <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                Pending Review
                            </div>
                            <div class="h5 mb-0 fw-bold text-gray-800">
                                <?php echo $stats['pending']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                Approved
                            </div>
                            <div class="h5 mb-0 fw-bold text-gray-800">
                                <?php echo $stats['approved']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">
                                Rejected
                            </div>
                            <div class="h5 mb-0 fw-bold text-gray-800">
                                <?php echo $stats['rejected']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                Total Applications
                            </div>
                            <div class="h5 mb-0 fw-bold text-gray-800">
                                <?php echo $stats['total']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?php echo $statusFilter === 'all' ? 'active' : ''; ?>" 
                       href="?status=all">
                        All Applications (<?php echo $stats['total']; ?>)
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>" 
                       href="?status=pending">
                        Pending (<?php echo $stats['pending']; ?>)
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>" 
                       href="?status=approved">
                        Approved (<?php echo $stats['approved']; ?>)
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>" 
                       href="?status=rejected">
                        Rejected (<?php echo $stats['rejected']; ?>)
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <?php if (empty($applications)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted mb-0">
                        <?php if ($statusFilter === 'pending'): ?>
                            No pending applications to review.
                        <?php elseif ($statusFilter === 'approved'): ?>
                            No approved applications yet.
                        <?php elseif ($statusFilter === 'rejected'): ?>
                            No rejected applications yet.
                        <?php else: ?>
                            No applications have been submitted yet.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Project Lead</th>
                                <th>Applied Date</th>
                                <th>Status</th>
                                <th>Days Pending</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): 
                                $daysPending = ceil((time() - strtotime($app['applied_at'])) / 86400);
                                $urgencyClass = $daysPending > 7 ? 'text-danger' : ($daysPending > 3 ? 'text-warning' : 'text-muted');
                            ?>
                            <tr>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($app['project_name']); ?></strong>
                                        <?php if (!empty($app['project_website'])): ?>
                                            <br><small>
                                                <a href="<?php echo htmlspecialchars($app['project_website']); ?>" 
                                                   target="_blank" class="text-muted">
                                                    <i class="fas fa-external-link-alt me-1"></i>
                                                    <?php echo htmlspecialchars($app['project_website']); ?>
                                                </a>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php echo htmlspecialchars($app['project_lead_name']); ?>
                                        <br><small class="text-muted">
                                            <?php echo htmlspecialchars($app['project_lead_email']); ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($app['applied_at'])); ?>
                                    <br><small class="text-muted">
                                        <?php echo date('g:i A', strtotime($app['applied_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php
                                    $statusClasses = [
                                        'pending' => 'bg-warning text-dark',
                                        'approved' => 'bg-success text-white',
                                        'rejected' => 'bg-danger text-white'
                                    ];
                                    $statusClass = $statusClasses[$app['status']] ?? 'bg-secondary text-white';
                                    ?>
                                    <span class="badge <?php echo $statusClass; ?>">
                                        <?php echo strtoupper($app['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="<?php echo $urgencyClass; ?>">
                                        <?php echo $daysPending; ?> day<?php echo $daysPending !== 1 ? 's' : ''; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-sm btn-info" 
                                                onclick="viewApplication(<?php echo $app['application_id']; ?>)"
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($app['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-success" 
                                                    onclick="approveApplication(<?php echo $app['application_id']; ?>)"
                                                    title="Approve Application">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="showRejectionModal(<?php echo $app['application_id']; ?>)"
                                                    title="Reject Application">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
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

<!-- Application Details Modal -->
<div class="modal fade" id="applicationModal" tabindex="-1" aria-labelledby="applicationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="applicationModalLabel">Application Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="applicationModalBody">
                <!-- Dynamic content loaded here -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <div id="applicationActions">
                    <!-- Dynamic action buttons -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Reason Modal -->
<div class="modal fade" id="rejectionModal" tabindex="-1" aria-labelledby="rejectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectionModalLabel">Reject Application</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="rejectionForm" onsubmit="submitRejection(event)">
                <div class="modal-body">
                    <input type="hidden" id="rejectionApplicationId" name="application_id">
                    <div class="mb-3">
                        <label for="rejectionReason" class="form-label">
                            Reason for Rejection <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" 
                                  id="rejectionReason" 
                                  name="rejection_reason" 
                                  rows="4" 
                                  required 
                                  placeholder="Please provide a clear reason for rejection..."></textarea>
                        <small class="form-text text-muted">
                            This will be sent to the applicant via email.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-1"></i> Reject Application
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Base path for API calls - CRITICAL FIX
const BASE_PATH = '<?php echo BASE_PATH; ?>';
const CSRF_TOKEN = '<?php echo $auth->generateCSRFToken(); ?>';

/**
 * View application details
 */
function viewApplication(applicationId) {
    fetch(`${BASE_PATH}/api/applications/index.php?id=${applicationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const app = data.data;
                displayApplicationDetails(app);
            } else {
                alert('Error loading application: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load application details');
        });
}

/**
 * Display application details in modal
 */
function displayApplicationDetails(app) {
    const modalBody = document.getElementById('applicationModalBody');
    const modalActions = document.getElementById('applicationActions');
    
    // Status badge styling
    const statusClasses = {
        'pending': 'bg-warning text-dark',
        'approved': 'bg-success text-white',
        'rejected': 'bg-danger text-white'
    };
    const statusClass = statusClasses[app.status] || 'bg-secondary text-white';
    
    // Build modal content
    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted">Project Name</h6>
                <p class="fw-bold">${escapeHtml(app.project_name)}</p>
                
                <h6 class="text-muted">Project Lead</h6>
                <p>${escapeHtml(app.project_lead_name)}<br>
                   <small class="text-muted">${escapeHtml(app.project_lead_email)}</small>
                </p>
                
                <h6 class="text-muted">Profile Name (Login)</h6>
                <p><code>${escapeHtml(app.profile_name)}</code></p>
                
                ${app.project_email ? `
                    <h6 class="text-muted">Project Email</h6>
                    <p>${escapeHtml(app.project_email)}</p>
                ` : ''}
                
                ${app.project_website ? `
                    <h6 class="text-muted">Project Website</h6>
                    <p><a href="${escapeHtml(app.project_website)}" target="_blank">
                        ${escapeHtml(app.project_website)}
                        <i class="fas fa-external-link-alt ms-1"></i>
                    </a></p>
                ` : ''}
            </div>
            
            <div class="col-md-6">
                <h6 class="text-muted">Status</h6>
                <p><span class="badge ${statusClass}">${app.status.toUpperCase()}</span></p>
                
                <h6 class="text-muted">Submitted</h6>
                <p>${new Date(app.applied_at).toLocaleString()}</p>
                
                ${app.reviewed_at ? `
                    <h6 class="text-muted">Reviewed</h6>
                    <p>${new Date(app.reviewed_at).toLocaleString()}</p>
                ` : ''}
                
                ${app.rejection_reason ? `
                    <h6 class="text-muted text-danger">Rejection Reason</h6>
                    <p class="alert alert-danger">${escapeHtml(app.rejection_reason)}</p>
                ` : ''}
                
                ${app.presentation_file ? `
                    <h6 class="text-muted">Presentation</h6>
                    <a href="${BASE_PATH}/assets/uploads/presentations/${escapeHtml(app.presentation_file)}" 
                       target="_blank" 
                       class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-file-pdf me-1"></i> View Presentation
                    </a>
                ` : ''}
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <h6 class="text-muted">Project Description</h6>
                <p class="border rounded p-3 bg-light">${escapeHtml(app.description)}</p>
            </div>
        </div>
    `;
    
    // Build action buttons based on status
    if (app.status === 'pending') {
        modalActions.innerHTML = `
            <button type="button" class="btn btn-success" onclick="approveApplication(${app.application_id}); bootstrap.Modal.getInstance(document.getElementById('applicationModal')).hide();">
                <i class="fas fa-check me-1"></i> Approve
            </button>
            <button type="button" class="btn btn-danger" onclick="showRejectionModal(${app.application_id}); bootstrap.Modal.getInstance(document.getElementById('applicationModal')).hide();">
                <i class="fas fa-times me-1"></i> Reject
            </button>
        `;
    } else {
        modalActions.innerHTML = '';
    }
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('applicationModal'));
    modal.show();
}

/**
 * Approve application
 */
function approveApplication(applicationId) {
    if (!confirm('Are you sure you want to APPROVE this application?\n\nThis will:\n- Create a new project\n- Add the project lead as a team member\n- Send approval email to the applicant\n- Allow them to login and start using the system')) {
        return;
    }
    
    // Show loading state
    const btn = event.target;
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Approving...';
    
    fetch(`${BASE_PATH}/api/applications/review.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'approve',
            application_id: applicationId,
            csrf_token: CSRF_TOKEN
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Application approved successfully!\n\nProject ID: ' + data.project_id + '\n\nThe applicant will receive a confirmation email.');
            location.reload();
        } else {
            alert('❌ Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Failed to approve application. Please check the console for details.');
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    });
}

/**
 * Show rejection modal
 */
function showRejectionModal(applicationId) {
    document.getElementById('rejectionApplicationId').value = applicationId;
    document.getElementById('rejectionReason').value = '';
    document.getElementById('rejectionForm').classList.remove('was-validated');
    
    const modal = new bootstrap.Modal(document.getElementById('rejectionModal'));
    modal.show();
}

/**
 * Submit rejection
 */
function submitRejection(event) {
    event.preventDefault();
    
    const form = event.target;
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }
    
    const applicationId = document.getElementById('rejectionApplicationId').value;
    const rejectionReason = document.getElementById('rejectionReason').value;
    
    if (!rejectionReason.trim()) {
        alert('Please provide a reason for rejection.');
        return;
    }
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalHTML = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Rejecting...';
    
    fetch(`${BASE_PATH}/api/applications/review.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'reject',
            application_id: applicationId,
            rejection_reason: rejectionReason,
            csrf_token: CSRF_TOKEN
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Application rejected.\n\nThe applicant will receive an email with the rejection reason.');
            bootstrap.Modal.getInstance(document.getElementById('rejectionModal')).hide();
            location.reload();
        } else {
            alert('❌ Error: ' + data.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHTML;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Failed to reject application. Please check the console for details.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHTML;
    });
}

/**
 * Refresh applications list
 */
function refreshApplications() {
    location.reload();
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include '../../templates/footer.php'; ?>