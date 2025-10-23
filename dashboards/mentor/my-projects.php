<?php
/**
 * dashboards/mentor/my-projects.php
 * COMPLETE FILE - Mentor Projects Dashboard with Comments Section
 */

require_once '../../includes/init.php';

// Require mentor authentication
$auth->requireUserType(USER_TYPE_MENTOR);

$mentorId = $auth->getUserId();
$viewProject = null;
$errors = [];
$success = '';

// Check if viewing specific project
if (isset($_GET['id'])) {
    $projectId = intval($_GET['id']);
    
    // Verify mentor is assigned to this project
    $assignment = $database->getRow("
        SELECT * FROM project_mentors 
        WHERE project_id = ? AND mentor_id = ? AND is_active = 1
    ", [$projectId, $mentorId]);
    
    if ($assignment) {
        $viewProject = $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);
        
        if ($viewProject) {
            // Get project team
            $teamMembers = $database->getRows("
                SELECT * FROM project_innovators 
                WHERE project_id = ? AND is_active = 1
                ORDER BY added_at ASC
            ", [$projectId]);
            
            // Get other mentors
            $otherMentors = $database->getRows("
                SELECT m.*, pm.assigned_at
                FROM mentors m
                INNER JOIN project_mentors pm ON m.mentor_id = pm.mentor_id
                WHERE pm.project_id = ? AND pm.is_active = 1
                ORDER BY pm.assigned_at ASC
            ", [$projectId]);
            
            // Get resources for this project
            $projectResources = $database->getRows("
                SELECT mr.*, m.name as mentor_name
                FROM mentor_resources mr
                INNER JOIN mentors m ON mr.mentor_id = m.mentor_id
                WHERE mr.project_id = ? AND mr.is_deleted = 0
                ORDER BY mr.created_at DESC
            ", [$projectId]);
            
            // Get assessments
            $assessments = $database->getRows("
                SELECT * FROM project_assessments
                WHERE project_id = ? AND mentor_id = ? AND is_deleted = 0
                ORDER BY created_at DESC
            ", [$projectId, $mentorId]);
            
            // Get learning objectives
            $learningObjectives = $database->getRows("
                SELECT * FROM learning_objectives
                WHERE project_id = ? AND mentor_id = ? AND is_deleted = 0
                ORDER BY created_at DESC
            ", [$projectId, $mentorId]);
        }
    }
} else {
    // Get all projects assigned to this mentor
    $myProjects = $database->getRows("
        SELECT p.*, 
               pm.assigned_at,
               (SELECT COUNT(*) FROM project_innovators WHERE project_id = p.project_id AND is_active = 1) as team_count,
               (SELECT COUNT(*) FROM project_mentors WHERE project_id = p.project_id AND is_active = 1) as mentor_count
        FROM projects p
        INNER JOIN project_mentors pm ON p.project_id = pm.project_id
        WHERE pm.mentor_id = ? AND pm.is_active = 1
        ORDER BY p.updated_at DESC
    ", [$mentorId]);
}

$pageTitle = $viewProject ? htmlspecialchars($viewProject['project_name']) . " - Project Details" : "My Projects";
include '../../templates/header.php';
?>

<div class="mentor-dashboard">
    
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php foreach ($errors as $error): ?>
            <div><i class="fas fa-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($viewProject): ?>
        <!-- SINGLE PROJECT VIEW -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="my-projects.php" class="text-decoration-none text-muted mb-2 d-block">
                    <i class="fas fa-arrow-left me-1"></i> Back to My Projects
                </a>
                <h1 class="h3 mb-0"><?php echo htmlspecialchars($viewProject['project_name']); ?></h1>
                <p class="text-muted mb-0">Project Lead: <?php echo htmlspecialchars($viewProject['project_lead_name']); ?></p>
            </div>
            <div>
                <span class="badge bg-primary fs-6 me-2"><?php echo getStageName($viewProject['current_stage']); ?></span>
                <span class="badge bg-<?php echo $viewProject['status'] === 'active' ? 'success' : ($viewProject['status'] === 'completed' ? 'info' : 'danger'); ?> fs-6">
                    <?php echo ucfirst($viewProject['status']); ?>
                </span>
            </div>
        </div>

        <!-- Project Information Card -->
        <div class="card shadow mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Project Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>Description:</strong>
                        <p><?php echo nl2br(htmlspecialchars($viewProject['description'])); ?></p>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Project Lead:</strong>
                        <p><?php echo htmlspecialchars($viewProject['project_lead_name']); ?><br>
                        <?php echo htmlspecialchars($viewProject['project_lead_email']); ?></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <strong>Created:</strong> <?php echo date('M d, Y', strtotime($viewProject['created_at'])); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Status:</strong> <?php echo ucfirst($viewProject['status']); ?>
                    </div>
                    <div class="col-md-4">
                        <strong>Current Stage:</strong> <?php echo getStageName($viewProject['current_stage']); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Members Card -->
        <?php if (!empty($teamMembers)): ?>
        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Team Members (<?php echo count($teamMembers); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($teamMembers as $member): ?>
                    <div class="col-md-6 mb-3">
                        <div class="border p-3 rounded">
                            <h6 class="mb-1"><?php echo htmlspecialchars($member['name']); ?></h6>
                            <p class="text-muted mb-1 small"><?php echo htmlspecialchars($member['email']); ?></p>
                            <span class="badge bg-info"><?php echo htmlspecialchars($member['role']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Other Mentors Card -->
        <?php if (!empty($otherMentors) && count($otherMentors) > 1): ?>
        <div class="card shadow mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-user-tie me-2"></i>Other Mentors (<?php echo count($otherMentors) - 1; ?>)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($otherMentors as $mentor): ?>
                        <?php if ($mentor['mentor_id'] != $mentorId): ?>
                        <div class="col-md-6 mb-3">
                            <div class="border p-3 rounded">
                                <h6 class="mb-1"><?php echo htmlspecialchars($mentor['name']); ?></h6>
                                <p class="text-muted mb-0 small"><?php echo htmlspecialchars($mentor['email']); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Resources Card -->
        <?php if (!empty($projectResources)): ?>
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-book me-2"></i>Shared Resources (<?php echo count($projectResources); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Shared By</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projectResources as $resource): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($resource['title']); ?></td>
                                <td><?php echo htmlspecialchars(substr($resource['description'], 0, 50)) . '...'; ?></td>
                                <td><?php echo htmlspecialchars($resource['mentor_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($resource['created_at'])); ?></td>
                                <td>
                                    <?php if ($resource['resource_url']): ?>
                                    <a href="<?php echo htmlspecialchars($resource['resource_url']); ?>" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="fas fa-external-link-alt"></i> View
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- COMMENTS SECTION - THIS WAS MISSING! -->
        <?php include '../../templates/comments-section.php'; ?>

    <?php else: ?>
        <!-- PROJECT LIST VIEW -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0"><i class="fas fa-briefcase me-2"></i>My Projects</h1>
                <p class="text-muted">Projects you are mentoring</p>
            </div>
            <a href="available-projects.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Find More Projects
            </a>
        </div>

        <?php if (empty($myProjects)): ?>
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <i class="fas fa-briefcase fa-4x text-muted mb-3"></i>
                    <h4>No Projects Yet</h4>
                    <p class="text-muted">You haven't joined any projects as a mentor.</p>
                    <a href="available-projects.php" class="btn btn-primary mt-3">
                        <i class="fas fa-search me-2"></i>Browse Available Projects
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow">
                <div class="card-header">
                    <h5 class="mb-0">Your Projects (<?php echo count($myProjects); ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Project Name</th>
                                    <th>Stage</th>
                                    <th>Status</th>
                                    <th>Team</th>
                                    <th>Mentors</th>
                                    <th>Last Updated</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($myProjects as $project): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($project['project_name']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($project['project_lead_name']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo getStageName($project['current_stage']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $project['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($project['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="fas fa-users"></i> <?php echo $project['team_count']; ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-user-tie"></i> <?php echo $project['mentor_count']; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($project['updated_at'])); ?></td>
                                    <td>
                                        <a href="my-projects.php?id=<?php echo $project['project_id']; ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../../templates/footer.php'; ?>