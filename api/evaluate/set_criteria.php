<?php
// Prevent any output before JSON
ob_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helper_account.php';
require_once __DIR__ . '/../../config/helper_class.php';
require_once __DIR__ . '/../../config/helper_homework.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any output buffer and set JSON header
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// Check login
if (!is_login()) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập']);
    exit;
}

$userId = $_SESSION['user']['user_id'];
$userRole = $_SESSION['user']['role'];

// Only teachers can configure criteria
if ($userRole !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Chỉ giáo viên mới có quyền cấu hình tiêu chí']);
    exit;
}

try {
    $taskId = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
    $nhomTcId = isset($_POST['nhom_tc_id']) ? intval($_POST['nhom_tc_id']) : 0;

    if (!$taskId || !$nhomTcId) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin task_id hoặc nhom_tc_id']);
        exit;
    }

    // Get task info to verify permissions
    $taskInfo = getInfoTask($taskId);
    if (!$taskInfo) {
        echo json_encode(['success' => false, 'message' => 'Task không tồn tại']);
        exit;
    }

    // Check if user is teacher of this class
    if (!isTeacherInClass($taskInfo['class_id'], $userId)) {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền cấu hình task này']);
        exit;
    }

    // Check if any evaluations have been done
    if (hasTaskEvaluations($taskId)) {
        echo json_encode([
            'success' => false,
            'message' => 'Không thể thay đổi bộ tiêu chí vì đã có bài chấm được thực hiện'
        ]);
        exit;
    }

    // Set/update criteria group
    $result = setTaskCriteriaGroup($taskId, $nhomTcId);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Đã lưu cấu hình bộ tiêu chí thành công'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra khi lưu cấu hình'
        ]);
    }

} catch (Exception $e) {
    error_log("Error in set_criteria.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}
