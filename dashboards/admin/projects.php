<?php
// dashboards/admin/projects.php - Updated with admin override capabilities
require_once '../../includes/init.php';
require_once '../../includes/mentor-consensus-functions.php';

$auth->requireUserType(USER_TYPE_ADMIN);

$adminId = $auth->getUserId();
$errors = [];
$success = '';
$action = $_GET['action'] ?? 'list';
$projectId = $_GET['project_id'] ?? null;

// Handle project stage override
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'admin_override_stage') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token';
    } else {
        $projectId = intval($_POST['project_id']);
        $targetStage = intval($_POST['target_stage']);
        $overrideReason = trim($_POST['override_reason'] ?? '');
        
        if ($targetStage >= 1 && $targetStage <= 6) {
            $project = $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);
            
            if ($project) {
                $updateData = ['current_stage' => $targetStage];
                
                if ($targetStage == 6) {
                    $updateData['status'] = 'completed';
                    $updateData['completion_date'] = date('Y-m-d H:i:s');
                }
                
                $updated = $database->update('projects', $updateData, 'project_id = ?', [$projectId]);
                
                if ($updated) {
                    // Log the admin override
                    logActivity(
                        'admin', 
                        $adminId, 
                        'stage_override', 
                        "Admin override: Set project '{$project['project_name']}' to stage {$targetStage}. Reason: {$overrideReason}",
                        $projectId,
                        ['old_stage' => $project['current_stage'], 'new_stage' => $targetStage, 'reason' => $overrideReason]
                    );
                    
                    // Send notification
                    sendEmailNotification(
                        $project['project_lead_email'],
                        'Project Stage Updated by Administrator',
                        "Your project '{$project['project_name']}' has been updated to Stage {$targetStage} by an administrator.\n\nReason: {$overrideReason}\n\nBest regards,\nJHUB AFRICA Team",
                        NOTIFY_STAGE_UPDATED
                    );
                    
                    $success = "Project stage updated to {$targetStage} successfully!";
                } else {
                    $errors[] = 'Failed to update project stage';
                }
            } else {
                $errors[] = 'Project not found';
            }
        } else {
            $errors[] = 'Invalid stage number';
        }
    }
}

