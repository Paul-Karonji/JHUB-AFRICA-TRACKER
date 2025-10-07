<?php
// dashboards/admin/reports.php - Complete System Reports
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_ADMIN);

// Date filters
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Overall Statistics
$stats = [
    'total_projects' => $database->count('projects'),
    'active_projects' => $database->count('projects', 'status = ?', ['active']),
    'completed_projects' => $database->count('projects', 'status = ?', ['completed']),
    'terminated_projects' => $database->count('projects', 'status = ?', ['terminated']),
    'pending_applications' => $database->count('project_applications', 'status = ?', ['pending']),
    'approved_applications' => $database->count('project_applications', 'status = ?', ['approved']),
    'rejected_applications' => $database->count('project_applications', 'status = ?', ['rejected']),
    'total_mentors' => $database->count('mentors'),
    'active_mentors' => $database->count('mentors', 'is_active = 1'),
    'total_innovators' => $database->count('project_innovators', 'is_active = 1'),
    'total_resources' => $database->count('mentor_resources', 'is_deleted = 0'),
    'total_assessments' => $database->count('project_assessments', 'is_deleted = 0'),
    'total_learning' => $database->count('learning_objectives', 'is_deleted = 0')
];

// Projects by Stage
$projectsByStage = [];
for ($i = 1; $i <= 6; $i++) {
    $projectsByStage[$i] = $database->count('projects', 'current_stage = ? AND status = ?', [$i, 'active']);
}

