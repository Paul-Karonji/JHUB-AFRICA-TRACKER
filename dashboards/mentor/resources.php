<?php
// dashboards/mentor/resources.php
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_MENTOR);

$mentorId = $auth->getUserId();
$errors = [];
$success = '';
$action = $_GET['action'] ?? 'list';
$projectId = $_GET['project_id'] ?? null;

// Get mentor's projects for dropdown
$myProjects = $database->getRows("
    SELECT p.project_id, p.project_name
    FROM projects p
    INNER JOIN project_mentors pm ON p.project_id = pm.project_id
    WHERE pm.mentor_id = ? AND pm.is_active = 1 AND p.status = 'active'
    ORDER BY p.project_name ASC
", [$mentorId]);

// Handle resource creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token';
    } else {
        $validator = new Validator($_POST);
        $validator->required('project_id', 'Project is required')
                 ->required('title', 'Title is required')
                 ->required('resource_type', 'Resource type is required')
                 ->required('description', 'Description is required');
        
        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
        } else {
            // Verify mentor is assigned to this project
            $assignment = $database->getRow("
                SELECT * FROM project_mentors 
                WHERE project_id = ? AND mentor_id = ? AND is_active = 1
            ", [intval($_POST['project_id']), $mentorId]);
            
            if (!$assignment) {
                $errors[] = 'You are not assigned to this project';
            } else {
                $resourceData = [
                    'project_id' => intval($_POST['project_id']),
                    'mentor_id' => $mentorId,
                    'title' => trim($_POST['title']),
                    'description' => trim($_POST['description']),
                    'resource_type' => trim($_POST['resource_type']),
                    'resource_url' => !empty($_POST['resource_url']) ? trim($_POST['resource_url']) : null,
                    'file_path' => null
                ];
                
                // Handle file upload
                if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
                    $fileValidator = new Validator([]);
                    $fileValidator->file('file_upload', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip'], MAX_UPLOAD_SIZE);
                    
                    if (!$fileValidator->isValid()) {
                        $errors = array_merge($errors, $fileValidator->getErrors());
                    } else {
                        $fileExtension = strtolower(pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION));
                        $fileName = uniqid('resource_', true) . '.' . $fileExtension;
                        $uploadPath = UPLOAD_PATH . 'resources/' . $fileName;
                        
                        if (move_uploaded_file($_FILES['file_upload']['tmp_name'], $uploadPath)) {
                            $resourceData['file_path'] = $fileName;
                        } else {
                            $errors[] = 'Failed to upload file';
                        }
                    }
                }
                
                if (empty($errors)) {
                    $resourceId = $database->insert('mentor_resources', $resourceData);
                    
                    if ($resourceId) {
                        logActivity('mentor', $mentorId, 'resource_created', "Created resource: {$resourceData['title']}", null, ['resource_id' => $resourceId, 'project_id' => $resourceData['project_id']]);
                        $success = 'Resource created successfully!';
                        $action = 'list'; // Switch to list view
                    } else {
                        $errors[] = 'Failed to create resource';
                    }
                }
            }
        }
    }
}

