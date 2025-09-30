<?php
// dashboards/admin/register-mentor.php - Register New Mentor
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_ADMIN);

$adminId = $auth->getUserId();
$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Validate required fields
        $validator = new Validator($_POST);
        $validator->required('name', 'Mentor name is required')
                 ->required('email', 'Email is required')
                 ->email('email')
                 ->required('password', 'Password is required')
                 ->min('password', 8, 'Password must be at least 8 characters')
                 ->required('bio', 'Bio is required')
                 ->required('area_of_expertise', 'Area of expertise is required');

        if (!$validator->isValid()) {
            $errors = array_merge($errors, $validator->getErrors());
        } else {
            // Check if email already exists
            $existingMentor = $database->getRow(
                "SELECT email FROM mentors WHERE email = ?",
                [$_POST['email']]
            );

            if ($existingMentor) {
                $errors['email'][] = 'A mentor with this email already exists';
            } else {
                // Prepare mentor data
                $mentorData = [
                    'name' => trim($_POST['name']),
                    'email' => strtolower(trim($_POST['email'])),
                    'password' => Auth::hashPassword($_POST['password']),
                    'bio' => trim($_POST['bio']),
                    'area_of_expertise' => trim($_POST['area_of_expertise']),
                    'phone' => !empty($_POST['phone']) ? trim($_POST['phone']) : null,
                    'linkedin_url' => !empty($_POST['linkedin_url']) ? trim($_POST['linkedin_url']) : null,
                    'years_experience' => !empty($_POST['years_experience']) ? intval($_POST['years_experience']) : null,
                    'created_by' => $adminId,
                    'is_active' => 1
                ];

                // Insert mentor
                $mentorId = $database->insert('mentors', $mentorData);

                if ($mentorId) {
                    // Log activity
                    logActivity(
                        'admin',
                        $adminId,
                        'mentor_registered',
                        "Registered new mentor: {$mentorData['name']}",
                        null,
                        ['mentor_id' => $mentorId, 'mentor_email' => $mentorData['email']]
                    );

                    // Send welcome email (will be queued)
                    sendEmailNotification(
                        $mentorData['email'],
                        'Welcome to JHUB AFRICA - Mentor Account',
                        "Dear {$mentorData['name']},\n\nWelcome to JHUB AFRICA! Your mentor account has been created.\n\nLogin Credentials:\nEmail: {$mentorData['email']}\nPassword: {$_POST['password']}\n\nYou can login at: " . SITE_URL . "/auth/mentor-login.php\n\nBest regards,\nJHUB AFRICA Team",
                        'mentor_welcome'
                    );

                    $success = "Mentor registered successfully! Login credentials have been sent to {$mentorData['email']}";
                    
                    // Clear form
                    $_POST = [];
                } else {
                    $errors[] = 'Failed to register mentor. Please try again.';
                }
            }
        }
    }
}

$pageTitle = "Register New Mentor";
include '../../templates/header.php';
?>

<div class="register-mentor">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Register New Mentor</h1>
            <p class="text-muted">Add a new mentor to the system</p>
        </div>
        <div>
            <a href="mentors.php" class="btn btn-secondary">
                <i class="fas fa-users me-1"></i> View All Mentors
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?php echo e($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors) && !isset($errors['name'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user-plus me-2"></i>Mentor Registration Form
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="mentorForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">

                        <!-- Personal Information -->
                        <h5 class="mb-3">Personal Information</h5>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                                       id="name" name="name" value="<?php echo e($_POST['name'] ?? ''); ?>" required>
                                <?php if (isset($errors['name'])): ?>
                                    <div class="invalid-feedback"><?php echo e($errors['name'][0]); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                       id="email" name="email" value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?php echo e($errors['email'][0]); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                       id="password" name="password" minlength="8" required>
                                <small class="form-text text-muted">Minimum 8 characters</small>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback"><?php echo e($errors['password'][0]); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo e($_POST['phone'] ?? ''); ?>">
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Professional Information -->
                        <h5 class="mb-3">Professional Information</h5>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="area_of_expertise" class="form-label">Area of Expertise <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['area_of_expertise']) ? 'is-invalid' : ''; ?>" 
                                       id="area_of_expertise" name="area_of_expertise" 
                                       value="<?php echo e($_POST['area_of_expertise'] ?? ''); ?>" 
                                       placeholder="e.g., Software Development, Business Strategy" required>
                                <?php if (isset($errors['area_of_expertise'])): ?>
                                    <div class="invalid-feedback"><?php echo e($errors['area_of_expertise'][0]); ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="years_experience" class="form-label">Years of Experience</label>
                                <input type="number" class="form-control" id="years_experience" name="years_experience" 
                                       value="<?php echo e($_POST['years_experience'] ?? ''); ?>" min="0" max="50">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="linkedin_url" class="form-label">LinkedIn Profile URL</label>
                            <input type="url" class="form-control" id="linkedin_url" name="linkedin_url" 
                                   value="<?php echo e($_POST['linkedin_url'] ?? ''); ?>" 
                                   placeholder="https://www.linkedin.com/in/yourprofile">
                        </div>

                        <div class="mb-3">
                            <label for="bio" class="form-label">Biography <span class="text-danger">*</span></label>
                            <textarea class="form-control <?php echo isset($errors['bio']) ? 'is-invalid' : ''; ?>" 
                                      id="bio" name="bio" rows="5" required 
                                      placeholder="Brief professional background, achievements, and what you can offer as a mentor..."><?php echo e($_POST['bio'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">
                                Provide a detailed biography highlighting your experience and expertise
                            </small>
                            <?php if (isset($errors['bio'])): ?>
                                <div class="invalid-feedback"><?php echo e($errors['bio'][0]); ?></div>
                            <?php endif; ?>
                        </div>

                        <hr class="my-4">

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Important:</strong> The mentor will receive an email with their login credentials. 
                            Make sure to verify the email address is correct before submitting.
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="mentors.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-1"></i> Register Mentor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
document.getElementById('mentorForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const email = document.getElementById('email').value;

    // Validate password strength
    if (password.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long');
        return false;
    }

    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        e.preventDefault();
        alert('Please enter a valid email address');
        return false;
    }

    // Confirm submission
    if (!confirm('Are you sure you want to register this mentor? They will receive an email with their login credentials.')) {
        e.preventDefault();
        return false;
    }

    return true;
});

// Show/hide password
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordInput.setAttribute('type', type);
}
</script>

<?php include '../../templates/footer.php'; ?>