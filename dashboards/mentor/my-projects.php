<?php
// dashboards/mentor/my-projects.php
// Mentor's Assigned Projects
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_MENTOR);

$mentorId = $auth->getUserId();

// Get assigned projects
$assignedProjects = getUserProjects(USER_TYPE_MENTOR, $mentorId);

$pageTitle = "My Projects";
include '../../templates/header.php';
?>

<div class="mentor-my-projects">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">My Projects</h1>
            <p class="text-muted">Projects you're currently mentoring</p>
        </div>
        <a href="available-projects.php" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>Join New Project
        </a>
    </div>

    <!-- Projects Display -->
    <?php if (empty($assignedProjects)): ?>
        <div class="card shadow">
            <div class="card-body text-center py-5">
                <i class="fas fa-project-diagram fa-4x text-muted mb-3"></i>
                <h4>No Projects Yet</h4>
                <p class="text-muted">
                    You haven't joined any projects yet. Browse available projects and start mentoring innovations!
                </p>
                <a href="available-projects.php" class="btn btn-success btn-lg mt-3">
                    <i class="fas fa-search me-2"></i>Browse Available Projects
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    You are currently mentoring <strong><?php echo count($assignedProjects); ?></strong> project<?php echo count($assignedProjects) != 1 ? 's' : ''; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <?php foreach ($assignedProjects as $project): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 project-card shadow">
                    <div class="card-header d-flex justify-content-between align-items-center bg-primary text-white">
                        <h6 class="mb-0"><?php echo e($project['project_name']); ?></h6>
                        <span class="badge bg-light text-primary">Stage <?php echo $project['current_stage']; ?></span>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?php echo truncateText(e($project['description']), 100); ?></p>
                        
                        <div class="project-meta text-muted small mb-3">
                            <div class="mb-2">
                                <i class="fas fa-user me-1"></i> 
                                Lead: <?php echo e($project['project_lead_name']); ?>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span><i class="fas fa-users me-1"></i> <?php echo $project['innovator_count']; ?> Members</span>
                                <span><i class="fas fa-user-tie me-1"></i> <?php echo $project['mentor_count']; ?> Mentors</span>
                            </div>
                            <div class="mb-2">
                                <i class="fas fa-calendar me-1"></i> 
                                Joined: <?php echo formatDate($project['assigned_at']); ?>
                            </div>
                        </div>

                        <div class="progress mb-2" style="height: 20px;">
                            <div class="progress-bar" role="progressbar" 
                                 style="width: <?php echo getStageProgress($project['current_stage']); ?>%">
                                <?php echo number_format(getStageProgress($project['current_stage']), 0); ?>%
                            </div>
                        </div>
                        <small class="text-muted">Project Progress</small>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="d-grid gap-2">
                            <a href="project-details.php?id=<?php echo $project['project_id']; ?>" 
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-arrow-right me-1"></i> View Project
                            </a>
                            <div class="btn-group btn-group-sm">
                                <a href="resources.php?project=<?php echo $project['project_id']; ?>" 
                                   class="btn btn-outline-secondary">
                                    <i class="fas fa-share"></i> Resources
                                </a>
                                <a href="assessment.php?project=<?php echo $project['project_id']; ?>" 
                                   class="btn btn-outline-secondary">
                                    <i class="fas fa-clipboard-check"></i> Assess
                                </a>
                                <a href="learning.php?project=<?php echo $project['project_id']; ?>" 
                                   class="btn btn-outline-secondary">
                                    <i class="fas fa-graduation-cap"></i> Learning
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="card shadow mt-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <a href="resources.php" class="btn btn-outline-primary btn-block w-100">
                            <i class="fas fa-share-alt me-2"></i>Manage All Resources
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="assessment.php" class="btn btn-outline-success btn-block w-100">
                            <i class="fas fa-clipboard-check me-2"></i>Manage Assessments
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="learning.php" class="btn btn-outline-info btn-block w-100">
                            <i class="fas fa-graduation-cap me-2"></i>Manage Learning
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../../templates/footer.php'; ?>