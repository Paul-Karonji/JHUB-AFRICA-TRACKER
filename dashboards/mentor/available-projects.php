<?php
require_once __DIR__ . '/../../includes/init.php';
requireUserType('mentor');

$auth = new Auth();
$mentorService = new Mentor();
$available = $mentorService->getAvailableProjects($auth->getUserId());

$pageTitle = 'Available Projects';
$extraCss = ['css/dashboard.css'];
include ROOT_DIR . 'partials/header.php';
?>
<div class="card">
    <h2>Projects Looking for Mentors</h2>
    <?php foreach (($available['projects'] ?? []) as $project): ?>
        <div class="card" style="margin:0 0 1rem 0; box-shadow:none; border:1px solid rgba(15,53,102,0.08);">
            <strong><?php echo e($project['name']); ?></strong>
            <p class="muted"><?php echo truncateText($project['description'] ?? '', 140); ?></p>
            <form method="post" action="../../api/projects/mentors.php">
                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                <button class="button" type="submit">Join</button>
            </form>
        </div>
    <?php endforeach; ?>
    <?php if (empty($available['projects'])): ?>
        <p class="muted">No open projects at the moment. Check back soon!</p>
    <?php endif; ?>
</div>
<?php include ROOT_DIR . 'partials/footer.php'; ?>
