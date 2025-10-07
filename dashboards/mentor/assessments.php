<?php
// dashboards/mentor/assessments.php
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

// Handle assessment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token';
    } else {
        $validator = new Validator($_POST);
        $validator->required('project_id', 'Project is required')
                 ->required('title', 'Title is required')
                 ->required('description', 'Description is required');
        
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
                $assessmentData = [
                    'project_id' => intval($_POST['project_id']),
                    'mentor_id' => $mentorId,
                    'title' => trim($_POST['title']),
                    'description' => trim($_POST['description']),
                    'criteria' => trim($_POST['criteria'] ?? ''),
                    'is_completed' => 0
                ];
                
                $assessmentId = $database->insert('project_assessments', $assessmentData);
                
                if ($assessmentId) {
                    logActivity('mentor', $mentorId, 'assessment_created', "Created assessment: {$assessmentData['title']}", null, ['assessment_id' => $assessmentId, 'project_id' => $assessmentData['project_id']]);
                    $success = 'Assessment created successfully!';
                    $action = 'list';
                } else {
                    $errors[] = 'Failed to create assessment';
                }
            }
        }
    }
}

// Handle assessment status toggle
if (isset($_GET['toggle'])) {
    $assessmentId = intval($_GET['toggle']);
    
    $assessment = $database->getRow("
        SELECT * FROM project_assessments 
        WHERE assessment_id = ? AND mentor_id = ?
    ", [$assessmentId, $mentorId]);
    
    if ($assessment) {
        $newStatus = $assessment['is_completed'] ? 0 : 1;
        $updated = $database->update('project_assessments', ['is_completed' => $newStatus], 'assessment_id = ?', [$assessmentId]);
        
        if ($updated) {
            $success = 'Assessment status updated!';
        }
    }
}

// Handle assessment deletion
if (isset($_GET['delete'])) {
    $assessmentId = intval($_GET['delete']);
    
    $assessment = $database->getRow("
        SELECT * FROM project_assessments 
        WHERE assessment_id = ? AND mentor_id = ? AND is_deleted = 0
    ", [$assessmentId, $mentorId]);
    
    if ($assessment) {
        $deleted = $database->update('project_assessments', ['is_deleted' => 1], 'assessment_id = ?', [$assessmentId]);
        
        if ($deleted) {
            $success = 'Assessment deleted successfully!';
        }
    }
}

// Get assessments
$assessments = $database->getRows("
    SELECT pa.*, p.project_name
    FROM project_assessments pa
    INNER JOIN projects p ON pa.project_id = p.project_id
    WHERE pa.mentor_id = ? AND pa.is_deleted = 0
    ORDER BY pa.created_at DESC
", [$mentorId]);

if ($projectId) {
    $assessments = array_filter($assessments, function($a) use ($projectId) {
        return $a['project_id'] == $projectId;
    });
}

$pageTitle = "Assessment Management";
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
        <!-- Create Assessment Form -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Create Assessment</h1>
            <a href="assessments.php" class="btn btn-outline-secondary">
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
                        <label class="form-label">Assessment Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required
                               placeholder="e.g., Market Validation Checklist, Technical Readiness Assessment">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="3" required
                                  placeholder="Describe what this assessment evaluates..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Assessment Criteria</label>
                        <textarea name="criteria" class="form-control" rows="6"
                                  placeholder="List the checklist items (one per line):&#10;- Item 1&#10;- Item 2&#10;- Item 3"></textarea>
                        <small class="form-text text-muted">Enter each criterion on a new line. Use dashes (-) or numbers for better formatting.</small>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Assessment Best Practices:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Be specific and measurable</li>
                            <li>Focus on actionable outcomes</li>
                            <li>Align with project stage requirements</li>
                            <li>Include both technical and business aspects</li>
                        </ul>
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Create Assessment
                    </button>
                    <a href="assessments.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- Assessment List View -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Project Assessments</h1>
                <p class="text-muted">Track project progress and development</p>
            </div>
            <a href="assessments.php?action=create<?php echo $projectId ? '&project_id='.$projectId : ''; ?>" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> Create Assessment
            </a>
        </div>

        <?php if (empty($assessments)): ?>
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                    <h4>No Assessments Yet</h4>
                    <p class="text-muted">Create assessment checklists to track project development!</p>
                    <a href="assessments.php?action=create" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-2"></i> Create First Assessment
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
                            <select class="form-select" onchange="window.location.href='assessments.php?project_id='+this.value">
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

            <!-- Assessments List -->
            <div class="row">
                <?php foreach ($assessments as $assessment): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><?php echo e($assessment['title']); ?></h6>
                            <span class="badge bg-<?php echo $assessment['is_completed'] ? 'success' : 'warning'; ?>">
                                <?php echo $assessment['is_completed'] ? 'Completed' : 'Pending'; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <p class="card-text"><?php echo e($assessment['description']); ?></p>
                            
                            <?php if ($assessment['criteria']): ?>
                            <div class="mb-3">
                                <strong>Criteria:</strong>
                                <pre class="mt-2 bg-light p-2 rounded" style="white-space: pre-wrap; font-size: 0.9rem;"><?php echo e($assessment['criteria']); ?></pre>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-project-diagram me-1"></i>
                                    <?php echo e($assessment['project_name']); ?>
                                </small>
                            </div>
                            <div>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    Created <?php echo timeAgo($assessment['created_at']); ?>
                                </small>
                            </div>
                        </div>
                        <div class="card-footer bg-white">
                            <div class="d-flex gap-2">
                                <a href="assessments.php?toggle=<?php echo $assessment['assessment_id']; ?>" 
                                   class="btn btn-sm btn-<?php echo $assessment['is_completed'] ? 'warning' : 'success'; ?> flex-fill">
                                    <i class="fas fa-<?php echo $assessment['is_completed'] ? 'undo' : 'check'; ?> me-1"></i>
                                    <?php echo $assessment['is_completed'] ? 'Mark Pending' : 'Mark Complete'; ?>
                                </a>
                                <a href="assessments.php?delete=<?php echo $assessment['assessment_id']; ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to delete this assessment?')">
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