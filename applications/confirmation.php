<?php
/**
 * Application Confirmation Page
 * Location: applications/confirmation.php
 */
require_once '../includes/init.php';

// Get application ID from URL
$applicationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($applicationId === 0) {
    header('Location: ' . BASE_PATH . '/applications/submit.php');
    exit;
}

// Get application details
$application = $database->getRow(
    "SELECT * FROM project_applications WHERE application_id = ?",
    [$applicationId]
);

if (!$application) {
    header('Location: ' . BASE_PATH . '/applications/submit.php');
    exit;
}

$pageTitle = 'Application Submitted Successfully';
include '../templates/header.php';
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    
                    <h2 class="mb-3">Application Submitted Successfully!</h2>
                    
                    <div class="alert alert-success">
                        <strong>Application ID: <?php echo htmlspecialchars($application['application_id']); ?></strong>
                    </div>
                    
                    <p class="lead mb-4">
                        Thank you for submitting your project "<strong><?php echo htmlspecialchars($application['project_name']); ?></strong>" to JHUB AFRICA.
                    </p>
                    
                    <div class="card bg-light mb-4">
                        <div class="card-body text-start">
                            <h5 class="card-title">What happens next?</h5>
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <i class="fas fa-envelope text-primary me-2"></i>
                                    You will receive a confirmation email at <strong><?php echo htmlspecialchars($application['project_lead_email']); ?></strong>
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-user-check text-primary me-2"></i>
                                    Our admin team will review your application
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-clock text-primary me-2"></i>
                                    Review typically takes 3-5 business days
                                </li>
                                <li>
                                    <i class="fas fa-bell text-primary me-2"></i>
                                    You'll be notified via email about the decision
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <p class="mb-2">
                            <strong>Save your Application ID for tracking:</strong>
                        </p>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control text-center" 
                                   id="applicationIdInput" 
                                   value="<?php echo htmlspecialchars($application['application_id']); ?>" 
                                   readonly>
                            <button class="btn btn-outline-primary" type="button" 
                                    onclick="copyApplicationId()">
                                <i class="fas fa-copy me-1"></i>Copy
                            </button>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <a href="<?php echo BASE_PATH; ?>/applications/status.php" 
                           class="btn btn-primary btn-lg">
                            <i class="fas fa-search me-2"></i>Check Application Status
                        </a>
                        <a href="<?php echo BASE_PATH; ?>/" 
                           class="btn btn-outline-secondary">
                            <i class="fas fa-home me-2"></i>Return to Home
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Additional Information -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-info-circle me-2"></i>Important Information
                    </h5>
                    <p class="card-text">
                        <strong>Profile Name:</strong> <?php echo htmlspecialchars($application['profile_name']); ?><br>
                        <small class="text-muted">You'll use this to login once your application is approved</small>
                    </p>
                    <hr>
                    <p class="card-text mb-0">
                        <strong>Need Help?</strong><br>
                        Contact us at: <a href="mailto:support@jhubafrica.com">support@jhubafrica.com</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyApplicationId() {
    const input = document.getElementById('applicationIdInput');
    input.select();
    input.setSelectionRange(0, 99999); // For mobile devices
    
    navigator.clipboard.writeText(input.value).then(function() {
        // Show success feedback
        const btn = event.target.closest('button');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-1"></i>Copied!';
        btn.classList.remove('btn-outline-primary');
        btn.classList.add('btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-primary');
        }, 2000);
    });
}
</script>

<?php include '../templates/footer.php'; ?>