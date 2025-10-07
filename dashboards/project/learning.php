<?php
// dashboards/project/learning.php
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_PROJECT);

$projectId = $auth->getUserId();
$project = $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);

// Get learning objectives
$objectives = $database->getRows("
    SELECT lo.*, m.name as mentor_name, m.area_of_expertise
    FROM learning_objectives lo
    INNER JOIN mentors m ON lo.mentor_id = m.mentor_id
    WHERE lo.project_id = ? AND lo.is_deleted = 0
    ORDER BY lo.is_completed ASC, lo.created_at DESC
", [$projectId]);

// Calculate stats
$completedCount = count(array_filter($objectives, function($o) { return $o['is_completed']; }));
$pendingCount = count($objectives) - $completedCount;
$overdueCount = count(array_filter($objectives, function($o) {
    return !$o['is_completed'] && $o['target_completion_date'] && strtotime($o['target_completion_date']) < time();
}));

$pageTitle = "Learning Objectives";
include '../../templates/header.php';
?>

<div class="project-dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Learning & Development</h1>
            <p class="text-muted">Skill development goals set by your mentors</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <?php if (empty($objectives)): ?>
        <div class="card shadow">
            <div class="card-body text-center py-5">
                <i class="fas fa-graduation-cap fa-4x text-muted mb-3"></i>
                <h4>No Learning Objectives Yet</h4>
                <p class="text-muted">Your mentors will set learning goals to help develop your team's skills.</p>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Learning objectives focus on technical skills, business acumen, industry knowledge, and soft skills development.
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-info shadow-sm">
                    <div class="card-body">
                        <div class="text-center">
                            <i class="fas fa-tasks fa-2x text-info mb-2"></i>
                            <div class="h4 mb-0"><?php echo count($objectives); ?></div>
                            <div class="text-uppercase small text-muted">Total Goals</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success shadow-sm">
                    <div class="card-body">
                        <div class="text-center">
                            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                            <div class="h4 mb-0"><?php echo $completedCount; ?></div>
                            <div class="text-uppercase small text-muted">Completed</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning shadow-sm">
                    <div class="card-body">
                        <div class="text-center">
                            <i class="fas fa-hourglass-half fa-2x text-warning mb-2"></i>
                            <div class="h4 mb-0"><?php echo $pendingCount; ?></div>
                            <div class="text-uppercase small text-muted">In Progress</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-danger shadow-sm">
                    <div class="card-body">
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                            <div class="h4 mb-0"><?php echo $overdueCount; ?></div>
                            <div class="text-uppercase small text-muted">Overdue</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <?php if (count($objectives) > 0): ?>
        <div class="card shadow mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-bold">Overall Progress</span>
                    <span class="text-muted"><?php echo $completedCount; ?> of <?php echo count($objectives); ?> completed</span>
                </div>
                <div class="progress" style="height: 25px;">
                    <div class="progress-bar bg-success" 
                         style="width: <?php echo count($objectives) > 0 ? round(($completedCount / count($objectives)) * 100) : 0; ?>%">
                        <?php echo count($objectives) > 0 ? round(($completedCount / count($objectives)) * 100) : 0; ?>%
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Learning Objectives List -->
        <div class="row">
            <?php foreach ($objectives as $objective): ?>
            <?php 
            $isOverdue = !$objective['is_completed'] && $objective['target_completion_date'] && strtotime($objective['target_completion_date']) < time();
            ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100 shadow-sm <?php echo $objective['is_completed'] ? 'border-success' : ($isOverdue ? 'border-danger' : ''); ?>">
                    <div class="card-header <?php echo $objective['is_completed'] ? 'bg-success text-white' : ($isOverdue ? 'bg-danger text-white' : 'bg-warning text-white'); ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <?php if ($objective['is_completed']): ?>
                                    <i class="fas fa-check-circle me-1"></i>
                                <?php elseif ($isOverdue): ?>
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                <?php else: ?>
                                    <i class="fas fa-hourglass-half me-1"></i>
                                <?php endif; ?>
                                <?php echo e($objective['title']); ?>
                            </h6>
                            <span class="badge bg-light text-dark">
                                <?php echo ucwords(str_replace('_', ' ', $objective['objective_type'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo nl2br(e($objective['description'])); ?></p>
                        
                        <?php if ($objective['target_completion_date']): ?>
                        <div class="alert alert-<?php echo $isOverdue ? 'danger' : 'info'; ?> mb-3">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <strong>Target Date:</strong> <?php echo formatDate($objective['target_completion_date'], 'F j, Y'); ?>
                            <?php if ($isOverdue): ?>
                                <span class="badge bg-danger ms-2">OVERDUE</span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($objective['is_completed']): ?>
                        <div class="alert alert-success mb-3">
                            <i class="fas fa-trophy me-2"></i>
                            <strong>Completed!</strong> <?php echo timeAgo($objective['completed_at']); ?>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-tasks me-2"></i>
                            <strong>Status:</strong> Work in progress. Your mentor will mark this complete when you've achieved the objective.
                        </div>
                        <?php endif; ?>
                        
                        <div class="border-top pt-3">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo getGravatar($objective['mentor_id'], 30); ?>" 
                                     class="rounded-circle me-2" 
                                     alt="<?php echo e($objective['mentor_name']); ?>">
                                <div>
                                    <small class="fw-bold d-block"><?php echo e($objective['mentor_name']); ?></small>
                                    <small class="text-muted"><?php echo e($objective['area_of_expertise']); ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Created <?php echo timeAgo($objective['created_at']); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../../templates/footer.php'; ?>