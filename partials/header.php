<?php
if (!defined('JHUB_ACCESS')) {
    die('Direct access not permitted');
}

$pageTitle = $pageTitle ?? AppConfig::APP_NAME;
$pageDescription = $pageDescription ?? AppConfig::APP_DESCRIPTION;
$extraCss = $extraCss ?? [];
$bodyClass = $bodyClass ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <meta name="description" content="<?php echo e($pageDescription); ?>">
    <link rel="icon" href="<?php echo AppConfig::getAsset('images/favicon.ico'); ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo AppConfig::getAsset('css/main.css'); ?>">
    <?php foreach ($extraCss as $css): ?>
        <link rel="stylesheet" href="<?php echo AppConfig::getAsset($css); ?>">
    <?php endforeach; ?>
</head>
<body class="<?php echo e($bodyClass); ?>">
    <header class="app-header">
        <div class="container">
            <div class="branding">
                <a href="<?php echo AppConfig::BASE_URL; ?>" class="brand">JHUB AFRICA</a>
                <span class="tagline">Innovation Project Tracker</span>
            </div>
            <nav class="main-nav">
                <a href="<?php echo AppConfig::BASE_URL; ?>">Home</a>
                <a href="<?php echo AppConfig::BASE_URL; ?>public/projects.php">Projects</a>
                <a href="<?php echo AppConfig::BASE_URL; ?>public/create-project.php">Start a Project</a>
                <a href="<?php echo AppConfig::getLoginUrl(); ?>">Login</a>
            </nav>
        </div>
    </header>
    <main class="app-main">
        <div class="container">
