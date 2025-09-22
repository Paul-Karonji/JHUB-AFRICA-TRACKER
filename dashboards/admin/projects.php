<?php
require_once __DIR__ . '/../../includes/init.php';
requireUserType('admin');

$auth = new Auth();
$projectService = new Project();

$filters = [
    'status' => $_GET['status'] ?? null,
    'stage' => $_GET['stage'] ?? null,
    'search' => $_GET['search'] ?? null,
    'page' => $_GET['page'] ?? 1,
    'per_page' => $_GET['per_page'] ?? AppConfig::PROJECTS_PER_PAGE
];

$result = $projectService->getAllProjects($filters, (int) $filters['page'], (int) $filters['per_page']);
$projects = $result['projects'] ?? [];
$pagination = $result['pagination'] ?? [];

$pageTitle = 'Manage Projects';
$extraCss = ['css/dashboard.css'];
include ROOT_DIR . 'partials/header.php';
?>
<div class="card">
    <h2>Projects</h2>
    <form class="filter-form" method="get">
        <input type="text" name="search" placeholder="Search projects" value="<?php echo e($filters['search']); ?>">
        <select name="status">
            <option value="">All Status</option>
            <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
            <option value="completed" <?php echo $filters['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
            <option value="terminated" <?php echo $filters['status'] === 'terminated' ? 'selected' : ''; ?>>Terminated</option>
        </select>
        <select name="stage">
            <option value="">All Stages</option>
            <?php foreach (DatabaseConfig::getAllStages() as $stageNumber => $stageInfo): ?>
                <option value="<?php echo $stageNumber; ?>" <?php echo (string) $filters['stage'] === (string) $stageNumber ? 'selected' : ''; ?>>
                    <?php echo e($stageInfo['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="button" type="submit">Filter</button>
    </form>

    <table class="table">
        <thead>
            <tr>
                <th>Project</th>
                <th>Status</th>
                <th>Stage</th>
                <th>Mentors</th>
                <th>Innovators</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td>
                        <strong><?php echo e($project['name']); ?></strong>
                        <div class="muted"><?php echo truncateText($project['description'] ?? '', 70); ?></div>
                    </td>
                    <td><?php echo renderBadge(ucfirst($project['status']), statusBadgeClass($project['status'])); ?></td>
                    <td><?php echo renderBadge(formatStageName($project['current_stage']), stageBadgeClass($project['current_stage'])); ?></td>
                    <td><?php echo (int) ($project['mentor_count'] ?? 0); ?></td>
                    <td><?php echo (int) ($project['innovator_count'] ?? 0); ?></td>
                    <td><?php echo formatDate($project['created_at']); ?></td>
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
