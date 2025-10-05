<?php
// dashboards/admin/index.php - Complete Admin Dashboard
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_ADMIN);

$adminId = $auth->getUserId();
$admin = $database->getRow("SELECT * FROM admins WHERE admin_id = ?", [$adminId]);

// Get comprehensive statistics
$stats = [
    'total_projects' => $database->count('projects'),
    'active_projects' => $database->count('projects', 'status = ?', ['active']),
    'completed_projects' => $database->count('projects', 'status = ?', ['completed']),
    'pending_applications' => $database->count('project_applications', 'status = ?', ['pending']),
    'total_mentors' => $database->count('mentors'),
    'active_mentors' => $database->count('mentors', 'is_active = 1'),
    'total_innovators' => $database->count('project_innovators', 'is_active = 1'),
    'total_admins' => $database->count('admins', 'is_active = 1')
];

// Projects by stage
$projectsByStage = [];
for ($i = 1; $i <= 6; $i++) {
    $projectsByStage[$i] = $database->count('projects', 'current_stage = ? AND status = ?', [$i, 'active']);
}

// Recent projects
$recentProjects = $database->getRows("
    SELECT p.*, 
           (SELECT COUNT(*) FROM project_innovators WHERE project_id = p.project_id AND is_active = 1) as team_count,
           (SELECT COUNT(*) FROM project_mentors WHERE project_id = p.project_id AND is_active = 1) as mentor_count
    FROM projects p
    ORDER BY p.created_at DESC
    LIMIT 5
");

// Pending applications
$pendingApplications = $database->getRows("
    SELECT * FROM project_applications
    WHERE status = 'pending'
    ORDER BY applied_at ASC
    LIMIT 10
");

// Recent activity
$recentActivity = $database->getRows("
    SELECT * FROM activity_logs
    ORDER BY created_at DESC
    LIMIT 10
");

// Monthly growth - projects created in last 6 months
$monthlyGrowth = $database->getRows("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM projects
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
");

$pageTitle = "Admin Dashboard";
include '../../templates/header.php';
?>

<div class="admin-dashboard">
    <!-- Dashboard Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Admin Dashboard</h1>
            <p class="text-muted">Welcome back, <?php echo e($admin['username']); ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="register-mentor.php" class="btn btn-primary">
                <i class="fas fa-user-plus me-1"></i> Add Mentor
            </a>
            <a href="applications.php" class="btn btn-warning">
                <i class="fas fa-clipboard-list me-1"></i> 
                Applications 
                <?php if ($stats['pending_applications'] > 0): ?>
                    <span class="badge bg-danger"><?php echo $stats['pending_applications']; ?></span>
                <?php endif; ?>
            </a>
            <a href="reports.php" class="btn btn-info">
                <i class="fas fa-chart-bar me-1"></i> Reports
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Active Projects</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['active_projects']; ?></div>
                            <small class="text-muted">of <?php echo $stats['total_projects']; ?> total</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-project-diagram fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pending Applications</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['pending_applications']; ?></div>
                            <?php if ($stats['pending_applications'] > 0): ?>
                                <small class="text-danger">Needs review!</small>
                            <?php else: ?>
                                <small class="text-muted">All caught up</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Mentors</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['active_mentors']; ?></div>
                            <small class="text-muted">of <?php echo $stats['total_mentors']; ?> total</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-tie fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Innovators</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $stats['total_innovators']; ?></div>
                            <small class="text-muted">Team members</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Projects by Stage -->
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Projects by Stage</h6>
                    <a href="projects.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="stage-distribution">
                        <?php foreach ($projectsByStage as $stage => $count): ?>
                        <div class="stage-item mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold">Stage <?php echo $stage; ?>: <?php echo getStageName($stage); ?></span>
                                <span class="badge bg-primary"><?php echo $count; ?> projects</span>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar" style="width: <?php echo $stats['active_projects'] > 0 ? round(($count / $stats['active_projects']) * 100) : 0; ?>%">
                                    <?php if ($stats['active_projects'] > 0): ?>
                                        <?php echo round(($count / $stats['active_projects']) * 100); ?>%
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Projects -->
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Projects</h6>
                    <a href="projects.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentProjects)): ?>
                        <p class="text-muted mb-0">No projects yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Stage</th>
                                        <th>Team</th>
                                        <th>Mentors</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentProjects as $project): ?>
                                    <tr>
                                        <td>
                                            <a href="projects.php?id=<?php echo $project['project_id']; ?>">
                                                <?php echo e($project['project_name']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">Stage <?php echo $project['current_stage']; ?></span>
                                        </td>
                                        <td><?php echo $project['team_count']; ?></td>
                                        <td><?php echo $project['mentor_count']; ?></td>
                                        <td><small><?php echo timeAgo($project['created_at']); ?></small></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($recentActivity)): ?>
                        <p class="text-muted mb-0">No recent activity.</p>
                    <?php else: ?>
                        <div class="activity-feed">
                            <?php foreach ($recentActivity as $activity): ?>
                            <div class="activity-item border-bottom pb-2 mb-2">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <span class="badge bg-<?php echo $activity['user_type'] === 'admin' ? 'primary' : ($activity['user_type'] === 'mentor' ? 'success' : 'info'); ?>">
                                            <?php echo ucfirst($activity['user_type']); ?>
                                        </span>
                                        <strong class="ms-2"><?php echo e($activity['action']); ?></strong>
                                    </div>
                                    <small class="text-muted"><?php echo timeAgo($activity['created_at']); ?></small>
                                </div>
                                <small class="text-muted"><?php echo e($activity['description']); ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Pending Applications -->
            <div class="card shadow mb-4">
                <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">Pending Applications</h6>
                    <span class="badge bg-danger"><?php echo count($pendingApplications); ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($pendingApplications)): ?>
                        <p class="text-muted mb-0 text-center py-3">
                            <i class="fas fa-check-circle fa-2x mb-2 d-block"></i>
                            All caught up!
                        </p>
                    <?php else: ?>
                        <?php foreach ($pendingApplications as $application): ?>
                        <div class="application-item border-bottom pb-2 mb-2">
                            <div class="fw-bold"><?php echo e($application['project_name']); ?></div>
                            <small class="text-muted">
                                By: <?php echo e($application['project_lead_name']); ?><br>
                                Applied: <?php echo timeAgo($application['applied_at']); ?>
                            </small>
                            <div class="mt-2">
                                <a href="applications.php?id=<?php echo $application['application_id']; ?>" class="btn btn-sm btn-primary w-100">
                                    Review Application
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (count($pendingApplications) > 5): ?>
                        <div class="text-center mt-2">
                            <a href="applications.php" class="btn btn-sm btn-outline-warning">View All</a>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">System Overview</h6>
                </div>
                <div class="card-body">
                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Completed Projects:</span>
                            <strong><?php echo $stats['completed_projects']; ?></strong>
                        </div>
                    </div>
                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Total Mentors:</span>
                            <strong><?php echo $stats['total_mentors']; ?></strong>
                        </div>
                    </div>
                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Active Innovators:</span>
                            <strong><?php echo $stats['total_innovators']; ?></strong>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="d-flex justify-content-between">
                            <span>System Admins:</span>
                            <strong><?php echo $stats['total_admins']; ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="register-mentor.php" class="btn btn-primary">
                            <i class="fas fa-user-plus me-1"></i> Add New Mentor
                        </a>
                        <a href="applications.php" class="btn btn-warning">
                            <i class="fas fa-clipboard-list me-1"></i> Review Applications
                        </a>
                        <a href="projects.php" class="btn btn-info">
                            <i class="fas fa-project-diagram me-1"></i> Manage Projects
                        </a>
                        <a href="reports.php" class="btn btn-success">
                            <i class="fas fa-chart-bar me-1"></i> View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.border-left-primary {
    border-left: 4px solid #2c409a;
}
.border-left-success {
    border-left: 4px solid #3fa845;
}
.border-left-info {
    border-left: 4px solid #253683;
}
.border-left-warning {
    border-left: 4px solid #f6c23e;
}
</style>

<?php include '../../templates/footer.php'; ?>