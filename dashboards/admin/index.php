<?php
require_once __DIR__ . '/../../includes/init.php';
requireUserType('admin');

$auth = new Auth();
$projectService = new Project();
$mentorService = new Mentor();
$commentService = new Comment();
$ratingService = new Rating();
$notificationService = new Notification();

$pageTitle = 'Admin Dashboard';
$extraCss = ['css/dashboard.css'];

$stats = $projectService->getSystemStats();
$commentStats = $commentService->getSystemCommentStats();
$ratingStats = $ratingService->getSystemRatingStats();
$notifications = $notificationService->getNotifications('admin', $auth->getUserId() ?? null, ['limit' => 5]);

$recentProjects = $projectService->getAllProjects([], 1, 5);
$mentors = $mentorService->getMentors(['per_page' => 5]);

include ROOT_DIR . 'partials/header.php';
?>
<div class="grid grid-3">
    <div class="card metric">
        <span class="label">Total Projects</span>
        <span class="value"><?php echo (int) ($stats['total_projects'] ?? 0); ?></span>
        <span class="label">Active · <?php echo (int) ($stats['active_projects'] ?? 0); ?> &nbsp;|&nbsp; Completed · <?php echo (int) ($stats['completed_projects'] ?? 0); ?></span>
    </div>
    <div class="card metric">
        <span class="label">Innovators</span>
        <span class="value"><?php echo (int) ($stats['total_innovators'] ?? 0); ?></span>
        <span class="label">Comments (7d): <?php echo (int) ($commentStats['recent_comments'] ?? 0); ?></span>
    </div>
    <div class="card metric">
        <span class="label">Mentors</span>
        <span class="value"><?php echo (int) ($stats['total_mentors'] ?? 0); ?></span>
        <span class="label">Avg Rating · <?php echo number_format($ratingStats['average_percentage'] ?? 0, 1); ?>%</span>
    </div>
</div>

<div class="card">
    <h2>Recent Projects</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Project</th>
                <th>Status</th>
                <th>Stage</th>
                <th>Updated</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($recentProjects['projects'] ?? []) as $project): ?>
                <tr>
                    <td>
                        <strong><?php echo e($project['name']); ?></strong>
                        <div class="muted"><?php echo truncateText($project['description'] ?? '', 90); ?></div>
                    </td>
                    <td><?php echo renderBadge(ucfirst($project['status']), statusBadgeClass($project['status'])); ?></td>
                    <td><?php echo renderBadge(formatStageName($project['current_stage']), stageBadgeClass($project['current_stage'])); ?></td>
                    <td><?php echo formatRelativeTime($project['updated_at'] ?? $project['created_at']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="grid grid-3">
    <div class="card">
        <h3>Notifications</h3>
        <?php foreach (($notifications['notifications'] ?? []) as $notification): ?>
            <div class="notification<?php echo $notification['is_read'] ? ' notification--read' : ''; ?>">
                <strong><?php echo e($notification['title']); ?></strong>
                <p><?php echo e($notification['message']); ?></p>
                <small><?php echo formatRelativeTime($notification['created_at']); ?></small>
            </div>
        <?php endforeach; ?>
        <?php if (empty($notifications['notifications'])): ?>
            <p class="muted">No notifications yet.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Comments By Role</h3>
        <?php foreach (($commentStats['comments_by_type'] ?? []) as $type => $total): ?>
            <div class="metric">
                <span class="label"><?php echo ucfirst($type); ?></span>
                <span class="value"><?php echo (int) $total; ?></span>
            </div>
        <?php endforeach; ?>
        <?php if (empty($commentStats['comments_by_type'])): ?>
            <p class="muted">No comments recorded.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Mentor Snapshot</h3>
        <ul class="list">
            <?php foreach (($mentors['mentors'] ?? []) as $mentor): ?>
                <li>
                    <strong><?php echo e($mentor['name']); ?></strong>
                    <div class="muted"><?php echo e($mentor['expertise']); ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php include ROOT_DIR . 'partials/footer.php'; ?>

