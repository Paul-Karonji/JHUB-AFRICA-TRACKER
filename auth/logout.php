<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Logout Handler
 * 
 * This file handles user logout and session cleanup for all user types.
 * Supports both GET requests (direct access) and POST requests (AJAX).
 * 
 * @author JHUB AFRICA Development Team
 * @version 1.0
 * @since 2024
 */

// Initialize the application
require_once __DIR__ . '/../includes/init.php';

// Initialize authentication
$auth = new Auth();

try {
    // Handle AJAX logout requests
    if (isAjaxRequest()) {
        // Verify CSRF token for POST requests
        if (isPostRequest()) {
            $input = json_decode(file_get_contents('php://input'), true);
            $csrfToken = $input['csrf_token'] ?? $_POST['csrf_token'] ?? '';
            
            if (!$auth->verifyCSRFToken($csrfToken)) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid security token'
                ]);
                exit;
            }
        }
        
        // Perform logout
        $result = $auth->logout();
        
        // Return JSON response
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    // Handle regular logout (GET request)
    $userInfo = $auth->getUserInfo();
    $result = $auth->logout();
    
    if ($result['success']) {
        // Set success message for display
        session_start();
        $_SESSION['logout_message'] = 'You have been logged out successfully.';
        
        // Redirect to login page with success message
        redirect(AppConfig::getUrl('auth/login.php?logged_out=1'));
    } else {
        // Redirect to home page if logout failed
        redirect(AppConfig::getUrl());
    }
    
} catch (Exception $e) {
    logActivity('ERROR', "Logout error: " . $e->getMessage());
    
    if (isAjaxRequest()) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Logout failed. Please try again.'
        ]);
        exit;
    }
    
    // For regular requests, just redirect to home
    redirect(AppConfig::getUrl());
}
?>