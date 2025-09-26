<?php
// api/applications/index.php
// Get application details

header('Content-Type: application/json');
require_once '../../includes/init.php';

// Require admin authentication for this endpoint
$auth->requireUserType(USER_TYPE_ADMIN);

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get application ID
    if (!isset($_GET['id'])) {
        throw new Exception('Application ID is required');
    }

    $applicationId = intval($_GET['id']);

    // Get application details
    $application = $database->getRow(
        "SELECT a.*, 
                ad.username as reviewed_by_username
         FROM project_applications a
         LEFT JOIN admins ad ON a.reviewed_by = ad.admin_id
         WHERE a.application_id = ?",
        [$applicationId]
    );

    if (!$application) {
        throw new Exception('Application not found');
    }

    // Format dates for display
    $application['applied_at_formatted'] = formatDate($application['applied_at']);
    $application['reviewed_at_formatted'] = $application['reviewed_at'] ? formatDate($application['reviewed_at']) : null;

    // Check if presentation file exists
    if ($application['presentation_file']) {
        $application['presentation_file_path'] = SITE_URL . '/assets/uploads/presentations/' . $application['presentation_file'];
        $application['has_presentation'] = file_exists(UPLOAD_PATH . 'presentations/' . $application['presentation_file']);
    } else {
        $application['has_presentation'] = false;
    }

    echo json_encode([
        'success' => true,
        'data' => $application
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    error_log('Application details error: ' . $e->getMessage());
}