<?php
require_once __DIR__ . '/../../includes/init.php';
requireUserType('admin');

$auth = new Auth();
$mentorService = new Mentor();

$filters = [
    'page' => $_GET['page'] ?? 1,
    'per_page' => $_GET['per_page'] ?? AppConfig::MENTORS_PER_PAGE,
    'search' => $_GET['search'] ?? null,
    'active_only' => ($_GET['active_only'] ?? '0') === '1'
];

$result = $mentorService->getMentors($filters);
$mentors = $result['mentors'] ?? [];
$pagination = $result['pagination'] ?? [];

$pageTitle = 'Mentor Directory';
$extraCss = ['css/dashboard.css'];
include ROOT_DIR . 'partials/header.php';
?>
<div class="card">
    <h2>Mentors</h2>
    <form class="filter-form" method="get">
        <input type="text" name="search" placeholder="Search mentors" value="<?php echo e($filters['search']); ?>">
        <label>
            <input type="checkbox" name="active_only" value="1" <?php echo $filters['active_only'] ? 'checked' : ''; ?>> Active only
        </label>
        <button class="button" type="submit">Filter</button>
        <a class="button-secondary button" href="register-mentor.php">Register Mentor</a>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Expertise</th>
                <th>Projects</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mentors as $mentor): ?>
                <tr>
                    <td><?php echo e($mentor['name']); ?></td>
                    <td><?php echo e($mentor['email']); ?></td>
                    <td><?php echo e($mentor['expertise']); ?></td>
                    <td><?php echo (int) ($mentor['project_assignments'] ?? 0); ?></td>
                    <td><?php echo renderBadge($mentor['is_active'] ? 'Active' : 'Inactive', $mentor['is_active'] ? 'badge-success' : 'badge-secondary'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
        <div class="pagination">
            <?php for ($page = 1; $page <= $pagination['total_pages']; $page++): ?>
                <a class="<?php echo $page == $pagination['page'] ? 'active' : ''; ?>" href="?<?php echo http_build_query(array_merge($filters, ['page' => $page])); ?>">
                    <?php echo $page; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>
<?php include ROOT_DIR . 'partials/footer.php'; ?>
