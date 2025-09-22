<?php
require_once __DIR__ . '/../includes/init.php';

$projectService = new Project();
$filters = [
    'status' => 'active',
    'search' => $_GET['search'] ?? null,
    'page' => $_GET['page'] ?? 1,
    'per_page' => $_GET['per_page'] ?? AppConfig::PROJECTS_PER_PAGE
];

$result = $projectService->getAllProjects($filters, (int) $filters['page'], (int) $filters['per_page']);
$projects = $result['projects'] ?? [];
$pagination = $result['pagination'] ?? [];

$pageTitle = 'Explore Projects';
include ROOT_DIR . 'partials/header.php';
?>
<div class="card">
    <h1>Projects</h1>
    <p>Discover innovation journeys underway within the JHUB AFRICA ecosystem.</p>
    <form class="filter-form" method="get">
        <input type="text" name="search" placeholder="Search by name or description" value="<?php echo e($filters['search']); ?>">
        <button class="button" type="submit">Search</button>
    </form>

    <div class="grid grid-3">
        <?php foreach ($projects as $project): ?>
            <div class="card">
                <h3><?php echo e($project['name']); ?></h3>
                <p><?php echo truncateText($project['description'] ?? '', 120); ?></p>
                <p><?php echo renderBadge(formatStageName($project['current_stage']), stageBadgeClass($project['current_stage'])); ?></p>
                <a class="button" href="project-details.php?project_id=<?php echo $project['id']; ?>">View Details</a>
            </div>
        <?php endforeach; ?>
        <?php if (empty($projects)): ?>
            <p class="muted">No projects found.</p>
        <?php endif; ?>
    </div>

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
