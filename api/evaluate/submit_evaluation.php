<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helper_evaluate.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user']['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit;
}

$userId = $_SESSION['user']['user_id'];

// Get POST data
$evaluateId = isset($_POST['evaluate_id']) ? intval($_POST['evaluate_id']) : 0;
$deliverId = isset($_POST['deliver_id']) ? intval($_POST['deliver_id']) : 0;
$scores = isset($_POST['scores']) ? $_POST['scores'] : [];
$action = isset($_POST['action']) ? $_POST['action'] : 'draft';

if (!$evaluateId || !$deliverId || empty($scores)) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

// Verify user owns this evaluation
global $pdo;
$checkStmt = $pdo->prepare("
    SELECT id_evaluate, user_id, status
    FROM evaluate
    WHERE id_evaluate = :evaluate_id AND deliver_id = :deliver_id
");

$checkStmt->execute([
    ':evaluate_id' => $evaluateId,
    ':deliver_id' => $deliverId
]);

$evaluation = $checkStmt->fetch(PDO::FETCH_ASSOC);

if (!$evaluation) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy đánh giá.']);
    exit;
}

if ($evaluation['user_id'] != $userId) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền chỉnh sửa đánh giá này.']);
    exit;
}

if ($evaluation['status'] == 2) {
    echo json_encode(['success' => false, 'message' => 'Đánh giá đã hoàn thành, không thể chỉnh sửa.']);
    exit;
}

// Get criteria to validate scores
$taskId = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
$criteria = getEvaluationCriteria($taskId);

// Validate each score
foreach ($criteria as $criterion) {
    $tcId = $criterion['tc_id'];

    if (!isset($scores[$tcId])) {
        echo json_encode(['success' => false, 'message' => 'Thiếu điểm cho tiêu chí: ' . $criterion['name_tc']]);
        exit;
    }

    $score = floatval($scores[$tcId]);

    if ($score < $criterion['min_score'] || $score > $criterion['max_score']) {
        echo json_encode([
            'success' => false,
            'message' => "Điểm cho tiêu chí '{$criterion['name_tc']}' phải từ {$criterion['min_score']} đến {$criterion['max_score']}"
        ]);
        exit;
    }
}

// Determine status: 0=draft, 2=completed
$status = ($action === 'submit') ? 2 : 0;

// Save evaluation
$saveData = [
    'evaluate_id' => $evaluateId,
    'scores' => $scores,
    'status' => $status
];

$result = saveEvaluation($saveData);

if ($result) {
    $message = ($status == 2) ? 'Hoàn thành đánh giá thành công!' : 'Lưu nháp thành công!';
    echo json_encode(['success' => true, 'message' => $message]);
} else {
    echo json_encode(['success' => false, 'message' => 'Lỗi khi lưu đánh giá.']);
}
