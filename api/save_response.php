<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_homework.php';
require_once __DIR__ . '/../config/helper_task.php';

// Check authentication
if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Bạn chưa đăng nhập hoặc không có quyền.', 'message' => 'Vui lòng đăng nhập.']);
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

// Get user info from session
if (isset($_SESSION['user']['user_id'])) {
    $user_id = $_SESSION['user']['user_id'];
    $user_role = $_SESSION['user']['role'] ?? 'student';
} else {
    $user_id = $_SESSION['user_id'] ?? 0;
    $user_role = $_SESSION['role'] ?? 'student';
}

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Bạn chưa đăng nhập hoặc không có quyền.', 'message' => 'Không tìm thấy thông tin người dùng.']);
    exit;
}

// Validate required fields
$task_id = isset($input['task_id']) ? intval($input['task_id']) : 0;
$deliver_id = isset($input['deliver_id']) ? intval($input['deliver_id']) : 0;
$action_type = $input['action_type'] ?? ''; // 'analyze', 'chat', 'slideshow', 'quiz', 'tracking'
$ai_response = $input['ai_response'] ?? null;
$user_content = $input['user_content'] ?? '';
$parent_response_id = isset($input['parent_response_id']) ? intval($input['parent_response_id']) : null;
$target_user_id = isset($input['target_user_id']) ? intval($input['target_user_id']) : $user_id; // Default to current user
$tracking_data = $input['tracking_data'] ?? null; // New: User behavior tracking data

if ($task_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Nhiệm vụ không hợp lệ.', 'message' => 'Nhiệm vụ không hợp lệ.']);
    exit;
}

if ($deliver_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Bài giao không hợp lệ.', 'message' => 'Bài giao không hợp lệ.']);
    exit;
}

// Validate deliver access
if (!validateUserDeliverAccess($deliver_id, $user_id)) {
    http_response_code(403);
    echo json_encode(['error' => 'Bạn không có quyền thực hiện thao tác này.', 'message' => 'Bạn không có quyền với bài giao này.']);
    exit;
}

if (empty($action_type)) {
    http_response_code(400);
    echo json_encode(['error' => 'Thiếu loại thao tác.', 'message' => 'Thiếu loại thao tác.']);
    exit;
}

try {
    // Verify task exists and user has permission
    $taskData = getInfoTask($task_id);

    if (!$taskData) {
        http_response_code(404);
        echo json_encode(['error' => 'Không tìm thấy nhiệm vụ.', 'message' => 'Không tìm thấy nhiệm vụ.']);
        exit;
    }

    // Check student permission
    if ($user_role === 'student') {
        if (!checkTaskAssignedToStudent($task_id, $user_id)) {
            http_response_code(403);
            echo json_encode(['error' => 'Bạn không có quyền thực hiện thao tác này.', 'message' => 'Bạn không có quyền truy cập nhiệm vụ này.']);
            exit;
        }
    }

    // Handle tracking data separately
    if ($action_type === 'tracking' && $tracking_data) {
        // Get existing response for this deliver
        $stmt = $pdo->prepare("SELECT response FROM deliver WHERE id_deliver = :deliver_id");
        $stmt->execute([':deliver_id' => $deliver_id]);
        $deliverInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($deliverInfo && $deliverInfo['response']) {
            $existing_response_id = $deliverInfo['response'];
            
            // Update tracking data in response
            $tracking_json = json_encode($tracking_data, JSON_UNESCAPED_UNICODE);
            $stmt = $pdo->prepare("
                UPDATE response 
                SET tracking_data = :tracking_data,
                    tracking_updated_at = NOW()
                WHERE id_response = :response_id
            ");
            $stmt->execute([
                ':tracking_data' => $tracking_json,
                ':response_id' => $existing_response_id
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã lưu dữ liệu quá trình làm bài.',
                'data' => [
                    'response_id' => $existing_response_id,
                    'action_type' => $action_type,
                    'tracking_summary' => $tracking_data['globalSummary'] ?? null
                ]
            ]);
            exit;
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Không tìm thấy bài làm.', 'message' => 'Không tìm thấy bài làm để lưu dữ liệu quá trình.']);
            exit;
        }
    }

    // Insert response record with owner_user_id
    $response_id = insertResponse($task_id, $user_id, [
        'owner_user_id' => $target_user_id, // Set owner (student whose work this is)
        'json_ai' => $ai_response // Store AI response as JSON
    ]);

    if (!$response_id) {
        throw new Exception('Không lưu được bài làm.');
    }

    // Insert content if provided
    $content_ids = [];
    if (!empty($user_content)) {
        $content_ids = insertContent($response_id, $user_content, $user_id);
    }

    // Update deliver with response_id and status = 1 (in progress)
    updateDeliverStatus($deliver_id, $response_id, 1);

    // Return success with IDs
    echo json_encode([
        'success' => true,
        'message' => 'Đã lưu kết quả.',
        'data' => [
            'response_id' => $response_id,
            'content_ids' => $content_ids,
            'action_type' => $action_type,
            'deliver_id' => $deliver_id
        ]
    ]);

} catch (Exception $e) {
    error_log("Error saving response: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Lỗi máy chủ. Vui lòng thử lại.',
        'message' => 'Không lưu được kết quả. Vui lòng thử lại.'
    ]);
}
