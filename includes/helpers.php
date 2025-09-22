<?php
/**
 * View helper functions for dashboards and public pages.
 */

if (!defined('JHUB_ACCESS')) {
    die('Direct access not permitted');
}

function formatStageName($stage) {
    $info = DatabaseConfig::getStageInfo((int) $stage);
    return $info ? $info['name'] : 'Stage ' . (int) $stage;
}

function formatStageProgress($stage, $percentage) {
    $info = DatabaseConfig::getStageInfo((int) $stage);
    $base = $info ? $info['percentage'] : 0;
    return min(100, $base + (int) $percentage);
}

function stageBadgeClass($stage) {
    switch ((int) $stage) {
        case 1: return 'badge-primary';
        case 2: return 'badge-info';
        case 3: return 'badge-success';
        case 4: return 'badge-warning';
        case 5: return 'badge-secondary';
        case 6: return 'badge-dark';
        default: return 'badge-light';
    }
}

function statusBadgeClass($status) {
    $status = strtolower($status);
    $map = [
        'active' => 'badge-success',
        'completed' => 'badge-info',
        'terminated' => 'badge-danger',
        'draft' => 'badge-warning'
    ];
    return $map[$status] ?? 'badge-secondary';
}

function truncateText($text, $limit = 120) {
    $text = trim($text);
    if (strlen($text) <= $limit) {
        return $text;
    }
    return substr($text, 0, $limit - 3) . '...';
}

function formatDate($date, $format = 'M j, Y') {
    if (!$date) {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

function formatRelativeTime($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time / 60) . ' min ago';
    if ($time < 86400) return floor($time / 3600) . ' hr ago';
    if ($time < 2592000) return floor($time / 86400) . ' days ago';
    return date('M j, Y', strtotime($datetime));
}

function renderProgressBar($percentage) {
    $percentage = max(0, min(100, (int) $percentage));
    $class = 'bg-success';
    if ($percentage < 30) {
        $class = 'bg-danger';
    } elseif ($percentage < 60) {
        $class = 'bg-warning';
    }
    return sprintf(
        '<div class="progress"><div class="progress-bar %s" role="progressbar" style="width:%d%%">%d%%</div></div>',
        $class,
        $percentage,
        $percentage
    );
}

function renderBadge($text, $class) {
    return '<span class="badge ' . e($class) . '">' . e($text) . '</span>';
}

function getAvatarUrl($userType, $userId) {
    $auth = new Auth();
    return $auth->getUserAvatarUrl($userType, $userId);
}

function displayNotificationIcon($type) {
    $map = AppConfig::NOTIFICATION_TYPES;
    return $map[$type]['icon'] ?? 'dYT^';
}
?>
