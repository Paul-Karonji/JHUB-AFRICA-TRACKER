<?php
// public/projects.php
// Public Projects Showcase - View all active projects
require_once '../includes/init.php';

// Get filter parameters
$stage = isset($_GET['stage']) ? intval($_GET['stage']) : 0;
$status = isset($_GET['status']) ? $_GET['status'] : 'active';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = PROJECTS_PER_PAGE;
$offset = ($page - 1) * $perPage;

// Build query
$where = ["p.status = 'active'"]; // Only show active projects by default
$params = [];

if ($stage > 0 && $stage <= 6) {
    $where[] = "p.current_stage = ?";
    $params[] = $stage;
}

if ($search) {
    $where[] = "(p.project_name LIKE ? OR p.description LIKE ? OR p.target_market LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereClause = implode(' AND ', $where);

// Get total count
$totalProjects = $database->count('projects p', $whereClause, $params);
$totalPages = ceil($totalProjects / $perPage);

// Get projects
$projects = $database->getRows("
    SELECT p.*,
           (SELECT COUNT(*) FROM project_innovators WHERE project_id = p.project_id AND is_active = 1) as team_count,
           (SELECT COUNT(*) FROM project_mentors WHERE project_id = p.project_id AND is_active = 1) as mentor_count,
           (SELECT COUNT(*) FROM comments WHERE project_id = p.project_id) as comment_count
    FROM projects p
    WHERE {$whereClause}
    ORDER BY p.updated_at DESC
    LIMIT {$perPage} OFFSET {$offset}
", $params);

// Get statistics
$stats = [
    'total' => $database->count('projects', "status = 'active'"),
    'stage_1' => $database->count('projects', "status = 'active' AND current_stage = 1"),
    'stage_6' => $database->count('projects', "status = 'active' AND current_stage = 6"),
    'with_mentors' => $database->getRow("
        SELECT COUNT(DISTINCT p.project_id) as count
        FROM projects p
        INNER JOIN project_mentors pm ON p.project_id = pm.project_id
        WHERE p.status = 'active' AND pm.is_active = 1
    ")['count']
];

$pageTitle = "Innovation Showcase";

$customStyles = <<<CSS
    <style>
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: #ffffff;
            padding: 120px 0 80px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px 20px;
            text-align: center;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 26px 60px rgba(44, 64, 154, 0.18);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-color);
        }
        .project-card {
            border: none;
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
        }
        .project-card:hover {
            box-shadow: 0 24px 55px rgba(44, 64, 154, 0.18);
            transform: translateY(-8px);
        }
        .project-card .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: #ffffff;
            padding: 22px;
            border: none;
        }
        .project-stage {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.9);
            padding: 6px 18px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        .project-meta {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: var(--primary-dark, #253683);
            flex-wrap: wrap;
        }
        .filter-chip {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            border: 2px solid #e9ecef;
            text-decoration: none;
            color: #495057;
            transition: all 0.3s ease;
            margin: 5px;
            font-weight: 600;
            background: #ffffff;
        }
        .filter-chip:hover,
        .filter-chip.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: #ffffff;
            box-shadow: 0 10px 30px rgba(44, 64, 154, 0.25);
        }
        .search-box {
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
CSS;

require_once '../templates/public-header.php';

?>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <h1 class="display-4 fw-bold mb-3">Innovation Showcase</h1>
                    <p class="lead mb-0">
                        Discover groundbreaking African innovations, connect with visionary innovators, 
                        and support the future of technology across the continent.
                    </p>
                </div>
                <div class="col-lg-4">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['total']; ?></div>
                                <div class="text-muted">Active Projects</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-card">
                                <div class="stat-number"><?php echo $stats['with_mentors']; ?></div>
                                <div class="text-muted">With Mentors</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="container mb-4">
        <!-- Search Bar -->
        <div class="search-box mb-4">
            <form method="GET" action="projects.php">
                <div class="input-group input-group-lg">
                    <input type="text" class="form-control" name="search" 
                           placeholder="Search projects by name, description, or market..." 
                           value="<?php echo e($search); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search me-2"></i>Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Stage Filters -->
        <div class="text-center mb-4">
            <h5 class="mb-3">Filter by Stage:</h5>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['stage' => 0])); ?>" 
               class="filter-chip <?php echo $stage === 0 ? 'active' : ''; ?>">
                All Stages
            </a>
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['stage' => $i])); ?>" 
                   class="filter-chip <?php echo $stage === $i ? 'active' : ''; ?>">
                    Stage <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>

        <!-- Results Summary -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="mb-0">
                    <?php if ($search): ?>
                        Search results for "<?php echo e($search); ?>"
                    <?php elseif ($stage > 0): ?>
                        Projects in Stage <?php echo $stage; ?>
                    <?php else: ?>
                        All Projects
                    <?php endif; ?>
                    <span class="text-muted">(<?php echo $totalProjects; ?> found)</span>
                </h5>
            </div>
            <?php if ($search || $stage > 0): ?>
            <a href="projects.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-times me-1"></i> Clear Filters
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Projects Grid -->
    <div class="container mb-5">
        <?php if (empty($projects)): ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-4x text-muted mb-4"></i>
                <h4>No Projects Found</h4>
                <p class="text-muted">Try adjusting your search or filters</p>
                <a href="projects.php" class="btn btn-primary">View All Projects</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($projects as $project): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card project-card">
                        <div class="card-header position-relative">
                            <h5 class="mb-1 text-truncate"><?php echo e($project['project_name']); ?></h5>
                            <small>Led by <?php echo e($project['project_lead_name']); ?></small>
                            <div class="project-stage">Stage <?php echo $project['current_stage']; ?></div>
                        </div>
                        <div class="card-body">
                            <p class="card-text text-muted" style="min-height: 100px;">
                                <?php echo e(substr($project['description'], 0, 150)); ?>
                                <?php echo strlen($project['description']) > 150 ? '...' : ''; ?>
                            </p>
                            
                            <?php if ($project['target_market']): ?>
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-bullseye me-1"></i>
                                    <?php echo e($project['target_market']); ?>
                                </small>
                            </div>
                            <?php endif; ?>

                            <div class="project-meta mb-3">
                                <span><i class="fas fa-users me-1"></i><?php echo $project['team_count']; ?> Team</span>
                                <span><i class="fas fa-chalkboard-teacher me-1"></i><?php echo $project['mentor_count']; ?> Mentors</span>
                                <span><i class="fas fa-comments me-1"></i><?php echo $project['comment_count']; ?> Comments</span>
                            </div>

                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-success" 
                                     style="width: <?php echo ($project['current_stage'] / 6) * 100; ?>%">
                                </div>
                            </div>

                            <a href="project-details.php?id=<?php echo $project['project_id']; ?>" 
                               class="btn btn-primary w-100">
                                <i class="fas fa-eye me-2"></i>View Details
                            </a>
                        </div>
                        <div class="card-footer bg-light text-muted small">
                            <i class="fas fa-clock me-1"></i>
                            Updated <?php echo timeAgo($project['updated_at']); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Projects pagination" class="mt-5">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- CTA Section -->
    <div class="bg-light py-5">
        <div class="container text-center">
            <h2 class="mb-4">Have an Innovation Idea?</h2>
            <p class="lead mb-4">Join the JHUB AFRICA ecosystem and accelerate your innovation journey</p>
            <a href="../applications/submit.php" class="btn btn-primary btn-lg me-3">
                <i class="fas fa-rocket me-2"></i>Apply Now
            </a>
            <a href="about.php" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-info-circle me-2"></i>Learn More
            </a>
        </div>
    </div>

<?php require_once '../templates/public-footer.php'; ?>
