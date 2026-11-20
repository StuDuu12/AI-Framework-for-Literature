<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helper_evaluate.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user']['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in.']);
    exit;
}

$userId    = $_SESSION['user']['user_id'];
$userRole  = $_SESSION['user']['role'] ?? 'student'; // 'teacher' | 'student'

// Get POST data
$evaluateId = isset($_POST['evaluate_id']) ? intval($_POST['evaluate_id']) : 0;
$deliverId  = isset($_POST['deliver_id'])  ? intval($_POST['deliver_id'])  : 0;
$scores     = isset($_POST['scores'])      ? $_POST['scores']              : [];
$action     = isset($_POST['action'])      ? $_POST['action']              : 'draft';

if (!$evaluateId || !$deliverId || empty($scores)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data.']);
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
    echo json_encode(['success' => false, 'message' => 'Evaluation not found.']);
    exit;
}

if ($evaluation['user_id'] != $userId) {
    echo json_encode(['success' => false, 'message' => 'You do not have permission to edit this evaluation.']);
    exit;
}

if ($evaluation['status'] == 2) {
    echo json_encode(['success' => false, 'message' => 'Evaluation is completed and cannot be edited.']);
    exit;
}

// task_id chỉ dùng để tính lại S_Acc; phải khớp với task của deliver được đánh giá
$taskId = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

// Lấy tiêu chí từ CHÍNH bản ghi đánh giá (detail_evaluate) — không tin task_id client gửi,
// nếu không có thể gửi task_id sai để bỏ qua validate và nhồi điểm tuỳ ý.
$critStmt = $pdo->prepare("
    SELECT de.tc_id, tc.name_tc, tc.min_score, tc.max_score
    FROM detail_evaluate de
    INNER JOIN tieu_chi tc ON tc.tc_id = de.tc_id
    WHERE de.id_evaluate = :evaluate_id
");
$critStmt->execute([':evaluate_id' => $evaluateId]);
$criteria = $critStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($criteria) || !is_array($scores)) {
    echo json_encode(['success' => false, 'message' => 'No evaluation criteria configured for this evaluation.']);
    exit;
}

// Chỉ giữ điểm của đúng các tiêu chí thuộc bài đánh giá (bỏ key thừa) và kiểm tra khoảng điểm
$cleanScores = [];
foreach ($criteria as $criterion) {
    $tcId = (int)$criterion['tc_id'];

    if (!isset($scores[$tcId]) || !is_numeric($scores[$tcId])) {
        echo json_encode(['success' => false, 'message' => 'Missing score for criterion: ' . $criterion['name_tc']]);
        exit;
    }

    $score = floatval($scores[$tcId]);

    if ($score < $criterion['min_score'] || $score > $criterion['max_score']) {
        echo json_encode([
            'success' => false,
            'message' => "Score for criterion '{$criterion['name_tc']}' must be between {$criterion['min_score']} and {$criterion['max_score']}"
        ]);
        exit;
    }

    $cleanScores[$tcId] = $score;
}
$scores = $cleanScores;

// task_id dùng cho tính S_Acc phải cùng topic với deliver được đánh giá
// (task chấm điểm và task nguồn luôn nằm chung một topic)
require_once __DIR__ . '/../../config/helper_phase.php';
if ($taskId > 0 && getTopicIdOfTask($taskId) !== getTopicIdOfDeliver($deliverId)) {
    $taskId = 0; // không khớp => bỏ qua bước tính lại S_Acc thay vì tính trên task của lớp khác
}

// Determine status: 0=draft, 2=completed
$status    = ($action === 'submit') ? 2 : 0;
$isTeacher = ($userRole === 'teacher') ? 1 : 0;

// Save evaluation (also stamps is_teacher flag when submitting)
$saveData = [
    'evaluate_id' => $evaluateId,
    'scores'      => $scores,
    'status'      => $status,
    'is_teacher'  => $isTeacher,
];

$result = saveEvaluation($saveData);

if ($result) {
    // When a final submission is made (status=2), recalculate S_Acc for all
    // peer evaluators of the same response so scores stay up-to-date.
    if ($status === 2 && $taskId) {
        // Retrieve response_id from the just-saved evaluate record
        $respStmt = $pdo->prepare('SELECT response_id FROM evaluate WHERE id_evaluate = :id');
        $respStmt->execute([':id' => $evaluateId]);
        $respRow  = $respStmt->fetch(PDO::FETCH_ASSOC);
        $responseId = $respRow ? (int)$respRow['response_id'] : 0;

        if ($responseId) {
            calculateAndSaveAccuracyScores($responseId, $taskId);
        }
    }

    $message = ($status == 2) ? 'Evaluation submitted successfully!' : 'Draft saved successfully!';
    echo json_encode(['success' => true, 'message' => $message]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error saving evaluation.']);
}
