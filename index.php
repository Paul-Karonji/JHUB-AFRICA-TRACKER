<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Main Entry Point / Landing Page
 * 
 * This is the public landing page that showcases JHUB AFRICA's mission
 * and displays the project gallery for public viewing.
 * 
 * @author JHUB AFRICA Development Team
 * @version 1.0
 * @since 2024
 */

// Initialize the application
require_once __DIR__ . '/includes/init.php';

// Check if initialization was successful
if (!defined('JHUB_INITIALIZED')) {
    die('Application initialization failed');
}

try {
    // Initialize required classes
    $project = new Project();
    
    // Get public projects for display
    $projectsResult = $project->getAllProjects();
    $projects = $projectsResult['success'] ? $projectsResult['projects'] : [];
    
    // Get statistics for the hero section
    $stats = [
        'total_projects' => count($projects),
        'active_projects' => count(array_filter($projects, function($p) { return $p['status'] === 'active'; })),
        'completed_projects' => count(array_filter($projects, function($p) { return $p['status'] === 'completed'; })),
        'mentors' => 0, // We'll get this from database
        'innovators' => 0 // We'll get this from database
    ];
    
    // Get mentor and innovator counts
    $db = Database::getInstance();
    $stats['mentors'] = $db->fetchColumn("SELECT COUNT(*) FROM mentors");
    $stats['innovators'] = $db->fetchColumn("SELECT COUNT(DISTINCT email) FROM project_innovators");
    
} catch (Exception $e) {
    logActivity('ERROR', 'Landing page error: ' . $e->getMessage());
    $projects = [];
    $stats = ['total_projects' => 0, 'active_projects' => 0, 'completed_projects' => 0, 'mentors' => 0, 'innovators' => 0];
}

// Set page title and meta
$pageTitle = 'JHUB AFRICA - Innovation Ecosystem';
$pageDescription = 'Empowering African innovators through structured support, expert mentorship, and community collaboration.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="keywords" content="innovation, africa, startup, mentorship, entrepreneurship, technology">
    <meta name="author" content="JHUB AFRICA">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo AppConfig::getUrl(); ?>">
    <meta property="og:image" content="<?php echo AppConfig::getAsset('images/og-image.png'); ?>">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="twitter:image" content="<?php echo AppConfig::getAsset('images/og-image.png'); ?>">
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo AppConfig::getAsset('images/favicon.ico'); ?>" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo AppConfig::getAsset('images/favicon.ico'); ?>" type="image/x-icon">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo AppConfig::getAsset('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo AppConfig::getAsset('css/public.css'); ?>">
    
    <!-- CSRF Token for forms -->
    <meta name="csrf-token" content="<?php echo AppConfig::generateCSRFToken(); ?>">
    
    <style>
        /* Inline critical CSS for faster loading */
        body { 
            margin: 0; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            line-height: 1.6; 
            color: #333;
            background: linear-gradient(135deg, #e3f2fd 0%, #f8faff 100%);
        }
        
        .header {
            background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 20px rgba(21, 101, 192, 0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .nav-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logo-placeholder {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 50vh;
            font-size: 1.2rem;
            color: #1565c0;
        }
        
        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 1rem;
            border-radius: 8px;
            margin: 2rem 0;
            border-left: 4px solid #f44336;
        }
    </style>
</head>
<body>
    <!-- Header Navigation -->
    <div class="header">
        <div class="nav-container">
            <div class="logo-section">
                <div class="logo-placeholder">JHUB</div>
                <div