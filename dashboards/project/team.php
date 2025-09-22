<?php
require_once __DIR__ . '/../../includes/init.php';
requireUserType('project');

$auth = new Auth();
$projectId = $auth->getProjectId();
$projectService = new Project();

$message = null;
$error = null;

if (isPostRequest()) {
    try {
        $payload = $_POST;
        requireKeys($payload, ['name', 'email', 'role']);
        $payload['project_id'] = $projectId;
        $result = $projectService->addInnovator($projectId, $payload, $auth->getUserId());
        if ($result['success']) {
            $message = 'Team member added successfully.';
        } else {
            $error = $result['message'];
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE' || (isset($_GET['remove']) && $_GET['remove'])) {
    $innovatorId = $_GET['innovator_id'] ?? null;
    if ($innovatorId) {
        $result = $projectService->removeInnovator($projectId, $innovatorId, $auth->getUserId());
        if ($result['success']) {
            $message = 'Team member removed.';
        } else {
            $error = $result['message'];
        }
    }
}

$team = $projectService->getProjectTeam($projectId);
$pageTitle = 'Team Management';
$extraCss = ['css/dashboard.css'];
include ROOT_DIR . 'partials/header.php';
?>
<div class="card">
    <h2>Team Members</h2>
    <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>

    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Experience</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($team['team'] ?? []) as $member): ?>
                <tr>
                    <td><?php echo e($member['name']); ?></td>
                    <td><?php echo e($member['email']); ?></td>
                    <td><?php echo e($member['role']); ?></td>
                    <td><?php echo e($member['experience_level'] ?? ''); ?></td>
                    <td>
                        <a class="button-secondary button" style="background:#f44336;" href="?remove=1&innovator_id=<?php echo $member['id']; ?>">Remove</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="card">
    <h3>Add Team Member</h3>
    <form method="post" class="form-grid">
        <label>
            <span>Name</span>
            <input type="text" name="name" required>
        </label>
        <label>
            <span>Email</span>
            <input type="email" name="email" required>
        </label>
        <label>
            <span>Role</span>
            <input type="text" name="role" required>
        </label>
        <label>
            <span>Experience Level</span>
            <input type="text" name="experience_level">
        </label>
        <button class="button" type="submit">Add Member</button>
    </form>
</div>
<?php include ROOT_DIR . 'partials/footer.php'; ?>

