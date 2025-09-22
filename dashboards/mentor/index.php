<?php
require_once __DIR__ . '/../../includes/init.php';
requireUserType('mentor');

$auth = new Auth();
$mentorId = $auth->getUserId();
$mentorService = new Mentor();
$projectService = new Project();
$ratingService = new Rating();
$notificationService = new Notification();

$pageTitle = 'Mentor Dashboard';
$extraCss = ['css/dashboard.css'];

$assigned = $mentorService->getMentorProjects($mentorId);
$available = $mentorService->getAvailableProjects($mentorId);
$ratings = $ratingService->getRatingsByMentor($mentorId, 5);
$notifications = $notificationService->getNotifications('mentor', $mentorId, ['limit' => 5]);

include ROOT_DIR . 'partials/header.php';
?>
<div class="grid grid-3">
    <div class="card metric">
        <span class="label">Assigned Projects</span>
        <span class="value"><?php echo count($assigned['projects'] ?? []); ?></span>
    </div>
    <div class="card metric">
        <span class="label">Open Opportunities</span>
        <span class="value"><?php echo count($available['projects'] ?? []); ?></span>
    </div>
    <div class="card metric">
        <span class="label">Recent Ratings</span>
        <span class="value"><?php echo count($ratings['ratings'] ?? []); ?></span>
    </div>
</div>

<div class="card">
    <h2>Your Projects</h2>
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
            <?php foreach (($assigned['projects'] ?? []) as $project): ?>
                <tr>
                    <td><?php echo e($project['name']); ?></td>
                    <td><?php echo renderBadge(ucfirst($project['status']), statusBadgeClass($project['status'])); ?></td>
                    <td><?php echo renderBadge(formatStageName($project['current_stage']), stageBadgeClass($project['current_stage'])); ?></td>
                    <td><?php echo formatRelativeTime($project['assigned_at']); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($assigned['projects'])): ?>
                <tr><td colspan="4" class="muted">No project assignments yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="grid grid-3">
    <div class="card">
        <h3>Available Projects</h3>
        <?php foreach (($available['projects'] ?? []) as $project): ?>
            <div class="card" style="margin:0 0 1rem 0; box-shadow:none; border:1px solid rgba(15,53,102,0.08);">
                <strong><?php echo e($project['name']); ?></strong>
                <p class="muted"><?php echo truncateText($project['description'] ?? '', 80); ?></p>
                <form method="post" action="../../api/projects/mentors.php">
                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                    <button class="button" type="submit">Join Project</button>
                </form>
            </div>
        <?php endforeach; ?>
        <?php if (empty($available['projects'])): ?>
            <p class="muted">All caught up! No open projects right now.</p>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Recent Ratings</h3>
        <?php foreach (($ratings['ratings'] ?? []) as $rating): ?>
            <div class="metric">
                <span class="label"><?php echo e($rating['project_name'] ?? 'Project #' . $rating['project_id']); ?></span>
                <span class="value"><?php echo formatStageProgress($rating['stage'], $rating['percentage']); ?>%</span>
                <small class="muted"><?php echo formatRelativeTime($rating['rated_at']); ?></small>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <h3>Notifications</h3>
        <?php foreach (($notifications['notifications'] ?? []) as $notification): ?>
            <div class="notification<?php echo $notification['is_read'] ? ' notification--read' : ''; ?>">
                <strong><?php echo e($notification['title']); ?></strong>
                <p><?php echo e($notification['message']); ?></p>
                <small class="muted"><?php echo formatRelativeTime($notification['created_at']); ?></small>
            </div>
        <?php endforeach; ?>
        <?php if (empty($notifications['notifications'])): ?>
            <p class="muted">No notifications yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include ROOT_DIR . 'partials/footer.php'; ?>
