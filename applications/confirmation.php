<?php
// applications/confirmation.php
// Application Submission Confirmation Page
require_once '../includes/init.php';

// Get application ID
$applicationId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$applicationId) {
    redirect('/applications/submit.php');
}

// Get application details
$application = $database->getRow(
    "SELECT * FROM project_applications WHERE application_id = ?",
    [$applicationId]
);

if (!$application) {
    redirect('/applications/submit.php');
}

$pageTitle = "Application Submitted Successfully";
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
    <style>
        .success-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .success-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            margin: 2rem;
        }
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: -50px auto 2rem;
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check fa-3x text-white"></i>
            </div>
            
            <div class="p-4 text-center">
                <h1 class="h2 mb-3">Application Submitted Successfully!</h1>
                <p class="lead text-muted mb-4">
                    Thank you for applying to JHUB AFRICA
                </p>

                <div class="alert alert-info text-start">
                    <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>What Happens Next?</h5>
                    <hr>
                    <ol class="mb-0">
                        <li class="mb-2">Our team will review your application within <strong>5-7 business days</strong></li>
                        <li class="mb-2">You will receive an email at <strong><?php echo e($application['project_lead_email']); ?></strong> with the review outcome</li>
                        <li class="mb-2">If approved, you'll receive your login credentials to access your project dashboard</li>
                        <li>You can check your application status anytime using the link below</li>
                    </ol>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Application Details</h5>
                        <table class="table table-sm">
                            <tr>
                                <th width="40%">Application ID:</th>
                                <td><strong>#<?php echo $application['application_id']; ?></strong></td>
                            </tr>
                            <tr>
                                <th>Project Name:</th>
                                <td><?php echo e($application['project_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Submitted Date:</th>
                                <td><?php echo formatDate($application['applied_at']); ?></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td><span class="badge bg-warning">Pending Review</span></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="status.php?id=<?php echo $application['application_id']; ?>" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Check Application Status
                    </a>
                    <a href="../index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                </div>

                <div class="mt-4">
                    <small class="text-muted">
                        <i class="fas fa-envelope me-1"></i>
                        Questions? Contact us at support@jhubafrica.com
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>