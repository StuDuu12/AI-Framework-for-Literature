<?php
ob_start();
session_start();
require_once '../config/db.php';
require_once '../config/helper_phase.php';

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
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
                exit;
            }

            // Check if user is teacher
            if (!isTeacherInTopicClass($topicId, $userId)) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
                exit;
            }

            // Validate times
            if (strtotime($startTime) >= strtotime($endTime)) {
                echo json_encode(['success' => false, 'message' => 'Thời gian bắt đầu phải trước thời gian kết thúc']);
                exit;
            }

            // If flag_active is true, check if there's already an active phase
            if ($flagActive && hasActivePhase($topicId)) {
                echo json_encode(['success' => false, 'message' => 'Chủ đề đã có pha đang hoạt động']);
                exit;
            }

            $phaseId = createPhase($topicId, $phaseName, $mainPhaseId, $startTime, $endTime, $flagActive, $userId);

            echo json_encode([
                'success' => true,
                'message' => 'Tạo pha thành công',
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
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
                exit;
            }

            // Get phase to check topic
            $phase = getPhaseDetails($phaseId);
            if (!$phase) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy pha']);
                exit;
            }

            // Check if user is teacher
            if (!isTeacherInTopicClass($phase['topic_id'], $_SESSION['user']['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
                exit;
            }

            // Validate times
            if (strtotime($startTime) >= strtotime($endTime)) {
                echo json_encode(['success' => false, 'message' => 'Thời gian bắt đầu phải trước thời gian kết thúc']);
                exit;
            }

            // If flag_active is true, check if there's already an active phase (excluding current)
            if ($flagActive && hasActivePhase($phase['topic_id'], $phaseId)) {
                echo json_encode(['success' => false, 'message' => 'Chủ đề đã có pha khác đang hoạt động']);
                exit;
            }

            updatePhase($phaseId, $phaseName, $mainPhaseId, $startTime, $endTime, $flagActive);

            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật pha thành công'
            ]);
            break;

        case 'delete_phase':
            $phaseId = intval($_POST['phase_id']);
            $topicId = intval($_POST['topic_id']);

            if (!$phaseId) {
                echo json_encode(['success' => false, 'message' => 'Thiếu phase_id']);
                exit;
            }

            // Check if user is teacher
            if (!isTeacherInTopicClass($topicId, $_SESSION['user']['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này']);
                exit;
            }

            deletePhase($phaseId);

            echo json_encode([
                'success' => true,
                'message' => 'Xóa pha thành công'
            ]);
            break;

        case 'get_phase':
            $phaseId = intval($_POST['phase_id']);

            if (!$phaseId) {
                echo json_encode(['success' => false, 'message' => 'Thiếu phase_id']);
                exit;
            }

            $phase = getPhaseDetails($phaseId);

            if (!$phase) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy pha']);
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
        'message' => $e->getMessage()
    ]);
}
