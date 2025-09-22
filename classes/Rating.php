<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Project Rating Management Class
 * 
 * This class handles all project rating operations including stage updates,
 * rating history tracking, and progress calculations for the 6-stage system.
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
 * Rating Management Class
 * 
 * Handles project rating operations and stage progression
 */
class Rating {
    
    /** @var Database Database instance */
    private $db;
    
    /** @var Auth Authentication instance */
    private $auth;
    
    /**
     * Constructor - Initialize rating management system
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }
    
    /**
     * Update project rating and stage
     * 
     * @param int $projectId Project ID
     * @param int $mentorId Mentor ID who is rating
     * @param int $stage New stage (1-6)
     * @param int $percentage New percentage (0-100)
     * @param string|null $notes Optional rating notes
     * @return array Operation result
     */
    public function updateProjectRating($projectId, $mentorId, $stage, $percentage, $notes = null) {
        try {
            $this->db->beginTransaction();
            
            // Validate input
            if (!$this->validateStage($stage)) {
                throw new Exception("Invalid stage. Stage must be between 1 and 6.");
            }
            
            if (!$this->validatePercentage($percentage)) {
                throw new Exception("Invalid percentage. Percentage must be between 0 and 100.");
            }
            
            // Check if mentor is assigned to project
            if (!$this->isMentorAssignedToProject($mentorId, $projectId)) {
                throw new Exception("Mentor is not assigned to this project");
            }
            
            // Get current project status
            $currentProject = $this->getCurrentProjectStatus($projectId);
            if (!$currentProject) {
                throw new Exception("Project not found");
            }
            
            if ($currentProject['status'] === 'terminated') {
                throw new Exception("Cannot update rating for terminated projects");
            }
            
            // Use stored procedure for consistent rating updates
            $stmt = $this->db->prepare("CALL UpdateProjectRating(?, ?, ?, ?, ?)");
            $this->db->execute($stmt, [
                $projectId,
                $mentorId, 
                $stage,
                $percentage,
                $notes
            ]);
            
            $this->db->commit();
            
            // Log the rating update
            logActivity('INFO', 'Project rating updated', [
                'project_id' => $projectId,
                'mentor_id' => $mentorId,
                'old_stage' => $currentProject['current_stage'],
                'new_stage' => $stage,
                'old_percentage' => $currentProject['current_percentage'],
                'new_percentage' => $percentage
            ]);
            
            return [
                'success' => true,
                'message' => 'Project rating updated successfully',
                'previous' => [
                    'stage' => $currentProject['current_stage'],
                    'percentage' => $currentProject['current_percentage']
                ],
                'new' => [
                    'stage' => $stage,
                    'percentage' => $percentage
                ],
                'overall_progress' => $this->calculateOverallProgress($stage, $percentage)
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            logActivity('ERROR', 'Rating update error: ' . $e->getMessage(), [
                'project_id' => $projectId,
                'mentor_id' => $mentorId,
                'stage' => $stage,
                'percentage' => $percentage
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get project rating history
     * 
     * @param int $projectId Project ID
     * @param int|null $limit Number of ratings to return
     * @return array Operation result
     */
    public function getProjectRatings($projectId, $limit = null) {
        try {
            $limitClause = $limit ? "LIMIT " . (int)$limit : "";
            
            $stmt = $this->db->prepare("
                SELECT r.id, r.stage, r.percentage, r.previous_stage, r.previous_percentage,
                       r.rated_at, r.notes, m.name as mentor_name, m.expertise
                FROM ratings r
                JOIN mentors m ON r.mentor_id = m.id
                WHERE r.project_id = ?
                ORDER BY r.rated_at DESC
                $limitClause
            ");
            $this->db->execute($stmt, [$projectId]);
            $ratings = $stmt->fetchAll();
            
            // Add stage information and progress calculations
            foreach ($ratings as &$rating) {
                $rating['stage_info'] = DatabaseConfig::getStageInfo($rating['stage']);
                $rating['previous_stage_info'] = $rating['previous_stage'] ? 
                    DatabaseConfig::getStageInfo($rating['previous_stage']) : null;
                $rating['overall_progress'] = $this->calculateOverallProgress($rating['stage'], $rating['percentage']);
                $rating['formatted_date'] = date('M j, Y \a\t g:i A', strtotime($rating['rated_at']));
                
                // Calculate progress change
                if ($rating['previous_stage'] && $rating['previous_percentage']) {
                    $previousProgress = $this->calculateOverallProgress($rating['previous_stage'], $rating['previous_percentage']);
                    $rating['progress_change'] = $rating['overall_progress'] - $previousProgress;
                } else {
                    $rating['progress_change'] = $rating['overall_progress'];
                }
            }
            
            return ['success' => true, 'ratings' => $ratings];
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Get project ratings error: ' . $e->getMessage(), ['project_id' => $projectId]);
            return ['success' => false, 'message' => 'Failed to get project ratings'];
        }
    }
    
    /**
     * Get latest project rating
     * 
     * @param int $projectId Project ID
     * @return array|null Latest rating or null if none exists
     */
    public function getLatestRating($projectId) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.id, r.stage, r.percentage, r.rated_at, r.notes,
                       m.name as mentor_name, m.expertise
                FROM ratings r
                JOIN mentors m ON r.mentor_id = m.id
                WHERE r.project_id = ?
                ORDER BY r.rated_at DESC
                LIMIT 1
            ");
            $this->db->execute($stmt, [$projectId]);
            $rating = $stmt->fetch();
            
            if ($rating) {
                $rating['stage_info'] = DatabaseConfig::getStageInfo($rating['stage']);
                $rating['overall_progress'] = $this->calculateOverallProgress($rating['stage'], $rating['percentage']);
                $rating['formatted_date'] = date('M j, Y \a\t g:i A', strtotime($rating['rated_at']));
            }
            
            return $rating;
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Get latest rating error: ' . $e->getMessage(), ['project_id' => $projectId]);
            return null;
        }
    }
    
    /**
     * Get all ratings by a mentor
     * 
     * @param int $mentorId Mentor ID
     * @param int|null $limit Number of ratings to return
     * @return array Operation result
     */
    public function getRatingsByMentor($mentorId, $limit = null) {
        try {
            $limitClause = $limit ? "LIMIT " . (int)$limit : "";
            
            $stmt = $this->db->prepare("
                SELECT r.id, r.project_id, p.name as project_name, r.stage, r.percentage,
                       r.previous_stage, r.previous_percentage, r.rated_at, r.notes
                FROM ratings r
                JOIN projects p ON r.project_id = p.id
                WHERE r.mentor_id = ?
                ORDER BY r.rated_at DESC
                $limitClause
            ");
            $this->db->execute($stmt, [$mentorId]);
            $ratings = $stmt->fetchAll();
            
            // Add additional information
            foreach ($ratings as &$rating) {
                $rating['stage_info'] = DatabaseConfig::getStageInfo($rating['stage']);
                $rating['overall_progress'] = $this->calculateOverallProgress($rating['stage'], $rating['percentage']);
                $rating['formatted_date'] = date('M j, Y', strtotime($rating['rated_at']));
            }
            
            return ['success' => true, 'ratings' => $ratings];
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Get mentor ratings error: ' . $e->getMessage(), ['mentor_id' => $mentorId]);
            return ['success' => false, 'message' => 'Failed to get mentor ratings'];
        }
    }
    
    /**
     * Get rating statistics for a project
     * 
     * @param int $projectId Project ID
     * @return array Rating statistics
     */
    public function getProjectRatingStats($projectId) {
        try {
            $stats = [];
            
            // Total ratings
            $stats['total_ratings'] = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM ratings WHERE project_id = ?", 
                [$projectId]
            );
            
            // Average stage progression time
            $stmt = $this->db->prepare("
                SELECT AVG(TIMESTAMPDIFF(DAY, 
                    LAG(rated_at) OVER (ORDER BY rated_at), 
                    rated_at
                )) as avg_days_between_ratings
                FROM ratings 
                WHERE project_id = ?
            ");
            $this->db->execute($stmt, [$projectId]);
            $stats['avg_days_between_ratings'] = round($stmt->fetchColumn() ?? 0, 1);
            
            // Stage progression history
            $stmt = $this->db->prepare("
                SELECT stage, MIN(rated_at) as first_reached, COUNT(*) as rating_count
                FROM ratings 
                WHERE project_id = ?
                GROUP BY stage
                ORDER BY stage
            ");
            $this->db->execute($stmt, [$projectId]);
            $stats['stage_progression'] = $stmt->fetchAll();
            
            // Rating frequency by mentor
            $stmt = $this->db->prepare("
                SELECT m.name, COUNT(*) as rating_count, 
                       MAX(r.rated_at) as last_rating_date
                FROM ratings r
                JOIN mentors m ON r.mentor_id = m.id
                WHERE r.project_id = ?
                GROUP BY r.mentor_id, m.name
                ORDER BY rating_count DESC
            ");
            $this->db->execute($stmt, [$projectId]);
            $stats['mentor_activity'] = $stmt->fetchAll();
            
            return $stats;
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Get rating stats error: ' . $e->getMessage(), ['project_id' => $projectId]);
            return [
                'total_ratings' => 0,
                'avg_days_between_ratings' => 0,
                'stage_progression' => [],
                'mentor_activity' => []
            ];
        }
    }
    
    /**
     * Get system-wide rating statistics
     * 
     * @return array System rating statistics
     */
    public function getSystemRatingStats() {
        try {
            $stats = [];
            
            // Total ratings across all projects
            $stats['total_ratings'] = $this->db->fetchColumn("SELECT COUNT(*) FROM ratings");
            
            // Projects by current stage
            $stmt = $this->db->prepare("
                SELECT 
                    COALESCE(latest_rating.stage, p.current_stage, 1) as stage,
                    COUNT(*) as project_count
                FROM projects p
                LEFT JOIN (
                    SELECT project_id, stage,
                           ROW_NUMBER() OVER (PARTITION BY project_id ORDER BY rated_at DESC) as rn
                    FROM ratings
                ) latest_rating ON p.id = latest_rating.project_id AND latest_rating.rn = 1
                WHERE p.status != 'terminated'
                GROUP BY COALESCE(latest_rating.stage, p.current_stage, 1)
                ORDER BY stage
            ");
            $this->db->execute($stmt);
            $stageDistribution = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Fill in missing stages with 0
            for ($i = 1; $i <= 6; $i++) {
                $stats['projects_by_stage'][$i] = $stageDistribution[$i] ?? 0;
            }
            
            // Most active mentors
            $stmt = $this->db->prepare("
                SELECT m.name, m.expertise, COUNT(*) as total_ratings,
                       COUNT(DISTINCT r.project_id) as projects_rated
                FROM ratings r
                JOIN mentors m ON r.mentor_id = m.id
                GROUP BY r.mentor_id, m.name, m.expertise
                ORDER BY total_ratings DESC
                LIMIT 10
            ");
            $this->db->execute($stmt);
            $stats['most_active_mentors'] = $stmt->fetchAll();
            
            // Average project completion rate
            $completedProjects = $this->db->fetchColumn("
                SELECT COUNT(*) FROM projects 
                WHERE status = 'completed' OR current_stage = 6
            ");
            $totalProjects = $this->db->fetchColumn("
                SELECT COUNT(*) FROM projects WHERE status != 'terminated'
            ");
            $stats['completion_rate'] = $totalProjects > 0 ? 
                round(($completedProjects / $totalProjects) * 100, 1) : 0;
            
            return $stats;
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Get system rating stats error: ' . $e->getMessage());
            return [
                'total_ratings' => 0,
                'projects_by_stage' => array_fill(1, 6, 0),
                'most_active_mentors' => [],
                'completion_rate' => 0
            ];
        }
    }
    
    /**
     * Check if project can be rated by mentor
     * 
     * @param int $projectId Project ID
     * @param int $mentorId Mentor ID
     * @return array Validation result
     */
    public function canMentorRateProject($projectId, $mentorId) {
        try {
            // Check if mentor is assigned to project
            if (!$this->isMentorAssignedToProject($mentorId, $projectId)) {
                return [
                    'can_rate' => false,
                    'reason' => 'Mentor is not assigned to this project'
                ];
            }
            
            // Check if project exists and is not terminated
            $project = $this->getCurrentProjectStatus($projectId);
            if (!$project) {
                return [
                    'can_rate' => false,
                    'reason' => 'Project not found'
                ];
            }
            
            if ($project['status'] === 'terminated') {
                return [
                    'can_rate' => false,
                    'reason' => 'Cannot rate terminated projects'
                ];
            }
            
            // Check if project is already completed (stage 6)
            if ($project['current_stage'] == 6 && $project['status'] === 'completed') {
                return [
                    'can_rate' => false,
                    'reason' => 'Project is already completed'
                ];
            }
            
            return [
                'can_rate' => true,
                'current_stage' => $project['current_stage'],
                'current_percentage' => $project['current_percentage'],
                'project_status' => $project['status']
            ];
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Can mentor rate project check error: ' . $e->getMessage(), [
                'project_id' => $projectId,
                'mentor_id' => $mentorId
            ]);
            return [
                'can_rate' => false,
                'reason' => 'Error checking rating permissions'
            ];
        }
    }
    
    /**
     * Get suggested next stage for project
     * 
     * @param int $projectId Project ID
     * @return array Suggested rating information
     */
    public function getSuggestedRating($projectId) {
        try {
            $project = $this->getCurrentProjectStatus($projectId);
            if (!$project) {
                return null;
            }
            
            $currentStage = $project['current_stage'];
            $currentPercentage = $project['current_percentage'];
            
            // Suggest progression logic
            $suggestions = [];
            
            // If at 100% of current stage, suggest next stage
            if ($currentPercentage >= 100 && $currentStage < 6) {
                $nextStage = $currentStage + 1;
                $nextStageInfo = DatabaseConfig::getStageInfo($nextStage);
                
                $suggestions[] = [
                    'type' => 'next_stage',
                    'stage' => $nextStage,
                    'percentage' => 0,
                    'reason' => 'Ready to progress to next stage',
                    'stage_info' => $nextStageInfo
                ];
            }
            
            // Suggest incremental progress within current stage
            if ($currentPercentage < 100) {
                $suggestedIncrements = [25, 50, 75, 100];
                foreach ($suggestedIncrements as $increment) {
                    if ($increment > $currentPercentage) {
                        $suggestions[] = [
                            'type' => 'progress',
                            'stage' => $currentStage,
                            'percentage' => $increment,
                            'reason' => "Progress to {$increment}% of current stage",
                            'stage_info' => DatabaseConfig::getStageInfo($currentStage)
                        ];
                        break;
                    }
                }
            }
            
            return [
                'current' => [
                    'stage' => $currentStage,
                    'percentage' => $currentPercentage,
                    'stage_info' => DatabaseConfig::getStageInfo($currentStage)
                ],
                'suggestions' => $suggestions
            ];
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Get suggested rating error: ' . $e->getMessage(), ['project_id' => $projectId]);
            return null;
        }
    }
    
    /**
     * Validate stage number
     * 
     * @param int $stage Stage to validate
     * @return bool True if valid
     */
    private function validateStage($stage) {
        return is_numeric($stage) && $stage >= 1 && $stage <= 6;
    }
    
    /**
     * Validate percentage
     * 
     * @param int $percentage Percentage to validate
     * @return bool True if valid
     */
    private function validatePercentage($percentage) {
        return is_numeric($percentage) && $percentage >= 0 && $percentage <= 100;
    }
    
    /**
     * Check if mentor is assigned to project
     * 
     * @param int $mentorId Mentor ID
     * @param int $projectId Project ID
     * @return bool True if assigned
     */
    private function isMentorAssignedToProject($mentorId, $projectId) {
        $stmt = $this->db->prepare("
            SELECT 1 FROM project_mentors 
            WHERE mentor_id = ? AND project_id = ?
        ");
        $this->db->execute($stmt, [$mentorId, $projectId]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Get current project status
     * 
     * @param int $projectId Project ID
     * @return array|null Project status or null if not found
     */
    private function getCurrentProjectStatus($projectId) {
        $stmt = $this->db->prepare("
            SELECT id, name, current_stage, current_percentage, status
            FROM projects 
            WHERE id = ?
        ");
        $this->db->execute($stmt, [$projectId]);
        return $stmt->fetch();
    }
    
    /**
     * Calculate overall project progress percentage
     * 
     * @param int $stage Current stage
     * @param int $percentage Current percentage within stage
     * @return int Overall progress percentage
     */
    private function calculateOverallProgress($stage, $percentage) {
        // Stage cumulative percentages
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
     * Get project completion timeline
     * 
     * @param int $projectId Project ID
     * @return array Timeline data
     */
    public function getProjectTimeline($projectId) {
        try {
            // Get all ratings for timeline
            $stmt = $this->db->prepare("
                SELECT r.stage, r.percentage, r.rated_at, r.notes,
                       m.name as mentor_name
                FROM ratings r
                JOIN mentors m ON r.mentor_id = m.id
                WHERE r.project_id = ?
                ORDER BY r.rated_at ASC
            ");
            $this->db->execute($stmt, [$projectId]);
            $ratings = $stmt->fetchAll();
            
            // Get project creation date
            $project = $this->db->fetchOne(
                "SELECT name, created_at FROM projects WHERE id = ?",
                [$projectId]
            );
            
            $timeline = [];
            
            // Add project creation
            if ($project) {
                $timeline[] = [
                    'type' => 'created',
                    'stage' => 1,
                    'percentage' => 10,
                    'date' => $project['created_at'],
                    'description' => 'Project created',
                    'mentor_name' => null
                ];
            }
            
            // Add ratings
            foreach ($ratings as $rating) {
                $stageInfo = DatabaseConfig::getStageInfo($rating['stage']);
                $timeline[] = [
                    'type' => 'rating',
                    'stage' => $rating['stage'],
                    'percentage' => $rating['percentage'],
                    'date' => $rating['rated_at'],
                    'description' => $stageInfo ? $stageInfo['name'] : "Stage {$rating['stage']}",
                    'mentor_name' => $rating['mentor_name'],
                    'notes' => $rating['notes']
                ];
            }
            
            // Calculate time between milestones
            for ($i = 1; $i < count($timeline); $i++) {
                $current = strtotime($timeline[$i]['date']);
                $previous = strtotime($timeline[$i-1]['date']);
                $timeline[$i]['days_since_previous'] = round(($current - $previous) / (24 * 60 * 60));
            }
            
            return ['success' => true, 'timeline' => $timeline];
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Get project timeline error: ' . $e->getMessage(), ['project_id' => $projectId]);
            return ['success' => false, 'message' => 'Failed to get project timeline'];
        }
    }
}