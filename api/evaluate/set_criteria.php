<?php
// Prevent any output before JSON
ob_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helper_account.php';
require_once __DIR__ . '/../../config/helper_class.php';
require_once __DIR__ . '/../../config/helper_homework.php';
require_once __DIR__ . '/../../config/helper_phase.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear any output buffer and set JSON header
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// Check login
if (!is_login()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit;
}

$userId = $_SESSION['user']['user_id'];
$userRole = $_SESSION['user']['role'];

// Only teachers can configure criteria
if ($userRole !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Chỉ giáo viên được thiết lập tiêu chí.']);
    exit;
}

try {
    $taskId = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
    $nhomTcId = isset($_POST['nhom_tc_id']) ? intval($_POST['nhom_tc_id']) : 0;

    if (!$taskId || !$nhomTcId) {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin nhiệm vụ hoặc bộ tiêu chí.']);
        exit;
    }

    // Get task info to verify permissions
    $taskInfo = getInfoTask($taskId);
    if (!$taskInfo) {
        echo json_encode(['success' => false, 'message' => 'Nhiệm vụ không tồn tại.']);
        exit;
    }

    // Check if user is teacher of this class
    if (!isTeacherInClass($taskInfo['class_id'], $userId)) {
        echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thiết lập nhiệm vụ này.']);
        exit;
    }

    // Check if any evaluations have been done
    if (hasTaskEvaluations($taskId)) {
        echo json_encode([
            'success' => false,
            'message' => 'Đã có bài được chấm nên không đổi được bộ tiêu chí.'
        ]);
        exit;
    }

    // Set/update criteria group
    $result = setTaskCriteriaGroup($taskId, $nhomTcId);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Đã lưu cấu hình bộ tiêu chí.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không lưu được cấu hình. Vui lòng thử lại.'
        ]);
    }

} catch (Exception $e) {
    error_log("Error in set_criteria.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . client_error_message($e)
    ]);
}
