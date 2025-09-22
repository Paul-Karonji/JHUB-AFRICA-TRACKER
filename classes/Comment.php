<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Comment Management Class
 * 
 * This class handles all comment operations including threaded discussions,
 * user attribution, and comment management for project collaboration.
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
 * Comment Management Class
 * 
 * Handles project comments and threaded discussions
 */
class Comment {
    
    /** @var Database Database instance */
    private $db;
    
    /** @var Auth Authentication instance */
    private $auth;
    
    /**
     * Constructor - Initialize comment management system
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }
    
    /**
     * Add new comment to project
     * 
     * @param int $projectId Project ID
     * @param string $userType User type (admin, mentor, project, public)
     * @param int|null $userId User ID (null for public users)
     * @param string $commentText Comment content
     * @param int|null $parentId Parent comment ID for replies
     * @param string|null $commenterName Name for public users
     * @return array Operation result
     */
    public function addComment($projectId, $userType, $userId, $commentText, $parentId = null, $commenterName = null) {
        try {
            $this->db->beginTransaction();
            
            // Validate input
            if (!$this->validateCommentText($commentText)) {
                throw new Exception("Comment text is required and must be between " . COMMENT_MIN_LENGTH . " and " . COMMENT_MAX_LENGTH . " characters");
            }
            
            if (!$this->validateUserType($userType)) {
                throw new Exception("Invalid user type");
            }
            
            // Validate project exists and is not terminated
            if (!$this->canCommentOnProject($projectId, $userType, $userId)) {
                throw new Exception("Cannot comment on this project");
            }
            
            // Validate parent comment exists and belongs to same project
            if ($parentId && !$this->validateParentComment($parentId, $projectId)) {
                throw new Exception("Invalid parent comment");
            }
            
            // Check comment depth (prevent excessive nesting)
            if ($parentId && $this->getCommentDepth($parentId) >= COMMENT_MAX_DEPTH) {
                throw new Exception("Maximum comment depth exceeded");
            }
            
            // Get commenter name if not provided
            if (!$commenterName) {
                $commenterName = $this->getCommenterName($userType, $userId);
            }
            
            // Insert comment
            $stmt = $this->db->prepare("
                INSERT INTO comments (project_id, user_type, user_id, commenter_name, comment_text, parent_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $this->db->execute($stmt, [
                $projectId,
                $userType,
                $userId,
                $commenterName,
                trim($commentText),
                $parentId
            ]);
            
            $commentId = $this->db->lastInsertId();
            
            // Create notifications for relevant users
            $this->createCommentNotifications($commentId, $projectId, $userType, $userId, $parentId, $commenterName);
            
            $this->db->commit();
            
            // Log activity
            logActivity('INFO', 'Comment added', [
                'comment_id' => $commentId,
                'project_id' => $projectId,
                'user_type' => $userType,
                'user_id' => $userId,
                'is_reply' => $parentId !== null
            ]);
            
            // Get the created comment with full details
            $comment = $this->getComment($commentId);
            
            return [
                'success' => true,
                'comment_id' => $commentId,
                'comment' => $comment,
                'message' => 'Comment added successfully'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            logActivity('ERROR', 'Add comment error: ' . $e->getMessage(), [
                'project_id' => $projectId,
                'user_type' => $userType,
                'user_id' => $userId
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get project comments with threading
     * 
     * @param int $projectId Project ID
     * @param bool $includeReplies Whether to include threaded replies
     * @param string $sortOrder Sort order (newest_first, oldest_first)
     * @param int|null $limit Number of comments to return
     * @return array Operation result
     */
    public function getProjectComments($projectId, $includeReplies = true, $sortOrder = 'newest_first', $limit = null) {
        try {
            $orderBy = $sortOrder === 'oldest_first' ? 'ASC' : 'DESC';
            $limitClause = $limit ? "LIMIT " . (int)$limit : "";
            
            if ($includeReplies) {
                // Get all comments and organize into threads
                $stmt = $this->db->prepare("
                    SELECT c.id, c.user_type, c.user_id, c.commenter_name, c.comment_text, 
                           c.parent_id, c.created_at, c.updated_at,
                           -- Get user details based on user type
                           CASE 
                               WHEN c.user_type = 'admin' THEN 'Administrator'
                               WHEN c.user_type = 'mentor' THEN m.name
                               WHEN c.user_type = 'project' THEN 'Project Team'
                               ELSE COALESCE(c.commenter_name, 'Anonymous')
                           END as display_name,
                           CASE 
                               WHEN c.user_type = 'mentor' THEN m.expertise
                               ELSE NULL
                           END as user_expertise
                    FROM comments c
                    LEFT JOIN mentors m ON c.user_type = 'mentor' AND c.user_id = m.id
                    WHERE c.project_id = ?
                    ORDER BY c.created_at $orderBy
                    $limitClause
                ");
                $this->db->execute($stmt, [$projectId]);
                $allComments = $stmt->fetchAll();
                
                // Organize into threaded structure
                $comments = $this->organizeComments($allComments);
                
            } else {
                // Get only top-level comments
                $stmt = $this->db->prepare("
                    SELECT c.id, c.user_type, c.user_id, c.commenter_name, c.comment_text, 
                           c.parent_id, c.created_at, c.updated_at,
                           CASE 
                               WHEN c.user_type = 'admin' THEN 'Administrator'
                               WHEN c.user_type = 'mentor' THEN m.name
                               WHEN c.user_type = 'project' THEN 'Project Team'
                               ELSE COALESCE(c.commenter_name, 'Anonymous')
                           END as display_name,
                           CASE 
                               WHEN c.user_type = 'mentor' THEN m.expertise
                               ELSE NULL
                           END as user_expertise,
                           (SELECT COUNT(*) FROM comments WHERE parent_id = c.id) as reply_count
                    FROM comments c
                    LEFT JOIN mentors m ON c.user_type = 'mentor' AND c.user_id = m.id
                    WHERE c.project_id = ? AND c.parent_id IS NULL
                    ORDER BY c.created_at $orderBy
                    $limitClause
                ");
                $this->db->execute($stmt, [$projectId]);
                $comments = $stmt->fetchAll();
                
                // Add formatted data
                foreach ($comments as &$comment) {
                    $comment = $this->formatComment($comment);
                }
            }
            
            return [
                'success' => true, 
                'comments' => $comments,
                'total_comments' => $this->getProjectCommentCount($projectId)
            ];
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Get project comments error: ' . $e->getMessage(), ['project_id' => $projectId]);
            return ['success' => false, 'message' => 'Failed to get project comments'];
        }
    }
    
    /**
     * Get single comment by ID
     * 
     * @param int $commentId Comment ID
     * @return array|null Comment data or null if not found
     */
    public function getComment($commentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.id, c.project_id, c.user_type, c.user_id, c.commenter_name, 
                       c.comment_text, c.parent_id, c.created_at, c.updated_at,
                       CASE 
                           WHEN c.user_type = 'admin' THEN 'Administrator'
                           WHEN c.user_type = 'mentor' THEN m.name
                           WHEN c.user_type = 'project' THEN 'Project Team'
                           ELSE COALESCE(c.commenter_name, 'Anonymous')
                       END as display_name,
                       CASE 
                           WHEN c.user_type = 'mentor' THEN m.expertise
                           ELSE NULL
                       END as user_expertise
                FROM comments c
                LEFT JOIN mentors m ON c.user_type = 'mentor' AND c.user_id = m.id
                WHERE c.id = ?
            ");
            $this->db->execute($stmt, [$commentId]);
            $comment = $stmt->fetch();
            
            return $comment ? $this->formatComment($comment) : null;
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Get comment error: ' . $e->getMessage(), ['comment_id' => $commentId]);
            return null;
        }
    }
    
    /**
     * Update comment text
     * 
     * @param int $commentId Comment ID
     * @param string $newText New comment text
     * @param string $userType User type making the update
     * @param int|null $userId User ID making the update
     * @return array Operation result
     */
    public function updateComment($commentId, $newText, $userType, $userId) {
        try {
            // Validate comment text
            if (!$this->validateCommentText($newText)) {
                throw new Exception("Comment text is required and must be between " . COMMENT_MIN_LENGTH . " and " . COMMENT_MAX_LENGTH . " characters");
            }
            
            // Get original comment
            $originalComment = $this->getComment($commentId);
            if (!$originalComment) {
                throw new Exception("Comment not found");
            }
            
            // Check if user can edit this comment
            if (!$this->canEditComment($originalComment, $userType, $userId)) {
                throw new Exception("You don't have permission to edit this comment");
            }
            
            // Update comment
            $stmt = $this->db->prepare("
                UPDATE comments 
                SET comment_text = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $this->db->execute($stmt, [trim($newText), $commentId]);
            
            logActivity('INFO', 'Comment updated', [
                'comment_id' => $commentId,
                'project_id' => $originalComment['project_id'],
                'updated_by_type' => $userType,
                'updated_by_id' => $userId
            ]);
            
            return [
                'success' => true,
                'message' => 'Comment updated successfully',
                'comment' => $this->getComment($commentId)
            ];
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Update comment error: ' . $e->getMessage(), [
                'comment_id' => $commentId,
                'user_type' => $userType,
                'user_id' => $userId
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Delete comment
     * 
     * @param int $commentId Comment ID
     * @param string $userType User type making the deletion
     * @param int|null $userId User ID making the deletion
     * @return array Operation result
     */
    public function deleteComment($commentId, $userType, $userId) {
        try {
            $this->db->beginTransaction();
            
            // Get original comment
            $originalComment = $this->getComment($commentId);
            if (!$originalComment) {
                throw new Exception("Comment not found");
            }
            
            // Check if user can delete this comment
            if (!$this->canDeleteComment($originalComment, $userType, $userId)) {
                throw new Exception("You don't have permission to delete this comment");
            }
            
            // Check if comment has replies
            $replyCount = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM comments WHERE parent_id = ?",
                [$commentId]
            );
            
            if ($replyCount > 0) {
                // Don't actually delete, just mark as deleted to preserve thread structure
                $stmt = $this->db->prepare("
                    UPDATE comments 
                    SET comment_text = '[Comment deleted]', updated_at = NOW() 
                    WHERE id = ?
                ");
                $this->db->execute($stmt, [$commentId]);
                $message = 'Comment marked as deleted (has replies)';
            } else {
                // Actually delete the comment
                $stmt = $this->db->prepare("DELETE FROM comments WHERE id = ?");
                $this->db->execute($stmt, [$commentId]);
                $message = 'Comment deleted successfully';
            }
            
            $this->db->commit();
            
            logActivity('INFO', 'Comment deleted', [
                'comment_id' => $commentId,
                'project_id' => $originalComment['project_id'],
                'deleted_by_type' => $userType,
                'deleted_by_id' => $userId,
                'had_replies' => $replyCount > 0
            ]);
            
            return ['success' => true, 'message' => $message];
            
        } catch (Exception $e) {
            $this->db->rollback();
            logActivity('ERROR', 'Delete comment error: ' . $e->getMessage(), [
                'comment_id' => $commentId,
                'user_type' => $userType,
                'user_id' => $userId
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get comment replies
     * 
     * @param int $parentId Parent comment ID
     * @param string $sortOrder Sort order
     * @return array Comment replies
     */
    public function getCommentReplies($parentId, $sortOrder = 'oldest_first') {
        try {
            $orderBy = $sortOrder === 'newest_first' ? 'DESC' : 'ASC';
            
            $stmt = $this->db->prepare("
                SELECT c.id, c.user_type, c.user_id, c.commenter_name, c.comment_text, 
                       c.parent_id, c.created_at, c.updated_at,
                       CASE 
                           WHEN c.user_type = 'admin' THEN 'Administrator'
                           WHEN c.user_type = 'mentor' THEN m.name
                           WHEN c.user_type = 'project' THEN 'Project Team'
                           ELSE COALESCE(c.commenter_name, 'Anonymous')
                       END as display_name,
                       CASE 
                           WHEN c.user_type = 'mentor' THEN m.expertise
                           ELSE NULL
                       END as user_expertise
                FROM comments c
                LEFT JOIN mentors m ON c.user_type = 'mentor' AND c.user_id = m.id
                WHERE c.parent_id = ?
                ORDER BY c.created_at $orderBy
            ");
            $this->db->execute($stmt, [$parentId]);
            $replies = $stmt->fetchAll();
            
            // Format comments and get nested replies
            foreach ($replies as &$reply) {
                $reply = $this->formatComment($reply);
                $reply['replies'] = $this->getCommentReplies($reply['id'], $sortOrder);
            }
            
            return $replies;
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Get comment replies error: ' . $e->getMessage(), ['parent_id' => $parentId]);
            return [];
        }
    }
    
    /**
     * Get comment statistics for a project
     * 
     * @param int $projectId Project ID
     * @return array Comment statistics
     */
    public function getProjectCommentStats($projectId) {
        try {
            $stats = [];
            
            // Total comments
            $stats['total_comments'] = $this->getProjectCommentCount($projectId);
            
            // Comments by user type
            $stmt = $this->db->prepare("
                SELECT user_type, COUNT(*) as comment_count
                FROM comments 
                WHERE project_id = ?
                GROUP BY user_type
                ORDER BY comment_count DESC
            ");
            $this->db->execute($stmt, [$projectId]);
            $stats['comments_by_type'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Most active commenters
            $stmt = $this->db->prepare("
                SELECT 
                    CASE 
                        WHEN user_type = 'admin' THEN 'Administrator'
                        WHEN user_type = 'mentor' THEN m.name
                        WHEN user_type = 'project' THEN 'Project Team'
                        ELSE COALESCE(commenter_name, 'Anonymous')
                    END as display_name,
                    COUNT(*) as comment_count
                FROM comments c
                LEFT JOIN mentors m ON c.user_type = 'mentor' AND c.user_id = m.id
                WHERE c.project_id = ?
                GROUP BY c.user_type, c.user_id, display_name
                ORDER BY comment_count DESC
                LIMIT 5
            ");
            $this->db->execute($stmt, [$projectId]);
            $stats['most_active_commenters'] = $stmt->fetchAll();
            
            // Comment activity over time (last 30 days)
            $stmt = $this->db->prepare("
                SELECT DATE(created_at) as comment_date, COUNT(*) as comment_count
                FROM comments 
                WHERE project_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(created_at)
                ORDER BY comment_date DESC
            ");
            $this->db->execute($stmt, [$projectId]);
            $stats['recent_activity'] = $stmt->fetchAll();
            
            // Reply ratio
            $totalComments = $stats['total_comments'];
            $topLevelComments = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM comments WHERE project_id = ? AND parent_id IS NULL",
                [$projectId]
            );
            $stats['reply_ratio'] = $totalComments > 0 ? 
                round((($totalComments - $topLevelComments) / $totalComments) * 100, 1) : 0;
            
            return $stats;
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Get comment stats error: ' . $e->getMessage(), ['project_id' => $projectId]);
            return [
                'total_comments' => 0,
                'comments_by_type' => [],
                'most_active_commenters' => [],
                'recent_activity' => [],
                'reply_ratio' => 0
            ];
        }
    }
    
    /**
     * Get system-wide comment statistics
     * 
     * @return array System comment statistics
     */
    public function getSystemCommentStats() {
        try {
            $stats = [];
            
            // Total comments
            $stats['total_comments'] = $this->db->fetchColumn("SELECT COUNT(*) FROM comments");
            
            // Comments by user type
            $stmt = $this->db->prepare("
                SELECT user_type, COUNT(*) as comment_count
                FROM comments
                GROUP BY user_type
                ORDER BY comment_count DESC
            ");
            $this->db->execute($stmt);
            $stats['comments_by_type'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Most commented projects
            $stmt = $this->db->prepare("
                SELECT p.name, COUNT(c.id) as comment_count
                FROM projects p
                LEFT JOIN comments c ON p.id = c.project_id
                GROUP BY p.id, p.name
                ORDER BY comment_count DESC
                LIMIT 10
            ");
            $this->db->execute($stmt);
            $stats['most_commented_projects'] = $stmt->fetchAll();
            
            // Recent comment activity (last 7 days)
            $stats['recent_comments'] = $this->db->fetchColumn("
                SELECT COUNT(*) FROM comments 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            
            return $stats;
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Get system comment stats error: ' . $e->getMessage());
            return [
                'total_comments' => 0,
                'comments_by_type' => [],
                'most_commented_projects' => [],
                'recent_comments' => 0
            ];
        }
    }
    
    /**
     * Search comments across projects
     * 
     * @param string $searchTerm Search term
     * @param array $filters Additional filters
     * @param int $limit Number of results to return
     * @return array Search results
     */
    public function searchComments($searchTerm, $filters = [], $limit = 50) {
        try {
            $whereConditions = [];
            $params = [];
            
            // Text search
            if (!empty($searchTerm)) {
                $whereConditions[] = "c.comment_text LIKE ?";
                $params[] = '%' . $searchTerm . '%';
            }
            
            // Project filter
            if (!empty($filters['project_id'])) {
                $whereConditions[] = "c.project_id = ?";
                $params[] = $filters['project_id'];
            }
            
            // User type filter
            if (!empty($filters['user_type'])) {
                $whereConditions[] = "c.user_type = ?";
                $params[] = $filters['user_type'];
            }
            
            // Date range filter
            if (!empty($filters['date_from'])) {
                $whereConditions[] = "c.created_at >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $whereConditions[] = "c.created_at <= ?";
                $params[] = $filters['date_to'] . ' 23:59:59';
            }
            
            $whereClause = !empty($whereConditions) ? 
                'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $stmt = $this->db->prepare("
                SELECT c.id, c.project_id, p.name as project_name, c.user_type, c.user_id, 
                       c.commenter_name, c.comment_text, c.created_at,
                       CASE 
                           WHEN c.user_type = 'admin' THEN 'Administrator'
                           WHEN c.user_type = 'mentor' THEN m.name
                           WHEN c.user_type = 'project' THEN 'Project Team'
                           ELSE COALESCE(c.commenter_name, 'Anonymous')
                       END as display_name
                FROM comments c
                JOIN projects p ON c.project_id = p.id
                LEFT JOIN mentors m ON c.user_type = 'mentor' AND c.user_id = m.id
                $whereClause
                ORDER BY c.created_at DESC
                LIMIT ?
            ");
            
            $params[] = $limit;
            $this->db->execute($stmt, $params);
            $results = $stmt->fetchAll();
            
            // Format results
            foreach ($results as &$result) {
                $result['formatted_date'] = date('M j, Y \a\t g:i A', strtotime($result['created_at']));
                // Highlight search term in text
                if (!empty($searchTerm)) {
                    $result['highlighted_text'] = $this->highlightSearchTerm($result['comment_text'], $searchTerm);
                }
            }
            
            return ['success' => true, 'results' => $results, 'count' => count($results)];
            
        } catch (Exception $e) {
            logActivity('ERROR', 'Search comments error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Search failed'];
        }
    }
    
    // ==============================================
    // PRIVATE HELPER METHODS
    // ==============================================
    
    /**
     * Validate comment text
     */
    private function validateCommentText($text) {
        $length = strlen(trim($text));
        return $length >= COMMENT_MIN_LENGTH && $length <= COMMENT_MAX_LENGTH;
    }
    
    /**
     * Validate user type
     */
    private function validateUserType($userType) {
        return in_array($userType, ['admin', 'mentor', 'project', 'public']);
    }
    
    /**
     * Check if user can comment on project
     */
    private function canCommentOnProject($projectId, $userType, $userId) {
        // Check if project exists and is not terminated
        $project = $this->db->fetchOne(
            "SELECT status FROM projects WHERE id = ?",
            [$projectId]
        );
        
        if (!$project || $project['status'] === 'terminated') {
            return false;
        }
        
        // All user types can comment (public comments allowed)
        return true;
    }
    
    /**
     * Validate parent comment
     */
    private function validateParentComment($parentId, $projectId) {
        $parent = $this->db->fetchOne(
            "SELECT project_id FROM comments WHERE id = ?",
            [$parentId]
        );
        
        return $parent && $parent['project_id'] == $projectId;
    }
    
    /**
     * Get comment depth for nesting limits
     */
    private function getCommentDepth($commentId, $depth = 0) {
        $parent = $this->db->fetchOne(
            "SELECT parent_id FROM comments WHERE id = ?",
            [$commentId]
        );
        
        if (!$parent || !$parent['parent_id']) {
            return $depth;
        }
        
        return $this->getCommentDepth($parent['parent_id'], $depth + 1);
    }
    
    /**
     * Get commenter display name
     */
    private function getCommenterName($userType, $userId) {
        switch ($userType) {
            case 'admin':
                return 'Administrator';
                
            case 'mentor':
                $mentor = $this->db->fetchOne("SELECT name FROM mentors WHERE id = ?", [$userId]);
                return $mentor ? $mentor['name'] : 'Mentor';
                
            case 'project':
                return 'Project Team';
                
            case 'public':
            default:
                return null; // Will be handled as Anonymous
        }
    }
    
    /**
     * Check if user can edit comment
     */
    private function canEditComment($comment, $userType, $userId) {
        // Admins can edit any comment
        if ($userType === 'admin') {
            return true;
        }
        
        // Users can edit their own comments
        return $comment['user_type'] === $userType && $comment['user_id'] == $userId;
    }
    
    /**
     * Check if user can delete comment
     */
    private function canDeleteComment($comment, $userType, $userId) {
        // Same rules as editing for now
        return $this->canEditComment($comment, $userType, $userId);
    }
    
    /**
     * Format comment data
     */
    private function formatComment($comment) {
        $comment['formatted_date'] = date('M j, Y \a\t g:i A', strtotime($comment['created_at']));
        $comment['relative_time'] = $this->getRelativeTime($comment['created_at']);
        $comment['is_edited'] = $comment['updated_at'] && $comment['updated_at'] !== $comment['created_at'];
        $comment['user_badge'] = $this->getUserBadge($comment['user_type']);
        $comment['avatar_url'] = $this->getUserAvatarUrl($comment['user_type'], $comment['user_id']);
        
        return $comment;
    }
    
    /**
     * Organize flat comment list into threaded structure
     */
    private function organizeComments($comments) {
        $commentMap = [];
        
        foreach ($comments as $comment) {
            $formatted = $this->formatComment($comment);
            $formatted['id'] = (int)$formatted['id'];
            $formatted['parent_id'] = $formatted['parent_id'] !== null
                ? (int)$formatted['parent_id']
                : null;
            $formatted['replies'] = [];
            $commentMap[$formatted['id']] = $formatted;
        }
        
        return $this->buildCommentThread($commentMap);
    }

    /**
     * Build nested comment tree from flat map
     *
     * @param array $commentMap Comment data indexed by comment id
     * @param int|null $parentId Current parent id
     * @return array Threaded comments
     */
    private function buildCommentThread(array &$commentMap, $parentId = null) {
        $thread = [];
        
        foreach ($commentMap as $id => $comment) {
            $isRoot = $parentId === null && $comment['parent_id'] === null;
            $isChild = $parentId !== null && $comment['parent_id'] === $parentId;
            
            if ($isRoot || $isChild) {
                unset($commentMap[$id]);
                $comment['replies'] = $this->buildCommentThread($commentMap, $comment['id']);
                $thread[] = $comment;
            }
        }
        
        return $thread;
    }
        
    
    /**
     * Get project comment count
     */
    private function getProjectCommentCount($projectId) {
        return $this->db->fetchColumn(
            "SELECT COUNT(*) FROM comments WHERE project_id = ?",
            [$projectId]
        );
    }
    
    /**
     * Get user badge for display
     */
    private function getUserBadge($userType) {
        $badges = [
            'admin' => ['text' => 'Admin', 'class' => 'badge-admin'],
            'mentor' => ['text' => 'Mentor', 'class' => 'badge-mentor'], 
            'project' => ['text' => 'Team', 'class' => 'badge-project'],
            'public' => ['text' => 'Public', 'class' => 'badge-public']
        ];
        
        return $badges[$userType] ?? ['text' => 'User', 'class' => 'badge-default'];
    }
    
    /**
     * Get user avatar URL
     */
    private function getUserAvatarUrl($userType, $userId) {
        // This could integrate with a proper avatar system
        return AppConfig::getAsset('images/default-avatar.png');
    }
    
    /**
     * Get relative time string
     */
    private function getRelativeTime($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'just now';
        if ($time < 3600) return floor($time/60) . ' min ago';
        if ($time < 86400) return floor($time/3600) . ' hr ago';
        if ($time < 2592000) return floor($time/86400) . ' days ago';
        
        return date('M j, Y', strtotime($datetime));
    }
    
    /**
     * Highlight search terms in text
     */
    private function highlightSearchTerm($text, $searchTerm) {
        return preg_replace(
            '/(' . preg_quote($searchTerm, '/') . ')/i',
            '<mark>$1</mark>',
            $text
        );
    }
    
    /**
     * Create notifications for new comments.
     *
     * If notification creation fails, the exception is caught and a warning is logged.
     * Notification failures do not prevent the comment from being created.
     */
    private function createCommentNotifications($commentId, $projectId, $userType, $userId, $parentId, $commenterName) {
        try {
            if (!class_exists('Notification')) {
                return; // Notification system not available
            }
            
            $notification = new Notification();
            
            // Notify project team
            if ($userType !== 'project') {
                $notification->create([
                    'recipient_type' => 'project',
                    'recipient_id' => $projectId,
                    'notification_type' => 'comment_added',
                    'title' => 'New Comment',
                    'message' => $commenterName . ' added a comment to your project',
                    'related_project_id' => $projectId
                ]);
            }
            
            // If it's a reply, notify the parent comment author
            if ($parentId) {
                $parentComment = $this->getComment($parentId);
                if ($parentComment && 
                    $parentComment['user_type'] !== $userType && 
                    $parentComment['user_id'] != $userId) {
                    
                    $notification->create([
                        'recipient_type' => $parentComment['user_type'],
                        'recipient_id' => $parentComment['user_id'],
                        'notification_type' => 'comment_reply',
                        'title' => 'Reply to Your Comment',
                        'message' => $commenterName . ' replied to your comment',
                        'related_project_id' => $projectId
                    ]);
                }
            }
            
        } catch (Exception $e) {
            // Don't fail comment creation if notifications fail
            logActivity('WARNING', 'Comment notification creation failed: ' . $e->getMessage());
        }
    }
}
