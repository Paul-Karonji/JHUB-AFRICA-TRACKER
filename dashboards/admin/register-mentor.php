<?php
require_once __DIR__ . '/../../includes/init.php';
requireUserType('admin');

$auth = new Auth();
$mentorService = new Mentor();

$message = null;
$error = null;

if (isPostRequest()) {
    $payload = $_POST;
    if (empty($payload['password'])) {
        $payload['password'] = bin2hex(random_bytes(6));
    }
    $result = $mentorService->registerMentor($payload, $auth->getUserId());
    if ($result['success']) {
        $message = 'Mentor registered successfully. Temporary password: ' . e($payload['password']);
    } else {
        $error = $result['message'];
    }
}

$pageTitle = 'Register Mentor';
$extraCss = ['css/dashboard.css'];
include ROOT_DIR . 'partials/header.php';
?>
<div class="card">
    <h2>Register Mentor</h2>
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo e($error); ?></div>
    <?php endif; ?>

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
            <span>Password</span>
            <input type="text" name="password" placeholder="Leave blank to auto-generate">
        </label>
        <label>
            <span>Expertise</span>
            <input type="text" name="expertise" required>
        </label>
        <label>
            <span>Bio</span>
            <textarea name="bio" rows="4" required></textarea>
        </label>
        <label>
            <span>Phone</span>
            <input type="text" name="phone">
        </label>
        <label>
            <span>LinkedIn URL</span>
            <input type="url" name="linkedin_url">
        </label>
        <label>
            <span>Years Experience</span>
            <input type="number" name="years_experience" min="0">
        </label>
        <button class="button" type="submit">Create Mentor</button>
    </form>
</div>
<?php include ROOT_DIR . 'partials/footer.php'; ?>

