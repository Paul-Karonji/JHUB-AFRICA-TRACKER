<?php
require_once __DIR__ . '/../../includes/init.php';
requireUserType('admin');

$pageTitle = 'System Settings';
$extraCss = ['css/dashboard.css'];
include ROOT_DIR . 'partials/header.php';
?>
<div class="card">
    <h2>System Settings</h2>
    <p class="muted">Coming soon: toggle platform features, manage rate limits, update branding assets, and configure notification preferences.</p>
</div>
<?php include ROOT_DIR . 'partials/footer.php'; ?>
