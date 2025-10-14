<?php
// dashboards/project/resources.php
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_PROJECT);

$projectId = $auth->getUserId();
$project = $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);

// Get filter parameters
$filterType = $_GET['type'] ?? '';
$filterMentor = $_GET['mentor'] ?? '';

// Build query
$query = "
    SELECT mr.*, m.name as mentor_name, m.area_of_expertise
    FROM mentor_resources mr
    INNER JOIN mentors m ON mr.mentor_id = m.mentor_id
    WHERE mr.project_id = ? AND mr.is_deleted = 0
";
$params = [$projectId];

if ($filterType) {
    $query .= " AND mr.resource_type = ?";
    $params[] = $filterType;
}

if ($filterMentor) {
    $query .= " AND mr.mentor_id = ?";
    $params[] = intval($filterMentor);
}

$query .= " ORDER BY mr.created_at DESC";

$resources = $database->getRows($query, $params);

// Get available mentors for filter
$mentors = $database->getRows("
    SELECT DISTINCT m.mentor_id, m.name
    FROM mentors m
    INNER JOIN project_mentors pm ON m.mentor_id = pm.mentor_id
    WHERE pm.project_id = ? AND pm.is_active = 1
    ORDER BY m.name ASC
", [$projectId]);

// Get resource types for filter
$resourceTypes = [
    'article' => 'Article/Blog Post',
    'video' => 'Video Tutorial',
    'document' => 'Document/Guide',
    'tool' => 'Tool/Software',
    'template' => 'Template',
    'course' => 'Online Course',
    'book' => 'Book/eBook',
    'contact' => 'Industry Contact',
    'other' => 'Other'
];

$pageTitle = "Resources Library";
include '../../templates/header.php';
?>

<div class="project-dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Resources Library</h1>
            <p class="text-muted">Learning materials shared by your mentors</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <?php if (empty($resources) && !$filterType && !$filterMentor): ?>
        <div class="card shadow">
            <div class="card-body text-center py-5">
                <i class="fas fa-book-open fa-4x text-muted mb-3"></i>
                <h4>No Resources Yet</h4>
                <p class="text-muted">Your mentors will share valuable resources to help your project grow.</p>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Resources include articles, videos, tools, templates, and more to support your development.
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Filters -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-5 mb-2">
                        <label class="form-label small">Filter by Type:</label>
                        <select name="type" class="form-select form-select-sm">
                            <option value="">All Types</option>
                            <?php foreach ($resourceTypes as $value => $label): ?>
                                <option value="<?php echo $value; ?>" <?php echo $filterType === $value ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5 mb-2">
                        <label class="form-label small">Filter by Mentor:</label>
                        <select name="mentor" class="form-select form-select-sm">
                            <option value="">All Mentors</option>
                            <?php foreach ($mentors as $mentor): ?>
                                <option value="<?php echo $mentor['mentor_id']; ?>" <?php echo $filterMentor == $mentor['mentor_id'] ? 'selected' : ''; ?>>
                                    <?php echo e($mentor['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Apply</button>
                        <?php if ($filterType || $filterMentor): ?>
                            <a href="resources.php" class="btn btn-sm btn-outline-secondary w-100 mt-1">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($resources)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-filter me-2"></i>
                No resources found matching your filters. Try adjusting your selection.
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-4">
                <i class="fas fa-info-circle me-2"></i>
                Showing <strong><?php echo count($resources); ?></strong> resource<?php echo count($resources) > 1 ? 's' : ''; ?>
            </div>

            <!-- Resources Grid -->
            <div class="row">
                <?php foreach ($resources as $resource): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-light text-dark"><?php echo $resourceTypes[$resource['resource_type']] ?? $resource['resource_type']; ?></span>
                                <small><?php echo timeAgo($resource['created_at']); ?></small>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo e($resource['title']); ?></h5>
                            <p class="card-text"><?php echo e($resource['description']); ?></p>
                            
                            <div class="border-top pt-3 mt-3">
                                <div class="d-flex align-items-center mb-2">
                                    <img src="<?php echo getGravatar($resource['mentor_id'], 30); ?>" 
                                         class="rounded-circle me-2" 
                                         alt="<?php echo e($resource['mentor_name']); ?>">
                                    <div>
                                        <small class="fw-bold d-block"><?php echo e($resource['mentor_name']); ?></small>
                                        <small class="text-muted"><?php echo e($resource['area_of_expertise']); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
// Find this section in dashboards/project/resources.php (around line 135-145)
// Replace the card-footer section with this improved code:

?>
                        <div class="card-footer bg-white">
                            <?php 
                            // Check if we have URL, file, or both
                            $hasUrl = !empty($resource['resource_url']) && trim($resource['resource_url']) !== '';
                            $hasFile = !empty($resource['file_path']) && trim($resource['file_path']) !== '';
                            ?>
                            
                            <?php if ($hasUrl && $hasFile): ?>
                                <!-- Both URL and File available - show both buttons -->
                                <div class="d-flex gap-2">
                                    <a href="<?php echo e($resource['resource_url']); ?>" target="_blank" class="btn btn-primary flex-fill">
                                        <i class="fas fa-external-link-alt me-1"></i> View Link
                                    </a>
                                    <a href="../../assets/uploads/resources/<?php echo e($resource['file_path']); ?>" target="_blank" class="btn btn-success flex-fill">
                                        <i class="fas fa-download me-1"></i> Download File
                                    </a>
                                </div>
                            <?php elseif ($hasUrl): ?>
                                <!-- Only URL available -->
                                <a href="<?php echo e($resource['resource_url']); ?>" target="_blank" class="btn btn-primary w-100">
                                    <i class="fas fa-external-link-alt me-1"></i> View Resource
                                </a>
                            <?php elseif ($hasFile): ?>
                                <!-- Only File available -->
                                <a href="../../assets/uploads/resources/<?php echo e($resource['file_path']); ?>" target="_blank" class="btn btn-primary w-100">
                                    <i class="fas fa-download me-1"></i> Download Resource
                                </a>
                            <?php else: ?>
                                <!-- No resource link or file -->
                                <button class="btn btn-secondary w-100" disabled>No link available</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../../templates/footer.php'; ?>