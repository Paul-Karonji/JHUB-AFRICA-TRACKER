<?php
// applications/confirmation.php
// Application Submission Confirmation Page
require_once '../includes/init.php';

// Get application ID
$applicationId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$applicationId) {
    header('Location: submit.php');
    exit;
}

// Get application details
$application = $database->getRow(
    "SELECT * FROM project_applications WHERE application_id = ?",
    [$applicationId]
);

if (!$application) {
    header('Location: submit.php');
    exit;
}

$pageTitle = "Application Submitted";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .confirmation-hero {
            background: linear-gradient(135deg, #3fa845 0%, #56c05c 100%);
            color: white;
            padding: 80px 0;
        }
        .success-icon {
            width: 100px;
            height: 100px;
            line-height: 100px;
            font-size: 50px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            margin: 0 auto 30px;
        }
        .timeline {
            position: relative;
            padding-left: 40px;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #dee2e6;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 30px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -27px;
            top: 5px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: white;
            border: 3px solid #2c409a;
        }
        .timeline-item.completed::before {
            background: #3fa845;
            border-color: #3fa845;
        }
        .info-card {
            border-left: 4px solid #2c409a;
        }
    </style>
</head>
<body>
    <!-- Success Hero -->
    <div class="confirmation-hero text-center">
        <div class="container">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="display-4 fw-bold mb-3">Application Submitted Successfully!</h1>
            <p class="lead mb-4">Thank you for applying to JHUB AFRICA</p>
            <div class="badge bg-light text-success fs-5 px-4 py-2">
                Application ID: #<?php echo str_pad($applicationId, 6, '0', STR_PAD_LEFT); ?>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Application Summary -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Application Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Project Name:</strong><br>
                                <?php echo e($application['project_name']); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Project Lead:</strong><br>
                                <?php echo e($application['project_lead_name']); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Email:</strong><br>
                                <?php echo e($application['project_lead_email']); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Submitted:</strong><br>
                                <?php echo date('F j, Y \a\t g:i A', strtotime($application['applied_at'])); ?>
                            </div>
                            <div class="col-12">
                                <strong>Status:</strong><br>
                                <span class="badge bg-warning text-dark">
                                    <i class="fas fa-clock me-1"></i>Pending Review
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- What Happens Next -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-route me-2"></i>What Happens Next?</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item completed">
                                <h6 class="mb-1">Application Submitted âœ“</h6>
                                <p class="text-muted small mb-0">Your application has been received and is in our system.</p>
                            </div>
                            <div class="timeline-item">
                                <h6 class="mb-1">Review Process (5-7 business days)</h6>
                                <p class="text-muted small mb-0">Our team will carefully review your project presentation and details.</p>
                            </div>
                            <div class="timeline-item">
                                <h6 class="mb-1">Decision Notification</h6>
                                <p class="text-muted small mb-0">You'll receive an email with our decision and next steps.</p>
                            </div>
                            <div class="timeline-item">
                                <h6 class="mb-1">Dashboard Access (if approved)</h6>
                                <p class="text-muted small mb-0">Gain access to your project dashboard and start your journey!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <!-- Important Information -->
                    <div class="col-md-6">
                        <div class="card info-card h-100">
                            <div class="card-body">
                                <h5><i class="fas fa-envelope text-primary me-2"></i>Check Your Email</h5>
                                <p class="text-muted">A confirmation email has been sent to:</p>
                                <p class="fw-bold mb-0"><?php echo e($application['project_lead_email']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Login Credentials -->
                    <div class="col-md-6">
                        <div class="card info-card h-100">
                            <div class="card-body">
                                <h5><i class="fas fa-key text-success me-2"></i>Your Login Details</h5>
                                <p class="text-muted">Once approved, use these credentials:</p>
                                <p class="mb-0"><strong>Username:</strong> <?php echo e($application['profile_name']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Important Notes -->
                <div class="alert alert-warning border-warning">
                    <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Important Notes</h5>
                    <ul class="mb-0">
                        <li><strong>Save this page:</strong> Bookmark this confirmation for your records</li>
                        <li><strong>Check spam folder:</strong> Make sure our emails don't go to spam</li>
                        <li><strong>Response time:</strong> We aim to review applications within 5-7 business days</li>
                        <li><strong>Questions?</strong> Contact us at applications@jhubafrica.com with your application ID</li>
                    </ul>
                </div>

                <!-- Expected Timeline -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Expected Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="p-3">
                                    <i class="fas fa-clock fa-2x text-muted mb-2"></i>
                                    <h6>Today</h6>
                                    <small class="text-muted">Application Received</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="p-3">
                                    <i class="fas fa-search fa-2x text-primary mb-2"></i>
                                    <h6>Days 1-5</h6>
                                    <small class="text-muted">Under Review</small>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="p-3">
                                    <i class="fas fa-envelope fa-2x text-success mb-2"></i>
                                    <h6>Day 5-7</h6>
                                    <small class="text-muted">Decision Email Sent</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3">
                                    <i class="fas fa-rocket fa-2x text-warning mb-2"></i>
                                    <h6>If Approved</h6>
                                    <small class="text-muted">Start Your Journey</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="text-center">
                    <a href="../index.php" class="btn btn-primary btn-lg me-2">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-print me-2"></i>Print Confirmation
                    </button>
                </div>

                <!-- Help Section -->
                <div class="card mt-4 border-0 bg-light">
                    <div class="card-body text-center">
                        <h5><i class="fas fa-question-circle me-2"></i>Need Assistance?</h5>
                        <p class="mb-2">Our support team is here to help!</p>
                        <p class="mb-0">
                            <strong>Email:</strong> applications@jhubafrica.com<br>
                            <strong>Phone:</strong> +254 XXX XXX XXX<br>
                            <small class="text-muted">Monday - Friday, 9AM - 5PM EAT</small>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> JHUB AFRICA. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
