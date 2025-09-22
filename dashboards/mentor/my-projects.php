<?php
require_once __DIR__ . '/../../includes/init.php';
requireUserType('mentor');

$auth = new Auth();
$mentorService = new Mentor();

$projects = $mentorService->getMentorProjects($auth->getUserId());
$pageTitle = 'My Projects';
$extraCss = ['css/dashboard.css'];
include ROOT_DIR . 'partials/header.php';
?>
<div class="card">
    <h2>Assigned Projects</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Project</th>
                <th>Status</th>
                <th>Stage</th>
                <th>Joined</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($projects['projects'] ?? []) as $project): ?>
                <tr>
                    <td><?php echo e($project['name']); ?></td>
                    <td><?php echo renderBadge(ucfirst($project['status']), statusBadgeClass($project['status'])); ?></td>
                    <td><?php echo renderBadge(formatStageName($project['current_stage']), stageBadgeClass($project['current_stage'])); ?></td>
                    <td><?php echo formatDate($project['assigned_at']); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($projects['projects'])): ?>
                <tr><td colspan="4" class="muted">No projects yet. Visit Available Projects to join one.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include ROOT_DIR . 'partials/footer.php'; ?>
