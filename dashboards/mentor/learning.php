<?php
// dashboards/mentor/learning.php
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_MENTOR);

$mentorId = $auth->getUserId();
$errors = [];
$success = '';
$action = $_GET['action'] ?? 'list';
$projectId = $_GET['project_id'] ?? null;

// Get mentor's projects
$myProjects = $database->getRows("
    SELECT p.project_id, p.project_name
    FROM projects p
    INNER JOIN project_mentors pm ON p.project_id = pm.project_id
    WHERE pm.mentor_id = ? AND pm.is_active = 1
    ORDER BY p.project_name ASC
", [$mentorId]);

// Handle learning objective creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token';
    } else {
        $validator = new Validator($_POST);
        $validator->required('project_id', 'Project is required')
                 ->required('title', 'Title is required')
                 ->required('description', 'Description is required')
                 ->required('objective_type', 'Objective type is required');
        
        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
        } else {
            // Verify mentor assignment
            $assignment = $database->getRow("
                SELECT * FROM project_mentors 
                WHERE project_id = ? AND mentor_id = ? AND is_active = 1
            ", [intval($_POST['project_id']), $mentorId]);
            
            if (!$assignment) {
                $errors[] = 'You are not assigned to this project';
            } else {
                $objectiveData = [
                    'project_id' => intval($_POST['project_id']),
                    'mentor_id' => $mentorId,
                    'title' => trim($_POST['title']),
                    'description' => trim($_POST['description']),
                    'objective_type' => trim($_POST['objective_type']),
                    'target_completion_date' => !empty($_POST['target_completion_date']) ? $_POST['target_completion_date'] : null,
                    'is_completed' => 0
                ];
                
                $objectiveId = $database->insert('learning_objectives', $objectiveData);
                
                if ($objectiveId) {
                    logActivity('mentor', $mentorId, 'learning_objective_created', "Created learning objective: {$objectiveData['title']}", null, ['objective_id' => $objectiveId, 'project_id' => $objectiveData['project_id']]);
                    $success = 'Learning objective created successfully!';
                    $action = 'list';
                } else {
                    $errors[] = 'Failed to create learning objective';
                }
            }
        }
    }
}

