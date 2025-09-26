<?php
// applications/status.php
// Check Application Status
require_once '../includes/init.php';

$application = null;
$error = '';

// Check if application ID is provided
if (isset($_GET['id'])) {
    $applicationId = intval($_GET['id']);
    $application = $database->getRow(
        "SELECT * FROM project_applications WHERE application_id = ?",
        [$applicationId]
    );
    
    if (!$application) {
        $error = 'Application not found.';
    }
}

$pageTitle = "Check Application Status";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/main.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-rocket me-2"></i>JHUB AFRICA
            </a>
            <a href="../auth/login.php" class="btn btn-outline-primary">
                <i class="fas fa-sign-in-alt me-1"></i>Login
            </a>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-search me-2"></i>Check Application Status</h3>
                    </div>
                    <div class="card-body p-4">
                        
                        <?php if (!$application): ?>
                            <!-- Search Form -->
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo e($error); ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="GET" class="mb-4">
                                <div class="mb-3">
                                    <label for="applicationId" class="form-label">Enter Application ID</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text">#</span>
                                        <input type="number" class="form-control" id="applicationId" name="id" 
                                               placeholder="Enter your application ID" required>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-1"></i>Check Status
                                        </button>
                                    </div>
                                    <div class="form-text">Your application ID was provided when you submitted your application.</div>
                                </div>
                            </form>
                            
                            <div class="text-center text-muted">
                                <p>Don't have an application ID?</p>
                                <a href="submit.php" class="btn btn-outline-primary">
                                    <i class="fas fa-plus me-1"></i>Submit New Application
                                </a>
                            </div>
                            
                        <?php else: ?>
                            <!-- Application Status Display -->
                            <div class="mb-4">
                                <h4 class="border-bottom pb-3">Application #<?php echo $application['application_id']; ?></h4>
                                
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="30%">Project Name:</th>
                                        <td><strong><?php echo e($application['project_name']); ?></strong></td>
                                    </tr>
                                    <tr>
                                        <th>Project Lead:</th>
                                        <td><?php echo e($application['project_lead_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Submitted Date:</th>
                                        <td><?php echo formatDate($application['applied_at']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Current Status:</th>
                                        <td>
                                            <?php
                                            $statusBadge = [
                                                'pending' => '<span class="badge bg-warning"><i class="fas fa-clock me-1"></i>Pending Review</span>',
                                                'approved' => '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Approved</span>',
                                                'rejected' => '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Rejected</span>'
                                            ];
                                            echo $statusBadge[$application['status']];
                                            ?>
                                        </td>
                                    </tr>
                                    <?php if ($application['reviewed_at']): ?>
                                    <tr>
                                        <th>Reviewed Date:</th>
                                        <td><?php echo formatDate($application['reviewed_at']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>

                            <!-- Status-specific messages -->
                            <?php if ($application['status'] === 'pending'): ?>
                                <div class="alert alert-info">
                                    <h5 class="alert-heading"><i class="fas fa-hourglass-half me-2"></i>Under Review</h5>
                                    <p class="mb-0">Your application is currently being reviewed by our team. We typically complete reviews within 5-7 business days.</p>
                                </div>
                            
                            <?php elseif ($application['status'] === 'approved'): ?>
                                <div class="alert alert-success">
                                    <h5 class="alert-heading"><i class="fas fa-check-circle me-2"></i>Congratulations!</h5>
                                    <p>Your application has been approved! You should have received an email with your login credentials.</p>
                                    <hr>
                                    <div class="d-grid">
                                        <a href="../auth/project-login.php" class="btn btn-success">
                                            <i class="fas fa-sign-in-alt me-2"></i>Access Project Dashboard
                                        </a>
                                    </div>
                                </div>
                            
                            <?php elseif ($application['status'] === 'rejected'): ?>
                                <div class="alert alert-danger">
                                    <h5 class="alert-heading"><i class="fas fa-times-circle me-2"></i>Application Not Approved</h5>
                                    <p>Unfortunately, your application was not approved at this time.</p>
                                    <?php if ($application['rejection_reason']): ?>
                                        <hr>
                                        <p class="mb-0"><strong>Reason:</strong> <?php echo nl2br(e($application['rejection_reason'])); ?></p>
                                    <?php endif; ?>
                                    <hr>
                                    <p class="mb-0">You're welcome to refine your project and submit a new application in the future.</p>
                                </div>
                            <?php endif; ?>

                            <div class="text-center mt-4">
                                <a href="../index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-home me-1"></i>Back to Home
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>