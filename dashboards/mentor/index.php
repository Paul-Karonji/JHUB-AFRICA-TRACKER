<?php
// dashboards/mentor/index.php
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_MENTOR);

$mentorId = $auth->getUserId();

// Get mentor information
$mentor = $database->getRow("SELECT * FROM mentors WHERE mentor_id = ?", [$mentorId]);

// Get assigned projects with statistics
$myProjects = $database->getRows("
    SELECT p.*, 
           pm.assigned_at,
           pm.is_active as mentor_is_active,
           (SELECT COUNT(*) FROM project_innovators WHERE project_id = p.project_id AND is_active = 1) as team_count,
           (SELECT COUNT(*) FROM project_mentors WHERE project_id = p.project_id AND is_active = 1) as mentor_count
    FROM projects p
    INNER JOIN project_mentors pm ON p.project_id = pm.project_id
    WHERE pm.mentor_id = ? AND pm.is_active = 1
    ORDER BY p.updated_at DESC
", [$mentorId]);

// Get recent resources created
$recentResources = $database->getRows("
    SELECT mr.*, p.project_name
    FROM mentor_resources mr
    INNER JOIN projects p ON mr.project_id = p.project_id
    WHERE mr.mentor_id = ? AND mr.is_deleted = 0
    ORDER BY mr.created_at DESC
    LIMIT 5
", [$mentorId]);

// Get statistics
$stats = [
    'total_projects' => count($myProjects),
    'active_projects' => $database->count('project_mentors', 'mentor_id = ? AND is_active = 1', [$mentorId]),
    'resources_created' => $database->count('mentor_resources', 'mentor_id = ? AND is_deleted = 0', [$mentorId]),
    'assessments_created' => $database->count('project_assessments', 'mentor_id = ? AND is_deleted = 0', [$mentorId]),
    'learning_objectives' => $database->count('learning_objectives', 'mentor_id = ? AND is_deleted = 0', [$mentorId])
];

$pageTitle = "Mentor Dashboard - " . e($mentor['name']);
include '../../templates/header.php';
?>

<div class="mentor-dashboard">
    <!-- Dashboard Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Welcome, <?php echo e($mentor['name']); ?></h1>
            <p class="text-muted">Mentor Dashboard</p>
        </div>
        <div>
            <a href="available-projects.php" class="btn btn-primary">
                <i class="fas fa-project-diagram me-2"></i>Find Projects to Mentor
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-left-primary shadow">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">My Projects</div>
                    <div class="h5 mb-0 font-weight-bold"><?php echo $stats['active_projects']; ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-left-success shadow">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Resources Shared</div>
                    <div class="h5 mb-0 font-weight-bold"><?php echo $stats['resources_created']; ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-left-info shadow">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Assessments</div>
                    <div class="h5 mb-0 font-weight-bold"><?php echo $stats['assessments_created']; ?></div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-left-warning shadow">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Learning Objectives</div>
                    <div class="h5 mb-0 font-weight-bold"><?php echo $stats['learning_objectives']; ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs for Different Sections -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#projects" role="tab">
                        <i class="fas fa-project-diagram me-1"></i> My Projects
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#resources" role="tab">
                        <i class="fas fa-book me-1"></i> Recent Resources
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#assessments" role="tab">
                        <i class="fas fa-clipboard-check me-1"></i> Assessments
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#learning" role="tab">
                        <i class="fas fa-graduation-cap me-1"></i> Learning Objectives
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content">
                <!-- My Projects Tab -->
                <div class="tab-pane fade show active" id="projects" role="tabpanel">
                    <?php if (empty($myProjects)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            You haven't joined any projects yet. <a href="available-projects.php" class="alert-link">Browse available projects</a> to start mentoring.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($myProjects as $project): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card h-100 project-card">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0"><?php echo e($project['project_name']); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <span class="badge bg-primary me-2">Stage <?php echo $project['current_stage']; ?></span>
                                            <span class="badge bg-<?php echo $project['status'] === 'active' ? 'success' : ($project['status'] === 'completed' ? 'info' : 'danger'); ?>">
                                                <?php echo ucfirst($project['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <p class="card-text"><?php echo truncateText(e($project['description']), 120); ?></p>
                                        
                                        <div class="project-meta text-muted small mb-3">
                                            <div class="mb-2"><i class="fas fa-user me-1"></i> <?php echo e($project['project_lead_name']); ?></div>
                                            <div class="mb-2">
                                                <i class="fas fa-users me-1"></i> <?php echo $project['team_count']; ?> Team Members
                                                <span class="ms-2"><i class="fas fa-user-tie me-1"></i> <?php echo $project['mentor_count']; ?> Mentors</span>
                                            </div>
                                            <div><i class="fas fa-calendar me-1"></i> Joined <?php echo timeAgo($project['assigned_at']); ?></div>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <a href="my-projects.php?id=<?php echo $project['project_id']; ?>" class="btn btn-sm btn-primary flex-fill">
                                                <i class="fas fa-eye me-1"></i> View Details
                                            </a>
                                            <a href="resources.php?project_id=<?php echo $project['project_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-book me-1"></i> Resources
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-3">
                        <a href="my-projects.php" class="btn btn-outline-primary">View All My Projects</a>
                    </div>
                </div>

                <!-- Resources Tab -->
                <div class="tab-pane fade" id="resources" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Recently Created Resources</h5>
                        <a href="resources.php?action=create" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Create Resource
                        </a>
                    </div>
                    
                    <?php if (empty($recentResources)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            You haven't created any resources yet. Share learning materials, tools, and resources with your projects.
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recentResources as $resource): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo e($resource['title']); ?></h6>
                                    <small class="text-muted"><?php echo timeAgo($resource['created_at']); ?></small>
                                </div>
                                <p class="mb-1 small"><?php echo truncateText(e($resource['description']), 100); ?></p>
                                <small class="text-muted">
                                    <span class="badge bg-secondary"><?php echo e($resource['resource_type']); ?></span>
                                    For: <?php echo e($resource['project_name']); ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center mt-3">
                        <a href="resources.php" class="btn btn-outline-primary">Manage All Resources</a>
                    </div>
                </div>

                <!-- Assessments Tab -->
                <div class="tab-pane fade" id="assessments" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Project Assessments</h5>
                        <a href="assessments.php?action=create" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Create Assessment
                        </a>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-clipboard-check me-2"></i>
                        Create assessment checklists to track project progress and development milestones.
                    </div>
                    
                    <div class="text-center">
                        <a href="assessments.php" class="btn btn-outline-primary">Manage All Assessments</a>
                    </div>
                </div>

                <!-- Learning Objectives Tab -->
                <div class="tab-pane fade" id="learning" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Learning Objectives</h5>
                        <a href="learning.php?action=create" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Create Learning Objective
                        </a>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-graduation-cap me-2"></i>
                        Set learning objectives and skill development goals for innovators in your projects.
                    </div>
                    
                    <div class="text-center">
                        <a href="learning.php" class="btn btn-outline-primary">Manage Learning Objectives</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card shadow">
        <div class="card-header">
            <h5 class="mb-0">Quick Actions</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <a href="available-projects.php" class="btn btn-outline-primary w-100">
                        <i class="fas fa-search me-2"></i> Find New Projects
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="resources.php?action=create" class="btn btn-outline-success w-100">
                        <i class="fas fa-plus me-2"></i> Share Resource
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="profile.php" class="btn btn-outline-info w-100">
                        <i class="fas fa-user-edit me-2"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>