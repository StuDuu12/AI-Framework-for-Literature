<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_homework.php';

// Check authentication
if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get user_id with flexible session structure
$user_id = null;
if (isset($_SESSION['user']['user_id'])) {
    $user_id = $_SESSION['user']['user_id'];
} elseif (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'load_comments':
            $content_id = intval($input['content_id'] ?? 0);

            if ($content_id <= 0) {
                throw new Exception('Invalid content_id');
            }

            $comments = getCommentsHierarchical($content_id);

            echo json_encode([
                'success' => true,
                'comments' => $comments
            ]);
            break;

        case 'add_comment':
            $content_id = intval($input['content_id'] ?? 0);
            $task_id = intval($input['task_id'] ?? 0);
            $comment_text = trim($input['comment_text'] ?? '');
            $reply_comment_id = intval($input['reply_comment_id'] ?? 0);

            if ($content_id <= 0 || $task_id <= 0) {
                throw new Exception('Invalid content_id or task_id');
            }

            if (empty($comment_text)) {
                throw new Exception('Comment text is required');
            }

            // Check if commenting is allowed for this task
            $responseInfo = getResponseWithTaskInfo($input['response_id'] ?? 0);
            if (!$responseInfo || !$responseInfo['allow_comment']) {
                throw new Exception('Commenting is not allowed for this phase');
            }

            // Insert comment
            $comment_id = insertComment(
                $content_id,
                $user_id,
                $task_id,
                $comment_text,
                $reply_comment_id > 0 ? $reply_comment_id : null
            );

            // Get the newly created comment with user info
            $stmt = $pdo->prepare("
                SELECT c.*, u.fullname, u.email
                FROM comment c
                LEFT JOIN users u ON c.user_id = u.user_id
                WHERE c.comment_id = :comment_id
            ");
            $stmt->execute(['comment_id' => $comment_id]);
            $newComment = $stmt->fetch(PDO::FETCH_ASSOC);
            $newComment['avatar'] = 'https://i.pinimg.com/564x/92/26/5c/92265c40c8e428122e0b32adc1994594.jpg';
            $newComment['replies'] = [];

            echo json_encode([
                'success' => true,
                'comment' => $newComment,
                'message' => 'Comment added successfully'
            ]);
            break;

        case 'add_comment_danhgia':
            // Special case for evaluation page - no need to check response_id or allow_comment
            $content_id = intval($input['content_id'] ?? 0);
            $task_id = intval($input['task_id'] ?? 0);
            $comment_text = trim($input['comment_text'] ?? '');
            $reply_comment_id = intval($input['reply_comment_id'] ?? 0);

            if ($content_id <= 0 || $task_id <= 0) {
                throw new Exception('Invalid content_id or task_id');
            }

            if (empty($comment_text)) {
                throw new Exception('Comment text is required');
            }

            // Insert comment directly without checking allow_comment
            $comment_id = insertComment(
                $content_id,
                $user_id,
                $task_id,
                $comment_text,
                $reply_comment_id > 0 ? $reply_comment_id : null
            );

            // Get the newly created comment with user info
            $stmt = $pdo->prepare("
                SELECT c.*, u.fullname, u.email
                FROM comment c
                LEFT JOIN users u ON c.user_id = u.user_id
                WHERE c.comment_id = :comment_id
            ");
            $stmt->execute(['comment_id' => $comment_id]);
            $newComment = $stmt->fetch(PDO::FETCH_ASSOC);
            $newComment['avatar'] = 'https://i.pinimg.com/564x/92/26/5c/92265c40c8e428122e0b32adc1994594.jpg';
            $newComment['replies'] = [];

            echo json_encode([
                'success' => true,
                'comment' => $newComment,
                'message' => 'Comment added successfully'
            ]);
            break;

        case 'delete_comment':
            $comment_id = intval($input['comment_id'] ?? 0);

            if ($comment_id <= 0) {
                throw new Exception('Invalid comment_id');
            }

            $success = deleteComment($comment_id, $user_id);

            if (!$success) {
                throw new Exception('Permission denied or comment not found');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Comment deleted successfully'
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
