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
            $content_id = isset($input['content_id']) ? intval($input['content_id']) : null;
            $task_id = isset($input['task_id']) ? intval($input['task_id']) : 0;
            $response_id = isset($input['response_id']) ? intval($input['response_id']) : 0;

            // If content_id is provided, load content-specific comments
            if ($content_id > 0) {
                $comments = getCommentsHierarchical($content_id);
                error_log("Loading comments for content_id: $content_id, user_id: $user_id");
            }
            // If response_id is provided and content_id is null/0, load general comments for that response
            else if ($response_id > 0) {
                $comments = getGeneralCommentsByResponse($response_id);
                error_log("Loading general comments for response_id: $response_id, user_id: $user_id");
            }
            // If task_id is provided and content_id is null/0, load general comments (legacy support)
            else if ($task_id > 0) {
                $comments = getGeneralCommentsHierarchical($task_id);
                error_log("Loading general comments for task_id: $task_id, user_id: $user_id");
            } else {
                throw new Exception('Either content_id, response_id, or task_id is required');
            }

            error_log("Found " . count($comments) . " comments");

            echo json_encode([
                'success' => true,
                'comments' => $comments,
                'debug' => [
                        'content_id' => $content_id,
                        'response_id' => $response_id,
                        'task_id' => $task_id,
                        'user_id' => $user_id,
                        'comment_count' => count($comments)
                    ]
            ]);
            break;

        case 'add_comment':
            $content_id = isset($input['content_id']) ? intval($input['content_id']) : null;
            $task_id = intval($input['task_id'] ?? 0);
            $comment_text = trim($input['comment_text'] ?? '');
            $reply_comment_id = intval($input['reply_comment_id'] ?? 0);

            if ($task_id <= 0) {
                throw new Exception('Invalid task_id');
            }

            if (empty($comment_text)) {
                throw new Exception('Comment text is required');
            }

            // Only check allow_comment if this is a content-specific comment
            if ($content_id > 0) {
                // Check if commenting is allowed for this task
                $responseInfo = getResponseWithTaskInfo($input['response_id'] ?? 0);
                if (!$responseInfo || !$responseInfo['allow_comment']) {
                    throw new Exception('Commenting is not allowed for this phase');
                }
            }

            // Insert comment (content_id can be NULL for general comments)
            $comment_id = insertComment(
                $content_id, // NULL for general comments
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
            $content_id = isset($input['content_id']) ? intval($input['content_id']) : null;
            $task_id = intval($input['task_id'] ?? 0);
            $comment_text = trim($input['comment_text'] ?? '');
            $reply_comment_id = intval($input['reply_comment_id'] ?? 0);

            if ($task_id <= 0) {
                throw new Exception('Invalid task_id');
            }

            if (empty($comment_text)) {
                throw new Exception('Comment text is required');
            }

            // Insert comment directly without checking allow_comment
            // content_id can be NULL for general comments
            $comment_id = insertComment(
                $content_id, // NULL for general comments
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
