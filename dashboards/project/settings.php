<?php
require_once __DIR__ . '/../../includes/init.php';
requireUserType('project');

$pageTitle = 'Project Settings';
$extraCss = ['css/dashboard.css'];
include ROOT_DIR . 'partials/header.php';
?>
<div class="card">
    <h2>Project Account</h2>
    <p class="muted">Settings management coming soon. Update your profile, change login credentials, and manage public visibility from here.</p>
</div>
<?php include ROOT_DIR . 'partials/footer.php'; ?>
