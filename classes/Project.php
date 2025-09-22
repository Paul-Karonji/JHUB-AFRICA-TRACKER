<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Project Management Class
 * 
 * This class handles all project-related operations including creation,
 * management, team handling, and project lifecycle operations.
 * 
 * @author JHUB AFRICA Development Team
 * @version 1.0
 * @since 2024
 */

// Prevent direct access
if (!defined('JHUB_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Project Management Class
 * 
 * Handles all project operations and team management
 */
class Project {
    
    /** @var Database Database instance */
    private $db;
    
    /** @var Auth Authentication instance */
    private $auth;
    
    /**
     * Constructor - Initialize project management system
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }
    
    /**
     * Create new project
     * 
     * @param array $data Project data
     * @return array Operation result
     */
    public function createProject($data) {
        try {
            $this->db->beginTransaction();
            
            // Validate required fields
            $required = ['name', 'description', 'date', 'profile_name', 'password'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Validate data types and lengths
            $this->validateProjectData($data);
            
            // Check if profile_name is unique
            $stmt = $this->db->prepare("SELECT id FROM projects WHERE profile_name = ?");
            $this->db->execute($stmt, [$data['profile_name']]);
            if ($stmt->fetch()) {
                throw new Exception("Profile name already exists. Please choose a different username.");
            }
            
            // Validate and format date
            $projectDate = $this->validateDate($data['date']);
            
            // Insert project
            $stmt = $this->db->prepare("
                INSERT INTO projects (name, description, date, email, website, profile_name, password, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $this->db->execute($stmt, [
                trim($data['name']),
                trim($data['description']),
                $projectDate,
                !empty($data['email']) ? trim($data['email']) : null,
                !empty($data['website']) ? trim($data['website']) : null,
                trim($data['profile_name']),
                Auth::hashPassword($data['password'])
            ]);
            
            $projectId = $this->db->lastInsertId();
            
            // Create initial project log entry
            $this->logProjectActivity($projectId, 'project_created', 'Project created successfully', [
                'creator_ip' => getClientIP(),
                'creator_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
            ]);
            
            // Create notification for admin
            if (class_exists('Notification')) {
                $notification = new Notification();
                $notification->create([
                    'recipient_type' => 'admin',
                    'recipient_id' => 1, // Default admin
                    'notification_type' => 'project_created',
                    'title' => 'New Project Created',
                    'message' => "New project '{$data['name']}' has been created by a public user",
                    'related_project_id' => $projectId
                ]);
            }
            
            $this->db->commit();
            
            logActivity('INFO', "Project created successfully", [
                'project_id' => $projectId,
                'project_name' => $data['name'],
                'profile_name' => $data['profile_name']
            ]);
            
            return [
                'success' => true, 
                'project_id' => $projectId,
                'message' => 'Project created successfully',
                'login_credentials' => [
                    'profile_name' => $data['profile_name'],
                    'login_url' => AppConfig::getLoginUrl('project')
                ]
            ];
            
        } catch (Exception $e) {
            logActivity('ERROR', "Get project team error: " . $e->getMessage(), ['project_id' => $projectId]);
            return ['success' => false, 'message' => 'Failed to get project team'];
        }
    }
    
    /**
     * Get project mentors
     * 
     * @param int $projectId Project ID
     * @return array Operation result
     */
    public function getProjectMentors($projectId) {
        try {
            $stmt = $this->db->prepare("
                SELECT m.id, m.name, m.email, m.expertise, m.bio, pm.assigned_at, pm.self_assigned
                FROM project_mentors pm
                JOIN mentors m ON pm.mentor_id = m.id
                WHERE pm.project_id = ?
                ORDER BY pm.assigned_at ASC
            ");
            $this->db->execute($stmt, [$projectId]);
            $mentors = $stmt->fetchAll();
            
            return ['success' => true, 'mentors' => $mentors];
            
        } catch (Exception $e) {
            logActivity('ERROR', "Get project mentors error: " . $e->getMessage(), ['project_id' => $projectId]);
            return ['success' => false, 'message' => 'Failed to get project mentors'];
        }
    }
    
    /**
     * Get project statistics
     * 
     * @param int $projectId Project ID
     * @return array Project statistics
     */
    public function getProjectStats($projectId) {
        try {
            $stats = [];
            
            // Team size
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM project_innovators WHERE project_id = ?");
            $this->db->execute($stmt, [$projectId]);
            $stats['team_size'] = $stmt->fetchColumn();
            
            // Mentor count
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM project_mentors WHERE project_id = ?");
            $this->db->execute($stmt, [$projectId]);
            $stats['mentor_count'] = $stmt->fetchColumn();
            
            // Comment count
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM comments WHERE project_id = ?");
            $this->db->execute($stmt, [$projectId]);
            $stats['comment_count'] = $stmt->fetchColumn();
            
            // Rating count
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM ratings WHERE project_id = ?");
            $this->db->execute($stmt, [$projectId]);
            $stats['rating_count'] = $stmt->fetchColumn();
            
            // Days active (since creation)
            $stmt = $this->db->prepare("SELECT DATEDIFF(NOW(), created_at) FROM projects WHERE id = ?");
            $this->db->execute($stmt, [$projectId]);
            $stats['days_active'] = $stmt->fetchColumn();
            
            // Last activity (most recent rating or comment)
            $stmt = $this->db->prepare("
                SELECT MAX(activity_date) FROM (
                    SELECT MAX(rated_at) as activity_date FROM ratings WHERE project_id = ?
                    UNION ALL
                    SELECT MAX(created_at) as activity_date FROM comments WHERE project_id = ?
                ) activities
            ");
            $this->db->execute($stmt, [$projectId, $projectId]);
            $lastActivity = $stmt->fetchColumn();
            $stats['last_activity'] = $lastActivity;
            
            return $stats;
            
        } catch (Exception $e) {
            logActivity('ERROR', "Get project stats error: " . $e->getMessage(), ['project_id' => $projectId]);
            return [
                'team_size' => 0,
                'mentor_count' => 0,
                'comment_count' => 0,
                'rating_count' => 0,
                'days_active' => 0,
                'last_activity' => null
            ];
        }
    }
    
    /**
     * Get projects by mentor ID
     * 
     * @param int $mentorId Mentor ID
     * @param bool $activeOnly Whether to include only active projects
     * @return array Operation result
     */
    public function getProjectsByMentor($mentorId, $activeOnly = true) {
        try {
            $whereClause = $activeOnly ? "AND p.status = 'active'" : "AND p.status != 'terminated'";
            
            $stmt = $this->db->prepare("
                SELECT p.id, p.name, p.description, p.date, p.status, p.created_at,
                       COALESCE(latest_rating.stage, p.current_stage, 1) as current_stage,
                       COALESCE(latest_rating.percentage, p.current_percentage, 10) as current_percentage,
                       pm.assigned_at,
                       (SELECT COUNT(*) FROM project_innovators pi WHERE pi.project_id = p.id) as innovator_count,
                       (SELECT COUNT(*) FROM comments c WHERE c.project_id = p.id) as comment_count
                FROM project_mentors pm
                JOIN projects p ON pm.project_id = p.id
                LEFT JOIN (
                    SELECT project_id, stage, percentage,
                           ROW_NUMBER() OVER (PARTITION BY project_id ORDER BY rated_at DESC) as rn
                    FROM ratings
                ) latest_rating ON p.id = latest_rating.project_id AND latest_rating.rn = 1
                WHERE pm.mentor_id = ? $whereClause
                ORDER BY pm.assigned_at DESC
            ");
            $this->db->execute($stmt, [$mentorId]);
            $projects = $stmt->fetchAll();
            
            // Add stage information
            foreach ($projects as &$project) {
                $project['stage_info'] = DatabaseConfig::getStageInfo($project['current_stage']);
                $project['overall_progress'] = $this->calculateOverallProgress(
                    $project['current_stage'], 
                    $project['current_percentage']
                );
            }
            
            return ['success' => true, 'projects' => $projects];
            
        } catch (Exception $e) {
            logActivity('ERROR', "Get projects by mentor error: " . $e->getMessage(), ['mentor_id' => $mentorId]);
            return ['success' => false, 'message' => 'Failed to get mentor projects'];
        }
    }
    
    /**
     * Get available projects for mentor assignment
     * 
     * @param int $mentorId Mentor ID (to exclude already assigned projects)
     * @return array Operation result
     */
    public function getAvailableProjectsForMentor($mentorId) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.id, p.name, p.description, p.date, p.status, p.created_at,
                       COALESCE(latest_rating.stage, p.current_stage, 1) as current_stage,
                       COALESCE(latest_rating.percentage, p.current_percentage, 10) as current_percentage,
                       (SELECT COUNT(*) FROM project_innovators pi WHERE pi.project_id = p.id) as innovator_count,
                       (SELECT COUNT(*) FROM project_mentors pm2 WHERE pm2.project_id = p.id) as mentor_count,
                       (SELECT COUNT(*) FROM comments c WHERE c.project_id = p.id) as comment_count
                FROM projects p
                LEFT JOIN (
                    SELECT project_id, stage, percentage,
                           ROW_NUMBER() OVER (PARTITION BY project_id ORDER BY rated_at DESC) as rn
                    FROM ratings
                ) latest_rating ON p.id = latest_rating.project_id AND latest_rating.rn = 1
                WHERE p.status = 'active' 
                  AND p.id NOT IN (
                      SELECT project_id FROM project_mentors WHERE mentor_id = ?
                  )
                ORDER BY p.created_at DESC
            ");
            $this->db->execute($stmt, [$mentorId]);
            $projects = $stmt->fetchAll();
            
            // Add stage information
            foreach ($projects as &$project) {
                $project['stage_info'] = DatabaseConfig::getStageInfo($project['current_stage']);
                $project['overall_progress'] = $this->calculateOverallProgress(
                    $project['current_stage'], 
                    $project['current_percentage']
                );
            }
            
            return ['success' => true, 'projects' => $projects];
            
        } catch (Exception $e) {
            logActivity('ERROR', "Get available projects error: " . $e->getMessage(), ['mentor_id' => $mentorId]);
            return ['success' => false, 'message' => 'Failed to get available projects'];
        }
    }
    
    /**
     * Search projects with advanced filters
     * 
     * @param array $searchCriteria Search criteria
     * @return array Operation result
     */
    public function searchProjects($searchCriteria) {
        try {
            $whereConditions = ["p.status != 'terminated'"];
            $params = [];
            
            // Text search
            if (!empty($searchCriteria['query'])) {
                $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
                $searchTerm = '%' . $searchCriteria['query'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Stage filter
            if (!empty($searchCriteria['stages']) && is_array($searchCriteria['stages'])) {
                $stagePlaceholders = str_repeat('?,', count($searchCriteria['stages']) - 1) . '?';
                $whereConditions[] = "COALESCE(latest_rating.stage, p.current_stage, 1) IN ($stagePlaceholders)";
                $params = array_merge($params, $searchCriteria['stages']);
            }
            
            // Status filter
            if (!empty($searchCriteria['statuses']) && is_array($searchCriteria['statuses'])) {
                $statusPlaceholders = str_repeat('?,', count($searchCriteria['statuses']) - 1) . '?';
                $whereConditions[] = "p.status IN ($statusPlaceholders)";
                $params = array_merge($params, $searchCriteria['statuses']);
            }
            
            // Date range filter
            if (!empty($searchCriteria['date_from'])) {
                $whereConditions[] = "p.created_at >= ?";
                $params[] = $searchCriteria['date_from'];
            }
            
            if (!empty($searchCriteria['date_to'])) {
                $whereConditions[] = "p.created_at <= ?";
                $params[] = $searchCriteria['date_to'] . ' 23:59:59';
            }
            
            // Mentor expertise filter
            if (!empty($searchCriteria['mentor_expertise'])) {
                $whereConditions[] = "EXISTS (
                    SELECT 1 FROM project_mentors pm2 
                    JOIN mentors m ON pm2.mentor_id = m.id 
                    WHERE pm2.project_id = p.id AND m.expertise LIKE ?
                )";
                $params[] = '%' . $searchCriteria['mentor_expertise'] . '%';
            }
            
            // Team size filter
            if (!empty($searchCriteria['min_team_size'])) {
                $whereConditions[] = "(SELECT COUNT(*) FROM project_innovators pi WHERE pi.project_id = p.id) >= ?";
                $params[] = (int)$searchCriteria['min_team_size'];
            }
            
            if (!empty($searchCriteria['max_team_size'])) {
                $whereConditions[] = "(SELECT COUNT(*) FROM project_innovators pi WHERE pi.project_id = p.id) <= ?";
                $params[] = (int)$searchCriteria['max_team_size'];
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Build query
            $sql = "
                SELECT p.id, p.name, p.description, p.date, p.email, p.website, p.status, p.created_at,
                       COALESCE(latest_rating.stage, p.current_stage, 1) as current_stage,
                       COALESCE(latest_rating.percentage, p.current_percentage, 10) as current_percentage,
                       (SELECT COUNT(*) FROM project_innovators pi WHERE pi.project_id = p.id) as innovator_count,
                       (SELECT COUNT(*) FROM project_mentors pm WHERE pm.project_id = p.id) as mentor_count,
                       (SELECT COUNT(*) FROM comments c WHERE c.project_id = p.id) as comment_count
                FROM projects p
                LEFT JOIN (
                    SELECT project_id, stage, percentage,
                           ROW_NUMBER() OVER (PARTITION BY project_id ORDER BY rated_at DESC) as rn
                    FROM ratings
                ) latest_rating ON p.id = latest_rating.project_id AND latest_rating.rn = 1
                WHERE $whereClause
                ORDER BY p.created_at DESC
                LIMIT 100
            ";
            
            $projects = $this->db->fetchAll($sql, $params);
            
            // Add stage information
            foreach ($projects as &$project) {
                $project['stage_info'] = DatabaseConfig::getStageInfo($project['current_stage']);
                $project['overall_progress'] = $this->calculateOverallProgress(
                    $project['current_stage'], 
                    $project['current_percentage']
                );
            }
            
            return ['success' => true, 'projects' => $projects, 'count' => count($projects)];
            
        } catch (Exception $e) {
            logActivity('ERROR', "Search projects error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to search projects'];
        }
    }
    
    /**
     * Get project activity log
     * 
     * @param int $projectId Project ID
     * @param int $limit Number of entries to return
     * @return array Operation result
     */
    public function getProjectActivityLog($projectId, $limit = 50) {
        try {
            // This would require a project_activity_log table
            // For now, we'll simulate with recent ratings and comments
            $activities = [];
            
            // Get recent ratings
            $stmt = $this->db->prepare("
                SELECT r.id, r.stage, r.percentage, r.rated_at as activity_date, 
                       m.name as actor_name, 'rating_updated' as activity_type,
                       CONCAT('Project rated at Stage ', r.stage, ' (', r.percentage, '%)') as activity_description
                FROM ratings r
                JOIN mentors m ON r.mentor_id = m.id
                WHERE r.project_id = ?
                ORDER BY r.rated_at DESC
                LIMIT ?
            ");
            $this->db->execute($stmt, [$projectId, $limit]);
            $ratings = $stmt->fetchAll();
            
            // Get recent comments
            $stmt = $this->db->prepare("
                SELECT c.id, c.created_at as activity_date, c.user_type,
                       CASE 
                           WHEN c.user_type = 'mentor' THEN m.name
                           WHEN c.user_type = 'admin' THEN 'Administrator'
                           WHEN c.user_type = 'innovator' THEN pi.name
                           ELSE 'Public User'
                       END as actor_name,
                       'comment_added' as activity_type,
                       CONCAT('Added a comment: ', LEFT(c.comment_text, 50), 
                              CASE WHEN LENGTH(c.comment_text) > 50 THEN '...' ELSE '' END) as activity_description
                FROM comments c
                LEFT JOIN mentors m ON c.user_type = 'mentor' AND c.user_id = m.id
                LEFT JOIN project_innovators pi ON c.user_type = 'innovator' AND c.user_id = pi.id
                WHERE c.project_id = ?
                ORDER BY c.created_at DESC
                LIMIT ?
            ");
            $this->db->execute($stmt, [$projectId, $limit]);
            $comments = $stmt->fetchAll();
            
            // Combine and sort activities
            $activities = array_merge($ratings, $comments);
            
            // Sort by activity date descending
            usort($activities, function($a, $b) {
                return strtotime($b['activity_date']) - strtotime($a['activity_date']);
            });
            
            // Limit results
            $activities = array_slice($activities, 0, $limit);
            
            return ['success' => true, 'activities' => $activities];
            
        } catch (Exception $e) {
            logActivity('ERROR', "Get project activity log error: " . $e->getMessage(), ['project_id' => $projectId]);
            return ['success' => false, 'message' => 'Failed to get project activity log'];
        }
    }
    
    /**
     * Validate project data
     * 
     * @param array $data Project data to validate
     * @throws Exception If validation fails
     */
    private function validateProjectData($data) {
        // Validate project name
        if (strlen(trim($data['name'])) < MIN_PROJECT_NAME_LENGTH) {
            throw new Exception("Project name must be at least " . MIN_PROJECT_NAME_LENGTH . " characters long");
        }
        
        if (strlen(trim($data['name'])) > MAX_PROJECT_NAME_LENGTH) {
            throw new Exception("Project name must not exceed " . MAX_PROJECT_NAME_LENGTH . " characters");
        }
        
        // Validate description
        if (strlen(trim($data['description'])) < 10) {
            throw new Exception("Project description must be at least 10 characters long");
        }
        
        if (strlen(trim($data['description'])) > 5000) {
            throw new Exception("Project description must not exceed 5000 characters");
        }
        
        // Validate profile name
        if (strlen(trim($data['profile_name'])) < MIN_USERNAME_LENGTH) {
            throw new Exception("Profile name must be at least " . MIN_USERNAME_LENGTH . " characters long");
        }
        
        if (strlen(trim($data['profile_name'])) > MAX_USERNAME_LENGTH) {
            throw new Exception("Profile name must not exceed " . MAX_USERNAME_LENGTH . " characters");
        }
        
        if (!preg_match(USERNAME_PATTERN, $data['profile_name'])) {
            throw new Exception("Profile name can only contain letters, numbers, underscores, and hyphens");
        }
        
        // Validate password
        if (strlen($data['password']) < MIN_PASSWORD_LENGTH) {
            throw new Exception("Password must be at least " . MIN_PASSWORD_LENGTH . " characters long");
        }
        
        if (strlen($data['password']) > MAX_PASSWORD_LENGTH) {
            throw new Exception("Password must not exceed " . MAX_PASSWORD_LENGTH . " characters");
        }
        
        // Validate email if provided
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address format");
        }
        
        // Validate website if provided
        if (!empty($data['website']) && !filter_var($data['website'], FILTER_VALIDATE_URL)) {
            throw new Exception("Invalid website URL format");
        }
    }
    
    /**
     * Validate and format date
     * 
     * @param string $date Date string
     * @return string Formatted date
     * @throws Exception If date is invalid
     */
    private function validateDate($date) {
        $dateTime = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateTime) {
            throw new Exception("Invalid date format. Use YYYY-MM-DD format");
        }
        
        // Check if date is not in the future
        if ($dateTime > new DateTime()) {
            throw new Exception("Project date cannot be in the future");
        }
        
        // Check if date is not too old (more than 5 years ago)
        $fiveYearsAgo = new DateTime('-5 years');
        if ($dateTime < $fiveYearsAgo) {
            throw new Exception("Project date cannot be more than 5 years ago");
        }
        
        return $dateTime->format('Y-m-d');
    }
    
    /**
     * Calculate overall project progress
     * 
     * @param int $stage Current stage
     * @param int $percentage Current percentage
     * @return int Overall progress percentage
     */
    private function calculateOverallProgress($stage, $percentage) {
        $stageCumulative = [
            1 => 0,   // 0% + current
            2 => 10,  // 10% + current
            3 => 30,  // 10% + 20% + current
            4 => 50,  // 10% + 20% + 20% + current
            5 => 60,  // 10% + 20% + 20% + 10% + current
            6 => 80   // 10% + 20% + 20% + 10% + 20% + current
        ];
        
        $baseProgress = $stageCumulative[$stage] ?? 0;
        $stageInfo = DatabaseConfig::getStageInfo($stage);
        $stageMaxPercentage = $stageInfo ? $stageInfo['percentage'] : 20;
        
        $currentStageProgress = ($percentage / 100) * $stageMaxPercentage;
        
        return min(100, $baseProgress + $currentStageProgress);
    }
    
    /**
     * Log project activity
     * 
     * @param int $projectId Project ID
     * @param string $activityType Activity type
     * @param string $description Activity description
     * @param array $metadata Additional metadata
     */
    private function logProjectActivity($projectId, $activityType, $description, $metadata = []) {
        try {
            logActivity('INFO', "Project Activity: $description", array_merge([
                'project_id' => $projectId,
                'activity_type' => $activityType
            ], $metadata));
        } catch (Exception $e) {
            // Don't fail the main operation if logging fails
            error_log("Failed to log project activity: " . $e->getMessage());
        }
    }
    
    /**
     * Get project dashboard data
     * 
     * @param int $projectId Project ID
     * @return array Dashboard data
     */
    public function getProjectDashboardData($projectId) {
        try {
            $result = $this->getProject($projectId, true, true);
            if (!$result['success']) {
                return $result;
            }
            
            $project = $result['project'];
            
            // Get recent activity
            $activityResult = $this->getProjectActivityLog($projectId, 10);
            $project['recent_activity'] = $activityResult['success'] ? $activityResult['activities'] : [];
            
            // Get stage timeline
            $project['stage_timeline'] = $this->getStageTimeline($projectId);
            
            // Get next milestone
            $project['next_milestone'] = $this->getNextMilestone($project['current_stage']);
            
            return ['success' => true, 'dashboard' => $project];
            
        } catch (Exception $e) {
            logActivity('ERROR', "Get project dashboard error: " . $e->getMessage(), ['project_id' => $projectId]);
            return ['success' => false, 'message' => 'Failed to get project dashboard data'];
        }
    }
    
    /**
     * Get stage timeline for project
     * 
     * @param int $projectId Project ID
     * @return array Stage timeline
     */
    private function getStageTimeline($projectId) {
        $timeline = [];
        
        // Get all stages with their completion status
        $allStages = DatabaseConfig::getAllStages();
        
        // Get current stage
        $project = $this->getProject($projectId, false, false);
        $currentStage = $project['success'] ? $project['project']['current_stage'] : 1;
        
        foreach ($allStages as $stageNumber => $stageInfo) {
            $timeline[] = [
                'stage' => $stageNumber,
                'name' => $stageInfo['name'],
                'description' => $stageInfo['description'],
                'percentage' => $stageInfo['percentage'],
                'status' => $stageNumber < $currentStage ? 'completed' : 
                           ($stageNumber == $currentStage ? 'current' : 'pending'),
                'completed_at' => null // Would need to track this in ratings table
            ];
        }
        
        return $timeline;
    }
    
    /**
     * Get next milestone information
     * 
     * @param int $currentStage Current project stage
     * @return array|null Next milestone or null if completed
     */
    private function getNextMilestone($currentStage) {
        if ($currentStage >= 6) {
            return null; // Project completed
        }
        
        $nextStage = $currentStage + 1;
        $stageInfo = DatabaseConfig::getStageInfo($nextStage);
        
        if (!$stageInfo) {
            return null;
        }
        
        return [
            'stage' => $nextStage,
            'name' => $stageInfo['name'],
            'description' => $stageInfo['description'],
            'percentage' => $stageInfo['percentage']
        ];
    }
    
    /**
     * Get system-wide project statistics
     * 
     * @return array System statistics
     */
    public function getSystemStats() {
        try {
            $stats = [];
            
            // Total projects
            $stats['total_projects'] = $this->db->fetchColumn("
                SELECT COUNT(*) FROM projects WHERE status != 'terminated'
            ");
            
            // Active projects
            $stats['active_projects'] = $this->db->fetchColumn("
                SELECT COUNT(*) FROM projects WHERE status = 'active'
            ");
            
            // Completed projects
            $stats['completed_projects'] = $this->db->fetchColumn("
                SELECT COUNT(*) FROM projects WHERE status = 'completed' 
                OR (SELECT stage FROM ratings WHERE project_id = projects.id ORDER BY rated_at DESC LIMIT 1) = 6
            ");
            
            // Total innovators
            $stats['total_innovators'] = $this->db->fetchColumn("
                SELECT COUNT(DISTINCT email) FROM project_innovators
            ");
            
            // Total mentors
            $stats['total_mentors'] = $this->db->fetchColumn("
                SELECT COUNT(*) FROM mentors
            ");
            
            // Projects by stage
            $stats['projects_by_stage'] = [];
            for ($stage = 1; $stage <= 6; $stage++) {
                $count = $this->db->fetchColumn("
                    SELECT COUNT(*) FROM projects p
                    LEFT JOIN (
                        SELECT project_id, stage,
                               ROW_NUMBER() OVER (PARTITION BY project_id ORDER BY rated_at DESC) as rn
                        FROM ratings
                    ) latest_rating ON p.id = latest_rating.project_id AND latest_rating.rn = 1
                    WHERE COALESCE(latest_rating.stage, p.current_stage, 1) = ? 
                    AND p.status != 'terminated'
                ", [$stage]);
                
                $stats['projects_by_stage'][$stage] = $count;
            }
            
            // Recent activity count (last 30 days)
            $stats['recent_projects'] = $this->db->fetchColumn("
                SELECT COUNT(*) FROM projects 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                AND status != 'terminated'
            ");
            
            return $stats;
            
        } catch (Exception $e) {
            logActivity('ERROR', "Get system stats error: " . $e->getMessage());
            return [
                'total_projects' => 0,
                'active_projects' => 0,
                'completed_projects' => 0,
                'total_innovators' => 0,
                'total_mentors' => 0,
                'projects_by_stage' => array_fill(1, 6, 0),
                'recent_projects' => 0
            ];
        }
    }
}
            $this->db->rollback();
            logActivity('ERROR', "Project creation error: " . $e->getMessage(), [
                'project_data' => array_intersect_key($data, array_flip(['name', 'profile_name', 'email']))
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get project details with current stage and ratings
     * 
     * @param int $projectId Project ID
     * @param bool $includeTeam Whether to include team members
     * @param bool $includeMentors Whether to include mentors
     * @return array Operation result
     */
    public function getProject($projectId, $includeTeam = true, $includeMentors = true) {
        try {
            // Get project basic information with current stage
            $stmt = $this->db->prepare("
                SELECT p.*, 
                       COALESCE(latest_rating.stage, p.current_stage, 1) as current_stage,
                       COALESCE(latest_rating.percentage, p.current_percentage, 10) as current_percentage,
                       latest_rating.rated_at as last_rated_at,
                       latest_rating.mentor_name as last_rated_by,
                       CASE 
                           WHEN p.status = 'terminated' THEN 'Terminated'
                           WHEN COALESCE(latest_rating.stage, p.current_stage, 1) = 6 THEN 'Completed'
                           ELSE 'Active'
                       END as display_status
                FROM projects p
                LEFT JOIN (
                    SELECT r.project_id, r.stage, r.percentage, r.rated_at, m.name as mentor_name,
                           ROW_NUMBER() OVER (PARTITION BY r.project_id ORDER BY r.rated_at DESC) as rn
                    FROM ratings r
                    JOIN mentors m ON r.mentor_id = m.id
                ) latest_rating ON p.id = latest_rating.project_id AND latest_rating.rn = 1
                WHERE p.id = ?
            ");
            $this->db->execute($stmt, [$projectId]);
            $project = $stmt->fetch();
            
            if (!$project) {
                return ['success' => false, 'message' => 'Project not found'];
            }
            
            // Remove password from response
            unset($project['password']);
            
            // Add stage information
            $stageInfo = DatabaseConfig::getStageInfo($project['current_stage']);
            $project['stage_info'] = $stageInfo;
            
            // Calculate overall progress
            $project['overall_progress'] = $this->calculateOverallProgress(
                $project['current_stage'], 
                $project['current_percentage']
            );
            
            // Get project statistics
            $project['stats'] = $this->getProjectStats($projectId);
            
            // Get team members if requested
            if ($includeTeam) {
                $project['team'] = $this->getProjectTeam($projectId);
            }
            
            // Get mentors if requested
            if ($includeMentors) {
                $project['mentors'] = $this->getProjectMentors($projectId);
            }
            
            return ['success' => true, 'project' => $project];
            
        } catch (Exception $e) {
            logActivity('ERROR', "Get project error: " . $e->getMessage(), ['project_id' => $projectId]);
            return ['success' => false, 'message' => 'Failed to get project details'];
        }
    }
    
    /**
     * Get all projects with filtering and pagination
     * 
     * @param array $filters Filter criteria
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array Operation result
     */
    public function getAllProjects($filters = [], $page = 1, $perPage = null) {
        try {
            $perPage = $perPage ?: AppConfig::PROJECTS_PER_PAGE;
            $offset = ($page - 1) * $perPage;
            
            // Build WHERE clause
            $whereConditions = ["p.status != 'terminated'"];
            $params = [];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $whereConditions[] = "p.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['stage'])) {
                $whereConditions[] = "COALESCE(latest_rating.stage, p.current_stage, 1) = ?";
                $params[] = (int)$filters['stage'];
            }
            
            if (!empty($filters['search'])) {
                $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($filters['mentor_id'])) {
                $whereConditions[] = "EXISTS (SELECT 1 FROM project_mentors pm WHERE pm.project_id = p.id AND pm.mentor_id = ?)";
                $params[] = $filters['mentor_id'];
            }
            
            if (!empty($filters['created_after'])) {
                $whereConditions[] = "p.created_at >= ?";
                $params[] = $filters['created_after'];
            }
            
            if (!empty($filters['created_before'])) {
                $whereConditions[] = "p.created_at <= ?";
                $params[] = $filters['created_before'];
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Get total count for pagination
            $countSql = "
                SELECT COUNT(DISTINCT p.id)
                FROM projects p
                LEFT JOIN (
                    SELECT project_id, stage,
                           ROW_NUMBER() OVER (PARTITION BY project_id ORDER BY rated_at DESC) as rn
                    FROM ratings
                ) latest_rating ON p.id = latest_rating.project_id AND latest_rating.rn = 1
                WHERE $whereClause
            ";
            
            $totalCount = $this->db->fetchColumn($countSql, $params);
            
            // Get projects
            $sql = "
                SELECT p.id, p.name, p.description, p.date, p.email, p.website, p.status, p.created_at,
                       COALESCE(latest_rating.stage, p.current_stage, 1) as current_stage,
                       COALESCE(latest_rating.percentage, p.current_percentage, 10) as current_percentage,
                       (SELECT COUNT(*) FROM project_innovators pi WHERE pi.project_id = p.id) as innovator_count,
                       (SELECT COUNT(*) FROM project_mentors pm WHERE pm.project_id = p.id) as mentor_count,
                       (SELECT COUNT(*) FROM comments c WHERE c.project_id = p.id) as comment_count,
                       CASE 
                           WHEN p.status = 'terminated' THEN 'Terminated'
                           WHEN COALESCE(latest_rating.stage, p.current_stage, 1) = 6 THEN 'Completed'
                           ELSE 'Active'
                       END as display_status
                FROM projects p
                LEFT JOIN (
                    SELECT project_id, stage, percentage,
                           ROW_NUMBER() OVER (PARTITION BY project_id ORDER BY rated_at DESC) as rn
                    FROM ratings
                ) latest_rating ON p.id = latest_rating.project_id AND latest_rating.rn = 1
                WHERE $whereClause
                ORDER BY p.created_at DESC
                LIMIT $perPage OFFSET $offset
            ";
            
            $projects = $this->db->fetchAll($sql, $params);
            
            // Add stage information for each project
            foreach ($projects as &$project) {
                $project['stage_info'] = DatabaseConfig::getStageInfo($project['current_stage']);
                $project['overall_progress'] = $this->calculateOverallProgress(
                    $project['current_stage'], 
                    $project['current_percentage']
                );
            }
            
            return [
                'success' => true, 
                'projects' => $projects,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $perPage,
                    'total' => $totalCount,
                    'total_pages' => ceil($totalCount / $perPage),
                    'has_next' => ($page * $perPage) < $totalCount,
                    'has_prev' => $page > 1
                ]
            ];
            
        } catch (Exception $e) {
            logActivity('ERROR', "Get all projects error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to get projects'];
        }
    }
    
    /**
     * Add innovator to project team
     * 
     * @param int $projectId Project ID
     * @param array $innovatorData Innovator information
     * @param int|null $addedBy ID of innovator who added them
     * @return array Operation result
     */
    public function addInnovator($projectId, $innovatorData, $addedBy = null) {
        try {
            // Validate required fields
            $required = ['name', 'email', 'role'];
            foreach ($required as $field) {
                if (empty($innovatorData[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            // Validate email format
            if (!filter_var($innovatorData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address format");
            }
            
            // Check if email already exists in this project
            $stmt = $this->db->prepare("
                SELECT id, name FROM project_innovators 
                WHERE project_id = ? AND email = ?
            ");
            $this->db->execute($stmt, [$projectId, $innovatorData['email']]);
            if ($existing = $stmt->fetch()) {
                throw new Exception("An innovator with email '{$innovatorData['email']}' is already part of this project");
            }
            
            // Validate project exists and is not terminated
            $project = $this->getProject($projectId, false, false);
            if (!$project['success']) {
                throw new Exception("Project not found");
            }
            
            if ($project['project']['status'] === 'terminated') {
                throw new Exception("Cannot add members to a terminated project");
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO project_innovators (project_id, name, email, role, experience_level, added_at, added_by_innovator_id) 
                VALUES (?, ?, ?, ?, ?, NOW(), ?)
            ");
            
            $this->db->execute($stmt, [
                $projectId,
                trim($innovatorData['name']),
                trim($innovatorData['email']),
                trim($innovatorData['role']),
                !empty($innovatorData['experience_level']) ? trim($innovatorData['experience_level']) : null,
                $addedBy
            ]);
            
            $innovatorId = $this->db->lastInsertId();
            
            // Log activity
            $this->logProjectActivity($projectId, 'innovator_added', 'New team member added', [
                'innovator_name' => $innovatorData['name'],
                'innovator_email' => $innovatorData['email'],
                'innovator_role' => $innovatorData['role'],
                'added_by' => $addedBy
            ]);
            
            // Create notifications for project mentors
            if (class_exists('Notification')) {
                $notification = new Notification();
                $mentors = $this->getProjectMentors($projectId);
                
                foreach ($mentors['mentors'] as $mentor) {
                    $notification->create([
                        'recipient_type' => 'mentor',
                        'recipient_id' => $mentor['id'],
                        'notification_type' => 'innovator_added',
                        'title' => 'New Team Member Added',
                        'message' => "New team member '{$innovatorData['name']}' has been added to {$project['project']['name']}",
                        'related_project_id' => $projectId
                    ]);
                }
            }
            
            logActivity('INFO', "Innovator added to project", [
                'project_id' => $projectId,
                'innovator_name' => $innovatorData['name'],
                'innovator_email' => $innovatorData['email']
            ]);
            
            return [
                'success' => true, 
                'innovator_id' => $innovatorId,
                'message' => 'Team member added successfully'
            ];
            
        } catch (Exception $e) {
            logActivity('ERROR', "Add innovator error: " . $e->getMessage(), [
                'project_id' => $projectId,
                'innovator_data' => array_intersect_key($innovatorData, array_flip(['name', 'email', 'role']))
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Remove innovator from project
     * 
     * @param int $projectId Project ID
     * @param int $innovatorId Innovator ID
     * @param int|null $removedBy ID of innovator who removed them
     * @return array Operation result
     */
    public function removeInnovator($projectId, $innovatorId, $removedBy = null) {
        try {
            // Get innovator details before removal
            $stmt = $this->db->prepare("
                SELECT name, email, role FROM project_innovators 
                WHERE id = ? AND project_id = ?
            ");
            $this->db->execute($stmt, [$innovatorId, $projectId]);
            $innovator = $stmt->fetch();
            
            if (!$innovator) {
                throw new Exception("Team member not found or not authorized to remove");
            }
            
            // Check if this is the last innovator (prevent removing all team members)
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM project_innovators WHERE project_id = ?");
            $this->db->execute($stmt, [$projectId]);
            $innovatorCount = $stmt->fetchColumn();
            
            if ($innovatorCount <= 1) {
                throw new Exception("Cannot remove the last team member from the project");
            }
            
            // Remove innovator
            $stmt = $this->db->prepare("
                DELETE FROM project_innovators 
                WHERE id = ? AND project_id = ?
            ");
            $this->db->execute($stmt, [$innovatorId, $projectId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Team member not found or already removed");
            }
            
            // Log activity
            $this->logProjectActivity($projectId, 'innovator_removed', 'Team member removed', [
                'innovator_name' => $innovator['name'],
                'innovator_email' => $innovator['email'],
                'removed_by' => $removedBy
            ]);
            
            logActivity('INFO', "Innovator removed from project", [
                'project_id' => $projectId,
                'innovator_name' => $innovator['name']
            ]);
            
            return ['success' => true, 'message' => 'Team member removed successfully'];
            
        } catch (Exception $e) {
            logActivity('ERROR', "Remove innovator error: " . $e->getMessage(), [
                'project_id' => $projectId,
                'innovator_id' => $innovatorId
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Assign mentor to project
     * 
     * @param int $projectId Project ID
     * @param int $mentorId Mentor ID
     * @param bool $selfAssigned Whether mentor assigned themselves
     * @return array Operation result
     */
    public function assignMentor($projectId, $mentorId, $selfAssigned = true) {
        try {
            $this->db->beginTransaction();
            
            // Validate project exists and is active
            $project = $this->getProject($projectId, false, false);
            if (!$project['success']) {
                throw new Exception("Project not found");
            }
            
            if ($project['project']['status'] === 'terminated') {
                throw new Exception("Cannot assign mentors to terminated projects");
            }
            
            // Validate mentor exists
            $stmt = $this->db->prepare("SELECT id, name, email, expertise FROM mentors WHERE id = ?");
            $this->db->execute($stmt, [$mentorId]);
            $mentor = $stmt->fetch();
            
            if (!$mentor) {
                throw new Exception("Mentor not found");
            }
            
            // Check if mentor is already assigned
            $stmt = $this->db->prepare("
                SELECT id FROM project_mentors 
                WHERE project_id = ? AND mentor_id = ?
            ");
            $this->db->execute($stmt, [$projectId, $mentorId]);
            if ($stmt->fetch()) {
                throw new Exception("Mentor is already assigned to this project");
            }
            
            // Assign mentor
            $stmt = $this->db->prepare("
                INSERT INTO project_mentors (project_id, mentor_id, assigned_at, self_assigned) 
                VALUES (?, ?, NOW(), ?)
            ");
            $this->db->execute($stmt, [$projectId, $mentorId, $selfAssigned]);
            
            $assignmentId = $this->db->lastInsertId();
            
            // Log activity
            $this->logProjectActivity($projectId, 'mentor_assigned', 'Mentor joined project', [
                'mentor_name' => $mentor['name'],
                'mentor_expertise' => $mentor['expertise'],
                'self_assigned' => $selfAssigned
            ]);
            
            // Create notification for project
            if (class_exists('Notification')) {
                $notification = new Notification();
                $notification->create([
                    'recipient_type' => 'project',
                    'recipient_id' => $projectId,
                    'notification_type' => 'mentor_joined',
                    'title' => 'New Mentor Joined',
                    'message' => "{$mentor['name']} has joined your project as a mentor",
                    'related_project_id' => $projectId
                ]);
                
                // Notify other mentors
                $existingMentors = $this->getProjectMentors($projectId);
                foreach ($existingMentors['mentors'] as $existingMentor) {
                    if ($existingMentor['id'] !== $mentorId) {
                        $notification->create([
                            'recipient_type' => 'mentor',
                            'recipient_id' => $existingMentor['id'],
                            'notification_type' => 'mentor_joined',
                            'title' => 'New Mentor Joined Project',
                            'message' => "{$mentor['name']} has joined the {$project['project']['name']} project",
                            'related_project_id' => $projectId
                        ]);
                    }
                }
            }
            
            $this->db->commit();
            
            logActivity('INFO', "Mentor assigned to project", [
                'project_id' => $projectId,
                'mentor_id' => $mentorId,
                'mentor_name' => $mentor['name'],
                'self_assigned' => $selfAssigned
            ]);
            
            return [
                'success' => true, 
                'assignment_id' => $assignmentId,
                'mentor' => $mentor,
                'message' => 'Mentor assigned successfully'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            logActivity('ERROR', "Assign mentor error: " . $e->getMessage(), [
                'project_id' => $projectId,
                'mentor_id' => $mentorId
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update project information
     * 
     * @param int $projectId Project ID
     * @param array $updateData Data to update
     * @param int|null $updatedBy User ID who made the update
     * @return array Operation result
     */
    public function updateProject($projectId, $updateData, $updatedBy = null) {
        try {
            // Validate project exists
            $project = $this->getProject($projectId, false, false);
            if (!$project['success']) {
                throw new Exception("Project not found");
            }
            
            if ($project['project']['status'] === 'terminated') {
                throw new Exception("Cannot update terminated projects");
            }
            
            // Validate update data
            $allowedFields = ['name', 'description', 'email', 'website'];
            $updateFields = [];
            $params = [];
            
            foreach ($allowedFields as $field) {
                if (isset($updateData[$field])) {
                    if ($field === 'name' && empty(trim($updateData[$field]))) {
                        throw new Exception("Project name cannot be empty");
                    }
                    if ($field === 'description' && empty(trim($updateData[$field]))) {
                        throw new Exception("Project description cannot be empty");
                    }
                    if ($field === 'email' && !empty($updateData[$field]) && !filter_var($updateData[$field], FILTER_VALIDATE_EMAIL)) {
                        throw new Exception("Invalid email address format");
                    }
                    if ($field === 'website' && !empty($updateData[$field]) && !filter_var($updateData[$field], FILTER_VALIDATE_URL)) {
                        throw new Exception("Invalid website URL format");
                    }
                    
                    $updateFields[] = "$field = ?";
                    $params[] = trim($updateData[$field]) ?: null;
                }
            }
            
            if (empty($updateFields)) {
                throw new Exception("No valid fields to update");
            }
            
            // Add updated timestamp
            $updateFields[] = "updated_at = NOW()";
            $params[] = $projectId;
            
            // Update project
            $sql = "UPDATE projects SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $this->db->execute($stmt, $params);
            
            // Log activity
            $this->logProjectActivity($projectId, 'project_updated', 'Project information updated', [
                'updated_fields' => array_keys($updateData),
                'updated_by' => $updatedBy
            ]);
            
            logActivity('INFO', "Project updated", [
                'project_id' => $projectId,
                'updated_fields' => array_keys($updateData)
            ]);
            
            return ['success' => true, 'message' => 'Project updated successfully'];
            
        } catch (Exception $e) {
            logActivity('ERROR', "Update project error: " . $e->getMessage(), [
                'project_id' => $projectId,
                'update_data' => array_keys($updateData)
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Terminate project (admin only)
     * 
     * @param int $projectId Project ID
     * @param int $adminId Admin ID who terminated the project
     * @param string $reason Termination reason
     * @return array Operation result
     */
    public function terminateProject($projectId, $adminId, $reason = null) {
        try {
            $this->db->beginTransaction();
            
            // Validate project exists and is not already terminated
            $project = $this->getProject($projectId, false, true);
            if (!$project['success']) {
                throw new Exception("Project not found");
            }
            
            if ($project['project']['status'] === 'terminated') {
                throw new Exception("Project is already terminated");
            }
            
            // Update project status
            $stmt = $this->db->prepare("
                UPDATE projects 
                SET status = 'terminated', terminated_by = ?, terminated_at = NOW()
                WHERE id = ?
            ");
            $this->db->execute($stmt, [$adminId, $projectId]);
            
            // Log activity
            $this->logProjectActivity($projectId, 'project_terminated', 'Project terminated by administrator', [
                'terminated_by' => $adminId,
                'reason' => $reason
            ]);
            
            // Create notifications for all project stakeholders
            if (class_exists('Notification')) {
                $notification = new Notification();
                
                // Notify project team
                $notification->create([
                    'recipient_type' => 'project',
                    'recipient_id' => $projectId,
                    'notification_type' => 'project_terminated',
                    'title' => 'Project Terminated',
                    'message' => 'Your project has been terminated by an administrator' . ($reason ? ": $reason" : ''),
                    'related_project_id' => $projectId
                ]);
                
                // Notify mentors
                if (!empty($project['project']['mentors'])) {
                    foreach ($project['project']['mentors'] as $mentor) {
                        $notification->create([
                            'recipient_type' => 'mentor',
                            'recipient_id' => $mentor['id'],
                            'notification_type' => 'project_terminated',
                            'title' => 'Project Terminated',
                            'message' => "Project '{$project['project']['name']}' has been terminated",
                            'related_project_id' => $projectId
                        ]);
                    }
                }
            }
            
            $this->db->commit();
            
            logActivity('INFO', "Project terminated", [
                'project_id' => $projectId,
                'project_name' => $project['project']['name'],
                'terminated_by' => $adminId,
                'reason' => $reason
            ]);
            
            return ['success' => true, 'message' => 'Project terminated successfully'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            logActivity('ERROR', "Terminate project error: " . $e->getMessage(), [
                'project_id' => $projectId,
                'admin_id' => $adminId
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get project team members
     * 
     * @param int $projectId Project ID
     * @return array Operation result
     */
    public function getProjectTeam($projectId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, name, email, role, experience_level, added_at
                FROM project_innovators 
                WHERE project_id = ?
                ORDER BY added_at ASC
            ");
            $this->db->execute($stmt, [$projectId]);
            $team = $stmt->fetchAll();
            
            return ['success' => true, 'team' => $team];
            
        } catch (Exception $e) {