<?php
// dashboards/project/progress.php
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_PROJECT);

$projectId = $auth->getUserId();
$project = $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);

// Get all statistics
$stats = [
    'team_members' => $database->count('project_innovators', 'project_id = ? AND is_active = 1', [$projectId]),
    'mentors' => $database->count('project_mentors', 'project_id = ? AND is_active = 1', [$projectId]),
    'resources' => $database->count('mentor_resources', 'project_id = ? AND is_deleted = 0', [$projectId]),
    'assessments' => $database->count('project_assessments', 'project_id = ? AND is_deleted = 0', [$projectId]),
    'assessments_completed' => $database->count('project_assessments', 'project_id = ? AND is_deleted = 0 AND is_completed = 1', [$projectId]),
    'learning_objectives' => $database->count('learning_objectives', 'project_id = ? AND is_deleted = 0', [$projectId]),
    'learning_completed' => $database->count('learning_objectives', 'project_id = ? AND is_deleted = 0 AND is_completed = 1', [$projectId]),
    'comments' => $database->count('comments', 'project_id = ? AND is_deleted = 0', [$projectId])
];

// Stage information
$stages = [
    1 => ['name' => 'Project Creation', 'description' => 'Initial setup and team building', 'icon' => 'fa-rocket'],
    2 => ['name' => 'Mentorship', 'description' => 'Mentor assignment and guidance', 'icon' => 'fa-user-tie'],
    3 => ['name' => 'Assessment', 'description' => 'Project evaluation and feedback', 'icon' => 'fa-clipboard-check'],
    4 => ['name' => 'Learning & Development', 'description' => 'Skills and knowledge building', 'icon' => 'fa-graduation-cap'],
    5 => ['name' => 'Progress Tracking', 'description' => 'Monitoring and refinement', 'icon' => 'fa-chart-line'],
    6 => ['name' => 'Showcase & Integration', 'description' => 'Final presentation and ecosystem entry', 'icon' => 'fa-trophy']
];

// Calculate days in current stage
$daysInStage = floor((time() - strtotime($project['updated_at'])) / (60 * 60 * 24));

$pageTitle = "Project Progress";
include '../../templates/header.php';
?>