// Handle objective status toggle
if (isset($_GET['toggle'])) {
    $objectiveId = intval($_GET['toggle']);
    
    $objective = $database->getRow("
        SELECT * FROM learning_objectives 
        WHERE objective_id = ? AND mentor_id = ?
    ", [$objectiveId, $mentorId]);
    
    if ($objective) {
        $newStatus = $objective['is_completed'] ? 0 : 1;
        $updateData = ['is_completed' => $newStatus];
        
        if ($newStatus == 1) {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
        } else {
            $updateData['completed_at'] = null;
        }
        
        $updated = $database->update('learning_objectives', $updateData, 'objective_id = ?', [$objectiveId]);
        
        if ($updated) {
            $success = 'Objective status updated!';
        }
    }
}

// Handle objective deletion
if (isset($_GET['delete'])) {
    $objectiveId = intval($_GET['delete']);
    
    $objective = $database->getRow("
        SELECT * FROM learning_objectives 
        WHERE objective_id = ? AND mentor_id = ? AND is_deleted = 0
    ", [$objectiveId, $mentorId]);
    
    if ($objective) {
        $deleted = $database->update('learning_objectives', ['is_deleted' => 1], 'objective_id = ?', [$objectiveId]);
        
        if ($deleted) {
            $success = 'Learning objective deleted successfully!';
        }
    }
}

// Get learning objectives
$objectives = $database->getRows("
    SELECT lo.*, p.project_name
    FROM learning_objectives lo
    INNER JOIN projects p ON lo.project_id = p.project_id
    WHERE lo.mentor_id = ? AND lo.is_deleted = 0
    ORDER BY lo.created_at DESC
", [$mentorId]);

if ($projectId) {
    $objectives = array_filter($objectives, function($o) use ($projectId) {
        return $o['project_id'] == $projectId;
    });
}

$pageTitle = "Learning Objectives";
include '../../templates/header.php';
?>

<div class="mentor-dashboard">
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php foreach ($errors as $field => $fieldErrors): ?>
                <?php if (is_array($fieldErrors)): ?>
                    <?php foreach ($fieldErrors as $error): ?>
                        <div><?php echo e($error); ?></div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div><?php echo e($fieldErrors); ?></div>
                <?php endif; ?>
            <?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo e($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($action === 'create'): ?>
        <!-- Create Learning Objective Form -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Create Learning Objective</h1>
            <a href="learning.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <form method="POST">
                    <?php echo Validator::csrfInput(); ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Select Project <span class="text-danger">*</span></label>
                        <select name="project_id" class="form-select" required>
                            <option value="">-- Select Project --</option>
                            <?php foreach ($myProjects as $project): ?>
                                <option value="<?php echo $project['project_id']; ?>"
                                        <?php echo ($projectId == $project['project_id']) ? 'selected' : ''; ?>>
                                    <?php echo e($project['project_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Learning Objective Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required
                               placeholder="e.g., Complete Market Analysis, Learn Financial Modeling">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Objective Type <span class="text-danger">*</span></label>
                        <select name="objective_type" class="form-select" required>
                            <option value="">-- Select Type --</option>
                            <option value="technical">Technical Skill</option>
                            <option value="business">Business Acumen</option>
                            <option value="industry">Industry Knowledge</option>
                            <option value="soft_skill">Soft Skills</option>
                            <option value="leadership">Leadership Development</option>
                            <option value="marketing">Marketing & Sales</option>
                            <option value="financial">Financial Management</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="4" required
                                  placeholder="Describe what the team should learn and achieve..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Target Completion Date (Optional)</label>
                        <input type="date" name="target_completion_date" class="form-control" 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>Effective Learning Objectives:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Specific and achievable</li>
                            <li>Aligned with project stage and needs</li>
                            <li>Measurable outcomes</li>
                            <li>Relevant to team's growth</li>
                            <li>Time-bound when appropriate</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Create Objective
                    </button>
                    <a href="learning.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- Learning Objectives List View -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Learning Objectives</h1>
                <p class="text-muted">Skill development goals for your projects</p>
            </div>
            <a href="learning.php?action=create<?php echo $projectId ? '&project_id='.$projectId : ''; ?>" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> Create Objective
            </a>
        </div>

        <?php if (empty($objectives)): ?>
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                    <h4>No Learning Objectives Yet</h4>
                    <p class="text-muted">Create learning goals to guide innovator skill development!</p>
                    <a href="learning.php?action=create" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-2"></i> Create First Objective
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Filter -->
            <?php if (!empty($myProjects)): ?>
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <label class="form-label mb-md-0">Filter by Project:</label>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" onchange="window.location.href='learning.php?project_id='+this.value">
                                <option value="">All Projects</option>
                                <?php foreach ($myProjects as $project): ?>
                                    <option value="<?php echo $project['project_id']; ?>"
                                            <?php echo ($projectId == $project['project_id']) ? 'selected' : ''; ?>>
                                        <?php echo e($project['project_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Statistics -->
            <?php 
            $completedCount = count(array_filter($objectives, function($o) { return $o['is_completed']; }));
            $pendingCount = count($objectives) - $completedCount;
            ?>
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border-success shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-success text-uppercase small mb-1">Completed</div>
                                    <div class="h3 mb-0"><?php echo $completedCount; ?></div>
                                </div>
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-warning shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-warning text-uppercase small mb-1">Pending</div>
                                    <div class="h3 mb-0"><?php echo $pendingCount; ?></div>
                                </div>
                                <i class="fas fa-hourglass-half fa-2x text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Objectives List -->
            <div class="row">
                <?php foreach ($objectives as $objective): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm <?php echo $objective['is_completed'] ? 'border-success' : ''; ?>">
                        <div class="card-header bg-warning text-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><?php echo e($objective['title']); ?></h6>
                            <span class="badge bg-<?php echo $objective['is_completed'] ? 'success' : 'light text-dark'; ?>">
                                <?php echo $objective['is_completed'] ? 'Completed' : 'In Progress'; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <span class="badge bg-secondary"><?php echo ucwords(str_replace('_', ' ', $objective['objective_type'])); ?></span>
                            </div>
                            
                            <p class="card-text"><?php echo nl2br(e($objective['description'])); ?></p>
                            
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-project-diagram me-1"></i>
                                    <?php echo e($objective['project_name']); ?>
                                </small>
                            </div>
                            
                            <?php if ($objective['target_completion_date']): ?>
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    Target: <?php echo formatDate($objective['target_completion_date'], 'M d, Y'); ?>
                                    <?php if (strtotime($objective['target_completion_date']) < time() && !$objective['is_completed']): ?>
                                        <span class="badge bg-danger ms-1">Overdue</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                            <?php endif; ?>
                            
                            <div>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    Created <?php echo timeAgo($objective['created_at']); ?>
                                </small>
                            </div>
                            
                            <?php if ($objective['is_completed'] && $objective['completed_at']): ?>
                            <div class="mt-2 p-2 bg-success bg-opacity-10 rounded">
                                <small class="text-success">
                                    <i class="fas fa-check-circle me-1"></i>
                                    Completed <?php echo timeAgo($objective['completed_at']); ?>
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-flex gap-2">
                                <a href="learning.php?toggle=<?php echo $objective['objective_id']; ?>" 
                                   class="btn btn-sm btn-<?php echo $objective['is_completed'] ? 'warning' : 'success'; ?> flex-fill">
                                    <i class="fas fa-<?php echo $objective['is_completed'] ? 'undo' : 'check'; ?> me-1"></i>
                                    <?php echo $objective['is_completed'] ? 'Mark Incomplete' : 'Mark Complete'; ?>
                                </a>
                                <a href="learning.php?delete=<?php echo $objective['objective_id']; ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to delete this learning objective?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../../templates/footer.php'; ?>
