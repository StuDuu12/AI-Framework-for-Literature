<?php
ob_start();
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_phase.php';

header('Content-Type: application/json');

// Clean any output before JSON
ob_end_clean();

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create_phase':
            $topicId = intval($_POST['topic_id']);
            $phaseName = trim($_POST['phase_name']);
            $mainPhaseId = intval($_POST['main_phase_id']);
            $startTime = $_POST['start_time'];
            $endTime = $_POST['end_time'];
            $flagActive = isset($_POST['flag_active']) ? 1 : 0;
            $userId = $_SESSION['user']['user_id'];

            // Validate
            if (!$topicId || !$phaseName || !$mainPhaseId || !$startTime || !$endTime) {
                echo json_encode(['success' => false, 'message' => 'Missing required information']);
                exit;
            }

            // Check if user is teacher
            if (!isTeacherInTopicClass($topicId, $userId)) {
                echo json_encode(['success' => false, 'message' => 'You do not have permission to perform this action']);
                exit;
            }

            // Validate times
            if (strtotime($startTime) >= strtotime($endTime)) {
                echo json_encode(['success' => false, 'message' => 'Start time must be before end time']);
                exit;
            }

            // If flag_active is true, check if there's already an active phase
            if ($flagActive && hasActivePhase($topicId)) {
                echo json_encode(['success' => false, 'message' => 'Topic already has an active phase']);
                exit;
            }

            $phaseId = createPhase($topicId, $phaseName, $mainPhaseId, $startTime, $endTime, $flagActive, $userId);

            echo json_encode([
                'success' => true,
                'message' => 'Phase created successfully',
                'phase_id' => $phaseId
            ]);
            break;

        case 'update_phase':
            $phaseId = intval($_POST['phase_id']);
            $phaseName = trim($_POST['phase_name']);
            $mainPhaseId = intval($_POST['main_phase_id']);
            $startTime = $_POST['start_time'];
            $endTime = $_POST['end_time'];
            $flagActive = isset($_POST['flag_active']) ? 1 : 0;

            // Validate
            if (!$phaseId || !$phaseName || !$mainPhaseId || !$startTime || !$endTime) {
                echo json_encode(['success' => false, 'message' => 'Missing required information']);
                exit;
            }

            // Get phase to check topic
            $phase = getPhaseDetails($phaseId);
            if (!$phase) {
                echo json_encode(['success' => false, 'message' => 'Phase not found']);
                exit;
            }

            // Check if user is teacher
            if (!isTeacherInTopicClass($phase['topic_id'], $_SESSION['user']['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'You do not have permission to perform this action']);
                exit;
            }

            // Validate times
            if (strtotime($startTime) >= strtotime($endTime)) {
                echo json_encode(['success' => false, 'message' => 'Start time must be before end time']);
                exit;
            }

            // If flag_active is true, check if there's already an active phase (excluding current)
            if ($flagActive && hasActivePhase($phase['topic_id'], $phaseId)) {
                echo json_encode(['success' => false, 'message' => 'Topic already has another active phase']);
                exit;
            }

            updatePhase($phaseId, $phaseName, $mainPhaseId, $startTime, $endTime, $flagActive);

            echo json_encode([
                'success' => true,
                'message' => 'Phase updated successfully'
            ]);
            break;

        case 'delete_phase':
            $phaseId = intval($_POST['phase_id']);
            $topicId = intval($_POST['topic_id']);

            if (!$phaseId) {
                echo json_encode(['success' => false, 'message' => 'Missing phase_id']);
                exit;
            }

            // Check if user is teacher
            if (!isTeacherInTopicClass($topicId, $_SESSION['user']['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'You do not have permission to perform this action']);
                exit;
            }

            // Phase phải thuộc đúng topic đã kiểm tra quyền (chống xoá phase lớp khác)
            if (getTopicIdOfPhase($phaseId) !== $topicId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You do not have permission to perform this action']);
                exit;
            }

            // Khoá ngoại task -> phase là RESTRICT: phase còn task thì DB từ chối xoá.
            // Báo rõ lý do thay vì để lỗi SQL chung chung.
            $taskCountStmt = $pdo->prepare("SELECT COUNT(*) FROM task WHERE phase_id = ?");
            $taskCountStmt->execute([$phaseId]);
            $taskCount = (int)$taskCountStmt->fetchColumn();
            if ($taskCount > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => "This phase still has {$taskCount} task(s). Please delete its tasks first."
                ]);
                exit;
            }

            deletePhase($phaseId);

            echo json_encode([
                'success' => true,
                'message' => 'Phase deleted successfully'
            ]);
            break;

        case 'get_phase':
            $phaseId = intval($_POST['phase_id']);

            if (!$phaseId) {
                echo json_encode(['success' => false, 'message' => 'Missing phase_id']);
                exit;
            }

            $phase = getPhaseDetails($phaseId);

            if (!$phase) {
                echo json_encode(['success' => false, 'message' => 'Phase not found']);
                exit;
            }

            echo json_encode([
                'success' => true,
                'phase' => $phase
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => client_error_message($e)
    ]);
}
