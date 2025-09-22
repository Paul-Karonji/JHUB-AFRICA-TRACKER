<?php
/**
 * JHUB AFRICA PROJECT TRACKER
 * Notification Management Class
 *
 * Handles creation, retrieval, and management of user notifications across
 * the platform. Notifications are persisted to the database and exposed via
 * dashboards and API endpoints.
 */

if (!defined('JHUB_ACCESS')) {
    die('Direct access not permitted');
}

class Notification {
    /** @var Database */
    private $db;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Create a notification record.
     *
     * Expected keys: recipient_type, recipient_id (nullable for broadcast),
     * notification_type, title, message, related_project_id, related_entity_id,
     * metadata (array|optional).
     */
    public function create(array $data) {
        try {
            $payload = $this->validateAndPreparePayload($data);

            $stmt = $this->db->prepare("
                INSERT INTO notifications (
                    recipient_type,
                    recipient_id,
                    notification_type,
                    title,
                    message,
                    related_project_id,
                    related_entity_type,
                    related_entity_id,
                    metadata,
                    is_read,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
            ");

            $this->db->execute($stmt, [
                $payload['recipient_type'],
                $payload['recipient_id'],
                $payload['notification_type'],
                $payload['title'],
                $payload['message'],
                $payload['related_project_id'],
                $payload['related_entity_type'],
                $payload['related_entity_id'],
                $payload['metadata']
            ]);

            $notificationId = $this->db->lastInsertId();

            logActivity('INFO', 'Notification created', [
                'notification_id' => $notificationId,
                'recipient_type' => $payload['recipient_type'],
                'recipient_id' => $payload['recipient_id'],
                'notification_type' => $payload['notification_type']
            ]);

            return [
                'success' => true,
                'notification_id' => $notificationId
            ];
        } catch (Exception $e) {
            logActivity('ERROR', 'Notification create error: ' . $e->getMessage(), $data);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Fetch notifications for a recipient.
     */
    public function getNotifications($recipientType, $recipientId = null, $options = []) {
        try {
            $filters = array_merge([
                'limit' => 20,
                'offset' => 0,
                'unread_only' => false
            ], $options);

            $params = [$recipientType];
            $where = ['recipient_type = ?'];

            if ($recipientId !== null) {
                $where[] = '(recipient_id = ? OR recipient_id IS NULL)';
                $params[] = $recipientId;
            } else {
                $where[] = 'recipient_id IS NULL';
            }

            if (!empty($filters['unread_only'])) {
                $where[] = 'is_read = 0';
            }

            $sql = "
                SELECT id, recipient_type, recipient_id, notification_type,
                       title, message, related_project_id, related_entity_type,
                       related_entity_id, metadata, is_read, created_at
                FROM notifications
                WHERE " . implode(' AND ', $where) . "
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ";

            $params[] = (int) $filters['limit'];
            $params[] = (int) $filters['offset'];

            $stmt = $this->db->prepare($sql);
            $this->db->execute($stmt, $params);
            $notifications = $stmt->fetchAll();

            foreach ($notifications as &$notification) {
                if (!empty($notification['metadata'])) {
                    $decoded = json_decode($notification['metadata'], true);
                    $notification['metadata'] = $decoded ?? $notification['metadata'];
                }
            }

            return [
                'success' => true,
                'notifications' => $notifications,
                'total_unread' => $this->countUnread($recipientType, $recipientId)
            ];
        } catch (Exception $e) {
            logActivity('ERROR', 'Notification fetch error: ' . $e->getMessage(), [
                'recipient_type' => $recipientType,
                'recipient_id' => $recipientId
            ]);
            return ['success' => false, 'message' => 'Failed to load notifications'];
        }
    }

    /**
     * Mark single notification as read.
     */
    public function markAsRead($notificationId, $recipientType, $recipientId = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE notifications
                SET is_read = 1, read_at = NOW()
                WHERE id = ? AND recipient_type = ?
                  AND (recipient_id = ? OR recipient_id IS NULL)
            ");

            $this->db->execute($stmt, [
                $notificationId,
                $recipientType,
                $recipientId
            ]);

            return ['success' => true];
        } catch (Exception $e) {
            logActivity('ERROR', 'Notification markAsRead error: ' . $e->getMessage(), [
                'notification_id' => $notificationId
            ]);
            return ['success' => false, 'message' => 'Failed to update notification'];
        }
    }

    /**
     * Mark all notifications for user as read.
     */
    public function markAllAsRead($recipientType, $recipientId = null) {
        try {
            $params = [$recipientType];
            $where = ['recipient_type = ?'];

            if ($recipientId !== null) {
                $where[] = '(recipient_id = ? OR recipient_id IS NULL)';
                $params[] = $recipientId;
            } else {
                $where[] = 'recipient_id IS NULL';
            }

            $sql = "
                UPDATE notifications
                SET is_read = 1, read_at = NOW()
                WHERE " . implode(' AND ', $where) . "
            ";

            $stmt = $this->db->prepare($sql);
            $this->db->execute($stmt, $params);

            return ['success' => true];
        } catch (Exception $e) {
            logActivity('ERROR', 'Notification markAllAsRead error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update notifications'];
        }
    }

    /**
     * Delete notification for the given recipient.
     */
    public function delete($notificationId, $recipientType, $recipientId = null) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM notifications
                WHERE id = ? AND recipient_type = ?
                  AND (recipient_id = ? OR recipient_id IS NULL)
            ");

            $this->db->execute($stmt, [
                $notificationId,
                $recipientType,
                $recipientId
            ]);

            return ['success' => true];
        } catch (Exception $e) {
            logActivity('ERROR', 'Notification delete error: ' . $e->getMessage(), [
                'notification_id' => $notificationId
            ]);
            return ['success' => false, 'message' => 'Failed to delete notification'];
        }
    }

    /**
     * Count unread notifications for a recipient.
     */
    public function countUnread($recipientType, $recipientId = null) {
        try {
            $params = [$recipientType];
            $where = ['recipient_type = ?'];

            if ($recipientId !== null) {
                $where[] = '(recipient_id = ? OR recipient_id IS NULL)';
                $params[] = $recipientId;
            } else {
                $where[] = 'recipient_id IS NULL';
            }

            $sql = "
                SELECT COUNT(*) FROM notifications
                WHERE " . implode(' AND ', $where) . " AND is_read = 0
            ";

            return (int) $this->db->fetchColumn($sql, $params);
        } catch (Exception $e) {
            logActivity('ERROR', 'Notification countUnread error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Helper to sanitize payload.
     */
    private function validateAndPreparePayload(array $data) {
        $recipientType = $data['recipient_type'] ?? null;
        if (!$recipientType || !in_array($recipientType, ['admin', 'mentor', 'project', 'public'])) {
            throw new Exception('Invalid recipient type');
        }

        if (!empty($data['recipient_id']) && !is_numeric($data['recipient_id'])) {
            throw new Exception('Invalid recipient ID');
        }

        $notificationType = $data['notification_type'] ?? 'general';
        $title = trim($data['title'] ?? 'Notification');
        $message = trim($data['message'] ?? '');

        if ($message === '') {
            throw new Exception('Notification message is required');
        }

        $metadata = null;
        if (isset($data['metadata'])) {
            $metadata = is_array($data['metadata']) ? json_encode($data['metadata']) : (string) $data['metadata'];
        }

        return [
            'recipient_type' => $recipientType,
            'recipient_id' => $data['recipient_id'] ?? null,
            'notification_type' => $notificationType,
            'title' => $title,
            'message' => $message,
            'related_project_id' => $data['related_project_id'] ?? null,
            'related_entity_type' => $data['related_entity_type'] ?? null,
            'related_entity_id' => $data['related_entity_id'] ?? null,
            'metadata' => $metadata
        ];
    }
}
?>
