<?php
// dashboards/admin/applications.php
// Admin Application Management
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_ADMIN);

// Get filter status
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query based on filter
$whereClause = '';
$params = [];

if ($statusFilter !== 'all') {
    $whereClause = 'WHERE status = ?';
    $params[] = $statusFilter;
}

// Get applications
$applications = $database->getRows("
    SELECT * FROM project_applications 
    {$whereClause}
    ORDER BY 
        CASE 
            WHEN status = 'pending' THEN 1
            WHEN status = 'approved' THEN 2
            WHEN status = 'rejected' THEN 3
        END,
        applied_at DESC
", $params);

// Count by status
$stats = [
    'total' => $database->count('project_applications'),
    'pending' => $database->count('project_applications', 'status = ?', ['pending']),
    'approved' => $database->count('project_applications', 'status = ?', ['approved']),
    'rejected' => $database->count('project_applications', 'status = ?', ['rejected'])
];

$pageTitle = "Application Management";
include '../../templates/header.php';
?>

<div class="admin-applications">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Application Management</h1>
            <p class="text-muted">Review and manage project applications</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Applications</div>
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
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Pending Review</div>
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
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Approved</div>
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
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Rejected</div>
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

    <!-- Filters and Applications Table -->
    <div class="card shadow">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Applications</h5>
                <div class="btn-group">
                    <a href="?status=all" class="btn btn-sm <?php echo $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        All (<?php echo $stats['total']; ?>)
                    </a>
                    <a href="?status=pending" class="btn btn-sm <?php echo $statusFilter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                        Pending (<?php echo $stats['pending']; ?>)
                    </a>
                    <a href="?status=approved" class="btn btn-sm <?php echo $statusFilter === 'approved' ? 'btn-success' : 'btn-outline-success'; ?>">
                        Approved (<?php echo $stats['approved']; ?>)
                    </a>
                    <a href="?status=rejected" class="btn btn-sm <?php echo $statusFilter === 'rejected' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                        Rejected (<?php echo $stats['rejected']; ?>)
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($applications)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No applications found</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
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
                                <td>#<?php echo $app['application_id']; ?></td>
                                <td>
                                    <strong><?php echo e($app['project_name']); ?></strong>
                                    <br><small class="text-muted"><?php echo e($app['profile_name']); ?></small>
                                </td>
                                <td>
                                    <?php echo e($app['project_lead_name']); ?>
                                    <br><small class="text-muted"><?php echo e($app['project_lead_email']); ?></small>
                                </td>
                                <td><?php echo formatDate($app['applied_at']); ?></td>
                                <td>
                                    <?php
                                    $statusBadges = [
                                        'pending' => '<span class="badge bg-warning">Pending</span>',
                                        'approved' => '<span class="badge bg-success">Approved</span>',
                                        'rejected' => '<span class="badge bg-danger">Rejected</span>'
                                    ];
                                    echo $statusBadges[$app['status']];
                                    ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary btn-review-application" 
                                            data-application-id="<?php echo $app['application_id']; ?>">
                                        <i class="fas fa-eye"></i> Review
                                    </button>
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

<!-- Include modal templates -->
<?php include '../../templates/modals.php'; ?>

<?php include '../../templates/footer.php'; ?>