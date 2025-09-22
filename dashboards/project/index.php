<?php
require_once __DIR__ . '/../../includes/init.php';
requireUserType('project');

$auth = new Auth();
$projectId = $auth->getProjectId();
$projectService = new Project();
$ratingService = new Rating();
$commentService = new Comment();
$notificationService = new Notification();

$pageTitle = 'Project Dashboard';
$extraCss = ['css/dashboard.css'];

$project = $projectService->getProject($projectId, true, true);
$timeline = $ratingService->getProjectTimeline($projectId);
$comments = $commentService->getProjectComments($projectId, true, 'newest_first', 5);
$notifications = $notificationService->getNotifications('project', $projectId, ['limit' => 5]);

include ROOT_DIR . 'partials/header.php';
?>
<?php if (!($project['success'] ?? false)): ?>
    <div class="card">
        <p class="alert alert-danger">Unable to load project data.</p>
    </div>
<?php else: ?>
    <?php $data = $project['project']; ?>
    <div class="grid grid-3">
        <div class="card metric">
            <span class="label">Stage</span>
            <span class="value"><?php echo formatStageName($data['current_stage']); ?></span>
        </div>
        <div class="card metric">
            <span class="label">Progress</span>
            <span class="value"><?php echo (int) ($data['current_percentage'] ?? 0); ?>%</span>
        </div>
        <div class="card metric">
            <span class="label">Mentors</span>
            <span class="value"><?php echo count($data['mentors'] ?? []); ?></span>
        </div>
    </div>

    <div class="card">
        <h2>Project Overview</h2>
        <p><?php echo nl2br(e($data['description'] ?? '')); ?></p>
        <p>Status: <?php echo renderBadge(ucfirst($data['status']), statusBadgeClass($data['status'])); ?></p>
        <p>Stage: <?php echo renderBadge(formatStageName($data['current_stage']), stageBadgeClass($data['current_stage'])); ?></p>
        <?php echo renderProgressBar($data['current_percentage'] ?? 0); ?>
    </div>

    <div class="grid grid-3">
        <div class="card">
            <h3>Team Members</h3>
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

        <div class="card">
            <h3>Notifications</h3>
            <?php foreach (($notifications['notifications'] ?? []) as $notification): ?>
                <div class="notification<?php echo $notification['is_read'] ? ' notification--read' : ''; ?>">
                    <strong><?php echo e($notification['title']); ?></strong>
                    <p><?php echo e($notification['message']); ?></p>
                    <small class="muted"><?php echo formatRelativeTime($notification['created_at']); ?></small>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card">
        <h3>Recent Activity</h3>
        <ul class="list">
            <?php foreach (($timeline['timeline'] ?? []) as $event): ?>
                <li>
                    <strong><?php echo e($event['description'] ?? 'Milestone'); ?></strong>
                    <div class="muted"><?php echo formatDate($event['date'] ?? ''); ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="card">
        <h3>Latest Comments</h3>
        <?php foreach (($comments['comments'] ?? []) as $comment): ?>
            <div class="comment">
                <strong><?php echo e($comment['commenter_name']); ?></strong>
                <p><?php echo e($comment['comment_text']); ?></p>
                <small class="muted"><?php echo formatRelativeTime($comment['created_at']); ?></small>
            </div>
        <?php endforeach; ?>
        <?php if (empty($comments['comments'])): ?>
            <p class="muted">No comments yet.</p>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php include ROOT_DIR . 'partials/footer.php'; ?>
