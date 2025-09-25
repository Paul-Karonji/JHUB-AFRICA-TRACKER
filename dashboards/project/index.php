<?php
// dashboards/project/index.php - Comprehensive Project Dashboard
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_PROJECT);

$projectId = $auth->getUserId();

// Get project information
$project = $database->getRow("
    SELECT * FROM projects 
    WHERE project_id = ?
", [$projectId]);

if (!$project) {
    die("Project not found");
}

// Get team members
$teamMembers = getProjectTeam($projectId);

// Get assigned mentors
$mentors = getProjectMentors($projectId);

// Get project statistics
$projectStats = [
    'team_members' => count($teamMembers),
    'mentors' => count($mentors),
    'comments' => $database->count('comments', 'project_id = ? AND is_deleted = 0', [$projectId]),
    'resources' => $database->count('mentor_resources', 'project_id = ?', [$projectId])
];

$pageTitle = $project['project_name'] . " - Project Dashboard";
include '../../templates/header.php';
?>

<div class="project-dashboard">
    <!-- Dashboard Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?php echo e($project['project_name']); ?></h1>
            <p class="text-muted">Project Lead: <?php echo e($project['project_lead_name']); ?></p>
            <div class="d-flex align-items-center">
                <span class="badge bg-primary me-2">Stage <?php echo $project['current_stage']; ?></span>
                <span class="badge bg-<?php echo $project['status'] === 'active' ? 'success' : ($project['status'] === 'completed' ? 'info' : 'secondary'); ?>">
                    <?php echo ucfirst($project['status']); ?>
                </span>
            </div>
        </div>
        <div class="project-actions">
            <a href="team.php" class="btn btn-primary">
                <i class="fas fa-users me-1"></i> Manage Team
            </a>
            <a href="progress.php" class="btn btn-success">
                <i class="fas fa-chart-line me-1"></i> View Progress
            </a>
            <a href="settings.php" class="btn btn-info">
                <i class="fas fa-cog me-1"></i> Settings
            </a>
        </div>
    </div>

    <!-- Project Progress -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-chart-line me-2"></i>Project Progress
            </h5>
        </div>
        <div class="card-body">
            <div class="progress-container">
                <h6>Current Stage: <?php echo getStageName($project['current_stage']); ?></h6>
                <p class="text-muted mb-3"><?php echo getStageDescription($project['current_stage']); ?></p>
                
                <div class="progress mb-3" style="height: 25px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         style="width: <?php echo getStageProgress($project['current_stage']); ?>%">
                        <?php echo number_format(getStageProgress($project['current_stage']), 1); ?>%
                    </div>
                </div>
                
                <!-- Stage Timeline -->
                <div class="stage-timeline">
                    <?php for ($stage = 1; $stage <= 6; $stage++): ?>
                    <div class="stage-step <?php echo $stage <= $project['current_stage'] ? 'completed' : 'pending'; ?>">
                        <div class="stage-number"><?php echo $stage; ?></div>
                        <div class="stage-info">
                            <strong><?php echo getStageName($stage); ?></strong>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Team Members
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $projectStats['team_members']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Mentors
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $projectStats['mentors']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-tie fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Resources Available
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $projectStats['resources']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-share fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Comments
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $projectStats['comments']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comments fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Tabs -->
    <div class="card shadow">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="projectTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button">
                        <i class="fas fa-home me-2"></i>Overview
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="team-tab" data-bs-toggle="tab" data-bs-target="#team" type="button">
                        <i class="fas fa-users me-2"></i>Team
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="mentors-tab" data-bs-toggle="tab" data-bs-target="#mentors" type="button">
                        <i class="fas fa-user-tie me-2"></i>Mentors
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="resources-tab" data-bs-toggle="tab" data-bs-target="#resources" type="button">
                        <i class="fas fa-share-alt me-2"></i>Resources
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="assessments-tab" data-bs-toggle="tab" data-bs-target="#assessments" type="button">
                        <i class="fas fa-clipboard-check me-2"></i>Assessments
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="learning-tab" data-bs-toggle="tab" data-bs-target="#learning" type="button">
                        <i class="fas fa-graduation-cap me-2"></i>Learning
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" type="button">
                        <i class="fas fa-comments me-2"></i>Discussion
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="projectTabContent">
                <!-- Overview Tab -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <div class="row">
                        <div class="col-md-8">
                            <h5>Project Description</h5>
                            <p><?php echo nl2br(e($project['description'])); ?></p>
                            
                            <?php if ($project['target_market']): ?>
                            <h6>Target Market</h6>
                            <p><?php echo e($project['target_market']); ?></p>
                            <?php endif; ?>
                            
                            <?php if ($project['business_model']): ?>
                            <h6>Business Model</h6>
                            <p><?php echo nl2br(e($project['business_model'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Project Details</h6>
                                </div>
                                <div class="card-body">
                                    <p><strong>Created:</strong> <?php echo formatDate($project['created_at']); ?></p>
                                    <p><strong>Stage:</strong> <?php echo getStageName($project['current_stage']); ?></p>
                                    <p><strong>Status:</strong> <?php echo ucfirst($project['status']); ?></p>
                                    <?php if ($project['project_website']): ?>
                                    <p><strong>Website:</strong> 
                                        <a href="<?php echo e($project['project_website']); ?>" target="_blank">
                                            <?php echo e($project['project_website']); ?>
                                        </a>
                                    </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Tab -->
                <div class="tab-pane fade" id="team" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Team Members</h5>
                        <a href="team.php?action=add" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i> Add Member
                        </a>
                    </div>
                    
                    <?php if (empty($teamMembers)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-users me-2"></i>
                            No team members yet. Add team members to start collaborating.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($teamMembers as $member): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-2">
                                            <img src="<?php echo getGravatar($member['email'], 40); ?>" 
                                                 class="rounded-circle me-3" alt="<?php echo e($member['name']); ?>">
                                            <div>
                                                <h6 class="mb-0"><?php echo e($member['name']); ?></h6>
                                                <small class="text-muted"><?php echo e($member['role']); ?></small>
                                            </div>
                                        </div>
                                        <p class="mb-1"><?php echo e($member['email']); ?></p>
                                        <?php if ($member['level_of_experience']): ?>
                                        <small class="badge bg-secondary">
                                            <?php echo e($member['level_of_experience']); ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Mentors Tab -->
                <div class="tab-pane fade" id="mentors" role="tabpanel">
                    <h5>Assigned Mentors</h5>
                    
                    <?php if (empty($mentors)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-user-tie me-2"></i>
                            No mentors assigned yet. Mentors will join your project to provide guidance and support.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($mentors as $mentor): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-2">
                                            <img src="<?php echo getGravatar($mentor['email'], 50); ?>" 
                                                 class="rounded-circle me-3" alt="<?php echo e($mentor['name']); ?>">
                                            <div>
                                                <h6 class="mb-0"><?php echo e($mentor['name']); ?></h6>
                                                <small class="text-muted"><?php echo e($mentor['area_of_expertise']); ?></small>
                                            </div>
                                        </div>
                                        <p class="card-text"><?php echo truncateText(e($mentor['bio']), 100); ?></p>
                                        <small class="text-muted">
                                            Joined: <?php echo formatDate($mentor['assigned_at']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Resources Tab -->
                <div class="tab-pane fade" id="resources" role="tabpanel">
                    <h5>Available Resources</h5>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Resources shared by your mentors will appear here.
                    </div>
                    <div class="text-center">
                        <a href="resources.php" class="btn btn-outline-primary">View All Resources</a>
                    </div>
                </div>

                <!-- Assessments Tab -->
                <div class="tab-pane fade" id="assessments" role="tabpanel">
                    <h5>Project Assessments</h5>
                    <div class="alert alert-info">
                        <i class="fas fa-clipboard-check me-2"></i>
                        Assessment checklists from your mentors will be shown here.
                    </div>
                    <div class="text-center">
                        <a href="assessments.php" class="btn btn-outline-primary">View All Assessments</a>
                    </div>
                </div>

                <!-- Learning Tab -->
                <div class="tab-pane fade" id="learning" role="tabpanel">
                    <h5>Learning Objectives</h5>
                    <div class="alert alert-info">
                        <i class="fas fa-graduation-cap me-2"></i>
                        Learning objectives and skill development goals assigned by mentors will appear here.
                    </div>
                    <div class="text-center">
                        <a href="learning.php" class="btn btn-outline-primary">View Learning Objectives</a>
                    </div>
                </div>

                <!-- Comments Tab -->
                <div class="tab-pane fade" id="comments" role="tabpanel">
                    <h5>Project Discussion</h5>
                    <div class="text-center">
                        <a href="comments.php" class="btn btn-outline-primary">View Full Discussion</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>