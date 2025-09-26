<?php
// api/projects/innovator.php
// Team member (innovator) management API

header('Content-Type: application/json');
require_once '../../includes/init.php';

// Require authentication
if (!$auth->isValidSession()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$userType = $auth->getUserType();
$userId = $auth->getUserId();

try {
    // Handle POST requests (add/remove team member)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST; // Fallback to form data
        }

        // Validate CSRF token
        if (!isset($input['csrf_token']) || !$auth->validateCSRFToken($input['csrf_token'])) {
            throw new Exception('Invalid security token');
        }

        $action = $input['action'] ?? '';
        $projectId = intval($input['project_id'] ?? 0);

        if (!$projectId) {
            throw new Exception('Project ID is required');
        }

        // Check permissions
        if ($userType === USER_TYPE_PROJECT && $userId != $projectId) {
            throw new Exception('Access denied');
        } elseif ($userType === USER_TYPE_MENTOR) {
            if (!isMentorAssignedToProject($userId, $projectId)) {
                throw new Exception('Access denied');
            }
        } elseif ($userType !== USER_TYPE_ADMIN) {
            throw new Exception('Access denied');
        }

        switch ($action) {
            case 'add':
                // Add new team member
                $validator = new Validator($input);
                $validator->required('name', 'Name is required')
                         ->required('email', 'Email is required')
                         ->email('email')
                         ->required('role', 'Role is required');

                if (!$validator->isValid()) {
                    throw new Exception('Please fill in all required fields correctly');
                }

                // Check if email already exists in this project
                $existing = $database->getRow(
                    "SELECT pi_id FROM project_innovators 
                     WHERE project_id = ? AND email = ? AND is_active = 1",
                    [$projectId, $input['email']]
                );

                if ($existing) {
                    throw new Exception('A team member with this email already exists in this project');
                }

                // Add team member
                $memberData = [
                    'project_id' => $projectId,
                    'name' => trim($input['name']),
                    'email' => trim($input['email']),
                    'role' => trim($input['role']),
                    'level_of_experience' => !empty($input['level_of_experience']) ? trim($input['level_of_experience']) : null,
                    'phone' => !empty($input['phone']) ? trim($input['phone']) : null,
                    'linkedin_url' => !empty($input['linkedin_url']) ? trim($input['linkedin_url']) : null,
                    'bio' => !empty($input['bio']) ? trim($input['bio']) : null,
                    'added_by_type' => $userType,
                    'added_by_id' => $userId,
                    'is_active' => 1
                ];

                $memberId = $database->insert('project_innovators', $memberData);

                if (!$memberId) {
                    throw new Exception('Failed to add team member');
                }

                // Log activity
                logActivity(
                    $userType,
                    $userId,
                    'team_member_added',
                    "Added team member: {$memberData['name']} to project",
                    $projectId,
                    ['member_id' => $memberId, 'member_email' => $memberData['email']]
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'Team member added successfully',
                    'member_id' => $memberId
                ]);
                break;

            case 'remove':
                // Remove team member (soft delete)
                $memberId = intval($input['member_id'] ?? 0);

                if (!$memberId) {
                    throw new Exception('Member ID is required');
                }

                // Get member details
                $member = $database->getRow(
                    "SELECT * FROM project_innovators WHERE pi_id = ? AND project_id = ?",
                    [$memberId, $projectId]
                );

                if (!$member) {
                    throw new Exception('Team member not found');
                }

                // Only admin or assigned mentors can remove team members
                if ($userType === USER_TYPE_PROJECT) {
                    throw new Exception('Projects cannot remove team members. Please contact an admin or mentor.');
                }

                // Soft delete
                $database->update(
                    'project_innovators',
                    ['is_active' => 0],
                    'pi_id = ?',
                    [$memberId]
                );

                // Log activity
                logActivity(
                    $userType,
                    $userId,
                    'team_member_removed',
                    "Removed team member: {$member['name']} from project",
                    $projectId,
                    ['member_id' => $memberId, 'member_email' => $member['email']]
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'Team member removed successfully'
                ]);
                break;

            case 'update':
                // Update team member info
                $memberId = intval($input['member_id'] ?? 0);

                if (!$memberId) {
                    throw new Exception('Member ID is required');
                }

                $member = $database->getRow(
                    "SELECT * FROM project_innovators WHERE pi_id = ? AND project_id = ?",
                    [$memberId, $projectId]
                );

                if (!$member) {
                    throw new Exception('Team member not found');
                }

                $updateData = [
                    'name' => trim($input['name'] ?? $member['name']),
                    'role' => trim($input['role'] ?? $member['role']),
                    'level_of_experience' => isset($input['level_of_experience']) ? trim($input['level_of_experience']) : $member['level_of_experience'],
                    'phone' => isset($input['phone']) ? trim($input['phone']) : $member['phone'],
                    'linkedin_url' => isset($input['linkedin_url']) ? trim($input['linkedin_url']) : $member['linkedin_url'],
                    'bio' => isset($input['bio']) ? trim($input['bio']) : $member['bio']
                ];

                $database->update(
                    'project_innovators',
                    $updateData,
                    'pi_id = ?',
                    [$memberId]
                );

                logActivity(
                    $userType,
                    $userId,
                    'team_member_updated',
                    "Updated team member: {$updateData['name']}",
                    $projectId
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'Team member updated successfully'
                ]);
                break;

            default:
                throw new Exception('Invalid action');
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get team members for a project
        $projectId = intval($_GET['project_id'] ?? 0);

        if (!$projectId) {
            throw new Exception('Project ID is required');
        }

        // Check permissions
        if ($userType === USER_TYPE_PROJECT && $userId != $projectId) {
            throw new Exception('Access denied');
        }

        $members = $database->getRows("
            SELECT * FROM project_innovators
            WHERE project_id = ? AND is_active = 1
            ORDER BY added_at ASC
        ", [$projectId]);

        echo json_encode([
            'success' => true,
            'data' => $members,
            'count' => count($members)
        ]);

    } else {
        throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
    error_log('Team member management error: ' . $e->getMessage());
}