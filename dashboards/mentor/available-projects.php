<?php
// dashboards/mentor/available-projects.php
// Available Projects for Mentor Assignment
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_MENTOR);

$mentorId = $auth->getUserId();

// Get available projects (not assigned to this mentor)
$availableProjects = getAvailableProjectsForMentor($mentorId);

$pageTitle = "Available Projects";
include '../../templates/header.php';
?>

<div class="mentor-available-projects">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Available Projects</h1>
            <p class="text-muted">Browse and join innovation projects that need mentorship</p>
        </div>
        <a href="my-projects.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i>Back to My Projects
        </a>
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info mb-4">
        <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>How It Works</h5>
        <ul class="mb-0">
            <li>Browse active projects looking for mentorship</li>
            <li>Click "View Details" to learn more about a project</li>
            <li>Click "Join Project" to become a mentor for that innovation</li>
            <li>Once joined, the project will move to Stage 2: Mentorship</li>
            <li>You can mentor multiple projects simultaneously</li>
        </ul>
    </div>

    <!-- Projects Grid -->
    <?php if (empty($availableProjects)): ?>
        <div class="card shadow">
            <div class="card-body text-center py-5">
                <i class="fas fa-project-diagram fa-4x text-muted mb-3"></i>
                <h4>No Available Projects</h4>
                <p class="text-muted">
                    All active projects currently have mentors assigned, or you're already mentoring all available projects.
                </p>
                <a href="my-projects.php" class="btn btn-primary mt-3">
                    <i class="fas fa-arrow-left me-2"></i>View My Projects
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($availableProjects as $project): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 project-card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><?php echo e($project['project_name']); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="badge bg-primary me-2">Stage <?php echo $project['current_stage']; ?></span>
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
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-grid gap-2">
                            <a href="../../public/project-details.php?id=<?php echo $project['project_id']; ?>" 
                               class="btn btn-outline-primary btn-sm" target="_blank">
                                <i class="fas fa-eye me-1"></i> View Details
                            </a>
                            <button class="btn btn-success btn-sm btn-join-project" 
                                    data-project-id="<?php echo $project['project_id']; ?>"
                                    data-project-name="<?php echo e($project['project_name']); ?>">
                                <i class="fas fa-plus me-1"></i> Join as Mentor
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4">
            <p class="text-muted">
                Showing <?php echo count($availableProjects); ?> available project<?php echo count($availableProjects) != 1 ? 's' : ''; ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Join project buttons
    document.querySelectorAll('.btn-join-project').forEach(button => {
        button.addEventListener('click', function() {
            const projectId = this.dataset.projectId;
            const projectName = this.dataset.projectName;
            
            if (confirm(`Are you sure you want to join "${projectName}" as a mentor?\n\nYou will be able to share resources, create assessments, and guide the team through their innovation journey.`)) {
                joinProject(projectId, this);
            }
        });
    });
});

function joinProject(projectId, button) {
    // Disable button
    const originalHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Joining...';
    
    fetch('../../api/projects/mentors.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'join',
            project_id: projectId,
            csrf_token: window.JHUB.csrfToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.JHUB.Utils.showAlert(data.message, 'success');
            
            // Redirect to my projects after brief delay
            setTimeout(() => {
                window.location.href = 'my-projects.php';
            }, 2000);
        } else {
            window.JHUB.Utils.showAlert(data.message, 'danger');
            button.disabled = false;
            button.innerHTML = originalHtml;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        window.JHUB.Utils.showAlert('An error occurred while joining the project', 'danger');
        button.disabled = false;
        button.innerHTML = originalHtml;
    });
}
</script>

<?php include '../../templates/footer.php'; ?>