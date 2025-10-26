<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_homework.php';

// Check authentication
if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Vui lòng đăng nhập']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
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
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Không tìm thấy thông tin người dùng']);
    exit;
}

// Validate required fields
$task_id = isset($input['task_id']) ? intval($input['task_id']) : 0;
$action_type = $input['action_type'] ?? ''; // 'analyze', 'chat', 'slideshow', 'quiz'
$ai_response = $input['ai_response'] ?? null;
$user_content = $input['user_content'] ?? '';
$parent_response_id = isset($input['parent_response_id']) ? intval($input['parent_response_id']) : null;

if ($task_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid task_id', 'message' => 'Task ID không hợp lệ']);
    exit;
}

if (empty($action_type)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing action_type', 'message' => 'Thiếu loại hành động']);
    exit;
}

try {
    // Verify task exists and user has permission
    $taskData = getInfoTask($task_id);

    if (!$taskData) {
        http_response_code(404);
        echo json_encode(['error' => 'Task not found', 'message' => 'Không tìm thấy nhiệm vụ']);
        exit;
    }

    // Check student permission
    if ($user_role === 'student') {
        if (!checkTaskAssignedToStudent($task_id, $user_id)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden', 'message' => 'Bạn không có quyền truy cập nhiệm vụ này']);
            exit;
        }
    }

    // Insert response record
    $response_id = insertResponse($task_id, $user_id, [
        // 'parent_response' => $parent_response_id,
        'json_ai' => $ai_response // Store AI response as JSON
    ]);

    if (!$response_id) {
        throw new Exception('Failed to insert response');
    }

    // Insert content if provided
    $content_ids = [];
    if (!empty($user_content)) {
        $content_ids = insertContent($response_id, $user_content, $user_id);
    }

    // Return success with IDs
    echo json_encode([
        'success' => true,
        'message' => 'Đã lưu kết quả thành công',
        'data' => [
            'response_id' => $response_id,
            'content_ids' => $content_ids,
            'action_type' => $action_type
        ]
    ]);

} catch (Exception $e) {
    error_log("Error saving response: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => 'Đã xảy ra lỗi khi lưu kết quả: ' . $e->getMessage()
    ]);
}
