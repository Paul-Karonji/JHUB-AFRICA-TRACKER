<?php
/**
 * public/project-details.php
 * UPDATED: Public Project Details Page with Comment Approval Filtering
 * 
 * CHANGES:
 * - Only shows approved investor comments to public
 * - Public users can submit comments that require approval
 * - Shows approval pending message after submission
 */

require_once '../includes/init.php';

// Get project ID
$projectId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$projectId) {
    header('Location: projects.php');
    exit;
}

// Get project details
$project = $database->getRow("
    SELECT p.* FROM projects p
    WHERE p.project_id = ? AND p.status = 'active'
", [$projectId]);

if (!$project) {
    header('Location: projects.php');
    exit;
}

// Get team members
$teamMembers = $database->getRows("
    SELECT * FROM project_innovators 
    WHERE project_id = ? AND is_active = 1
    ORDER BY added_at ASC
", [$projectId]);

// Get mentors
$mentors = $database->getRows("
    SELECT m.*, pm.assigned_at
    FROM mentors m
    INNER JOIN project_mentors pm ON m.mentor_id = pm.mentor_id
    WHERE pm.project_id = ? AND pm.is_active = 1
    ORDER BY pm.assigned_at ASC
", [$projectId]);

// Get comments - ONLY approved investor comments for public view
$comments = $database->getRows("
    SELECT * FROM comments 
    WHERE project_id = ? 
      AND parent_comment_id IS NULL
      AND is_deleted = 0
      AND is_approved = 1
      AND commenter_type = 'investor'
    ORDER BY created_at DESC
", [$projectId]);

// Handle comment submission (public users)
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    if (!Validator::validateCSRF()) {
        $errors[] = 'Invalid security token';
    } else {
        $validator = new Validator($_POST);
        $validator->required('commenter_name', 'Name is required')
                 ->required('commenter_email', 'Email is required')
                 ->email('commenter_email')
                 ->required('comment_text', 'Comment is required')
                 ->min('comment_text', 10);
        
        if ($validator->isValid()) {
            // Insert comment with approval required
            $commentData = [
                'project_id' => $projectId,
                'commenter_type' => 'investor',
                'commenter_name' => trim($_POST['commenter_name']),
                'commenter_email' => trim($_POST['commenter_email']),
                'comment_text' => trim($_POST['comment_text']),
                'is_approved' => 0, // Pending approval
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ];
            
            $commentId = $database->insert('comments', $commentData);
            
            if ($commentId) {
                // Notify admins about pending comment
                $admins = $database->getRows("SELECT admin_id FROM admins WHERE is_active = 1");
                
                foreach ($admins as $admin) {
                    $database->insert('notifications', [
                        'user_id' => $admin['admin_id'],
                        'user_type' => 'admin',
                        'title' => 'New Comment Pending Approval',
                        'message' => "A new public comment from " . trim($_POST['commenter_name']) . " needs approval on project: {$project['project_name']}",
                        'notification_type' => 'warning',
                        'action_url' => '/dashboards/admin/moderate-comments.php'
                    ]);
                }
                
                $success = 'Comment submitted successfully! It will be visible after admin approval.';
                
                // Clear form
                $_POST = [];
            } else {
                $errors[] = 'Failed to post comment. Please try again.';
            }
        } else {
            $errors = $validator->getErrors();
        }
    }
}

$pageTitle = htmlspecialchars($project['project_name']) . ' - Project Details';
require_once '../templates/public-header.php';
?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/public/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/public/projects.php">Projects</a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($project['project_name']); ?></li>
        </ol>
    </nav>

    <!-- Project Header -->
    <div class="card shadow-lg mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 mb-3"><?php echo htmlspecialchars($project['project_name']); ?></h1>
                    <p class="lead text-muted mb-3"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
                    
                    <div class="mb-3">
                        <span class="badge bg-primary me-2">Stage <?php echo $project['current_stage']; ?></span>
                        <span class="badge bg-success"><?php echo ucfirst($project['status']); ?></span>
                    </div>
                    
                    <?php if ($project['project_website']): ?>
                    <a href="<?php echo htmlspecialchars($project['project_website']); ?>" 
                       target="_blank" class="btn btn-outline-primary">
                        <i class="fas fa-globe"></i> Visit Website
                    </a>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-center">
                    <?php if ($project['project_logo']): ?>
                        <img src="<?php echo SITE_URL . '/' . htmlspecialchars($project['project_logo']); ?>" 
                             alt="Project Logo" class="img-fluid rounded" style="max-height: 200px;">
                    <?php else: ?>
                        <div class="bg-light rounded p-5">
                            <i class="fas fa-lightbulb fa-5x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Information -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>Team Members</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($teamMembers)): ?>
                        <p class="text-muted">No team members listed yet.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($teamMembers as $member): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($member['name']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($member['role']); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Mentors</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($mentors)): ?>
                        <p class="text-muted">No mentors assigned yet.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($mentors as $mentor): ?>
                            <div class="list-group-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($mentor['name']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-briefcase me-1"></i>
                                        <?php echo htmlspecialchars($mentor['area_of_expertise']); ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Comments Section -->
    <div class="card shadow mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">
                <i class="fas fa-comments me-2"></i>Public Comments 
                <span class="badge bg-light text-dark float-end"><?php echo count($comments); ?></span>
            </h5>
        </div>
        <div class="card-body">
            
            <!-- Success/Error Messages -->
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php foreach ($errors as $error): ?>
                    <div><i class="fas fa-exclamation-triangle me-2"></i><?php echo is_array($error) ? implode(', ', $error) : $error; ?></div>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Info Alert -->
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Note:</strong> All public comments are reviewed by administrators before being published to ensure quality and relevance.
            </div>

            <!-- Comment Form -->
            <form method="POST" class="mb-4">
                <?php echo Validator::csrfInput(); ?>
                <h6 class="mb-3">Leave a Comment</h6>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Your Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="commenter_name" 
                               value="<?php echo isset($_POST['commenter_name']) ? htmlspecialchars($_POST['commenter_name']) : ''; ?>" 
                               required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Your Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="commenter_email" 
                               value="<?php echo isset($_POST['commenter_email']) ? htmlspecialchars($_POST['commenter_email']) : ''; ?>" 
                               required>
                        <small class="form-text text-muted">Your email will not be displayed publicly.</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Comment <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="comment_text" rows="4" 
                              placeholder="Share your thoughts, questions, or feedback..." 
                              required minlength="10"><?php echo isset($_POST['comment_text']) ? htmlspecialchars($_POST['comment_text']) : ''; ?></textarea>
                    <small class="form-text text-muted">Minimum 10 characters</small>
                </div>
                <button type="submit" name="submit_comment" class="btn btn-primary">
                    <i class="fas fa-paper-plane me-2"></i>Submit Comment
                </button>
            </form>

            <hr>

            <!-- Comments List -->
            <h6 class="mb-3">Community Feedback</h6>
            <?php if (empty($comments)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-comment-slash fa-3x mb-3 d-block"></i>
                    <p>No approved comments yet. Be the first to comment!</p>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                <div class="card mb-3 border-start border-info border-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong class="text-info"><?php echo htmlspecialchars($comment['commenter_name']); ?></strong>
                                <span class="badge bg-info ms-2">Investor</span>
                            </div>
                            <small class="text-muted">
                                <?php echo date('M j, Y', strtotime($comment['created_at'])); ?>
                            </small>
                        </div>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Back Button -->
    <div class="text-center">
        <a href="<?php echo SITE_URL; ?>/public/projects.php" class="btn btn-lg btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Projects
        </a>
    </div>
</div>

<?php require_once '../templates/public-footer.php'; ?>