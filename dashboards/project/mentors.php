<?php
// dashboards/project/mentors.php
require_once '../../includes/init.php';

$auth->requireUserType(USER_TYPE_PROJECT);

$projectId = $auth->getUserId();
$project = $database->getRow("SELECT * FROM projects WHERE project_id = ?", [$projectId]);

// Get assigned mentors with details
$mentors = $database->getAll("
    SELECT m.*, pm.assigned_at, pm.is_active
    FROM mentors m
    INNER JOIN project_mentors pm ON m.mentor_id = pm.mentor_id
    WHERE pm.project_id = ? AND pm.is_active = 1
    ORDER BY pm.assigned_at ASC
", [$projectId]);

// Get mentor contributions
$mentorStats = [];
foreach ($mentors as $mentor) {
    $mentorStats[$mentor['mentor_id']] = [
        'resources' => $database->count('mentor_resources', 'project_id = ? AND mentor_id = ? AND is_deleted = 0', [$projectId, $mentor['mentor_id']]),
        'assessments' => $database->count('project_assessments', 'project_id = ? AND mentor_id = ? AND is_deleted = 0', [$projectId, $mentor['mentor_id']]),
        'learning_objectives' => $database->count('learning_objectives', 'project_id = ? AND mentor_id = ? AND is_deleted = 0', [$projectId, $mentor['mentor_id']])
    ];
}

$pageTitle = "Our Mentors";
include '../../templates/header.php';
?>

<div class="project-dashboard">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Our Mentors</h1>
            <p class="text-muted">Experts guiding your project to success</p>
        </div>
        <a href="index.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <?php if (empty($mentors)): ?>
        <div class="card shadow">
            <div class="card-body text-center py-5">
                <i class="fas fa-user-tie fa-4x text-muted mb-3"></i>
                <h4>No Mentors Assigned Yet</h4>
                <p class="text-muted">Mentors will join your project soon to provide guidance and support.</p>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    Mentors can self-assign to your project. Once they join, you'll see them here!
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-success mb-4">
            <i class="fas fa-check-circle me-2"></i>
            You have <strong><?php echo count($mentors); ?></strong> mentor<?php echo count($mentors) > 1 ? 's' : ''; ?> working with your project.
        </div>

        <div class="row">
            <?php foreach ($mentors as $mentor): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <img src="<?php echo getGravatar($mentor['email'], 100); ?>" 
                                 class="rounded-circle mb-2" 
                                 alt="<?php echo e($mentor['name']); ?>">
                            <h5 class="card-title mb-1"><?php echo e($mentor['name']); ?></h5>
                            <p class="text-muted small mb-2"><?php echo e($mentor['area_of_expertise']); ?></p>
                            <?php if ($mentor['years_experience']): ?>
                                <span class="badge bg-info"><?php echo $mentor['years_experience']; ?> years experience</span>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <strong>About:</strong>
                            <p class="small mt-1"><?php echo e($mentor['bio']); ?></p>
                        </div>

                        <?php if ($mentor['linkedin_url']): ?>
                        <div class="mb-3">
                            <a href="<?php echo e($mentor['linkedin_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary w-100">
                                <i class="fab fa-linkedin me-1"></i> View LinkedIn Profile
                            </a>
                        </div>
                        <?php endif; ?>

                        <!-- Mentor Contributions -->
                        <div class="border-top pt-3">
                            <h6 class="small mb-2">Contributions to Your Project:</h6>
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="contribution-stat">
                                        <i class="fas fa-book text-success"></i>
                                        <div class="fw-bold"><?php echo $mentorStats[$mentor['mentor_id']]['resources']; ?></div>
                                        <small class="text-muted">Resources</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="contribution-stat">
                                        <i class="fas fa-clipboard-check text-info"></i>
                                        <div class="fw-bold"><?php echo $mentorStats[$mentor['mentor_id']]['assessments']; ?></div>
                                        <small class="text-muted">Assessments</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="contribution-stat">
                                        <i class="fas fa-graduation-cap text-warning"></i>
                                        <div class="fw-bold"><?php echo $mentorStats[$mentor['mentor_id']]['learning_objectives']; ?></div>
                                        <small class="text-muted">Learning</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-top pt-3 mt-3">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                Joined <?php echo timeAgo($mentor['assigned_at']); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.contribution-stat {
    padding: 0.5rem 0;
}
.contribution-stat i {
    font-size: 1.2rem;
    margin-bottom: 0.25rem;
}
</style>

<?php include '../../templates/footer.php'; ?>