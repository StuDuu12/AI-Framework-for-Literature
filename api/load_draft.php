<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_task.php';

$user_id = $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập hoặc không có quyền.']);
    exit;
}

$task_id = intval($_GET['task_id'] ?? 0);
$deliver_id = intval($_GET['deliver_id'] ?? 0);

if (!$task_id || !$deliver_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin nhiệm vụ hoặc bài giao.']);
    exit;
}

if (!validateUserDeliverAccess($deliver_id, $user_id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT draft_data, updated_at FROM analysis_draft
        WHERE user_id = :uid AND task_id = :tid AND deliver_id = :did
        LIMIT 1
    ");
    $stmt->execute([':uid' => $user_id, ':tid' => $task_id, ':did' => $deliver_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['success' => true, 'has_draft' => false]);
        exit;
    }
    echo json_encode([
        'success' => true,
        'has_draft' => true,
        'draft_data' => json_decode($row['draft_data'], true),
        'updated_at' => $row['updated_at']
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => true, 'has_draft' => false]);
}
