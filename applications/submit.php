<?php
// applications/submit.php
// Project Application Submission Form
require_once '../includes/init.php';

$pageTitle = "Apply for JHUB AFRICA Program";
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
    <link href="../assets/css/application.css" rel="stylesheet">
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
                <div class="card shadow-lg">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h1 class="mb-0"><i class="fas fa-rocket me-2"></i>Apply for JHUB AFRICA Program</h1>
                        <p class="mb-0 mt-2">Submit your innovation project for our 6-stage development journey</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Alert Container -->
                        <div id="alertContainer"></div>
                        
                        <form id="applicationForm" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <?php echo Validator::csrfInput(); ?>
                            
                            <!-- Project Information Section -->
                            <div class="form-section mb-4">
                                <h4 class="border-start border-primary border-4 ps-3 mb-3">
                                    <i class="fas fa-project-diagram me-2 text-primary"></i>Project Information
                                </h4>
                                
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label for="projectName" class="form-label">Project Name *</label>
                                        <input type="text" class="form-control" id="projectName" name="project_name" required
                                               placeholder="Enter your project name">
                                        <div class="invalid-feedback">Please provide a project name.</div>
                                    </div>
                                    
                                    <div class="col-md-4 mb-3">
                                        <label for="projectDate" class="form-label">Project Date</label>
                                        <input type="date" class="form-control" id="projectDate" name="date" 
                                               value="<?php echo date('Y-m-d'); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="projectEmail" class="form-label">Project Email</label>
                                        <input type="email" class="form-control" id="projectEmail" name="project_email"
                                               placeholder="project@example.com">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="projectWebsite" class="form-label">Project Website</label>
                                        <input type="url" class="form-control" id="projectWebsite" name="project_website"
                                               placeholder="https://yourproject.com">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Project Description *</label>
                                    <textarea class="form-control" id="description" name="description" rows="5" required
                                              placeholder="Describe your project, its goals, target market, and potential impact..."></textarea>
                                    <div class="form-text">Provide a comprehensive description of your innovation project.</div>
                                    <div class="invalid-feedback">Please provide a project description.</div>
                                </div>
                            </div>
                            
                            <!-- Project Lead Information -->
                            <div class="form-section mb-4">
                                <h4 class="border-start border-primary border-4 ps-3 mb-3">
                                    <i class="fas fa-user-tie me-2 text-primary"></i>Project Lead Information
                                </h4>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="projectLeadName" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="projectLeadName" name="project_lead_name" required
                                               placeholder="Enter your full name">
                                        <div class="invalid-feedback">Please provide the project lead's name.</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="projectLeadEmail" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="projectLeadEmail" name="project_lead_email" required
                                               placeholder="your@email.com">
                                        <div class="invalid-feedback">Please provide a valid email address.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Account Setup -->
                            <div class="form-section mb-4">
                                <h4 class="border-start border-primary border-4 ps-3 mb-3">
                                    <i class="fas fa-key me-2 text-primary"></i>Account Setup
                                </h4>
                                <p class="text-muted">Create your project dashboard login credentials</p>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="profileName" class="form-label">Profile Name *</label>
                                        <input type="text" class="form-control" id="profileName" name="profile_name" required
                                               placeholder="Choose a unique profile name">
                                        <div class="form-text">This will be your login username. Must be unique.</div>
                                        <div class="invalid-feedback">Please choose a profile name.</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Password *</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="password" name="password" required
                                                   minlength="8" placeholder="Create a secure password">
                                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Minimum 8 characters required.</div>
                                        <div class="invalid-feedback">Password must be at least 8 characters long.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Project Presentation Upload -->
                            <div class="form-section mb-4">
                                <h4 class="border-start border-primary border-4 ps-3 mb-3">
                                    <i class="fas fa-upload me-2 text-primary"></i>Project Presentation (Optional)
                                </h4>
                                <p class="text-muted">Upload your project presentation (PDF, PPT, or DOC format - Max 10MB)</p>
                                
                                <input type="file" class="form-control" id="presentationFile" name="presentation_file" 
                                       accept=".pdf,.doc,.docx,.ppt,.pptx">
                                <div class="form-text">Supported formats: PDF, DOC, DOCX, PPT, PPTX (Max 10MB)</div>
                            </div>
                            
                            <!-- Terms and Submit -->
                            <div class="form-section mb-4">
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                                    <label class="form-check-label" for="agreeTerms">
                                        I agree that the information provided is accurate and I accept the program terms *
                                    </label>
                                    <div class="invalid-feedback">You must agree to continue.</div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Application
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="text-muted">
                                <small>
                                    <i class="fas fa-info-circle me-1"></i>
                                    Applications are reviewed within 5-7 business days. You will receive an email confirmation.
                                </small>
                            </p>
                            <a href="../index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/application.js"></script>
</body>
</html>