<div class="project-dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Project Progress Tracker</h1>
            <p class="text-muted">Monitor your journey through the 6 stages</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <!-- Current Stage Overview -->
    <div class="card shadow mb-4 border-primary">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-flag me-2"></i>Current Stage</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <div class="display-1 text-primary">
                        <i class="fas <?php echo $stages[$project['current_stage']]['icon']; ?>"></i>
                    </div>
                </div>
                <div class="col-md-10">
                    <h3>Stage <?php echo $project['current_stage']; ?>: <?php echo $stages[$project['current_stage']]['name']; ?></h3>
                    <p class="lead text-muted mb-3"><?php echo $stages[$project['current_stage']]['description']; ?></p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="progress mb-2" style="height: 30px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                     style="width: <?php echo getStageProgress($project['current_stage']); ?>%">
                                    <?php echo getStageProgress($project['current_stage']); ?>% Complete
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-clock me-2"></i>
                                <strong><?php echo $daysInStage; ?></strong> days in this stage
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stage Timeline -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list-ol me-2"></i>Stage Timeline</h5>
        </div>
        <div class="card-body">
            <div class="timeline">
                <?php foreach ($stages as $stageNum => $stageInfo): ?>
                <div class="timeline-item <?php echo $stageNum <= $project['current_stage'] ? 'completed' : 'pending'; ?> <?php echo $stageNum == $project['current_stage'] ? 'active' : ''; ?>">
                    <div class="timeline-marker">
                        <i class="fas <?php echo $stageNum < $project['current_stage'] ? 'fa-check' : ($stageNum == $project['current_stage'] ? 'fa-circle' : $stageInfo['icon']); ?>"></i>
                    </div>
                    <div class="timeline-content">
                        <h5>Stage <?php echo $stageNum; ?>: <?php echo $stageInfo['name']; ?></h5>
                        <p class="text-muted mb-2"><?php echo $stageInfo['description']; ?></p>
                        <?php if ($stageNum < $project['current_stage']): ?>
                            <span class="badge bg-success"><i class="fas fa-check me-1"></i> Completed</span>
                        <?php elseif ($stageNum == $project['current_stage']): ?>
                            <span class="badge bg-primary"><i class="fas fa-play me-1"></i> In Progress</span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><i class="fas fa-lock me-1"></i> Locked</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Project Statistics -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Team & Mentorship</h5>
                </div>
                <div class="card-body">
                    <div class="stat-item">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span><i class="fas fa-users text-primary me-2"></i>Team Members</span>
                            <strong class="h4 mb-0"><?php echo $stats['team_members']; ?></strong>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span><i class="fas fa-user-tie text-success me-2"></i>Mentors</span>
                            <strong class="h4 mb-0"><?php echo $stats['mentors']; ?></strong>
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-comments text-info me-2"></i>Comments</span>
                            <strong class="h4 mb-0"><?php echo $stats['comments']; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Development & Learning</h5>
                </div>
                <div class="card-body">
                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="fas fa-book text-success me-2"></i>Resources Shared</span>
                            <strong class="h4 mb-0"><?php echo $stats['resources']; ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-item mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="fas fa-clipboard-check text-info me-2"></i>Assessments</span>
                            <strong class="h4 mb-0"><?php echo $stats['assessments_completed']; ?> / <?php echo $stats['assessments']; ?></strong>
                        </div>
                        <?php if ($stats['assessments'] > 0): ?>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-info" style="width: <?php echo round(($stats['assessments_completed'] / $stats['assessments']) * 100); ?>%"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="stat-item">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="fas fa-graduation-cap text-warning me-2"></i>Learning Objectives</span>
                            <strong class="h4 mb-0"><?php echo $stats['learning_completed']; ?> / <?php echo $stats['learning_objectives']; ?></strong>
                        </div>
                        <?php if ($stats['learning_objectives'] > 0): ?>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-warning" style="width: <?php echo round(($stats['learning_completed'] / $stats['learning_objectives']) * 100); ?>%"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Information -->
    <div class="card shadow">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Project Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-4">Project Name:</dt>
                        <dd class="col-sm-8"><?php echo e($project['project_name']); ?></dd>
                        
                        <dt class="col-sm-4">Project Lead:</dt>
                        <dd class="col-sm-8"><?php echo e($project['project_lead_name']); ?></dd>
                        
                        <dt class="col-sm-4">Status:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-<?php echo $project['status'] === 'active' ? 'success' : ($project['status'] === 'completed' ? 'info' : 'danger'); ?>">
                                <?php echo ucfirst($project['status']); ?>
                            </span>
                        </dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-4">Started:</dt>
                        <dd class="col-sm-8"><?php echo formatDate($project['created_at'], 'F j, Y'); ?></dd>
                        
                        <dt class="col-sm-4">Last Updated:</dt>
                        <dd class="col-sm-8"><?php echo timeAgo($project['updated_at']); ?></dd>
                        
                        <?php if ($project['status'] === 'completed' && $project['completion_date']): ?>
                        <dt class="col-sm-4">Completed:</dt>
                        <dd class="col-sm-8"><?php echo formatDate($project['completion_date'], 'F j, Y'); ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 0;
}
.timeline-item {
    position: relative;
    padding-left: 60px;
    padding-bottom: 30px;
}
.timeline-item:last-child {
    padding-bottom: 0;
}
.timeline-item::before {
    content: '';
    position: absolute;
    left: 20px;
    top: 30px;
    bottom: -10px;
    width: 2px;
    background: #dee2e6;
}
.timeline-item:last-child::before {
    display: none;
}
.timeline-marker {
    position: absolute;
    left: 0;
    top: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    color: #253683;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    border: 3px solid #fff;
    box-shadow: 0 0 0 3px #dee2e6;
}
.timeline-item.completed .timeline-marker {
    background: #3fa845;
    color: white;
    box-shadow: 0 0 0 3px #3fa845;
}
.timeline-item.active .timeline-marker {
    background: #2c409a;
    color: white;
    box-shadow: 0 0 0 3px #2c409a;
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}
</style>

<?php include '../../templates/footer.php'; ?>