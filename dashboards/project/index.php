<?php
// dashboards/project/index.php
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_PROJECT);

$projectId = $auth->getUserId();

// Get project information
$project = $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);

if (!$project) {
    die("Project not found");
}

// Get team members
$teamMembers = $database->getRows("
    SELECT * FROM project_innovators 
    WHERE project_id = ? AND is_active = 1
    ORDER BY added_at ASC
", [$projectId]);

// Get assigned mentors
$mentors = $database->getRows("
    SELECT m.*, pm.assigned_at
    FROM mentors m
    INNER JOIN project_mentors pm ON m.mentor_id = pm.mentor_id
    WHERE pm.project_id = ? AND pm.is_active = 1
    ORDER BY pm.assigned_at ASC
", [$projectId]);

// Get recent resources
$recentResources = $database->getRows("
    SELECT mr.*, m.name as mentor_name
    FROM mentor_resources mr
    INNER JOIN mentors m ON mr.mentor_id = m.mentor_id
    WHERE mr.project_id = ? AND mr.is_deleted = 0
    ORDER BY mr.created_at DESC
    LIMIT 5
", [$projectId]);

// Get statistics
$projectStats = [
    'team_members' => count($teamMembers),
    'mentors' => count($mentors),
    'resources' => $database->count('mentor_resources', 'project_id = ? AND is_deleted = 0', [$projectId]),
    'assessments' => $database->count('project_assessments', 'project_id = ? AND is_deleted = 0', [$projectId]),
    'learning_objectives' => $database->count('learning_objectives', 'project_id = ? AND is_deleted = 0', [$projectId]),
    'comments' => $database->count('comments', 'project_id = ? AND is_deleted = 0', [$projectId])
];

// Stage descriptions array (moved from function)
$stageDescriptions = [
    1 => 'Initial project setup and team building',
    2 => 'Working with mentors for guidance and support',
    3 => 'Project assessment and evaluation in progress',
    4 => 'Focused on skills development and learning',
    5 => 'Progress monitoring and feedback collection',
    6 => 'Final showcase and ecosystem integration'
];

$pageTitle = $project['project_name'] . " - Dashboard";
include '../../templates/header.php';
?>

<div class="project-dashboard">
    <!-- Dashboard Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?php echo e($project['project_name']); ?></h1>
            <p class="text-muted mb-0">Project Lead: <?php echo e($project['project_lead_name']); ?></p>
        </div>
        <div class="d-flex gap-2">
            <span class="badge bg-primary fs-6">Stage <?php echo $project['current_stage']; ?></span>
            <span class="badge bg-<?php echo $project['status'] === 'active' ? 'success' : ($project['status'] === 'completed' ? 'info' : 'danger'); ?> fs-6">
                <?php echo ucfirst($project['status']); ?>
            </span>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-primary">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="team.php" class="btn btn-primary w-100">
                                <i class="fas fa-users me-1"></i> Manage Team
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="resources.php" class="btn btn-success w-100">
                                <i class="fas fa-book me-1"></i> View Resources
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="progress.php" class="btn btn-info w-100">
                                <i class="fas fa-chart-line me-1"></i> Track Progress
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="profile.php" class="btn btn-warning w-100">
                                <i class="fas fa-cog me-1"></i> Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Team Members</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $projectStats['team_members']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Mentors</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $projectStats['mentors']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-tie fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Resources</div>
                            <div class="h5 mb-0 font-weight-bold"><?php echo $projectStats['resources']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-book fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Project Progress Card -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Project Progress</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Current Stage: <?php echo getStageName($project['current_stage']); ?></h6>
                        <p class="text-muted small mb-2">
                            <?php echo $stageDescriptions[$project['current_stage']] ?? 'Stage in progress'; ?>
                        </p>
                    </div>
                    
                    <div class="progress mb-3" style="height: 30px;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                             style="width: <?php echo getStageProgress($project['current_stage']); ?>%">
                            <?php echo number_format(getStageProgress($project['current_stage']), 0); ?>% Complete
                        </div>
                    </div>

                    <!-- Stage Timeline -->
                    <div class="row text-center mb-3">
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                        <div class="col-2">
                            <div class="stage-indicator <?php echo $i <= $project['current_stage'] ? 'active' : ''; ?>">
                                <div class="stage-circle mb-1">
                                    <?php if ($i < $project['current_stage']): ?>
                                        <i class="fas fa-check"></i>
                                    <?php elseif ($i == $project['current_stage']): ?>
                                        <i class="fas fa-circle"></i>
                                    <?php else: ?>
                                        <?php echo $i; ?>
                                    <?php endif; ?>
                                </div>
                                <small class="d-block">Stage <?php echo $i; ?></small>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>

                    <div class="text-center">
                        <a href="progress.php" class="btn btn-primary">
                            <i class="fas fa-info-circle me-1"></i> View Detailed Progress
                        </a>
                    </div>
                </div>
            </div>

            <!-- Project Overview Card -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Project Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Description:</strong>
                        <p class="mt-2"><?php echo nl2br(e($project['description'])); ?></p>
                    </div>
                    
                    <?php if ($project['project_website']): ?>
                    <div class="mb-3">
                        <strong>Website:</strong>
                        <a href="<?php echo e($project['project_website']); ?>" target="_blank" class="ms-2">
                            <?php echo e($project['project_website']); ?> <i class="fas fa-external-link-alt"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($project['target_market']): ?>
                    <div class="mb-3">
                        <strong>Target Market:</strong>
                        <p class="mt-1"><?php echo e($project['target_market']); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Project Lead:</strong>
                            <p><?php echo e($project['project_lead_name']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Started:</strong>
                            <p><?php echo formatDate($project['created_at'], 'F j, Y'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Resources Card -->
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Resources</h5>
                    <a href="resources.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentResources)): ?>
                        <p class="text-muted mb-0">No resources shared yet. Your mentors will share resources to help your project grow.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentResources as $resource): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex w-100 justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo e($resource['title']); ?></h6>
                                        <p class="mb-1 small"><?php echo truncateText(e($resource['description']), 80); ?></p>
                                        <small class="text-muted">
                                            <span class="badge bg-secondary"><?php echo e($resource['resource_type']); ?></span>
                                            By: <?php echo e($resource['mentor_name']); ?> • <?php echo timeAgo($resource['created_at']); ?>
                                        </small>
                                    </div>
                                    <div>
                                        <?php if ($resource['resource_url']): ?>
                                            <a href="<?php echo e($resource['resource_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        <?php elseif ($resource['file_path']): ?>
                                            <a href="../../assets/uploads/resources/<?php echo e($resource['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Team Members Card -->
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Team Members</h5>
                    <a href="team.php" class="btn btn-sm btn-primary">Manage</a>
                </div>
                <div class="card-body">
                    <?php if (empty($teamMembers)): ?>
                        <p class="text-muted small">No team members yet. Add your team to start collaborating!</p>
                        <a href="team.php" class="btn btn-primary btn-sm w-100">Add Team Members</a>
                    <?php else: ?>
                        <?php foreach ($teamMembers as $member): ?>
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo getGravatar($member['email'], 40); ?>" class="rounded-circle me-2" alt="<?php echo e($member['name']); ?>">
                            <div>
                                <div class="fw-bold small"><?php echo e($member['name']); ?></div>
                                <small class="text-muted"><?php echo e($member['role']); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (count($teamMembers) > 3): ?>
                        <div class="text-center">
                            <a href="team.php" class="btn btn-sm btn-outline-primary">View All (<?php echo count($teamMembers); ?>)</a>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mentors Card -->
            <div class="card shadow mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Mentors</h5>
                    <a href="mentors.php" class="btn btn-sm btn-success">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($mentors)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-user-tie fa-3x text-muted mb-2"></i>
                            <p class="text-muted small mb-0">Waiting for mentors to join your project</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($mentors as $mentor): ?>
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?php echo getGravatar($mentor['email'], 40); ?>" class="rounded-circle me-2" alt="<?php echo e($mentor['name']); ?>">
                            <div>
                                <div class="fw-bold small"><?php echo e($mentor['name']); ?></div>
                                <small class="text-muted"><?php echo e($mentor['area_of_expertise']); ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Additional Stats Card -->
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">Activity</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Assessments:</span>
                        <strong><?php echo $projectStats['assessments']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Learning Objectives:</span>
                        <strong><?php echo $projectStats['learning_objectives']; ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Comments:</span>
                        <strong><?php echo $projectStats['comments']; ?></strong>
                    </div>
                    <hr>
                    <div class="d-grid">
                        <a href="assessments.php" class="btn btn-sm btn-outline-info mb-2">View Assessments</a>
                        <a href="learning.php" class="btn btn-sm btn-outline-warning">View Learning Goals</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stage-indicator {
    text-align: center;
}
.stage-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-weight: bold;
    border: 2px solid #dee2e6;
}
.stage-indicator.active .stage-circle {
    background-color: #17a2b8;
    color: white;
    border-color: #17a2b8;
}
.border-left-primary {
    border-left: 4px solid #4e73df;
}
.border-left-success {
    border-left: 4px solid #1cc88a;
}
.border-left-info {
    border-left: 4px solid #36b9cc;
}
</style>

<?php include '../../templates/footer.php'; ?>