<?php
// dashboards/mentor/available-projects.php
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_MENTOR);

$mentorId = $auth->getUserId();

// Get all active projects (not assigned to this mentor)
$availableProjects = $database->getRows("
    SELECT p.*, 
           (SELECT COUNT(*) FROM project_innovators WHERE project_id = p.project_id AND is_active = 1) as innovator_count,
           (SELECT COUNT(*) FROM project_mentors WHERE project_id = p.project_id AND is_active = 1) as mentor_count
    FROM projects p
    WHERE p.status = 'active'
    AND p.project_id NOT IN (
        SELECT project_id FROM project_mentors 
        WHERE mentor_id = ? AND is_active = 1
    )
    ORDER BY p.created_at DESC
", [$mentorId]);

$pageTitle = "Available Projects";
include '../../templates/header.php';
?>

<div class="mentor-dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Available Projects</h1>
            <p class="text-muted">Projects looking for mentorship</p>
        </div>
        <a href="my-projects.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to My Projects
        </a>
    </div>

    <?php if (empty($availableProjects)): ?>
        <div class="card shadow">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                <h4>Great! You're mentoring all available projects!</h4>
                <p class="text-muted">Check back later for new projects that need mentorship.</p>
                <a href="my-projects.php" class="btn btn-primary mt-3">
                    <i class="fas fa-arrow-left me-2"></i>View My Projects
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info mb-4">
            <i class="fas fa-info-circle me-2"></i>
            <strong><?php echo count($availableProjects); ?> projects</strong> are looking for mentors. Click "Join Project" to start mentoring.
        </div>

        <div class="row">
            <?php foreach ($availableProjects as $project): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 project-card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><?php echo e($project['project_name']); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="badge bg-primary me-2"><?php echo getStageName($project['current_stage']); ?></span>
                            <span class="badge bg-success"><?php echo ucfirst($project['status']); ?></span>
                        </div>
                        
                        <p class="card-text"><?php echo truncateText(e($project['description']), 120); ?></p>
                        
                        <div class="project-meta text-muted small mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-user me-1"></i> <?php echo e($project['project_lead_name']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-users me-1"></i> <?php echo $project['innovator_count']; ?> Team Members</span>
                                <span><i class="fas fa-user-tie me-1"></i> <?php echo $project['mentor_count']; ?> Mentors</span>
                            </div>
                            <div>
                                <i class="fas fa-calendar me-1"></i> Started <?php echo formatDate($project['created_at'], 'M Y'); ?>
                            </div>
                        </div>

                        <?php if ($project['target_market']): ?>
                        <div class="mb-2">
                            <small class="text-muted">Target Market:</small>
                            <p class="mb-0 small"><?php echo e($project['target_market']); ?></p>
                        </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2 mt-3">
                            <button class="btn btn-primary btn-join-project" 
                                    data-project-id="<?php echo $project['project_id']; ?>"
                                    data-project-name="<?php echo e($project['project_name']); ?>">
                                <i class="fas fa-plus me-1"></i> Join as Mentor
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" 
                                    data-bs-target="#details-<?php echo $project['project_id']; ?>">
                                <i class="fas fa-info-circle me-1"></i> More Details
                            </button>
                        </div>

                        <div class="collapse mt-3" id="details-<?php echo $project['project_id']; ?>">
                            <hr>
                            <small>
                                <strong>Full Description:</strong><br>
                                <?php echo nl2br(e($project['description'])); ?>
                            </small>
                            <?php if ($project['project_website']): ?>
                                <div class="mt-2">
                                    <a href="<?php echo e($project['project_website']); ?>" target="_blank" class="small">
                                        Visit Website <i class="fas fa-external-link-alt ms-1"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle join project buttons
    document.querySelectorAll('.btn-join-project').forEach(button => {
        button.addEventListener('click', function() {
            const projectId = this.dataset.projectId;
            const projectName = this.dataset.projectName;
            
            if (!confirm(`Are you sure you want to join "${projectName}" as a mentor?`)) {
                return;
            }
            
            // Disable button
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Joining...';
            
            // Make API call
            fetch('../../api/mentors/assign-to-project.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    project_id: projectId,
                    csrf_token: '<?php echo $auth->generateCSRFToken(); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.href = 'my-projects.php?id=' + projectId;
                } else {
                    alert('Error: ' + data.message);
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-plus me-1"></i> Join as Mentor';
                }
            })
            .catch(error => {
                alert('Network error. Please try again.');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-plus me-1"></i> Join as Mentor';
            });
        });
    });
});
</script>

<?php include '../../templates/footer.php'; ?>