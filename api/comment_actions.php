<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_homework.php';
require_once __DIR__ . '/../config/helper_task.php';

// Check authentication
if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập hoặc không có quyền.']);
    exit;
}

// Get user_id with flexible session structure
$user_id = null;
if (isset($_SESSION['user']['user_id'])) {
    $user_id = $_SESSION['user']['user_id'];
} elseif (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}
$user_id = (int)$user_id;

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

/**
 * Người dùng phải là thành viên (hoặc admin) của lớp chứa đối tượng.
 * Ném lỗi "Access denied" nếu không — chống đọc/ghi bình luận xuyên lớp.
 */
function requireCommentClassAccess(int $classId, int $userId): void
{
    if ($classId <= 0 || getCommentRoleInClass($classId, $userId) === null) {
        throw new Exception('Bạn không có quyền truy cập.');
    }
}

/**
 * Kiểm tra quyền + tính nhất quán cho thao tác thêm bình luận:
 * task, content, response và bình luận cha phải cùng một lớp mà user là thành viên.
 * Trả về response_id thực của content (nếu có content_id).
 */
function validateCommentTargets(int $taskId, ?int $contentId, ?int $responseId, int $replyCommentId, int $userId): int
{
    $classId = commentClassOfTask($taskId);
    requireCommentClassAccess($classId, $userId);

    $contentResponseId = 0;
    if ($contentId) {
        $contentResponseId = commentResponseOfContent($contentId);
        if ($contentResponseId <= 0 || commentClassOfResponse($contentResponseId) !== $classId) {
            throw new Exception('Bạn không có quyền truy cập.');
        }
    }
    if ($responseId && commentClassOfResponse($responseId) !== $classId) {
        throw new Exception('Bạn không có quyền truy cập.');
    }
    if ($replyCommentId > 0 && commentClassOfComment($replyCommentId) !== $classId) {
        throw new Exception('Bạn không có quyền truy cập.');
    }

    return $contentResponseId;
}

/** Lấy bình luận vừa tạo kèm thông tin hiển thị (không trả email) */
function fetchNewComment(int $commentId): array
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.*, u.fullname
        FROM comment c
        LEFT JOIN users u ON c.user_id = u.user_id
        WHERE c.comment_id = :comment_id
    ");
    $stmt->execute(['comment_id' => $commentId]);
    $newComment = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $newComment['avatar'] = 'https://i.pinimg.com/564x/92/26/5c/92265c40c8e428122e0b32adc1994594.jpg';
    $newComment['replies'] = [];
    return $newComment;
}

