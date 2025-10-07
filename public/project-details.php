<?php
// public/project-details.php
// Public Project Details Page with Comments
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

// Get comments
$comments = $database->getRows("
    SELECT * FROM comments 
    WHERE project_id = ? AND parent_comment_id IS NULL
    ORDER BY created_at DESC
", [$projectId]);

// Handle comment submission
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
            $commentData = [
                'project_id' => $projectId,
                'commenter_type' => 'investor',
                'commenter_name' => trim($_POST['commenter_name']),
                'commenter_email' => trim($_POST['commenter_email']),
                'comment_text' => trim($_POST['comment_text']),
            ];
            
            $commentId = $database->insert('comments', $commentData);
            
            if ($commentId) {
                $success = 'Comment posted successfully!';
                // Reload comments
                $comments = $database->getRows("
                    SELECT * FROM comments 
                    WHERE project_id = ? AND parent_comment_id IS NULL
                    ORDER BY created_at DESC
                ", [$projectId]);
            } else {
                $errors[] = 'Failed to post comment';
            }
        } else {
            $errors = $validator->getErrors();
        }
    }
}

$pageTitle = $project['project_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?> - JHUB AFRICA</title>
    <meta name="description" content="<?php echo e(substr($project['description'], 0, 160)); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .project-header {
            background: linear-gradient(135deg, #3b54c7 0%, #0e015b 100%);
            color: white;
            padding: 60px 0;
        }
        .stage-progress {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stage-dot {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 auto 10px;
        }
        .stage-dot.completed {
            background: #3fa845;
            color: white;
        }
        .stage-dot.current {
            background: #3b54c7;
            color: white;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .team-member-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
        }
        .team-member-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }
        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b54c7 0%, #0e015b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 15px;
        }
        .comment-card {
            border-left: 3px solid #3b54c7;
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">
                <i class="fas fa-lightbulb me-2"></i>JHUB AFRICA
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="projects.php">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../applications/submit.php">Apply</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Project Header -->
    <div class="project-header">
        <div class="container">
            <div class="mb-3">
                <a href="projects.php" class="text-white text-decoration-none">
                    <i class="fas fa-arrow-left me-2"></i>Back to Projects
                </a>
            </div>
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-3"><?php echo e($project['project_name']); ?></h1>
                    <p class="lead mb-3"><?php echo nl2br(e(substr($project['description'], 0, 200))); ?>...</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <span class="badge bg-light text-dark px-3 py-2">
                            <i class="fas fa-user me-1"></i><?php echo e($project['project_lead_name']); ?>
                        </span>
                        <?php if ($project['target_market']): ?>
                        <span class="badge bg-light text-dark px-3 py-2">
                            <i class="fas fa-bullseye me-1"></i><?php echo e($project['target_market']); ?>
                        </span>
                        <?php endif; ?>
                        <?php if ($project['project_website']): ?>
                        <a href="<?php echo e($project['project_website']); ?>" target="_blank" 
                           class="badge bg-light text-dark px-3 py-2 text-decoration-none">
                            <i class="fas fa-globe me-1"></i>Visit Website
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="stage-progress">
                        <h5 class="text-dark mb-3">Project Progress</h5>
                        <div class="progress mb-3" style="height: 12px;">
                            <div class="progress-bar bg-success" 
                                 style="width: <?php echo ($project['current_stage'] / 6) * 100; ?>%">
                            </div>
                        </div>
                        <div class="text-dark text-center">
                            <strong>Stage <?php echo $project['current_stage']; ?> of 6</strong><br>
                            <small class="text-muted"><?php echo getStageName($project['current_stage']); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Full Description -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>About This Project</h5>
                    </div>
                    <div class="card-body">
                        <p class="lead"><?php echo nl2br(e($project['description'])); ?></p>
                        
                        <?php if ($project['business_model']): ?>
                        <hr>
                        <h6 class="fw-bold">Business Model:</h6>
                        <p><?php echo nl2br(e($project['business_model'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Development Stages -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Development Journey</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center g-3">
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                            <div class="col-4 col-md-2">
                                <div class="stage-dot <?php echo $i < $project['current_stage'] ? 'completed' : ($i == $project['current_stage'] ? 'current' : ''); ?>">
                                    <?php echo $i < $project['current_stage'] ? '&#10003;' : $i; ?>
                                </div>
                                <small class="d-block"><?php echo getStageName($i); ?></small>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!-- Team Members -->
                <?php if (!empty($teamMembers)): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-users me-2"></i>Team Members (<?php echo count($teamMembers); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($teamMembers as $member): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="team-member-card">
                                    <div class="avatar">
                                        <?php echo strtoupper(substr($member['name'], 0, 1)); ?>
                                    </div>
                                    <h6 class="mb-1"><?php echo e($member['name']); ?></h6>
                                    <small class="text-muted d-block mb-2"><?php echo e($member['role']); ?></small>
                                    <?php if ($member['level_of_experience']): ?>
                                    <span class="badge bg-light text-dark"><?php echo e($member['level_of_experience']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Comments Section -->
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Comments (<?php echo count($comments); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo e($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php foreach ($errors as $error): ?>
                                <div><?php echo is_array($error) ? implode(', ', $error) : $error; ?></div>
                            <?php endforeach; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <!-- Comment Form -->
                        <form method="POST" class="mb-4">
                            <?php echo Validator::csrfInput(); ?>
                            <h6 class="mb-3">Leave a Comment</h6>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Your Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="commenter_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Your Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="commenter_email" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Comment <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="comment_text" rows="4" 
                                          placeholder="Share your thoughts, questions, or feedback..." required></textarea>
                            </div>
                            <button type="submit" name="submit_comment" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Post Comment
                            </button>
                        </form>

                        <hr>

                        <!-- Comments List -->
                        <?php if (empty($comments)): ?>
                            <p class="text-muted text-center py-4">
                                <i class="fas fa-comment-slash fa-2x mb-3 d-block"></i>
                                No comments yet. Be the first to comment!
                            </p>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                            <div class="comment-card p-3 mb-3 rounded">
                                <div class="d-flex justify-content-between mb-2">
                                    <strong><?php echo e($comment['commenter_name']); ?></strong>
                                    <small class="text-muted">
                                        <?php echo timeAgo($comment['created_at']); ?>
                                    </small>
                                </div>
                                <p class="mb-0"><?php echo nl2br(e($comment['comment_text'])); ?></p>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="col-lg-4">
                <!-- Mentors -->
                <?php if (!empty($mentors)): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Mentors</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($mentors as $mentor): ?>
                        <div class="mb-3 pb-3 <?php echo end($mentors) !== $mentor ? 'border-bottom' : ''; ?>">
                            <h6 class="mb-1"><?php echo e($mentor['name']); ?></h6>
                            <small class="text-muted d-block mb-2"><?php echo e($mentor['expertise']); ?></small>
                            <?php if ($mentor['email']): ?>
                            <small>
                                <i class="fas fa-envelope me-1"></i>
                                <a href="mailto:<?php echo e($mentor['email']); ?>"><?php echo e($mentor['email']); ?></a>
                            </small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Info -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-info me-2"></i>Quick Info</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="fas fa-calendar text-primary me-2"></i>
                                Started: <?php echo date('F Y', strtotime($project['date'])); ?>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-users text-success me-2"></i>
                                Team Size: <?php echo count($teamMembers); ?> members
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-chalkboard-teacher text-warning me-2"></i>
                                Mentors: <?php echo count($mentors); ?>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-chart-line text-info me-2"></i>
                                Progress: <?php echo round(($project['current_stage'] / 6) * 100); ?>%
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Connect -->
                <div class="card shadow-sm bg-primary text-white">
                    <div class="card-body text-center">
                        <h5 class="mb-3">Interested in This Project?</h5>
                        <p class="small mb-3">Connect with the team or support their innovation journey</p>
                        <?php if ($project['project_email']): ?>
                        <a href="mailto:<?php echo e($project['project_email']); ?>" 
                           class="btn btn-light w-100 mb-2">
                            <i class="fas fa-envelope me-2"></i>Contact Team
                        </a>
                        <?php endif; ?>
                        <a href="contact.php?project=<?php echo $projectId; ?>" 
                           class="btn btn-outline-light w-100">
                            <i class="fas fa-handshake me-2"></i>Partner With Us
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> JHUB AFRICA. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
