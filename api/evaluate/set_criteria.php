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
    echo json_encode(['success' => false, 'message' => 'Please log in']);
    exit;
}

$userId = $_SESSION['user']['user_id'];
$userRole = $_SESSION['user']['role'];

// Only teachers can configure criteria
if ($userRole !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Only teachers have permission to configure criteria']);
    exit;
}

try {
    $taskId = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
    $nhomTcId = isset($_POST['nhom_tc_id']) ? intval($_POST['nhom_tc_id']) : 0;

    if (!$taskId || !$nhomTcId) {
        echo json_encode(['success' => false, 'message' => 'Missing task_id or nhom_tc_id information']);
        exit;
    }

    // Get task info to verify permissions
    $taskInfo = getInfoTask($taskId);
    if (!$taskInfo) {
        echo json_encode(['success' => false, 'message' => 'Task does not exist']);
        exit;
    }

    // Check if user is teacher of this class
    if (!isTeacherInClass($taskInfo['class_id'], $userId)) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to configure this task']);
        exit;
    }

    // Check if any evaluations have been done
    if (hasTaskEvaluations($taskId)) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot change the criteria group because evaluations have already been performed'
        ]);
        exit;
    }

    // Set/update criteria group
    $result = setTaskCriteriaGroup($taskId, $nhomTcId);

    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Criteria group configuration saved successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while saving configuration'
        ]);
    }

} catch (Exception $e) {
    error_log("Error in set_criteria.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'System error: ' . $e->getMessage()
    ]);
}
