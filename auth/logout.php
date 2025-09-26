<?php
// auth/logout.php
// Logout Handler
require_once '../includes/init.php';

if ($auth->isValidSession()) {
    $userType = $auth->getUserType();
    $userId = $auth->getUserId();
    
    // Log the logout
    logActivity($userType, $userId, 'logout', ucfirst($userType) . ' logout');
    
    // Destroy session
    $auth->logout();
    
    setFlashMessage('You have been successfully logged out.', 'success');
}

// Redirect to home page
redirect('/index.php');
?>