try {
    switch ($action) {
        case 'poll_all_comments':
            $contentIds = array_values(array_filter(array_map('intval', (array)($input['content_ids'] ?? []))));
            $response_id = isset($input['response_id']) ? intval($input['response_id']) : 0;
            $deliver_id = isset($input['deliver_id']) ? intval($input['deliver_id']) : 0;

            if ($deliver_id <= 0 || !validateUserDeliverAccess($deliver_id, $user_id)) {
                throw new Exception('Bạn không có quyền truy cập.');
            }

            // content_ids / response_id phải thuộc cùng lớp với deliver đã được kiểm tra quyền
            $deliverTaskStmt = $pdo->prepare("SELECT task_id FROM deliver WHERE id_deliver = :id");
            $deliverTaskStmt->execute([':id' => $deliver_id]);
            $deliverClassId = commentClassOfTask((int)$deliverTaskStmt->fetchColumn());

            $byContent = [];
            foreach ($contentIds as $contentId) {
                if ($contentId > 0 && commentClassOfContent($contentId) === $deliverClassId) {
                    $byContent[$contentId] = getCommentsHierarchical($contentId);
                }
            }

            $general = [];
            if ($response_id > 0 && commentClassOfResponse($response_id) === $deliverClassId) {
                $general = getGeneralCommentsByResponse($response_id);
            }

            echo json_encode([
                'success' => true,
                'content' => $byContent,
                'general' => $general,
                'server_time' => date('c'),
            ]);
            break;

        case 'load_comments':
            $content_id = isset($input['content_id']) ? intval($input['content_id']) : null;
            $task_id = isset($input['task_id']) ? intval($input['task_id']) : 0;
            $response_id = isset($input['response_id']) ? intval($input['response_id']) : 0;

            // If content_id is provided, load content-specific comments
            if ($content_id > 0) {
                requireCommentClassAccess(commentClassOfContent($content_id), $user_id);
                $comments = getCommentsHierarchical($content_id);
            }
            // If response_id is provided and content_id is null/0, load general comments for that response
            else if ($response_id > 0) {
                requireCommentClassAccess(commentClassOfResponse($response_id), $user_id);
                $comments = getGeneralCommentsByResponse($response_id);
            }
            // If task_id is provided and content_id is null/0, load general comments (legacy support)
            else if ($task_id > 0) {
                requireCommentClassAccess(commentClassOfTask($task_id), $user_id);
                $comments = getGeneralCommentsHierarchical($task_id);
            } else {
                throw new Exception('Thiếu thông tin bài làm.');
            }

            echo json_encode([
                'success' => true,
                'comments' => $comments,
            ]);
            break;

        case 'add_comment':
            $content_id = isset($input['content_id']) ? intval($input['content_id']) : null;
            $task_id = intval($input['task_id'] ?? 0);
            $response_id = isset($input['response_id']) ? intval($input['response_id']) : null;
            $comment_text = trim($input['comment_text'] ?? '');
            $reply_comment_id = intval($input['reply_comment_id'] ?? 0);

            if ($task_id <= 0) {
                throw new Exception('Nhiệm vụ không hợp lệ.');
            }

            if (empty($comment_text)) {
                throw new Exception('Vui lòng nhập nội dung bình luận.');
            }

            validateCommentTargets($task_id, $content_id, $response_id, $reply_comment_id, $user_id);

            // Only check allow_comment if this is a content-specific comment.
            // response_id là bài đang xem ở pha hiện tại (có thể khác response gốc của content
            // trong chuỗi bài qua các pha); đã được kiểm tra cùng lớp ở validateCommentTargets.
            if ($content_id > 0) {
                $responseInfo = getResponseWithTaskInfo($response_id ?? 0);
                if (!$responseInfo || !$responseInfo['allow_comment']) {
                    throw new Exception('Giai đoạn này không cho phép bình luận.');
                }
            }

            // Insert comment (content_id can be NULL for general comments)
            // Pass response_id for general comments (when content_id is NULL)
            $comment_id = insertComment(
                $content_id, // NULL for general comments
                $user_id,
                $task_id,
                $comment_text,
                $reply_comment_id > 0 ? $reply_comment_id : null,
                $content_id === null ? $response_id : null // Only set reponse_general if content_id is NULL
            );

            echo json_encode([
                'success' => true,
                'comment' => fetchNewComment((int)$comment_id),
                'message' => 'Đã thêm bình luận.'
            ]);
            break;

        case 'add_comment_danhgia':
            // Special case for evaluation page - no need to check allow_comment,
            // nhưng vẫn bắt buộc cùng lớp như mọi bình luận khác
            $content_id = isset($input['content_id']) ? intval($input['content_id']) : null;
            $task_id = intval($input['task_id'] ?? 0);
            $response_id = isset($input['response_id']) ? intval($input['response_id']) : null;
            $comment_text = trim($input['comment_text'] ?? '');
            $reply_comment_id = intval($input['reply_comment_id'] ?? 0);

            if ($task_id <= 0) {
                throw new Exception('Nhiệm vụ không hợp lệ.');
            }

            if (empty($comment_text)) {
                throw new Exception('Vui lòng nhập nội dung bình luận.');
            }

            validateCommentTargets($task_id, $content_id, $response_id, $reply_comment_id, $user_id);

            // content_id can be NULL for general comments
            // Pass response_id for general comments
            $comment_id = insertComment(
                $content_id, // NULL for general comments
                $user_id,
                $task_id,
                $comment_text,
                $reply_comment_id > 0 ? $reply_comment_id : null,
                $content_id === null ? $response_id : null // Only set reponse_general if content_id is NULL
            );

            echo json_encode([
                'success' => true,
                'comment' => fetchNewComment((int)$comment_id),
                'message' => 'Đã thêm bình luận.'
            ]);
            break;

        case 'delete_comment':
            $comment_id = intval($input['comment_id'] ?? 0);

            if ($comment_id <= 0) {
                throw new Exception('Bình luận không hợp lệ.');
            }

            $success = deleteComment($comment_id, $user_id);

            if (!$success) {
                throw new Exception('Không tìm thấy bình luận hoặc bạn không có quyền.');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Đã xoá bình luận.'
            ]);
            break;

        default:
            throw new Exception('Thao tác không hợp lệ.');
    }
} catch (Exception $e) {
    http_response_code($e->getMessage() === 'Bạn không có quyền truy cập.' ? 403 : 400);
    echo json_encode([
        'success' => false,
        'message' => client_error_message($e)
    ]);
}
