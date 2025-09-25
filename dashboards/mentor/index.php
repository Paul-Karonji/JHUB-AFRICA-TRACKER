<?php
// dashboards/mentor/index.php - Comprehensive Mentor Dashboard
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_MENTOR);

$mentorId = $auth->getUserId();

// Get mentor information
$mentor = $database->getRow("SELECT * FROM mentors WHERE mentor_id = ?", [$mentorId]);

// Get assigned projects
$assignedProjects = getUserProjects(USER_TYPE_MENTOR, $mentorId);

// Get available projects for assignment
$availableProjects = getAvailableProjectsForMentor($mentorId);

// Get mentor statistics
$mentorStats = [
    'total_projects' => count($assignedProjects),
    'active_projects' => count(array_filter($assignedProjects, fn($p) => $p['status'] === 'active')),
    'completed_projects' => count(array_filter($assignedProjects, fn($p) => $p['status'] === 'completed')),
    'resources_shared' => $database->count('mentor_resources', 'mentor_id = ?', [$mentorId])
];

$pageTitle = "Mentor Dashboard";
include '../../templates/header.php';
?>

<div class="mentor-dashboard">
    <!-- Dashboard Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Mentor Dashboard</h1>
            <p class="text-muted">Welcome back, <?php echo e($mentor['name']); ?></p>
            <small class="badge bg-secondary"><?php echo e($mentor['area_of_expertise']); ?></small>
        </div>
        <div class="mentor-actions">
            <a href="resources.php" class="btn btn-primary">
                <i class="fas fa-share-alt me-1"></i> Manage Resources
            </a>
            <a href="available-projects.php" class="btn btn-success">
                <i class="fas fa-plus me-1"></i> Join Projects
            </a>
            <a href="profile.php" class="btn btn-info">
                <i class="fas fa-user me-1"></i> Profile
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Assigned Projects
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $mentorStats['total_projects']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-project-diagram fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Projects
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $mentorStats['active_projects']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-tasks fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Completed Projects
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $mentorStats['completed_projects']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Resources Shared
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $mentorStats['resources_shared']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-share fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Tabs -->
    <div class="card shadow">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="mentorTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="my-projects-tab" data-bs-toggle="tab" data-bs-target="#my-projects" type="button">
                        <i class="fas fa-project-diagram me-2"></i>My Projects
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="available-tab" data-bs-toggle="tab" data-bs-target="#available" type="button">
                        <i class="fas fa-plus me-2"></i>Available Projects
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="resources-tab" data-bs-toggle="tab" data-bs-target="#resources" type="button">
                        <i class="fas fa-share-alt me-2"></i>My Resources
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="assessments-tab" data-bs-toggle="tab" data-bs-target="#assessments" type="button">
                        <i class="fas fa-clipboard-check me-2"></i>Assessments
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="learning-tab" data-bs-toggle="tab" data-bs-target="#learning" type="button">
                        <i class="fas fa-graduation-cap me-2"></i>Learning Objectives
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="mentorTabContent">
                <!-- My Projects Tab -->
                <div class="tab-pane fade show active" id="my-projects" role="tabpanel">
                    <?php if (empty($assignedProjects)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-project-diagram fa-4x text-muted mb-3"></i>
                            <h4>No Projects Yet</h4>
                            <p class="text-muted">Join available projects to start mentoring innovations</p>
                            <a href="available-projects.php" class="btn btn-primary">Browse Available Projects</a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($assignedProjects as $project): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card project-card">
                                    <div class="card-header d-flex justify-content-between">
                                        <h6 class="mb-0"><?php echo e($project['project_name']); ?></h6>
                                        <span class="badge bg-primary">Stage <?php echo $project['current_stage']; ?></span>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text"><?php echo truncateText(e($project['description']), 100); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <?php echo $project['innovator_count']; ?> team members
                                            </small>
                                            <div class="btn-group btn-group-sm">
                                                <a href="project-details.php?id=<?php echo $project['project_id']; ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="assessments.php?project=<?php echo $project['project_id']; ?>" class="btn btn-outline-success">
                                                    <i class="fas fa-clipboard-check"></i>
                                                </a>
                                                <a href="resources.php?project=<?php echo $project['project_id']; ?>" class="btn btn-outline-info">
                                                    <i class="fas fa-share"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Available Projects Tab -->
                <div class="tab-pane fade" id="available" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Available Projects</h5>
                        <a href="available-projects.php" class="btn btn-primary btn-sm">View All</a>
                    </div>
                    
                    <?php if (empty($availableProjects)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No new projects available for assignment at the moment.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach (array_slice($availableProjects, 0, 6) as $project): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo e($project['project_name']); ?></h6>
                                        <p class="card-text"><?php echo truncateText(e($project['description']), 80); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <?php echo $project['innovator_count']; ?> team members
                                            </small>
                                            <a href="project-details.php?id=<?php echo $project['project_id']; ?>&action=join" class="btn btn-sm btn-primary">
                                                Join Project
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Resources Tab -->
                <div class="tab-pane fade" id="resources" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>My Resources</h5>
                        <a href="resources.php?action=create" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Add Resource
                        </a>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb me-2"></i>
                        Create and share resources like links, documents, and tools with your assigned projects.
                    </div>
                    
                    <div class="text-center">
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
                        Create assessment checklists for your projects to track their progress and development.
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
                            <i class="fas fa-plus me-1"></i> Create Objective
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
</div>

<?php include '../../templates/footer.php'; ?>