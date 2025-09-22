<?php
require_once __DIR__ . '/../../includes/init.php';
requireUserType('project');

$auth = new Auth();
$projectId = $auth->getProjectId();
$commentService = new Comment();

$message = null;
$error = null;

if (isPostRequest()) {
    $payload = $_POST;
    try {
        requireKeys($payload, ['comment_text']);
        $result = $commentService->addComment(
            $projectId,
            'project',
            $auth->getUserId(),
            $payload['comment_text'],
            $payload['parent_id'] ?? null,
            $_SESSION['project_name'] ?? 'Project Team'
        );
        if ($result['success']) {
            $message = 'Comment posted successfully.';
        } else {
            $error = $result['message'];
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$thread = $commentService->getProjectComments($projectId, true);
$pageTitle = 'Project Discussions';
$extraCss = ['css/dashboard.css'];
include ROOT_DIR . 'partials/header.php';
?>
<div class="card">
    <h2>Discussion</h2>
    <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

    <form method="post">
        <textarea name="comment_text" rows="4" placeholder="Share an update" required></textarea>
        <button class="button" type="submit">Post Comment</button>
    </form>

    <div class="comments">
        <?php foreach (($thread['comments'] ?? []) as $comment): ?>
            <div class="comment">
                <strong><?php echo e($comment['commenter_name']); ?></strong>
                <small class="muted"><?php echo formatRelativeTime($comment['created_at']); ?></small>
                <p><?php echo e($comment['comment_text']); ?></p>
            </div>
            <?php foreach (($comment['replies'] ?? []) as $reply): ?>
                <div class="comment" style="margin-left:1.5rem;">
                    <strong><?php echo e($reply['commenter_name']); ?></strong>
                    <small class="muted"><?php echo formatRelativeTime($reply['created_at']); ?></small>
                    <p><?php echo e($reply['comment_text']); ?></p>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php include ROOT_DIR . 'partials/footer.php'; ?>
