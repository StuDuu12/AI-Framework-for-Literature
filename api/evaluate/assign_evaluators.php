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

// Only teachers can assign evaluators
if ($userRole !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Only teachers have permission to assign evaluators']);
    exit;
}

try {
    $taskId = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;
    $assignmentsJson = isset($_POST['assignments']) ? $_POST['assignments'] : '';
    $assignments = [];

    if ($assignmentsJson) {
        $assignments = json_decode($assignmentsJson, true);
    }

    if (!$taskId) {
        echo json_encode(['success' => false, 'message' => 'Missing task_id information']);
        exit;
    }

    if (empty($assignments) || !is_array($assignments)) {
        echo json_encode(['success' => false, 'message' => 'Missing evaluation assignment information']);
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
        echo json_encode(['success' => false, 'message' => 'You do not have permission to assign this task']);
        exit;
    }

    // Check if criteria group is configured
    $criteriaGroup = getTaskCriteriaGroup($taskId);
    if (!$criteriaGroup) {
        echo json_encode([
            'success' => false,
            'message' => 'Please configure the evaluation criteria group before assigning evaluators'
        ]);
        exit;
    }

    $successCount = 0;
    $errors = [];

    // Process each assignment
    foreach ($assignments as $assignment) {
        $responseId = isset($assignment['response_id']) ? intval($assignment['response_id']) : 0;
        $evaluatorType = isset($assignment['evaluator_type']) ? $assignment['evaluator_type'] : '';
        $evaluatorValue = isset($assignment['evaluator_value']) ? intval($assignment['evaluator_value']) : 0;

        if (!$responseId || !in_array($evaluatorType, ['user', 'group']) || !$evaluatorValue) {
            $errors[] = "Invalid information for response_id: $responseId";
            continue;
        }

        $result = assignEvaluator($taskId, $responseId, $evaluatorType, $evaluatorValue, $userId);

        if ($result === true) {
            $successCount++;
        } else {
            $errors[] = "Response $responseId: $result";
        }
    }

    if ($successCount > 0) {
        $message = "Successfully assigned $successCount evaluation(s)";
        if (!empty($errors)) {
            $message .= ". There is " . count($errors) . " error(s): " . implode("; ", $errors);
        }

        echo json_encode([
            'success' => true,
            'message' => $message,
            'success_count' => $successCount,
            'error_count' => count($errors),
            'errors' => $errors
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot assign evaluators. Error: ' . implode("; ", $errors),
            'errors' => $errors
        ]);
    }

} catch (Exception $e) {
    error_log("Error in assign_evaluators.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'System error: ' . client_error_message($e)
    ]);
}