// Top Mentors
$topMentors = $database->getRows("
    SELECT m.name, m.email,
           COUNT(DISTINCT pm.project_id) as projects,
           COUNT(DISTINCT mr.resource_id) as resources,
           COUNT(DISTINCT pa.assessment_id) as assessments
    FROM mentors m
    LEFT JOIN project_mentors pm ON m.mentor_id = pm.mentor_id AND pm.is_active = 1
    LEFT JOIN mentor_resources mr ON m.mentor_id = mr.mentor_id AND mr.is_deleted = 0
    LEFT JOIN project_assessments pa ON m.mentor_id = pa.mentor_id AND pa.is_deleted = 0
    WHERE m.is_active = 1
    GROUP BY m.mentor_id
    ORDER BY projects DESC, resources DESC
    LIMIT 10
");

// Recent Activity Summary
$activitySummary = $database->getRows("
    SELECT DATE(created_at) as date,
           user_type,
           COUNT(*) as activity_count
    FROM activity_logs
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE(created_at), user_type
    ORDER BY date DESC
", [$startDate, $endDate]);

// Growth Data (last 6 months)
$growthData = $database->getRows("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
           COUNT(*) as projects
    FROM projects
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");

$pageTitle = "System Reports & Analytics";
include '../../templates/header.php';
?>

<div class="admin-dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">System Reports & Analytics</h1>
            <p class="text-muted">Comprehensive system performance overview</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print me-1"></i> Print Report
            </button>
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4 mb-2">
                    <label class="form-label">Start Date:</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label">End Date:</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                </div>
                <div class="col-md-4 mb-2">
                    <button type="submit" class="btn btn-primary w-100">Apply Date Range</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Overall Statistics -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-primary shadow">
                <div class="card-body text-center">
                    <i class="fas fa-project-diagram fa-3x text-primary mb-2"></i>
                    <h3 class="mb-0"><?php echo $stats['total_projects']; ?></h3>
                    <p class="text-muted mb-0">Total Projects</p>
                    <small class="text-success"><?php echo $stats['active_projects']; ?> active</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-success shadow">
                <div class="card-body text-center">
                    <i class="fas fa-user-tie fa-3x text-success mb-2"></i>
                    <h3 class="mb-0"><?php echo $stats['total_mentors']; ?></h3>
                    <p class="text-muted mb-0">Total Mentors</p>
                    <small class="text-success"><?php echo $stats['active_mentors']; ?> active</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-info shadow">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x text-info mb-2"></i>
                    <h3 class="mb-0"><?php echo $stats['total_innovators']; ?></h3>
                    <p class="text-muted mb-0">Total Innovators</p>
                    <small>Team members</small>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-warning shadow">
                <div class="card-body text-center">
                    <i class="fas fa-clipboard-list fa-3x text-warning mb-2"></i>
                    <h3 class="mb-0"><?php echo $stats['pending_applications']; ?></h3>
                    <p class="text-muted mb-0">Pending Apps</p>
                    <small><?php echo $stats['approved_applications']; ?> approved</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Projects by Stage -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Active Projects by Stage</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($projectsByStage as $stage => $count): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Stage <?php echo $stage; ?>: <?php echo getStageName($stage); ?></span>
                            <strong><?php echo $count; ?> projects</strong>
                        </div>
                        <div class="progress" style="height: 25px;">
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

            <!-- Growth Chart -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Project Growth (Last 6 Months)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>New Projects</th>
                                    <th>Growth</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($growthData as $data): ?>
                                <tr>
                                    <td><?php echo date('F Y', strtotime($data['month'] . '-01')); ?></td>
                                    <td><?php echo $data['projects']; ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px; width: 100px;">
                                            <div class="progress-bar bg-success" style="width: <?php echo min(100, $data['projects'] * 10); ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Resource & Learning Stats -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">Platform Activity</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <i class="fas fa-book fa-2x text-success mb-2"></i>
                            <h4><?php echo $stats['total_resources']; ?></h4>
                            <p class="text-muted mb-0">Resources Shared</p>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-clipboard-check fa-2x text-info mb-2"></i>
                            <h4><?php echo $stats['total_assessments']; ?></h4>
                            <p class="text-muted mb-0">Assessments Created</p>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-graduation-cap fa-2x text-warning mb-2"></i>
                            <h4><?php echo $stats['total_learning']; ?></h4>
                            <p class="text-muted mb-0">Learning Objectives</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Top Mentors -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Top Mentors</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($topMentors)): ?>
                        <p class="text-muted mb-0">No mentor data yet.</p>
                    <?php else: ?>
                        <?php foreach ($topMentors as $index => $mentor): ?>
                        <div class="mentor-item mb-3 pb-3 <?php echo $index < count($topMentors) - 1 ? 'border-bottom' : ''; ?>">
                            <div class="d-flex align-items-center mb-2">
                                <div class="badge bg-primary me-2">#<?php echo $index + 1; ?></div>
                                <strong><?php echo e($mentor['name']); ?></strong>
                            </div>
                            <div class="small">
                                <div><i class="fas fa-project-diagram me-1 text-primary"></i> <?php echo $mentor['projects']; ?> projects</div>
                                <div><i class="fas fa-book me-1 text-success"></i> <?php echo $mentor['resources']; ?> resources</div>
                                <div><i class="fas fa-clipboard-check me-1 text-info"></i> <?php echo $mentor['assessments']; ?> assessments</div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Application Statistics -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">Application Stats</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Approved</span>
                            <strong class="text-success"><?php echo $stats['approved_applications']; ?></strong>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" style="width: <?php echo $stats['approved_applications'] + $stats['rejected_applications'] > 0 ? round(($stats['approved_applications'] / ($stats['approved_applications'] + $stats['rejected_applications'])) * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Rejected</span>
                            <strong class="text-danger"><?php echo $stats['rejected_applications']; ?></strong>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-danger" style="width: <?php echo $stats['approved_applications'] + $stats['rejected_applications'] > 0 ? round(($stats['rejected_applications'] / ($stats['approved_applications'] + $stats['rejected_applications'])) * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Pending</span>
                            <strong class="text-warning"><?php echo $stats['pending_applications']; ?></strong>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-warning" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, .sidebar, .navbar { display: none !important; }
    .card { border: 1px solid #ddd !important; page-break-inside: avoid; }
}
</style>

<?php include '../../templates/footer.php'; ?>