// Handle resource deletion
if (isset($_GET['delete'])) {
    $resourceId = intval($_GET['delete']);
    
    // Verify ownership
    $resource = $database->getRow("
        SELECT * FROM mentor_resources 
        WHERE resource_id = ? AND mentor_id = ? AND is_deleted = 0
    ", [$resourceId, $mentorId]);
    
    if ($resource) {
        $deleted = $database->update('mentor_resources', ['is_deleted' => 1], 'resource_id = ?', [$resourceId]);
        
        if ($deleted) {
            logActivity('mentor', $mentorId, 'resource_deleted', "Deleted resource: {$resource['title']}");
            $success = 'Resource deleted successfully!';
        } else {
            $errors[] = 'Failed to delete resource';
        }
    } else {
        $errors[] = 'Resource not found or you do not have permission';
    }
}

// Get resources list
$resources = $database->getRows("
    SELECT mr.*, p.project_name
    FROM mentor_resources mr
    INNER JOIN projects p ON mr.project_id = p.project_id
    WHERE mr.mentor_id = ? AND mr.is_deleted = 0
    ORDER BY mr.created_at DESC
", [$mentorId]);

// Filter by project if specified
if ($projectId) {
    $resources = array_filter($resources, function($r) use ($projectId) {
        return $r['project_id'] == $projectId;
    });
}

$pageTitle = "Resource Management";
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
        <!-- Create Resource Form -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Share New Resource</h1>
            <a href="resources.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Resources
            </a>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
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
                        <label class="form-label">Resource Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required
                               placeholder="e.g., Market Research Template, Design Guidelines">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Resource Type <span class="text-danger">*</span></label>
                        <select name="resource_type" class="form-select" required>
                            <option value="">-- Select Type --</option>
                            <option value="article">Article/Blog Post</option>
                            <option value="video">Video Tutorial</option>
                            <option value="document">Document/Guide</option>
                            <option value="tool">Tool/Software</option>
                            <option value="template">Template</option>
                            <option value="course">Online Course</option>
                            <option value="book">Book/eBook</option>
                            <option value="contact">Industry Contact</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="4" required
                                  placeholder="Describe the resource and how it can help the project..."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Resource URL (Optional)</label>
                        <input type="url" name="resource_url" class="form-control"
                               placeholder="https://example.com/resource">
                        <small class="form-text text-muted">Link to external resource</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Or Upload File (Optional)</label>
                        <input type="file" name="file_upload" class="form-control"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip">
                        <small class="form-text text-muted">Accepted: PDF, DOC, XLS, PPT, ZIP (Max 10MB)</small>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        You can provide either a URL or upload a file. If both are provided, the URL will be prioritized.
                    </div>

                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Create Resource
                    </button>
                    <a href="resources.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- Resource List View -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">My Resources</h1>
                <p class="text-muted">Manage resources shared with your projects</p>
            </div>
            <a href="resources.php?action=create<?php echo $projectId ? '&project_id='.$projectId : ''; ?>" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i> Share New Resource
            </a>
        </div>

        <?php if (empty($resources)): ?>
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <i class="fas fa-book fa-3x text-muted mb-3"></i>
                    <h4>No Resources Yet</h4>
                    <p class="text-muted">Start sharing valuable resources with your projects!</p>
                    <a href="resources.php?action=create" class="btn btn-primary mt-3">
                        <i class="fas fa-plus me-2"></i> Create First Resource
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Filter by Project -->
            <?php if (!empty($myProjects)): ?>
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <label class="form-label mb-md-0">Filter by Project:</label>
                        </div>
                        <div class="col-md-4">
                            <select class="form-select" onchange="window.location.href='resources.php?project_id='+this.value">
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

            <!-- Resources Grid -->
            <div class="row">
                <?php foreach ($resources as $resource): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><?php echo e($resource['title']); ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <span class="badge bg-secondary"><?php echo e($resource['resource_type']); ?></span>
                            </div>
                            
                            <p class="card-text"><?php echo truncateText(e($resource['description']), 100); ?></p>
                            
                            <small class="text-muted">
                                <i class="fas fa-project-diagram me-1"></i>
                                <?php echo e($resource['project_name']); ?>
                            </small>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo timeAgo($resource['created_at']); ?>
                            </small>
                        </div>
                            <div class="card-footer bg-white">
                            <?php 
                            // Check if we have URL, file, or both
                            $hasUrl = !empty($resource['resource_url']) && trim($resource['resource_url']) !== '';
                            $hasFile = !empty($resource['file_path']) && trim($resource['file_path']) !== '';
                            ?>
                            
                            <div class="d-flex gap-2">
                                <?php if ($hasUrl && $hasFile): ?>
                                    <!-- Both URL and File available - show both buttons side by side -->
                                    <a href="<?php echo e($resource['resource_url']); ?>" target="_blank" class="btn btn-sm btn-primary flex-fill">
                                        <i class="fas fa-external-link-alt me-1"></i> View Link
                                    </a>
                                    <a href="../../assets/uploads/resources/<?php echo e($resource['file_path']); ?>" target="_blank" class="btn btn-sm btn-success flex-fill">
                                        <i class="fas fa-download me-1"></i> Download
                                    </a>
                                <?php elseif ($hasUrl): ?>
                                    <!-- Only URL available -->
                                    <a href="<?php echo e($resource['resource_url']); ?>" target="_blank" class="btn btn-sm btn-primary flex-fill">
                                        <i class="fas fa-external-link-alt me-1"></i> View
                                    </a>
                                <?php elseif ($hasFile): ?>
                                    <!-- Only File available -->
                                    <a href="../../assets/uploads/resources/<?php echo e($resource['file_path']); ?>" target="_blank" class="btn btn-sm btn-primary flex-fill">
                                        <i class="fas fa-download me-1"></i> Download
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Delete button always present for mentor's own resources -->
                                <a href="resources.php?delete=<?php echo $resource['resource_id']; ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to delete this resource?');">
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