<?php
// dashboards/admin/register-mentor.php
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_ADMIN);

$adminId = $auth->getUserId();
$errors = [];
$success = '';

// Handle mentor registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid security token';
    } else {
        // Validate inputs
        $validator = new Validator($_POST);
        $validator->required('name', 'Name is required')
                 ->required('email', 'Email is required')
                 ->email('email')
                 ->required('password', 'Password is required')
                 ->min('password', 8)
                 ->required('bio', 'Bio is required')
                 ->required('area_of_expertise', 'Area of expertise is required');
        
        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
        } else {
            // Check if email already exists
            $existingMentor = $database->getRow(
                "SELECT mentor_id FROM mentors WHERE email = ?",
                [trim($_POST['email'])]
            );
            
            if ($existingMentor) {
                $errors['email'] = ['This email is already registered'];
            } else {
                // Create mentor account
                $mentorData = [
                    'name' => trim($_POST['name']),
                    'email' => trim($_POST['email']),
                    'password' => Auth::hashPassword($_POST['password']),
                    'bio' => trim($_POST['bio']),
                    'area_of_expertise' => trim($_POST['area_of_expertise']),
                    'phone' => trim($_POST['phone'] ?? ''),
                    'linkedin_url' => trim($_POST['linkedin_url'] ?? ''),
                    'years_experience' => intval($_POST['years_experience'] ?? 0),
                    'created_by' => $adminId,
                    'is_active' => 1
                ];
                
                $mentorId = $database->insert('mentors', $mentorData);
                
                if ($mentorId) {
                    // Log activity
                    logActivity('admin', $adminId, 'mentor_created', "Created mentor account: {$mentorData['name']}", null, ['mentor_id' => $mentorId]);
                    
                    // Send welcome email
                    sendEmailNotification(
                        $mentorData['email'],
                        'Welcome to JHUB AFRICA - Mentor Account Created',
                        "Dear {$mentorData['name']},\n\nYour mentor account has been created!\n\nLogin Details:\nEmail: {$mentorData['email']}\nPassword: {$_POST['password']}\n\nLogin at: " . SITE_URL . "/auth/mentor-login.php\n\nBest regards,\nJHUB AFRICA Team",
                        'mentor_welcome'
                    );
                    
                    $success = "Mentor account created successfully! Login credentials have been sent to {$mentorData['email']}";
                    $_POST = []; // Clear form
                } else {
                    $errors[] = 'Failed to create mentor account. Please try again.';
                }
            }
        }
    }
}

$pageTitle = "Register New Mentor";
include '../../templates/header.php';
?>

<div class="admin-dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Register New Mentor</h1>
            <p class="text-muted">Create a new mentor account</p>
        </div>
        <a href="mentors.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Mentors
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo e($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Please fix the following errors:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $field => $fieldErrors): ?>
                    <?php if (is_array($fieldErrors)): ?>
                        <?php foreach ($fieldErrors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><?php echo e($fieldErrors); ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Mentor Registration Form</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <?php echo Validator::csrfInput(); ?>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> The mentor will receive an email with their login credentials after registration.
                        </div>

                        <h6 class="border-bottom pb-2 mb-3">Basic Information</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" 
                                       value="<?php echo e($_POST['name'] ?? ''); ?>" required>
                                <?php if (isset($errors['name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['name'][0]; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                       value="<?php echo e($_POST['email'] ?? ''); ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['email'][0]; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                       required minlength="8">
                                <small class="form-text text-muted">Minimum 8 characters</small>
                                <?php if (isset($errors['password'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['password'][0]; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" 
                                       value="<?php echo e($_POST['phone'] ?? ''); ?>"
                                       placeholder="+254 700 000 000">
                            </div>
                        </div>

                        <h6 class="border-bottom pb-2 mb-3 mt-4">Professional Information</h6>

                        <div class="mb-3">
                            <label class="form-label">Area of Expertise <span class="text-danger">*</span></label>
                            <input type="text" name="area_of_expertise" class="form-control <?php echo isset($errors['area_of_expertise']) ? 'is-invalid' : ''; ?>" 
                                   value="<?php echo e($_POST['area_of_expertise'] ?? ''); ?>" required
                                   placeholder="e.g., Software Development, Marketing Strategy, Financial Management">
                            <?php if (isset($errors['area_of_expertise'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['area_of_expertise'][0]; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Bio/Background <span class="text-danger">*</span></label>
                            <textarea name="bio" class="form-control <?php echo isset($errors['bio']) ? 'is-invalid' : ''; ?>" 
                                      rows="4" required 
                                      placeholder="Tell us about their experience, expertise, and what they can offer to projects..."><?php echo e($_POST['bio'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">This will be visible to projects when the mentor joins them</small>
                            <?php if (isset($errors['bio'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['bio'][0]; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Years of Experience</label>
                                <input type="number" name="years_experience" class="form-control" 
                                       value="<?php echo e($_POST['years_experience'] ?? ''); ?>" 
                                       min="0" max="50"
                                       placeholder="e.g., 10">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">LinkedIn Profile URL</label>
                                <input type="url" name="linkedin_url" class="form-control" 
                                       value="<?php echo e($_POST['linkedin_url'] ?? ''); ?>"
                                       placeholder="https://linkedin.com/in/username">
                            </div>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Security Note:</strong> Make sure to share the password securely with the mentor. The password will be sent via email after registration.
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Create Mentor Account
                            </button>
                            <a href="mentors.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Additional Help Card -->
            <div class="card shadow mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Mentor Capabilities</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">Once registered, mentors can:</p>
                    <ul class="mb-0">
                        <li>Browse and join active projects</li>
                        <li>Share resources with their projects</li>
                        <li>Create assessment checklists</li>
                        <li>Set learning objectives</li>
                        <li>Update project stages</li>
                        <li>Provide guidance and feedback</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../templates/footer.php'; ?>
