<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

$userId = $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập hoặc không có quyền.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$taskId = intval($input['task_id'] ?? 0);
$isAnonymous = intval($input['is_anonymous'] ?? 0);

if (!$taskId) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin nhiệm vụ.']);
    exit();
}

// Chỉ giáo viên của lớp chứa task mới được bật/tắt ẩn danh
require_once __DIR__ . '/../../config/helper_phase.php';
if (!isTeacherOfTask($taskId, (int)$userId)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền đổi cài đặt này.']);
    exit();
}
$isAnonymous = $isAnonymous ? 1 : 0;

try {
    // Check if setting exists for this task
    $stmt = $pdo->prepare("SELECT id_setting FROM setting WHERE task_id = :task_id");
    $stmt->execute([':task_id' => $taskId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $stmt = $pdo->prepare("UPDATE setting SET is_anonymous = :anon WHERE task_id = :task_id");
        $stmt->execute([':anon' => $isAnonymous, ':task_id' => $taskId]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO setting (task_id, is_anonymous) VALUES (:task_id, :anon)");
        $stmt->execute([':task_id' => $taskId, ':anon' => $isAnonymous]);
    }

    echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
} catch (PDOException $e) {
    error_log("toggle_anonymous error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu. Vui lòng thử lại.']);
}
