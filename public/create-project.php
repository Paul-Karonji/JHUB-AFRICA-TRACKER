<?php
require_once __DIR__ . '/../includes/init.php';

$projectService = new Project();
$message = null;
$error = null;
$credentials = null;

if (isPostRequest()) {
    $payload = $_POST;
    try {
        requireKeys($payload, ['name', 'description', 'date', 'profile_name', 'password']);
        $result = $projectService->createProject($payload);
        if ($result['success']) {
            $credentials = $result['login_credentials'] ?? null;
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$pageTitle = 'Launch Your Project';
include ROOT_DIR . 'partials/header.php';
?>
<div class="card">
    <h1>Create a Project</h1>
    <p>Kickstart your innovation journey on the JHUB AFRICA tracker.</p>
    <?php if ($message): ?><div class="alert alert-success"><?php echo e($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo e($error); ?></div><?php endif; ?>
    <?php if ($credentials): ?>
        <div class="alert alert-info">
            <strong>Login Credentials</strong>
            <p>Profile Name: <?php echo e($credentials['profile_name']); ?></p>
            <p>Login URL: <a href="<?php echo e($credentials['login_url']); ?>"><?php echo e($credentials['login_url']); ?></a></p>
        </div>
    <?php endif; ?>

    <form method="post" class="form-grid">
        <label>
            <span>Project Name</span>
            <input type="text" name="name" required>
        </label>
        <label>
            <span>Launch Date</span>
            <input type="date" name="date" required>
        </label>
        <label>
            <span>Project Email</span>
            <input type="email" name="email">
        </label>
        <label>
            <span>Website</span>
            <input type="url" name="website">
        </label>
        <label>
            <span>Description</span>
            <textarea name="description" rows="4" required></textarea>
        </label>
        <label>
            <span>Profile Name</span>
            <input type="text" name="profile_name" required>
        </label>
        <label>
            <span>Password</span>
            <input type="password" name="password" required>
        </label>
        <button class="button" type="submit">Create Project</button>
    </form>
</div>
<?php include ROOT_DIR . 'partials/footer.php'; ?>
