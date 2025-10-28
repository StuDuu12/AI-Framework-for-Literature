<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_homework.php';

// Check authentication
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Vui lòng đăng nhập']);
    exit;
}

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Get user info from session
$user_id = $_SESSION['user']['user_id'];

// Get task_id from query params
$task_id = isset($_GET['task_id']) ? intval($_GET['task_id']) : 0;

if ($task_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid task_id', 'message' => 'Task ID không hợp lệ']);
    exit;
}

try {
    // Get existing response for this user and task
    $response = getResponseUserByTask($task_id, $user_id);

    if (!$response) {
        // No saved data yet
        echo json_encode([
            'success' => true,
            'has_data' => false,
            'message' => 'Chưa có dữ liệu đã lưu'
        ]);
        exit;
    }

    // Get all content for this response
    $contents = getContentByResponse($response['id_response']);

    // Parse AI history from json_ai
    $ai_history = null;
    if (!empty($response['json_ai'])) {
        $ai_history = json_decode($response['json_ai'], true);
    }

    // Extract title from first content (if starts with #)
    $title = '';
    $sections = [];

    foreach ($contents as $content) {
        $text = $content['content'];

        // Check if first content is title (starts with #)
        if ($content['order'] == 0 && strpos(trim($text), '#') === 0) {
            $title = trim(str_replace('#', '', $text));
        } else {
            $sections[] = [
                'content' => $text,
                'order' => $content['order'],
                'content_id' => $content['content_id']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'has_data' => true,
        'data' => [
            'response_id' => $response['id_response'],
            'title' => $title,
            'sections' => $sections,
            'ai_history' => $ai_history,
            'created_at' => $response['created_at'],
            'updated_at' => $response['updated_at'] ?? $response['created_at']
        ]
    ]);

} catch (Exception $e) {
    error_log("Error loading analysis: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => 'Đã xảy ra lỗi khi tải dữ liệu: ' . $e->getMessage()
    ]);
}
