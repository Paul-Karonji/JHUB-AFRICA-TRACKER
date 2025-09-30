<?php
// dashboards/admin/index.php - Complete Admin Dashboard
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_ADMIN);

$adminId = $auth->getUserId();
$adminName = $auth->getUserIdentifier();

// Get comprehensive statistics
$stats = getSystemStatistics();

// Get recent projects
$recentProjects = $database->getRows("
    SELECT p.*, pa.applied_at 
    FROM projects p 
    LEFT JOIN project_applications pa ON p.created_from_application = pa.application_id
    ORDER BY p.created_at DESC 
    LIMIT 5
");

// Get pending applications
$pendingApplications = $database->getRows("
    SELECT * FROM project_applications 
    WHERE status = 'pending' 
    ORDER BY applied_at ASC 
    LIMIT 10
");

// Get recent activity
$recentActivity = $database->getRows("
    SELECT * FROM activity_logs 
    WHERE user_type != 'system' 
    ORDER BY created_at DESC 
    LIMIT 10
");

$pageTitle = "Admin Dashboard";
include '../../templates/header.php';
?>

<div class="admin-dashboard">
    <!-- Dashboard Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Admin Dashboard</h1>
            <p class="text-muted">Welcome back, <?php echo e($adminName); ?></p>
        </div>
        <div class="dashboard-actions">
            <a href="register-mentor.php" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i> Add Mentor
            </a>
            <a href="admin-management.php" class="btn btn-secondary">
                <i class="fas fa-users-cog me-1"></i> Manage Admins
            </a>
            <a href="reports.php" class="btn btn-info">
                <i class="fas fa-chart-bar me-1"></i> View Reports
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Active Projects
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['active_projects']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-project-diagram fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Pending Applications
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['pending_applications']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Active Mentors
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['total_mentors']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-tie fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Completed Projects
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['completed_projects']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="applications.php" class="btn btn-outline-primary btn-block w-100">
                                <i class="fas fa-clipboard-list me-2"></i>Review Applications
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="register-mentor.php" class="btn btn-outline-success btn-block w-100">
                                <i class="fas fa-user-plus me-2"></i>Register Mentor
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="mentors.php" class="btn btn-outline-info btn-block w-100">
                                <i class="fas fa-users me-2"></i>Manage Mentors
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="projects.php" class="btn btn-outline-warning btn-block w-100">
                                <i class="fas fa-project-diagram me-2"></i>Manage Projects
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="admin-management.php" class="btn btn-outline-secondary btn-block w-100">
                                <i class="fas fa-users-cog me-2"></i>Admin Management
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="reports.php" class="btn btn-outline-dark btn-block w-100">
                                <i class="fas fa-chart-line me-2"></i>System Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Pending Applications -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-clipboard-list me-2"></i>Pending Applications
                    </h6>
                    <a href="applications.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingApplications)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No pending applications</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($pendingApplications as $app): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo e($app['project_name']); ?></h6>
                                        <p class="mb-1 text-muted small">
                                            Lead: <?php echo e($app['project_lead_name']); ?>
                                        </p>
                                        <small class="text-muted">
                                            Applied: <?php echo timeAgo($app['applied_at']); ?>
                                        </small>
                                    </div>
                                    <a href="applications.php?id=<?php echo $app['application_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        Review
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Projects -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-project-diagram me-2"></i>Recent Projects
                    </h6>
                    <a href="projects.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentProjects)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No projects yet</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentProjects as $project): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo e($project['project_name']); ?></h6>
                                        <p class="mb-1">
                                            <span class="badge bg-primary">Stage <?php echo $project['current_stage']; ?></span>
                                            <span class="badge bg-<?php echo $project['status'] === 'active' ? 'success' : ($project['status'] === 'completed' ? 'info' : 'danger'); ?>">
                                                <?php echo ucfirst($project['status']); ?>
                                            </span>
                                        </p>
                                        <small class="text-muted">
                                            Created: <?php echo timeAgo($project['created_at']); ?>
                                        </small>
                                    </div>
                                    <a href="projects.php?id=<?php echo $project['project_id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history me-2"></i>Recent System Activity
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($recentActivity)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No recent activity</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User Type</th>
                                        <th>Action</th>
                                        <th>Description</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentActivity as $activity): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $activity['user_type'] === 'admin' ? 'danger' : 
                                                    ($activity['user_type'] === 'mentor' ? 'info' : 'success'); 
                                            ?>">
                                                <?php echo ucfirst($activity['user_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo e($activity['action']); ?></td>
                                        <td><?php echo e($activity['description']); ?></td>
                                        <td><?php echo timeAgo($activity['created_at']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>