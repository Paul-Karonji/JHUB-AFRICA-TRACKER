<?php
// dashboards/admin/reports.php - System Reports and Analytics
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_ADMIN);

// Get date range from filters or default to last 30 days
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Overall Statistics
$overallStats = [
    'total_projects' => $database->count('projects'),
    'active_projects' => $database->count('projects', 'status = ?', ['active']),
    'completed_projects' => $database->count('projects', 'status = ?', ['completed']),
    'terminated_projects' => $database->count('projects', 'status = ?', ['terminated']),
    'total_applications' => $database->count('project_applications'),
    'pending_applications' => $database->count('project_applications', 'status = ?', ['pending']),
    'approved_applications' => $database->count('project_applications', 'status = ?', ['approved']),
    'rejected_applications' => $database->count('project_applications', 'status = ?', ['rejected']),
    'total_mentors' => $database->count('mentors'),
    'active_mentors' => $database->count('mentors', 'is_active = 1'),
    'total_innovators' => $database->count('project_innovators', 'is_active = 1'),
    'total_admins' => $database->count('admins', 'is_active = 1')
];

// Projects by Stage
$projectsByStage = $database->getRows("
    SELECT current_stage, COUNT(*) as count
    FROM projects
    WHERE status = 'active'
    GROUP BY current_stage
    ORDER BY current_stage
");

$stageData = array_fill(1, 6, 0);
foreach ($projectsByStage as $stage) {
    $stageData[$stage['current_stage']] = $stage['count'];
}

// Recent projects in date range
$recentProjects = $database->getRows("
    SELECT DATE(created_at) as date, COUNT(*) as count
    FROM projects
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date DESC
", [$startDate, $endDate]);

// Applications statistics
$applicationStats = $database->getRows("
    SELECT DATE(applied_at) as date, status, COUNT(*) as count
    FROM project_applications
    WHERE applied_at BETWEEN ? AND ?
    GROUP BY DATE(applied_at), status
    ORDER BY date DESC
", [$startDate, $endDate]);

// Top performing mentors
$topMentors = $database->getRows("
    SELECT m.name, m.email, m.area_of_expertise,
           COUNT(DISTINCT pm.project_id) as projects_assigned,
           COUNT(DISTINCT mr.resource_id) as resources_shared,
           COUNT(DISTINCT pa.assessment_id) as assessments_created
    FROM mentors m
    LEFT JOIN project_mentors pm ON m.mentor_id = pm.mentor_id AND pm.is_active = 1
    LEFT JOIN mentor_resources mr ON m.mentor_id = mr.mentor_id
    LEFT JOIN project_assessments pa ON m.mentor_id = pa.mentor_id
    WHERE m.is_active = 1
    GROUP BY m.mentor_id
    ORDER BY projects_assigned DESC, resources_shared DESC
    LIMIT 10
");

// Project completion rate
$completionData = $database->getRow("
    SELECT 
        COUNT(*) as total_completed,
        AVG(DATEDIFF(completion_date, created_at)) as avg_days_to_complete
    FROM projects
    WHERE status = 'completed' AND completion_date IS NOT NULL
");

// Activity logs summary
$activitySummary = $database->getRows("
    SELECT user_type, action, COUNT(*) as count
    FROM activity_logs
    WHERE created_at BETWEEN ? AND ?
    GROUP BY user_type, action
    ORDER BY count DESC
    LIMIT 20
", [$startDate, $endDate]);

// Mentor engagement
$mentorEngagement = $database->getRows("
    SELECT m.name, m.email,
           COUNT(DISTINCT al.log_id) as total_actions,
           MAX(al.created_at) as last_activity
    FROM mentors m
    LEFT JOIN activity_logs al ON m.mentor_id = al.user_id AND al.user_type = 'mentor'
    WHERE m.is_active = 1 AND al.created_at BETWEEN ? AND ?
    GROUP BY m.mentor_id
    ORDER BY total_actions DESC
    LIMIT 10
", [$startDate, $endDate]);

$pageTitle = "System Reports";
include '../../templates/header.php';
?>

<div class="reports-dashboard">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">System Reports & Analytics</h1>
            <p class="text-muted">Comprehensive system performance overview</p>
        </div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <!-- Date Range Filter -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" 
                           value="<?php echo e($startDate); ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" 
                           value="<?php echo e($endDate); ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Apply Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Overall Statistics -->
    <h4 class="mb-3">Overall System Statistics</h4>
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Projects</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $overallStats['total_projects']; ?></div>
                            <small class="text-muted">
                                Active: <?php echo $overallStats['active_projects']; ?> | 
                                Completed: <?php echo $overallStats['completed_projects']; ?>
                            </small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-project-diagram fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Applications</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $overallStats['total_applications']; ?></div>
                            <small class="text-muted">
                                Pending: <?php echo $overallStats['pending_applications']; ?> | 
                                Approved: <?php echo $overallStats['approved_applications']; ?>
                            </small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Mentors</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $overallStats['active_mentors']; ?></div>
                            <small class="text-muted">Total: <?php echo $overallStats['total_mentors']; ?></small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-tie fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Innovators</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $overallStats['total_innovators']; ?></div>
                            <small class="text-muted">Active team members</small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects by Stage -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-layer-group me-2"></i>Active Projects by Stage
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="stageChart"></canvas>
                    <div class="mt-3">
                        <?php foreach ($stageData as $stage => $count): ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span>Stage <?php echo $stage; ?></span>
                                <strong><?php echo $count; ?> projects</strong>
                            </div>
                            <div class="progress" style="height: 20px;">
                                <div class="progress-bar bg-primary" role="progressbar" 
                                     style="width: <?php echo $overallStats['active_projects'] > 0 ? ($count / $overallStats['active_projects'] * 100) : 0; ?>%">
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie me-2"></i>Project Status Distribution
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="statusChart"></canvas>
                    <div class="mt-3">
                        <div class="d-flex justify-content-around text-center">
                            <div>
                                <div class="h4 mb-0 text-success"><?php echo $overallStats['active_projects']; ?></div>
                                <small class="text-muted">Active</small>
                            </div>
                            <div>
                                <div class="h4 mb-0 text-info"><?php echo $overallStats['completed_projects']; ?></div>
                                <small class="text-muted">Completed</small>
                            </div>
                            <div>
                                <div class="h4 mb-0 text-danger"><?php echo $overallStats['terminated_projects']; ?></div>
                                <small class="text-muted">Terminated</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performing Mentors -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-trophy me-2"></i>Top Performing Mentors
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (empty($topMentors)): ?>
                        <p class="text-muted text-center">No mentor data available</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Mentor</th>
                                    <th>Expertise</th>
                                    <th>Projects</th>
                                    <th>Resources</th>
                                    <th>Assessments</th>
                                    <th>Total Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topMentors as $index => $mentor): 
                                    $totalScore = ($mentor['projects_assigned'] * 3) + $mentor['resources_shared'] + ($mentor['assessments_created'] * 2);
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($index === 0): ?>
                                            <span class="badge bg-warning text-dark">🥇 #1</span>
                                        <?php elseif ($index === 1): ?>
                                            <span class="badge bg-secondary">🥈 #2</span>
                                        <?php elseif ($index === 2): ?>
                                            <span class="badge bg-danger">🥉 #3</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-dark">#<?php echo $index + 1; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo e($mentor['name']); ?></strong><br>
                                        <small class="text-muted"><?php echo e($mentor['email']); ?></small>
                                    </td>
                                    <td><?php echo e($mentor['area_of_expertise']); ?></td>
                                    <td><span class="badge bg-primary"><?php echo $mentor['projects_assigned']; ?></span></td>
                                    <td><span class="badge bg-info"><?php echo $mentor['resources_shared']; ?></span></td>
                                    <td><span class="badge bg-success"><?php echo $mentor['assessments_created']; ?></span></td>
                                    <td><strong><?php echo $totalScore; ?></strong></td>
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

    <!-- Activity Summary -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history me-2"></i>Activity Summary (<?php echo $startDate; ?> to <?php echo $endDate; ?>)
                    </h6>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($activitySummary)): ?>
                        <p class="text-muted">No activity in selected date range</p>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($activitySummary as $activity): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-<?php 
                                    echo $activity['user_type'] === 'admin' ? 'danger' : 
                                        ($activity['user_type'] === 'mentor' ? 'info' : 'success'); 
                                ?>">
                                    <?php echo ucfirst($activity['user_type']); ?>
                                </span>
                                <span class="ms-2"><?php echo e($activity['action']); ?></span>
                            </div>
                            <span class="badge bg-secondary rounded-pill"><?php echo $activity['count']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-fire me-2"></i>Most Active Mentors (<?php echo $startDate; ?> to <?php echo $endDate; ?>)
                    </h6>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if (empty($mentorEngagement)): ?>
                        <p class="text-muted">No mentor activity in selected date range</p>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($mentorEngagement as $mentor): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <strong><?php echo e($mentor['name']); ?></strong><br>
                                    <small class="text-muted"><?php echo e($mentor['email']); ?></small><br>
                                    <small class="text-muted">Last active: <?php echo timeAgo($mentor['last_activity']); ?></small>
                                </div>
                                <span class="badge bg-primary rounded-pill"><?php echo $mentor['total_actions']; ?> actions</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Metrics -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tachometer-alt me-2"></i>Performance Metrics
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <div class="border rounded p-3">
                                <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                                <h4 class="mb-0"><?php echo $completionData['total_completed'] ?? 0; ?></h4>
                                <small class="text-muted">Projects Completed</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="border rounded p-3">
                                <i class="fas fa-clock fa-3x text-info mb-2"></i>
                                <h4 class="mb-0"><?php echo $completionData['avg_days_to_complete'] ? round($completionData['avg_days_to_complete']) : 0; ?></h4>
                                <small class="text-muted">Avg Days to Complete</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="border rounded p-3">
                                <i class="fas fa-percentage fa-3x text-warning mb-2"></i>
                                <h4 class="mb-0">
                                    <?php 
                                    $approvalRate = $overallStats['total_applications'] > 0 
                                        ? round(($overallStats['approved_applications'] / $overallStats['total_applications']) * 100) 
                                        : 0;
                                    echo $approvalRate;
                                    ?>%
                                </h4>
                                <small class="text-muted">Application Approval Rate</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="border rounded p-3">
                                <i class="fas fa-users fa-3x text-primary mb-2"></i>
                                <h4 class="mb-0">
                                    <?php 
                                    $avgTeamSize = $overallStats['total_projects'] > 0 
                                        ? round($overallStats['total_innovators'] / $overallStats['total_projects'], 1) 
                                        : 0;
                                    echo $avgTeamSize;
                                    ?>
                                </h4>
                                <small class="text-muted">Avg Team Size</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-download me-2"></i>Export Reports
            </h6>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">Download comprehensive reports in various formats</p>
            <div class="btn-group">
                <button class="btn btn-outline-primary" onclick="alert('CSV export feature coming soon!')">
                    <i class="fas fa-file-csv me-1"></i> Export as CSV
                </button>
                <button class="btn btn-outline-success" onclick="alert('Excel export feature coming soon!')">
                    <i class="fas fa-file-excel me-1"></i> Export as Excel
                </button>
                <button class="btn btn-outline-danger" onclick="window.print()">
                    <i class="fas fa-file-pdf me-1"></i> Print/Save as PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Stage Distribution Chart
const stageCtx = document.getElementById('stageChart').getContext('2d');
new Chart(stageCtx, {
    type: 'bar',
    data: {
        labels: ['Stage 1', 'Stage 2', 'Stage 3', 'Stage 4', 'Stage 5', 'Stage 6'],
        datasets: [{
            label: 'Active Projects',
            data: <?php echo json_encode(array_values($stageData)); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.7)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Status Distribution Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Active', 'Completed', 'Terminated'],
        datasets: [{
            data: [
                <?php echo $overallStats['active_projects']; ?>,
                <?php echo $overallStats['completed_projects']; ?>,
                <?php echo $overallStats['terminated_projects']; ?>
            ],
            backgroundColor: [
                'rgba(40, 167, 69, 0.7)',
                'rgba(23, 162, 184, 0.7)',
                'rgba(220, 53, 69, 0.7)'
            ],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>

<?php include '../../templates/footer.php'; ?>