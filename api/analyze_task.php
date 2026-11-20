<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_homework.php';
require_once __DIR__ . '/../config/helper_task.php';

// Check authentication - more flexible check
if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Bạn chưa đăng nhập hoặc không có quyền.',
        'message' => 'Vui lòng đăng nhập.'
    ]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Phương thức không được hỗ trợ.']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Dữ liệu gửi lên không hợp lệ.']);
    exit;
}

$action = $input['action'] ?? '';
$task_id = isset($input['task_id']) ? intval($input['task_id']) : 0;
$deliver_id = isset($input['deliver_id']) ? intval($input['deliver_id']) : 0;

// Get user info from session - handle different session structures
if (isset($_SESSION['user']['user_id'])) {
    $user_id = $_SESSION['user']['user_id'];
    $user_role = $_SESSION['user']['role'] ?? 'student';
} else {
    $user_id = $_SESSION['user_id'] ?? 0;
    $user_role = $_SESSION['role'] ?? 'student';
}

if (!$user_id) {
    http_response_code(401);
    echo json_encode([
        'error' => 'Bạn chưa đăng nhập hoặc không có quyền.',
        'message' => 'Không tìm thấy thông tin người dùng.'
    ]);
    exit;
}

// Validate deliver_id if provided
if ($deliver_id > 0) {
    if (!validateUserDeliverAccess($deliver_id, $user_id)) {
        http_response_code(403);
        echo json_encode([
            'error' => 'Bạn không có quyền thực hiện thao tác này.',
            'message' => 'Bạn không có quyền với bài giao này.'
        ]);
        exit;
    }
}

// Task phải là task của deliver: suy ra từ deliver nếu client không gửi (hoặc gửi lệch),
// để các ràng buộc theo pha bên dưới (cấm AI / cấm chat) không bị bỏ qua.
if ($deliver_id > 0) {
    $deliverTaskStmt = $pdo->prepare("SELECT task_id FROM deliver WHERE id_deliver = :id");
    $deliverTaskStmt->execute([':id' => $deliver_id]);
    $deliverTaskId = (int)$deliverTaskStmt->fetchColumn();
    // Ở pha "chữa bài", task_id là task nguồn còn deliver thuộc task hiện tại —
    // hợp lệ miễn là hai task nằm chung một topic.
    require_once __DIR__ . '/../config/helper_phase.php';
    if ($task_id <= 0) {
        $task_id = $deliverTaskId;
    } elseif ($task_id !== $deliverTaskId && getTopicIdOfTask($task_id) !== getTopicIdOfTask($deliverTaskId)) {
        http_response_code(403);
        echo json_encode(['error' => 'Bạn không có quyền thực hiện thao tác này.', 'message' => 'Nhiệm vụ không khớp với bài giao.']);
        exit;
    }
}

// Validate action
if (!in_array($action, ['analyze', 'chat', 'slideshow', 'quiz'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Thao tác không hợp lệ.']);
    exit;
}

// If task_id is provided, validate permissions
if ($task_id > 0) {
    try {
        // Get task info
        $taskData = getInfoTask($task_id);

        if (!$taskData) {
            http_response_code(404);
            echo json_encode(['error' => 'Không tìm thấy nhiệm vụ.', 'message' => 'Không tìm thấy nhiệm vụ.']);
            exit;
        }

        // Check if student has access to this task
        if ($user_role === 'student') {
            if (!checkTaskAssignedToStudent($task_id, $user_id)) {
                http_response_code(403);
                echo json_encode(['error' => 'Bạn không có quyền thực hiện thao tác này.', 'message' => 'Bạn không có quyền truy cập nhiệm vụ này.']);
                exit;
            }
        }

        // Get phase restrictions
        $phaseCode = $taskData['phase_code'];
        $isPhaseStart = ($phaseCode === 'PHASE_START');

        // PHASE_START restrictions: no chat allowed
        if ($isPhaseStart && $action === 'chat') {
            http_response_code(403);
            echo json_encode([
                'error' => 'Bạn không có quyền thực hiện thao tác này.',
                'message' => 'Giai đoạn này không cho hỏi thêm AI.',
                'phase_code' => $phaseCode
            ]);
            exit;
        }

        // Check phase flags for other actions
        if ($action === 'analyze' && !$taskData['AI']) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Bạn không có quyền thực hiện thao tác này.',
                'message' => 'Giai đoạn này không cho dùng AI.',
                'phase_code' => $phaseCode
            ]);
            exit;
        }

    } catch (Exception $e) {
        error_log("Error validating task permissions: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi máy chủ. Vui lòng thử lại.', 'message' => 'Có lỗi khi kiểm tra quyền truy cập.']);
        exit;
    }
}

// Validate required data based on action
$data = $input['data'] ?? '';
if (empty($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Thiếu dữ liệu.', 'message' => 'Thiếu dữ liệu bắt buộc.']);
    exit;
}

// All validations passed - forward to the actual API handler
// Map 'chat' action to 'refine' for call_api.php compatibility
$apiAction = ($action === 'chat') ? 'refine' : $action;

// Prepare data for call_api.php
if ($action === 'chat' && isset($input['history'])) {
    // For chat/refine, wrap data and history into proper format
    $apiData = json_encode([
        'history' => $input['history'],
        'currentAnalysis' => null // Not needed for simple chat
    ]);
    $_POST['action'] = 'refine';
    $_POST['data'] = $apiData;
} else {
    // For analyze, slideshow, quiz - pass data directly
    $_POST['action'] = $apiAction;
    $_POST['data'] = $data;
}

// Mark that this request already passed authentication
define('AUTH_PASSED', true);

// Include call_api.php to handle the actual AI request
require_once __DIR__ . '/call_api.php';
