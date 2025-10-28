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
$user_id = $_SESSION['user']['user_id'];
$user_role = $_SESSION['user']['role'] ?? 'student';

// Validate required fields
$task_id = isset($input['task_id']) ? intval($input['task_id']) : 0;
$sections = $input['sections'] ?? [];
$title = $input['title'] ?? '';
$ai_history = $input['ai_history'] ?? null; // Full analysis data + conversation history

if ($task_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid task_id', 'message' => 'Task ID không hợp lệ']);
    exit;
}

if (empty($sections)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing sections', 'message' => 'Không có nội dung để lưu']);
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

    // Check if user already has a response for this task
    $existingResponse = getResponseUserByTask($task_id, $user_id);

    $response_id = null;

    if ($existingResponse) {
        // Update existing response with new AI history
        $response_id = $existingResponse['id_response'];

        // Update json_ai field
        if ($ai_history !== null) {
            $json_ai = is_array($ai_history) ? json_encode($ai_history, JSON_UNESCAPED_UNICODE) : $ai_history;

            $stmt = $pdo->prepare("
                UPDATE response 
                SET json_ai = :json_ai, 
                    updated_at = NOW()
                WHERE id_response = :response_id
            ");
            $stmt->execute([
                'json_ai' => $json_ai,
                'response_id' => $response_id
            ]);
        }

        // Get existing content for this response
        $existingContent = getContentByResponse($response_id);

    } else {
        // Create new response
        $response_id = insertResponse($task_id, $user_id, [
            'owner_user_id' => $user_id,
            'json_ai' => $ai_history
        ]);

        if (!$response_id) {
            throw new Exception('Failed to insert response');
        }
    }

    // Prepare content array with title and sections
    $contentArray = [];

    // Add title as first content
    if (!empty($title)) {
        $contentArray[] = [
            'content' => "# " . $title,
            'order' => 0,
            'type_create' => 'user'
        ];
    }

    // Add all sections
    foreach ($sections as $index => $sectionContent) {
        $contentArray[] = [
            'content' => $sectionContent,
            'order' => $index + 1,
            'type_create' => 'user'
        ];
    }

    // Now handle content updates
    $content_ids = [];

    if ($existingResponse && !empty($existingContent)) {
        // Update mode: Update existing, Insert new, Delete extra

        // Create a map of existing content by order
        $existingByOrder = [];
        foreach ($existingContent as $content) {
            $existingByOrder[$content['order']] = $content;
        }

        // Update or insert each content item
        foreach ($contentArray as $contentItem) {
            $order = $contentItem['order'];

            if (isset($existingByOrder[$order])) {
                // Update existing content
                $content_id = $existingByOrder[$order]['content_id'];
                $stmt = $pdo->prepare("
                    UPDATE content 
                    SET content = :content,
                        type_create = :type_create,
                        updated_at = NOW()
                    WHERE content_id = :content_id
                ");
                $stmt->execute([
                    'content' => $contentItem['content'],
                    'type_create' => $contentItem['type_create'],
                    'content_id' => $content_id
                ]);
                $content_ids[] = $content_id;

                // Remove from existing map (so we know what to delete later)
                unset($existingByOrder[$order]);
            } else {
                // Insert new content
                $stmt = $pdo->prepare("
                    INSERT INTO content (response_id, content, `order`, type_create, added_by, created_at)
                    VALUES (:response_id, :content, :order, :type_create, :added_by, NOW())
                ");
                $stmt->execute([
                    'response_id' => $response_id,
                    'content' => $contentItem['content'],
                    'order' => $contentItem['order'],
                    'type_create' => $contentItem['type_create'],
                    'added_by' => $user_id
                ]);
                $content_ids[] = $pdo->lastInsertId();
            }
        }

        // Delete any remaining content that wasn't updated (user removed sections)
        foreach ($existingByOrder as $remainingContent) {
            $stmt = $pdo->prepare("DELETE FROM content WHERE content_id = :content_id");
            $stmt->execute(['content_id' => $remainingContent['content_id']]);
        }

    } else {
        // Insert mode: All new content
        foreach ($contentArray as $contentItem) {
            $stmt = $pdo->prepare("
                INSERT INTO content (response_id, content, `order`, type_create, added_by, created_at)
                VALUES (:response_id, :content, :order, :type_create, :added_by, NOW())
            ");
            $stmt->execute([
                'response_id' => $response_id,
                'content' => $contentItem['content'],
                'order' => $contentItem['order'],
                'type_create' => $contentItem['type_create'],
                'added_by' => $user_id
            ]);
            $content_ids[] = $pdo->lastInsertId();
        }
    }

    // Return success with IDs
    echo json_encode([
        'success' => true,
        'message' => $existingResponse ? 'Đã cập nhật kết quả thành công' : 'Đã lưu kết quả thành công',
        'data' => [
            'response_id' => $response_id,
            'content_ids' => $content_ids,
            'is_new' => !$existingResponse,
            'total_sections' => count($contentArray)
        ]
    ]);

} catch (Exception $e) {
    error_log("Error saving analysis: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => 'Đã xảy ra lỗi khi lưu kết quả: ' . $e->getMessage()
    ]);
}
