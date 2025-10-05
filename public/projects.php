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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - JHUB AFRICA</title>
    <meta name="description" content="Explore innovative African projects in the JHUB AFRICA ecosystem. Discover groundbreaking innovations, connect with innovators, and support the future of African technology.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0 60px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .project-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
            height: 100%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .project-card:hover {
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            transform: translateY(-5px);
        }
        .project-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border: none;
        }
        .project-stage {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255,255,255,0.9);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #667eea;
        }
        .project-meta {
            display: flex;
            gap: 15px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        .filter-chip {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            border: 2px solid #dee2e6;
            text-decoration: none;
            color: #495057;
            transition: all 0.3s;
            margin: 5px;
        }
        .filter-chip:hover, .filter-chip.active {
            background: #667eea;
            border-color: #667eea;
            color: white;
        }
        .search-box {
            max-width: 600px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">
                <i class="fas fa-lightbulb me-2"></i>JHUB AFRICA
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="projects.php">Projects</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../applications/submit.php">Apply</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/login.php">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

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

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>JHUB AFRICA</h5>
                    <p class="mb-0">Nurturing African Innovations from Conception to Market Success</p>
                </div>
                <div class="col-md-3">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled">
                        <li><a href="projects.php" class="text-white-50">Projects</a></li>
                        <li><a href="about.php" class="text-white-50">About</a></li>
                        <li><a href="contact.php" class="text-white-50">Contact</a></li>
                        <li><a href="../applications/submit.php" class="text-white-50">Apply</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h6>Connect</h6>
                    <div class="social-links">
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-linkedin fa-lg"></i></a>
                        <a href="#" class="text-white-50 me-3"><i class="fab fa-facebook fa-lg"></i></a>
                        <a href="#" class="text-white-50"><i class="fab fa-instagram fa-lg"></i></a>
                    </div>
                </div>
            </div>
            <hr class="bg-secondary my-4">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> JHUB AFRICA. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>