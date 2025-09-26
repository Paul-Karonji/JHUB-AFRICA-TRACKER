<?php
// dashboards/admin/index.php - Comprehensive Admin Dashboard
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_ADMIN);

$adminId = $auth->getUserId();
$adminName = $auth->getUserIdentifier();

// Get comprehensive statistics
$stats = getSystemStatistics();

// Get recent activity
$recentProjects = $database->getRows("
    SELECT p.*, pa.applied_at 
    FROM projects p 
    LEFT JOIN project_applications pa ON p.created_from_application = pa.application_id
    ORDER BY p.created_at DESC 
    LIMIT 5
");

$pendingApplications = $database->getRows("
    SELECT * FROM project_applications 
    WHERE status = 'pending' 
    ORDER BY applied_at ASC 
    LIMIT 10
");

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
                                Pending Applications
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['pending_applications']; ?>
                            </div>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
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
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-1"><?php echo e($app['project_name']); ?></h6>
                                        <p class="mb-1 text-muted">
                                            Lead: <?php echo e($app['project_lead_name']); ?>
                                        </p>
                                        <small class="text-muted">
                                            Applied: <?php echo formatDate($app['applied_at']); ?>
                                        </small>
                                    </div>
                                    <div class="btn-group-vertical btn-group-sm">
                                        <a href="applications.php?action=review&id=<?php echo $app['application_id']; ?>" 
                                           class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="../../api/projects/create.php" class="btn btn-outline-primary btn-block">
                                <i class="fas fa-plus-circle me-2"></i>Create Project Directly
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="register-mentor.php" class="btn btn-outline-success btn-block">
                                <i class="fas fa-user-plus me-2"></i>Register Mentor
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="mentors.php" class="btn btn-outline-info btn-block">
                                <i class="fas fa-users me-2"></i>Manage Mentors
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="projects.php" class="btn btn-outline-warning btn-block">
                                <i class="fas fa-project-diagram me-2"></i>Manage Projects
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="admin-management.php" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-users-cog me-2"></i>Admin Management
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="reports.php" class="btn btn-outline-dark btn-block">
                                <i class="fas fa-chart-line me-2"></i>System Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Projects & Activity -->
    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history me-2"></i>Recent Projects
                    </h6>
                    <a href="projects.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Stage</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentProjects as $project): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo e($project['project_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo e($project['project_lead_name']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            Stage <?php echo $project['current_stage']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = [
                                            'active' => 'success',
                                            'completed' => 'info',
                                            'terminated' => 'danger'
                                        ];
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass[$project['status']] ?? 'secondary'; ?>">
                                            <?php echo ucfirst($project['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($project['created_at']); ?></td>
                                    <td>
                                        <a href="../../public/project-details.php?id=<?php echo $project['project_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bell me-2"></i>Recent Activity
                    </h6>
                </div>
                <div class="card-body">
                    <div class="activity-list">
                        <?php foreach ($recentActivity as $activity): ?>
                        <div class="activity-item mb-3">
                            <div class="d-flex">
                                <div class="activity-icon me-3">
                                    <i class="fas fa-circle text-primary"></i>
                                </div>
                                <div class="activity-content">
                                    <p class="mb-1"><?php echo e($activity['description']); ?></p>
                                    <small class="text-muted"><?php echo timeAgo($activity['created_at']); ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>