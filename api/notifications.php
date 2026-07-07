<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_notifications.php';

$user_id = $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $lang = appLanguageFromRequest();
    ensureNotificationsTable($pdo);
    upgradeLegacyNotifications($pdo, (int)$user_id);
    syncAssignmentNotifications($pdo, (int)$user_id);

    $stored = localizeNotificationsList(getNotifications($pdo, (int)$user_id, 20), $lang);
    $dynamic = getPendingEvaluationAlerts($pdo, (int)$user_id, $lang);
    $deadlineAlerts = getDeadlineReminderAlerts($pdo, (int)$user_id, $lang);
    $unread = getUnreadNotificationCount($pdo, (int)$user_id);
    // Dynamic eval + deadline alerts are informational — badge counts stored notifications only

    $all = array_merge($dynamic, $deadlineAlerts, $stored);
    usort($all, static function ($a, $b) {
        return strtotime($b['created_at'] ?? '') <=> strtotime($a['created_at'] ?? '');
    });

    echo json_encode([
        'success' => true,
        'notifications' => array_slice($all, 0, 25),
        'unread_count' => $unread,
    ]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'mark_read';
    if ($action === 'mark_all_read') {
        markAllNotificationsRead($pdo, (int)$user_id);
        echo json_encode(['success' => true]);
        exit;
    }
    $id = (int)($input['id'] ?? 0);
    if ($id > 0) {
        markNotificationRead($pdo, (int)$user_id, $id);
    }
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
