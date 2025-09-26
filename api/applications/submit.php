<?php
// api/applications/submit.php
// Handle project application submissions

header('Content-Type: application/json');
require_once '../../includes/init.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Validate CSRF token
    if (!Validator::validateCSRF()) {
        throw new Exception('Invalid security token. Please refresh and try again.');
    }

    // Validate required fields
    $validator = new Validator($_POST);
    $validator->required('project_name', 'Project name is required')
             ->required('description', 'Project description is required')
             ->required('project_lead_name', 'Project lead name is required')
             ->required('project_lead_email', 'Project lead email is required')
             ->email('project_lead_email')
             ->required('profile_name', 'Profile name is required')
             ->required('password', 'Password is required')
             ->min('password', 8);

    // Validate optional email fields
    if (!empty($_POST['project_email'])) {
        $validator->email('project_email');
    }
    
    if (!empty($_POST['project_website'])) {
        $validator->url('project_website');
    }

    if (!$validator->isValid()) {
        throw new Exception('Please fill in all required fields correctly.');
    }

    // Check if profile name already exists
    $existingProfile = $database->getRow(
        "SELECT profile_name FROM project_applications WHERE profile_name = ? 
         UNION 
         SELECT profile_name FROM projects WHERE profile_name = ?",
        [$_POST['profile_name'], $_POST['profile_name']]
    );

    if ($existingProfile) {
        throw new Exception('Profile name already exists. Please choose a different one.');
    }

    // Handle file upload
    $presentationFile = null;
    if (isset($_FILES['presentation_file']) && $_FILES['presentation_file']['error'] === UPLOAD_ERR_OK) {
        $fileValidator = new Validator([]);
        $fileValidator->file('presentation_file', ['pdf', 'doc', 'docx', 'ppt', 'pptx'], MAX_UPLOAD_SIZE);
        
        if (!$fileValidator->isValid()) {
            throw new Exception('Invalid file. Please upload PDF, DOC, or PPT files under 10MB.');
        }

        // Generate unique filename
        $fileExtension = strtolower(pathinfo($_FILES['presentation_file']['name'], PATHINFO_EXTENSION));
        $presentationFile = uniqid('presentation_', true) . '.' . $fileExtension;
        $uploadPath = UPLOAD_PATH . 'presentations/' . $presentationFile;

        // Move uploaded file
        if (!move_uploaded_file($_FILES['presentation_file']['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to upload presentation file. Please try again.');
        }
    }

    // Prepare data for insertion
    $applicationData = [
        'project_name' => trim($_POST['project_name']),
        'date' => !empty($_POST['date']) ? $_POST['date'] : date('Y-m-d'),
        'project_email' => !empty($_POST['project_email']) ? trim($_POST['project_email']) : null,
        'project_website' => !empty($_POST['project_website']) ? trim($_POST['project_website']) : null,
        'description' => trim($_POST['description']),
        'project_lead_name' => trim($_POST['project_lead_name']),
        'project_lead_email' => trim($_POST['project_lead_email']),
        'presentation_file' => $presentationFile,
        'profile_name' => trim($_POST['profile_name']),
        'password' => Auth::hashPassword($_POST['password']),
        'status' => APPLICATION_STATUS_PENDING,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
    ];

    // Insert application
    $applicationId = $database->insert('project_applications', $applicationData);

    if (!$applicationId) {
        // Clean up uploaded file if database insert fails
        if ($presentationFile && file_exists($uploadPath)) {
            unlink($uploadPath);
        }
        throw new Exception('Failed to submit application. Please try again.');
    }

    // Log the application
    logActivity(
        'system',
        null,
        'application_submitted',
        "New application submitted: {$applicationData['project_name']}",
        null,
        ['application_id' => $applicationId, 'profile_name' => $applicationData['profile_name']]
    );

    // Send confirmation email (queue for later sending)
    sendEmailNotification(
        $applicationData['project_lead_email'],
        'Application Received - JHUB AFRICA',
        "Dear {$applicationData['project_lead_name']},\n\nThank you for submitting your project application for {$applicationData['project_name']}. We have received your application and will review it within 5-7 business days.\n\nYou will receive an email notification once your application has been reviewed.\n\nBest regards,\nJHUB AFRICA Team",
        'application_confirmation'
    );

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully! You will receive an email confirmation shortly.',
        'application_id' => $applicationId,
        'redirect' => '/applications/confirmation.php?id=' . $applicationId
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    // Log error
    error_log('Application submission error: ' . $e->getMessage());
}