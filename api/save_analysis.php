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
$user_id = $_SESSION['user']['user_id'];
$user_role = $_SESSION['user']['role'] ?? 'student';

// Validate required fields
$task_id = isset($input['task_id']) ? intval($input['task_id']) : 0;
$deliver_id = isset($input['deliver_id']) ? intval($input['deliver_id']) : 0;
$sections = $input['sections'] ?? [];
$title = $input['title'] ?? '';
$ai_history = $input['ai_history'] ?? null; // Full analysis data + conversation history

if ($task_id <= 0 || $deliver_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Thông tin gửi lên không hợp lệ.', 'message' => 'Nhiệm vụ hoặc bài giao không hợp lệ.']);
    exit;
}

if (empty($sections)) {
    http_response_code(400);
    echo json_encode(['error' => 'Thiếu nội dung các phần.', 'message' => 'Không có nội dung để lưu.']);
    exit;
}

try {
    // Verify deliver exists and user has access
    require_once __DIR__ . '/../config/helper_task.php';

    if (!validateUserDeliverAccess($deliver_id, $user_id)) {
        http_response_code(403);
        echo json_encode(['error' => 'Bạn không có quyền thực hiện thao tác này.', 'message' => 'Bạn không có quyền với bài giao này.']);
        exit;
    }

    // Check if deliver already has a response
    $stmtDeliver = $pdo->prepare("SELECT response FROM deliver WHERE id_deliver = :deliver_id");
    $stmtDeliver->execute([':deliver_id' => $deliver_id]);
    $deliverInfo = $stmtDeliver->fetch(PDO::FETCH_ASSOC);

    $response_id = null;
    $existingContent = [];
    $existingResponse = false; // Track if updating existing or creating new

    if ($deliverInfo && !empty($deliverInfo['response'])) {
        // Deliver already has response - update it
        $response_id = $deliverInfo['response'];
        $existingResponse = true;

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
        // Deliver doesn't have response yet - create new one
        // Get deliver type and value to determine owner
        $stmtDeliverType = $pdo->prepare("
            SELECT type, value 
            FROM deliver 
            WHERE id_deliver = :deliver_id
        ");
        $stmtDeliverType->execute([':deliver_id' => $deliver_id]);
        $deliverTypeInfo = $stmtDeliverType->fetch(PDO::FETCH_ASSOC);

        $owner_user_id = $user_id; // Default owner is current user

        if ($deliverTypeInfo) {
            if ($deliverTypeInfo['type'] === 'user') {
                // Individual delivery - owner is the assigned user
                $owner_user_id = $deliverTypeInfo['value'];
            } elseif ($deliverTypeInfo['type'] === 'group') {
                // Group delivery - owner is current user (first to save)
                $owner_user_id = $user_id;
            }
        }

        // Create new response
        $json_ai = null;
        if ($ai_history !== null) {
            $json_ai = is_array($ai_history) ? json_encode($ai_history, JSON_UNESCAPED_UNICODE) : $ai_history;
        }

        $stmtCreateResponse = $pdo->prepare("
            INSERT INTO response (user_id, owner_user_id, parent_response, task_id, json_ai, created_at, updated_at)
            VALUES (:user_id, :owner_user_id, NULL, :task_id, :json_ai, NOW(), NOW())
        ");
        $stmtCreateResponse->execute([
            ':user_id' => $user_id,
            ':owner_user_id' => $owner_user_id,
            ':task_id' => $task_id,
            ':json_ai' => $json_ai
        ]);
        $response_id = $pdo->lastInsertId();

        if (!$response_id) {
            throw new Exception('Không tạo được bài làm.');
        }

        // Update deliver with response_id and status = 1 (đang thực hiện)
        $updateDeliverStmt = $pdo->prepare("
            UPDATE deliver 
            SET response = :response_id, status = 1
            WHERE id_deliver = :deliver_id
        ");
        $updateDeliverStmt->execute([
            ':response_id' => $response_id,
            ':deliver_id' => $deliver_id
        ]);
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
        'message' => $existingResponse ? 'Đã cập nhật kết quả.' : 'Đã lưu kết quả.',
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
        'error' => 'Lỗi máy chủ. Vui lòng thử lại.',
        'message' => 'Không lưu được kết quả. Vui lòng thử lại.'
    ]);
}
