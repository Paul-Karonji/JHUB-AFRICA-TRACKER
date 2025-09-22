<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Mentor Management Class
 */

if (!defined('JHUB_ACCESS')) {
    die('Direct access not permitted');
}

class Mentor {
    /** @var Database */
    private $db;

    /** @var Auth */
    private $auth;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->auth = new Auth();
    }

    /**
     * Register a new mentor (admin only).
     */
    public function registerMentor(array $data, $adminId) {
        try {
            if (!$this->auth->isAuthenticated() || $this->auth->getUserType() !== 'admin') {
                throw new Exception('Only administrators can register mentors');
            }

            if (!Validator::validateMentorData($data)) {
                throw new Exception(Validator::getFirstError());
            }

            if ($this->emailExists($data['email'])) {
                throw new Exception('Mentor with this email already exists');
            }

            $stmt = $this->db->prepare("
                INSERT INTO mentors (name, email, password, bio, expertise, created_at, created_by, phone, linkedin_url, years_experience)
                VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)
            ");

            $this->db->execute($stmt, [
                trim($data['name']),
                strtolower(trim($data['email'])),
                Auth::hashPassword($data['password']),
                trim($data['bio']),
                trim($data['expertise']),
                $adminId,
                $data['phone'] ?? null,
                $data['linkedin_url'] ?? null,
                $data['years_experience'] ?? null
            ]);

            $mentorId = $this->db->lastInsertId();

            logActivity('INFO', 'Mentor registered', [
                'mentor_id' => $mentorId,
                'created_by' => $adminId
            ]);

            return ['success' => true, 'mentor_id' => $mentorId, 'message' => SUCCESS_MENTOR_REGISTERED];
        } catch (Exception $e) {
            logActivity('ERROR', 'Register mentor error: ' . $e->getMessage(), $data);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update mentor profile (admin or mentor owner).
     */
    public function updateMentor($mentorId, array $data) {
        try {
            if (!$this->canManageMentor($mentorId)) {
                throw new Exception('You are not allowed to update this mentor');
            }

            $allowed = ['name', 'bio', 'expertise', 'phone', 'linkedin_url', 'years_experience', 'is_active'];
            $clean = Validator::cleanData($data, $allowed);

            if (isset($clean['name']) && !Validator::name($clean['name'])) {
                throw new Exception(Validator::getFirstError());
            }

            if (isset($clean['bio']) && !Validator::text($clean['bio'], 10, 1500, 'Bio')) {
                throw new Exception(Validator::getFirstError());
            }

            if (empty($clean)) {
                return ['success' => true, 'message' => 'No changes detected'];
            }

            $set = [];
            $params = [];
            foreach ($clean as $column => $value) {
                $set[] = "$column = ?";
                $params[] = $value;
            }
            $params[] = $mentorId;

            $sql = 'UPDATE mentors SET ' . implode(', ', $set) . ', updated_at = NOW() WHERE id = ?';
            $stmt = $this->db->prepare($sql);
            $this->db->execute($stmt, $params);

            logActivity('INFO', 'Mentor updated', ['mentor_id' => $mentorId]);

            return ['success' => true, 'message' => 'Mentor updated successfully'];
        } catch (Exception $e) {
            logActivity('ERROR', 'Update mentor error: ' . $e->getMessage(), ['mentor_id' => $mentorId]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get single mentor details.
     */
    public function getMentor($mentorId) {
        try {
            $stmt = $this->db->prepare("
                SELECT m.id, m.name, m.email, m.bio, m.expertise, m.is_active,
                       m.created_at, m.updated_at, m.phone, m.linkedin_url,
                       m.years_experience,
                       COUNT(pm.project_id) as project_count
                FROM mentors m
                LEFT JOIN project_mentors pm ON m.id = pm.mentor_id AND pm.is_active = 1
                WHERE m.id = ?
                GROUP BY m.id
            ");

            $this->db->execute($stmt, [$mentorId]);
            $mentor = $stmt->fetch();

            if (!$mentor) {
                return ['success' => false, 'message' => 'Mentor not found'];
            }

            return ['success' => true, 'mentor' => $mentor];
        } catch (Exception $e) {
            logActivity('ERROR', 'Get mentor error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load mentor'];
        }
    }

    /**
     * Retrieve mentors with pagination/search.
     */
    public function getMentors($filters = []) {
        try {
            $defaults = [
                'page' => 1,
                'per_page' => AppConfig::MENTORS_PER_PAGE,
                'search' => null,
                'active_only' => false
            ];
            $filters = array_merge($defaults, $filters);

            $where = [];
            $params = [];

            if ($filters['active_only']) {
                $where[] = 'm.is_active = 1';
            }

            if (!empty($filters['search'])) {
                $where[] = '(m.name LIKE ? OR m.email LIKE ? OR m.expertise LIKE ?)';
                $query = '%' . $filters['search'] . '%';
                $params = array_merge($params, [$query, $query, $query]);
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $countSql = 'SELECT COUNT(*) FROM mentors m ' . $whereSql;
            $total = $this->db->fetchColumn($countSql, $params);

            $offset = ($filters['page'] - 1) * $filters['per_page'];
            $sql = "
                SELECT m.id, m.name, m.email, m.expertise, m.created_at, m.is_active,
                       COUNT(pm.project_id) as project_assignments
                FROM mentors m
                LEFT JOIN project_mentors pm ON m.id = pm.mentor_id AND pm.is_active = 1
                $whereSql
                GROUP BY m.id
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?
            ";

            $queryParams = array_merge($params, [$filters['per_page'], $offset]);
            $stmt = $this->db->prepare($sql);
            $this->db->execute($stmt, $queryParams);
            $mentors = $stmt->fetchAll();

            return [
                'success' => true,
                'mentors' => $mentors,
                'pagination' => [
                    'total' => (int) $total,
                    'page' => (int) $filters['page'],
                    'per_page' => (int) $filters['per_page'],
                    'total_pages' => $filters['per_page'] > 0 ? (int) ceil($total / $filters['per_page']) : 1
                ]
            ];
        } catch (Exception $e) {
            logActivity('ERROR', 'Get mentors error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load mentors'];
        }
    }

    /**
     * Assign mentor to project.
     */
    public function assignToProject($mentorId, $projectId, $selfAssigned = true) {
        try {
            $project = new Project();
            $result = $project->assignMentor($projectId, $mentorId, $selfAssigned);
            if (!$result['success']) {
                throw new Exception($result['message']);
            }
            return $result;
        } catch (Exception $e) {
            logActivity('ERROR', 'Mentor assign error: ' . $e->getMessage(), [
                'mentor_id' => $mentorId,
                'project_id' => $projectId
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Remove mentor from project.
     */
    public function removeFromProject($mentorId, $projectId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE project_mentors SET is_active = 0, removed_at = NOW()
                WHERE mentor_id = ? AND project_id = ?
            ");
            $this->db->execute($stmt, [$mentorId, $projectId]);
            return ['success' => true, 'message' => 'Mentor removed from project'];
        } catch (Exception $e) {
            logActivity('ERROR', 'Mentor removal error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to remove mentor'];
        }
    }

    /**
     * Get mentor assigned projects.
     */
    public function getMentorProjects($mentorId) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.id, p.name, p.status, p.current_stage, p.current_percentage,
                       pm.assigned_at, pm.is_lead
                FROM project_mentors pm
                JOIN projects p ON pm.project_id = p.id
                WHERE pm.mentor_id = ? AND pm.is_active = 1
                ORDER BY pm.assigned_at DESC
            ");
            $this->db->execute($stmt, [$mentorId]);
            return ['success' => true, 'projects' => $stmt->fetchAll()];
        } catch (Exception $e) {
            logActivity('ERROR', 'Get mentor projects error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load projects'];
        }
    }

    /**
     * Get available projects for mentor to join.
     */
    public function getAvailableProjects($mentorId) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.id, p.name, p.status, p.current_stage, p.current_percentage,
                       COUNT(pm.mentor_id) as mentor_count
                FROM projects p
                LEFT JOIN project_mentors pm ON p.id = pm.project_id AND pm.is_active = 1
                WHERE p.status = 'active'
                  AND NOT EXISTS (
                      SELECT 1 FROM project_mentors
                      WHERE project_id = p.id AND mentor_id = ? AND is_active = 1
                  )
                GROUP BY p.id
                ORDER BY p.created_at DESC
            ");
            $this->db->execute($stmt, [$mentorId]);
            return ['success' => true, 'projects' => $stmt->fetchAll()];
        } catch (Exception $e) {
            logActivity('ERROR', 'Get available projects error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to load projects'];
        }
    }

    private function emailExists($email) {
        return (bool) $this->db->fetchColumn(
            'SELECT COUNT(*) FROM mentors WHERE email = ?',
            [strtolower(trim($email))]
        );
    }

    private function canManageMentor($mentorId) {
        if (!$this->auth->isAuthenticated()) {
            return false;
        }

        if ($this->auth->getUserType() === 'admin') {
            return true;
        }

        return $this->auth->getUserType() === 'mentor' && $this->auth->getUserId() == $mentorId;
    }
}
?>
