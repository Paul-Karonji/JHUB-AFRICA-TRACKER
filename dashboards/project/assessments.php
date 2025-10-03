<?php
// dashboards/project/assessments.php
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_PROJECT);

$projectId = $auth->getUserId();
$project = $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);

// Get assessments
$assessments = $database->getRows("
    SELECT pa.*, m.name as mentor_name, m.area_of_expertise
    FROM project_assessments pa
    INNER JOIN mentors m ON pa.mentor_id = m.mentor_id
    WHERE pa.project_id = ? AND pa.is_deleted = 0
    ORDER BY pa.created_at DESC
", [$projectId]);

// Calculate completion stats
$completedCount = count(array_filter($assessments, function($a) { return $a['is_completed']; }));
$pendingCount = count($assessments) - $completedCount;

$pageTitle = "Project Assessments";
include '../../templates/header.php';
?>

<div class="project-dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Project Assessments</h1>
            <p class="text-muted">Evaluation checklists from your mentors</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <?php if (empty($assessments)): ?>
        <div class="card shadow">
            <div class="card-body text-center py-5">
                <i class="fas fa-clipboard-check fa-4x text-muted mb-3"></i>
                <h4>No Assessments Yet</h4>
                <p class="text-muted">Your mentors will create assessment checklists to track your project's progress.</p>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Assessments help evaluate project development across technical, business, and market dimensions.
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-info shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-info text-uppercase small mb-1">Total Assessments</div>
                                <div class="h4 mb-0"><?php echo count($assessments); ?></div>
                            </div>
                            <i class="fas fa-clipboard-list fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-success text-uppercase small mb-1">Completed</div>
                                <div class="h4 mb-0"><?php echo $completedCount; ?></div>
                            </div>
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-warning shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-warning text-uppercase small mb-1">Pending</div>
                                <div class="h4 mb-0"><?php echo $pendingCount; ?></div>
                            </div>
                            <i class="fas fa-hourglass-half fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assessments List -->
        <div class="row">
            <?php foreach ($assessments as $assessment): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100 shadow-sm <?php echo $assessment['is_completed'] ? 'border-success' : ''; ?>">
                    <div class="card-header <?php echo $assessment['is_completed'] ? 'bg-success text-white' : 'bg-warning text-white'; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><?php echo e($assessment['title']); ?></h6>
                            <span class="badge bg-<?php echo $assessment['is_completed'] ? 'light text-success' : 'light text-dark'; ?>">
                                <?php echo $assessment['is_completed'] ? 'Completed' : 'Pending'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo nl2br(e($assessment['description'])); ?></p>
                        
                        <?php if ($assessment['criteria']): ?>
                        <div class="border-top pt-3 mt-3">
                            <h6 class="small mb-2"><strong>Assessment Criteria:</strong></h6>
                            <div class="bg-light p-3 rounded">
                                <pre class="mb-0" style="white-space: pre-wrap; font-size: 0.9rem; font-family: inherit;"><?php echo e($assessment['criteria']); ?></pre>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="border-top pt-3 mt-3">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo getGravatar($assessment['mentor_id'], 30); ?>" 
                                     class="rounded-circle me-2" 
                                     alt="<?php echo e($assessment['mentor_name']); ?>">
                                <div>
                                    <small class="fw-bold d-block"><?php echo e($assessment['mentor_name']); ?></small>
                                    <small class="text-muted"><?php echo e($assessment['area_of_expertise']); ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                Created <?php echo timeAgo($assessment['created_at']); ?>
                            </small>
                        </div>

                        <?php if ($assessment['is_completed']): ?>
                        <div class="alert alert-success mt-3 mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>Status:</strong> Your mentor has marked this assessment as complete!
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Status:</strong> Work on completing these criteria. Your mentor will mark it complete when ready.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../../templates/footer.php'; ?>