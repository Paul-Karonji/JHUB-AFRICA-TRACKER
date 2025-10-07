<?php
// applications/submit.php
// Public Application Submission Form
require_once '../includes/init.php';

$pageTitle = "Apply to JHUB AFRICA";
$hideNav = true; // Don't show logged-in navigation
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .application-hero {
            background: linear-gradient(135deg, #3b54c7 0%, #0e015b 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }
        .form-step {
            display: none;
        }
        .form-step.active {
            display: block;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 20px 10px;
            border-bottom: 3px solid #e0e0e0;
            position: relative;
        }
        .step.active {
            border-bottom-color: #2c409a;
            color: #2c409a;
            font-weight: 600;
        }
        .step.completed {
            border-bottom-color: #3fa845;
            color: #3fa845;
        }
        .step::before {
            content: attr(data-step);
            display: block;
            width: 40px;
            height: 40px;
            line-height: 40px;
            margin: 0 auto 10px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #666;
        }
        .step.active::before {
            background: #2c409a;
            color: white;
        }
        .step.completed::before {
            background: #3fa845;
            color: white;
            content: "\2713";
        }
        .file-upload-area {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-upload-area:hover {
            border-color: #2c409a;
            background: #f8f9ff;
        }
        .file-upload-area.dragover {
            border-color: #2c409a;
            background: #f0f4ff;
        }
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="application-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4 fw-bold mb-3">Apply to JHUB AFRICA</h1>
                    <p class="lead mb-0">Join Africa's premier innovation acceleration program. Submit your project for review and gain access to mentorship, resources, and a supportive ecosystem.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <a href="../index.php" class="btn btn-light btn-lg">
                            <i class="fas fa-home me-2"></i> Home
                        </a>
                        <a href="../public/projects.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-eye me-2"></i> View Projects
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Application Form -->
    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step active" data-step="1">
                        <span class="d-none d-md-inline">Project Details</span>
                        <span class="d-md-none">Project</span>
                    </div>
                    <div class="step" data-step="2">
                        <span class="d-none d-md-inline">Project Lead</span>
                        <span class="d-md-none">Lead</span>
                    </div>
                    <div class="step" data-step="3">
                        <span class="d-none d-md-inline">Documentation</span>
                        <span class="d-md-none">Docs</span>
                    </div>
                    <div class="step" data-step="4">
                        <span class="d-none d-md-inline">Account Setup</span>
                        <span class="d-md-none">Account</span>
                    </div>
                </div>

                <!-- Form Card -->
                <div class="card shadow-lg border-0">
                    <div class="card-body p-4 p-md-5">
                        <!-- Alert Area -->
                        <div id="alertArea"></div>

                        <!-- Application Form -->
                        <form id="applicationForm" enctype="multipart/form-data">
                            <?php echo Validator::csrfInput(); ?>

                            <!-- Step 1: Project Details -->
                            <div class="form-step active" data-step="1">
                                <h3 class="mb-4">Tell Us About Your Project</h3>
                                
                                <div class="mb-4">
                                    <label for="project_name" class="form-label required-field">Project Name</label>
                                    <input type="text" class="form-control form-control-lg" id="project_name" 
                                           name="project_name" required maxlength="255"
                                           placeholder="What's your innovation called?">
                                    <div class="invalid-feedback">Please provide a project name.</div>
                                </div>

                                <div class="mb-4">
                                    <label for="description" class="form-label required-field">Project Description</label>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="6" required minlength="100"
                                              placeholder="Describe your innovation, the problem it solves, and its potential impact. (Minimum 100 characters)"></textarea>
                                    <div class="form-text">
                                        <span id="charCount">0</span> / 100 minimum characters
                                    </div>
                                    <div class="invalid-feedback">Please provide at least 100 characters.</div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <label for="project_email" class="form-label">Project Email</label>
                                        <input type="email" class="form-control" id="project_email" 
                                               name="project_email"
                                               placeholder="project@example.com (optional)">
                                        <div class="form-text">Official email for your project</div>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <label for="project_website" class="form-label">Project Website</label>
                                        <input type="url" class="form-control" id="project_website" 
                                               name="project_website"
                                               placeholder="https://yourproject.com (optional)">
                                        <div class="form-text">If you have a website or landing page</div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="date" class="form-label">Project Start Date</label>
                                    <input type="date" class="form-control" id="date" name="date" 
                                           value="<?php echo date('Y-m-d'); ?>">
                                    <div class="form-text">When did you start working on this project?</div>
                                </div>
                            </div>

                            <!-- Step 2: Project Lead -->
                            <div class="form-step" data-step="2">
                                <h3 class="mb-4">Project Lead Information</h3>
                                <p class="text-muted mb-4">Tell us about the person leading this innovation.</p>

                                <div class="mb-4">
                                    <label for="project_lead_name" class="form-label required-field">Full Name</label>
                                    <input type="text" class="form-control form-control-lg" 
                                           id="project_lead_name" name="project_lead_name" required
                                           placeholder="Your full name">
                                    <div class="invalid-feedback">Please provide your full name.</div>
                                </div>

                                <div class="mb-4">
                                    <label for="project_lead_email" class="form-label required-field">Email Address</label>
                                    <input type="email" class="form-control form-control-lg" 
                                           id="project_lead_email" name="project_lead_email" required
                                           placeholder="your.email@example.com">
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        This email will receive all notifications about your application
                                    </div>
                                    <div class="invalid-feedback">Please provide a valid email address.</div>
                                </div>

                                <div class="alert alert-info">
                                    <i class="fas fa-lightbulb me-2"></i>
                                    <strong>Tip:</strong> Make sure this email is actively monitored. You'll receive important updates about your application status here.
                                </div>
                            </div>

                            <!-- Step 3: Documentation -->
                            <div class="form-step" data-step="3">
                                <h3 class="mb-4">Project Documentation</h3>
                                <p class="text-muted mb-4">Upload a presentation or document that explains your project in detail.</p>

                                <div class="mb-4">
                                    <label class="form-label required-field">Project Presentation</label>
                                    <div class="file-upload-area" id="fileUploadArea">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                        <h5>Drag & Drop your file here</h5>
                                        <p class="text-muted mb-3">or click to browse</p>
                                        <p class="small text-muted mb-0">
                                            Accepted formats: PDF, DOC, DOCX, PPT, PPTX<br>
                                            Maximum file size: 10MB
                                        </p>
                                        <input type="file" id="presentation_file" name="presentation_file" 
                                               class="d-none" accept=".pdf,.doc,.docx,.ppt,.pptx" required>
                                    </div>
                                    <div id="fileInfo" class="mt-3"></div>
                                    <div class="invalid-feedback d-block" id="fileError"></div>
                                </div>

                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Important:</strong> Your presentation should include:
                                    <ul class="mb-0 mt-2">
                                        <li>Problem statement and solution</li>
                                        <li>Target market and business model</li>
                                        <li>Current progress and milestones</li>
                                        <li>Team members and their roles</li>
                                        <li>Future plans and funding needs</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Step 4: Account Setup - FIXED VERSION -->
<div class="form-step" data-step="4">
    <h3 class="mb-4">Create Your Account</h3>
    <p class="text-muted mb-4">Set up your login credentials to access the project dashboard once approved.</p>

    <div class="mb-4">
        <label for="profile_name" class="form-label required-field">Profile Name (Username)</label>
        <!-- ✅ FIXED: Removed the 'v' flag from pattern regex -->
        <input type="text" class="form-control form-control-lg" 
               id="profile_name" name="profile_name" required
               pattern="[a-zA-Z0-9_-]+" minlength="4" maxlength="50"
               autocomplete="username"
               placeholder="Choose a unique username">
        <div class="form-text">
            Only letters, numbers, hyphens, and underscores. 4-50 characters.
        </div>
        <div class="invalid-feedback">
            Profile name must be 4-50 characters and contain only letters, numbers, hyphens, or underscores.
        </div>
    </div>

    <div class="mb-4">
        <label for="password" class="form-label required-field">Password</label>
        <div class="input-group">
            <input type="password" class="form-control form-control-lg" 
                   id="password" name="password" required minlength="8"
                   autocomplete="new-password"
                   placeholder="Create a strong password">
            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                <i class="fas fa-eye"></i>
            </button>
        </div>
        <div class="form-text">Minimum 8 characters</div>
        <div class="invalid-feedback">Password must be at least 8 characters long.</div>
    </div>

    <div class="mb-4">
        <label for="password_confirm" class="form-label required-field">Confirm Password</label>
        <input type="password" class="form-control form-control-lg" 
               id="password_confirm" name="password_confirm" required
               autocomplete="new-password"
               placeholder="Re-enter your password">
        <div class="invalid-feedback">Passwords do not match.</div>
    </div>

    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" id="terms" required>
        <label class="form-check-label" for="terms">
            I agree to the <a href="#" target="_blank">Terms and Conditions</a> and 
            <a href="#" target="_blank">Privacy Policy</a>
        </label>
        <div class="invalid-feedback">You must agree to the terms and conditions.</div>
    </div>
</div>

                            <!-- Navigation Buttons -->
                            <div class="d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-outline-secondary btn-lg" id="prevBtn" style="display: none;">
                                    <i class="fas fa-arrow-left me-2"></i> Previous
                                </button>
                                <div></div>
                                <button type="button" class="btn btn-primary btn-lg" id="nextBtn">
                                    Next <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                                <button type="submit" class="btn btn-success btn-lg" id="submitBtn" style="display: none;">
                                    <i class="fas fa-paper-plane me-2"></i> Submit Application
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="card mt-4 border-0 bg-light">
                    <div class="card-body text-center">
                        <h5><i class="fas fa-question-circle me-2"></i>Need Help?</h5>
                        <p class="mb-2">Contact us at: <strong>applications@jhubafrica.com</strong></p>
                        <p class="text-muted small mb-0">We typically respond within 24 hours</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let currentStep = 1;
        const totalSteps = 4;
        const form = document.getElementById('applicationForm');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');

        // Character counter
        const description = document.getElementById('description');
        const charCount = document.getElementById('charCount');
        description.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });

        // Password toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const type = password.type === 'password' ? 'text' : 'password';
            password.type = type;
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });

        // File upload handling
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('presentation_file');
        const fileInfo = document.getElementById('fileInfo');

        fileUploadArea.addEventListener('click', () => fileInput.click());

        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });

        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('dragover');
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                displayFileInfo(e.dataTransfer.files[0]);
            }
        });

        fileInput.addEventListener('change', function() {
            if (this.files.length) {
                displayFileInfo(this.files[0]);
            }
        });

        function displayFileInfo(file) {
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedTypes = ['application/pdf', 'application/msword', 
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation'];

            if (!allowedTypes.includes(file.type)) {
                fileInfo.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>Invalid file type. Please upload PDF, DOC, or PPT files.</div>';
                fileInput.value = '';
                return;
            }

            if (file.size > maxSize) {
                fileInfo.innerHTML = '<div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i>File too large. Maximum size is 10MB.</div>';
                fileInput.value = '';
                return;
            }

            const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
            fileInfo.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-file-alt me-2"></i>
                    <strong>${file.name}</strong> (${sizeInMB} MB)
                    <button type="button" class="btn-close float-end" onclick="document.getElementById('presentation_file').value=''; document.getElementById('fileInfo').innerHTML='';"></button>
                </div>
            `;
        }

        // Step navigation
        function showStep(step) {
            document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
            document.querySelector(`.form-step[data-step="${step}"]`).classList.add('active');

            document.querySelectorAll('.step').forEach((el, idx) => {
                el.classList.remove('active', 'completed');
                if (idx + 1 < step) el.classList.add('completed');
                if (idx + 1 === step) el.classList.add('active');
            });

            prevBtn.style.display = step === 1 ? 'none' : 'block';
            nextBtn.style.display = step === totalSteps ? 'none' : 'block';
            submitBtn.style.display = step === totalSteps ? 'block' : 'none';

            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function validateStep(step) {
            const stepDiv = document.querySelector(`.form-step[data-step="${step}"]`);
            const inputs = stepDiv.querySelectorAll('input[required], textarea[required]');
            let valid = true;

            inputs.forEach(input => {
                if (!input.checkValidity()) {
                    input.classList.add('is-invalid');
                    valid = false;
                } else {
                    input.classList.remove('is-invalid');
                }
            });

            // Special validation for password confirmation
            if (step === 4) {
                const password = document.getElementById('password');
                const passwordConfirm = document.getElementById('password_confirm');
                if (password.value !== passwordConfirm.value) {
                    passwordConfirm.classList.add('is-invalid');
                    valid = false;
                } else {
                    passwordConfirm.classList.remove('is-invalid');
                }
            }

            return valid;
        }

        nextBtn.addEventListener('click', function() {
            if (validateStep(currentStep)) {
                currentStep++;
                showStep(currentStep);
            }
        });

        prevBtn.addEventListener('click', function() {
            currentStep--;
            showStep(currentStep);
        });

        // Form submission with enhanced debugging
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (!validateStep(currentStep)) {
                return;
            }

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Submitting...';

            const formData = new FormData(form);

            console.log('=== FORM SUBMISSION DEBUG ===');
            console.log('Submitting to:', '../api/applications/submit.php');

            try {
                const response = await fetch('../api/applications/submit.php', {
                    method: 'POST',
                    body: formData
                });

                console.log('Response Status:', response.status);
                console.log('Response OK:', response.ok);

                const text = await response.text();
                console.log('Raw Response:', text);

                let data;
                try {
                    data = JSON.parse(text);
                    console.log('Parsed Data:', data);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    throw new Error('Invalid server response. Please contact support.');
                }

                if (data.success) {
                    showAlert('success', data.message);
                    setTimeout(() => {
                        window.location.href = 'confirmation.php?id=' + data.application_id;
                    }, 1000);
                } else {
                    showAlert('danger', data.message);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Submit Application';
                }
            } catch (error) {
                console.error('Submission Error:', error);
                showAlert('danger', 'Error: ' + error.message + ' Check the console for details.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane me-2"></i> Submit Application';
            }
        });

        function showAlert(type, message) {
            const alertArea = document.getElementById('alertArea');
            alertArea.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
    </script>
</body>
</html>