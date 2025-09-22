<?php
require_once __DIR__ . '/../../includes/init.php';
requireUserType('mentor');

$pageTitle = 'Mentor Profile';
$extraCss = ['css/dashboard.css'];
include ROOT_DIR . 'partials/header.php';
?>
<div class="card">
    <h2>Your Profile</h2>
    <p><strong>Name:</strong> <?php echo e($_SESSION['name'] ?? 'Mentor'); ?></p>
    <p><strong>Email:</strong> <?php echo e($_SESSION['email'] ?? ''); ?></p>
    <p><strong>Expertise:</strong> <?php echo e($_SESSION['expertise'] ?? ''); ?></p>
    <p><strong>Bio:</strong></p>
    <p><?php echo nl2br(e($_SESSION['bio'] ?? 'Update your biography.')); ?></p>
</div>
<?php include ROOT_DIR . 'partials/footer.php'; ?>
