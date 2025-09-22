<?php
require_once __DIR__ . '/../../includes/init.php';
requireUserType('project');

$auth = new Auth();
$projectId = $auth->getProjectId();
$ratingService = new Rating();

$timeline = $ratingService->getProjectTimeline($projectId);
$pageTitle = 'Progress Timeline';
$extraCss = ['css/dashboard.css'];
include ROOT_DIR . 'partials/header.php';
?>
<div class="card">
    <h2>Project Timeline</h2>
    <ul class="list">
        <?php foreach (($timeline['timeline'] ?? []) as $event): ?>
            <li>
                <strong><?php echo e($event['description'] ?? 'Milestone'); ?></strong>
                <div class="muted">
                    Stage <?php echo e($event['stage']); ?> · <?php echo formatDate($event['date'] ?? ''); ?>
                </div>
                <?php if (!empty($event['mentor_name'])): ?>
                    <div class="muted">Rated by <?php echo e($event['mentor_name']); ?></div>
                <?php endif; ?>
                <?php if (!empty($event['notes'])): ?>
                    <p><?php echo e($event['notes']); ?></p>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php include ROOT_DIR . 'partials/footer.php'; ?>