// Get projects with consensus status
if ($action === 'list') {
    $projects = $database->getRows("
        SELECT p.*,
               (SELECT COUNT(*) FROM project_mentors WHERE project_id = p.project_id AND is_active = 1) as mentor_count,
               (SELECT COUNT(*) FROM project_innovators WHERE project_id = p.project_id AND is_active = 1) as team_count
        FROM projects p
        WHERE p.status = 'active'
        ORDER BY p.updated_at DESC
    ");
    
    // Add consensus information for each project
    foreach ($projects as &$project) {
        $project['consensus_status'] = checkMentorConsensusForStageProgression($project['project_id'], $project['current_stage']);
        $project['mentor_approvals'] = getMentorApprovalStatus($project['project_id'], $project['current_stage']);
    }
}

$pageTitle = 'Project Management';
$breadcrumbs = [
    ['title' => 'Admin Dashboard', 'url' => BASE_URL . '/dashboards/admin/'],
    ['title' => 'Projects', 'url' => '']
];

include '../../templates/header.php';
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-project-diagram text-primary me-2"></i>Project Management
        </h1>
        <div class="btn-group">
            <a href="<?php echo BASE_URL; ?>/dashboards/admin/create-project.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus me-1"></i>Create Project
            </a>
            <a href="<?php echo BASE_URL; ?>/dashboards/admin/applications.php" class="btn btn-info btn-sm">
                <i class="fas fa-file-alt me-1"></i>Applications
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo e($success); ?></div>
    <?php endif; ?>

    <!-- Projects Table with Consensus Status -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Active Projects & Stage Progression Status</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="projectsTable">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Current Stage</th>
                            <th>Mentor Consensus</th>
                            <th>Team</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <div class="font-weight-bold"><?php echo e($project['project_name']); ?></div>
                                        <div class="text-muted small">Created: <?php echo formatDate($project['created_at']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-primary me-2">Stage <?php echo $project['current_stage']; ?></span>
                                    <div>
                                        <div class="small font-weight-bold"><?php echo getStageName($project['current_stage']); ?></div>
                                        <div class="text-muted small"><?php echo round(($project['current_stage'] / 6) * 100); ?>% Complete</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($project['mentor_count'] == 0): ?>
                                    <span class="badge bg-secondary">No Mentors</span>
                                <?php elseif ($project['consensus_status']['can_progress']): ?>
                                    <div class="text-success">
                                        <i class="fas fa-check-circle me-1"></i>
                                        <strong>Ready to Progress</strong>
                                        <div class="small">All <?php echo $project['mentor_count']; ?> mentors approved</div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-warning">
                                        <i class="fas fa-clock me-1"></i>
                                        <strong>Pending Consensus</strong>
                                        <div class="small">
                                            <?php echo $project['consensus_status']['approved_mentors']; ?>/<?php echo $project['consensus_status']['total_mentors']; ?> approved
                                        </div>
                                    </div>
                                    
                                    <!-- Show pending mentors -->
                                    <?php if (!empty($project['consensus_status']['pending_mentors'])): ?>
                                        <div class="small text-muted mt-1">
                                            Waiting for: 
                                            <?php 
                                            $pendingNames = array_map(function($m) { return $m['name']; }, $project['consensus_status']['pending_mentors']);
                                            echo implode(', ', $pendingNames);
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="text-center">
                                    <div class="font-weight-bold"><?php echo $project['team_count']; ?></div>
                                    <div class="small text-muted">Members</div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $project['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="<?php echo BASE_URL; ?>/public/project-details.php?id=<?php echo $project['project_id']; ?>" 
                                       class="btn btn-outline-primary" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <?php if ($project['current_stage'] < 6): ?>
                                        <button type="button" class="btn btn-outline-warning" 
                                                onclick="showStageOverrideModal(<?php echo $project['project_id']; ?>, '<?php echo e($project['project_name']); ?>', <?php echo $project['current_stage']; ?>)">
                                            <i class="fas fa-fast-forward"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-outline-info" 
                                            onclick="showConsensusDetails(<?php echo $project['project_id']; ?>)">
                                        <i class="fas fa-users"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Stage Override Modal -->
<div class="modal fade" id="stageOverrideModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Admin Stage Override</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="admin_override_stage">
                    <input type="hidden" id="override_project_id" name="project_id">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Admin Override:</strong> This will bypass mentor consensus and immediately update the project stage.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Project</label>
                        <input type="text" class="form-control" id="override_project_name" readonly>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <label class="form-label">Current Stage</label>
                            <input type="text" class="form-control" id="override_current_stage" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Target Stage <span class="text-danger">*</span></label>
                            <select class="form-control" name="target_stage" id="override_target_stage" required>
                                <option value="">Select Stage</option>
                                <option value="1">Stage 1: Project Creation</option>
                                <option value="2">Stage 2: Mentorship</option>
                                <option value="3">Stage 3: Assessment</option>
                                <option value="4">Stage 4: Learning and Development</option>
                                <option value="5">Stage 5: Progress Tracking</option>
                                <option value="6">Stage 6: Showcase and Integration</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Override Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="override_reason" rows="3" 
                                  placeholder="Please provide a reason for this admin override..." required></textarea>
                        <div class="form-text">This reason will be logged and sent to the project team.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-fast-forward me-2"></i>Override Stage
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Consensus Details Modal -->
<div class="modal fade" id="consensusDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mentor Consensus Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="consensusDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Show stage override modal
function showStageOverrideModal(projectId, projectName, currentStage) {
    document.getElementById('override_project_id').value = projectId;
    document.getElementById('override_project_name').value = projectName;
    document.getElementById('override_current_stage').value = 'Stage ' + currentStage;
    
    // Reset target stage
    document.getElementById('override_target_stage').value = '';
    
    new bootstrap.Modal(document.getElementById('stageOverrideModal')).show();
}

// Show consensus details
function showConsensusDetails(projectId) {
    const modal = new bootstrap.Modal(document.getElementById('consensusDetailsModal'));
    const contentDiv = document.getElementById('consensusDetailsContent');
    
    // Show loading
    contentDiv.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Fetch consensus details
    fetch(`<?php echo BASE_URL; ?>/api/projects/update-stage.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            csrf_token: '<?php echo $auth->generateCSRFToken(); ?>',
            project_id: projectId,
            action: 'get_consensus_status'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let html = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Current Stage</h6>
                        <p><strong>Stage ${data.project_stage}:</strong> ${getStageName(data.project_stage)}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Consensus Status</h6>
                        <p class="${data.consensus.can_progress ? 'text-success' : 'text-warning'}">
                            <i class="fas fa-${data.consensus.can_progress ? 'check-circle' : 'clock'} me-1"></i>
                            ${data.consensus.reason}
                        </p>
                    </div>
                </div>
                
                <h6>Mentor Approvals</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Mentor</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            data.mentor_approvals.forEach(approval => {
                html += `
                    <tr>
                        <td>${approval.mentor_name}</td>
                        <td>
                            ${approval.approved_for_next_stage ? 
                                '<span class="badge bg-success">Approved</span>' : 
                                '<span class="badge bg-warning">Pending</span>'
                            }
                        </td>
                        <td>
                            ${approval.approval_date ? 
                                new Date(approval.approval_date).toLocaleDateString() : 
                                '-'
                            }
                        </td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            if (!data.consensus.can_progress && data.consensus.pending_mentors.length > 0) {
                html += `
                    <div class="alert alert-info mt-3">
                        <strong>Waiting for approval from:</strong><br>
                        ${data.consensus.pending_mentors.map(m => m.name).join(', ')}
                    </div>
                `;
            }
            
            contentDiv.innerHTML = html;
        } else {
            contentDiv.innerHTML = `
                <div class="alert alert-danger">
                    Failed to load consensus details: ${data.message}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        contentDiv.innerHTML = `
            <div class="alert alert-danger">
                An error occurred while loading consensus details.
            </div>
        `;
    });
}

// Helper function to get stage name
function getStageName(stage) {
    const stageNames = {
        1: 'Project Creation',
        2: 'Mentorship',
        3: 'Assessment',
        4: 'Learning and Development',
        5: 'Progress Tracking and Feedback',
        6: 'Showcase and Integration'
    };
    return stageNames[stage] || 'Unknown Stage';
}

// Initialize DataTable if available
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#projectsTable').DataTable({
            "pageLength": 25,
            "order": [[ 0, "asc" ]],
            "columnDefs": [
                { "orderable": false, "targets": [5] }
            ]
        });
    }
});
</script>

<?php include '../../templates/footer.php'; ?>