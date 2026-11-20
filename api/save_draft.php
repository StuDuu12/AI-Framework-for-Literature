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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$task_id = intval($input['task_id'] ?? 0);
$deliver_id = intval($input['deliver_id'] ?? 0);
$draft_data = $input['draft_data'] ?? null;

if (!$task_id || !$deliver_id || $draft_data === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đủ các mục bắt buộc.']);
    exit;
}

if (!validateUserDeliverAccess($deliver_id, $user_id)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập.']);
    exit;
}

try {
    $json = json_encode($draft_data, JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare("
        INSERT INTO analysis_draft (user_id, task_id, deliver_id, draft_data)
        VALUES (:uid, :tid, :did, :data)
        ON DUPLICATE KEY UPDATE draft_data = :data2, updated_at = CURRENT_TIMESTAMP
    ");
    $stmt->execute([
        ':uid' => $user_id,
        ':tid' => $task_id,
        ':did' => $deliver_id,
        ':data' => $json,
        ':data2' => $json
    ]);
    echo json_encode(['success' => true, 'message' => 'Đã lưu nháp.', 'saved_at' => date('c')]);
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "analysis_draft") !== false) {
        http_response_code(503);
        echo json_encode(['success' => false, 'message' => 'Chưa cài bảng lưu nháp. Hãy chạy database/add_analysis_features.sql.']);
        exit;
    }
    error_log('save_draft: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không lưu được bản nháp.']);
}
