<?php
require_once __DIR__ . '/../includes/init.php';

$projectId = $_GET['project_id'] ?? null;
if (!$projectId) {
    redirect('projects.php');
}

$projectService = new Project();
$project = $projectService->getProject($projectId, true, true);

$pageTitle = 'Project Details';
include ROOT_DIR . 'partials/header.php';
?>
<?php if (!($project['success'] ?? false)): ?>
    <div class="card">
        <p class="alert alert-danger">Project not found.</p>
    </div>
<?php else: ?>
    <?php $data = $project['project']; ?>
    <div class="card">
        <h1><?php echo e($data['name']); ?></h1>
        <p><?php echo nl2br(e($data['description'] ?? '')); ?></p>
        <p>Status: <?php echo renderBadge(ucfirst($data['status']), statusBadgeClass($data['status'])); ?></p>
        <p>Stage: <?php echo renderBadge(formatStageName($data['current_stage']), stageBadgeClass($data['current_stage'])); ?></p>
        <?php echo renderProgressBar($data['current_percentage'] ?? 0); ?>
    </div>

    <div class="card">
        <h3>Team</h3>
        <ul class="list">
            <?php foreach (($data['team'] ?? []) as $member): ?>
                <li><?php echo e($member['name']); ?> · <?php echo e($member['role']); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="card">
        <h3>Mentors</h3>
        <ul class="list">
            <?php foreach (($data['mentors'] ?? []) as $mentor): ?>
                <li><?php echo e($mentor['name']); ?> · <?php echo e($mentor['expertise']); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<?php include ROOT_DIR . 'partials/footer.php'; ?>
