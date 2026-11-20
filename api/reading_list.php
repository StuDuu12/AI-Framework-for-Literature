<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$userId = $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập hoặc không có quyền.']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

/** Danh sách đọc có thuộc về user hiện tại không */
function ownsReadingList(PDO $pdo, int $listId, int $userId): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM reading_list WHERE list_id = :lid AND user_id = :uid");
    $stmt->execute([':lid' => $listId, ':uid' => $userId]);
    return (bool)$stmt->fetchColumn();
}

/**
 * User có được xem task này không (là thành viên lớp chứa task, hoặc admin).
 * Nếu không chặn, bookmark task_id bất kỳ rồi get_bookmarks sẽ lộ tiêu đề/mô tả task lớp khác.
 */
function canSeeTask(PDO $pdo, int $taskId, int $userId): bool
{
    if (($_SESSION['user']['role'] ?? '') === 'admin') {
        return true;
    }
    $stmt = $pdo->prepare("
        SELECT 1
        FROM task t
        INNER JOIN phase p ON t.phase_id = p.phase_id
        INNER JOIN topic tp ON p.topic_id = tp.topic_id
        INNER JOIN class_member cm ON cm.class_id = tp.class_id AND cm.user_id = :uid
        WHERE t.task_id = :tid
    ");
    $stmt->execute([':tid' => $taskId, ':uid' => $userId]);
    return (bool)$stmt->fetchColumn();
}

function readingListDeny(): void
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Không có quyền']);
    exit();
}

$userId = (int)$userId;

try {
    switch ($action) {
        case 'toggle_bookmark':
            $input = json_decode(file_get_contents('php://input'), true);
            $taskId = intval($input['task_id'] ?? 0);
            $note = trim($input['note'] ?? '');

            if (!$taskId) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin nhiệm vụ.']);
                exit();
            }

            // Check if already bookmarked (cho phép bỏ bookmark cũ kể cả khi đã rời lớp)
            $stmt = $pdo->prepare("SELECT 1 FROM bookmark WHERE user_id = :uid AND task_id = :tid");
            $stmt->execute([':uid' => $userId, ':tid' => $taskId]);
            if (!$stmt->fetchColumn() && !canSeeTask($pdo, $taskId, $userId)) {
                readingListDeny();
            }

            // Check if already bookmarked
            $stmt = $pdo->prepare("SELECT bookmark_id FROM bookmark WHERE user_id = :uid AND task_id = :tid");
            $stmt->execute([':uid' => $userId, ':tid' => $taskId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                // Remove bookmark
                $stmt = $pdo->prepare("DELETE FROM bookmark WHERE bookmark_id = :bid");
                $stmt->execute([':bid' => $existing['bookmark_id']]);
                echo json_encode(['success' => true, 'bookmarked' => false, 'message' => 'Đã xóa bookmark']);
            } else {
                // Add bookmark
                $stmt = $pdo->prepare("INSERT INTO bookmark (user_id, task_id, note) VALUES (:uid, :tid, :note)");
                $stmt->execute([':uid' => $userId, ':tid' => $taskId, ':note' => $note]);
                echo json_encode(['success' => true, 'bookmarked' => true, 'message' => 'Đã bookmark']);
            }
            break;

        case 'get_bookmarks':
            $stmt = $pdo->prepare("
                SELECT b.*, t.title as task_title, t.description as task_description,
                       p.phase_name, tp.topic_name
                FROM bookmark b
                INNER JOIN task t ON b.task_id = t.task_id
                LEFT JOIN phase p ON t.phase_id = p.phase_id
                LEFT JOIN topic tp ON p.topic_id = tp.topic_id
                WHERE b.user_id = :uid
                ORDER BY b.created_at DESC
            ");
            $stmt->execute([':uid' => $userId]);
            echo json_encode(['success' => true, 'bookmarks' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'check_bookmark':
            $taskId = intval($_GET['task_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT 1 FROM bookmark WHERE user_id = :uid AND task_id = :tid");
            $stmt->execute([':uid' => $userId, ':tid' => $taskId]);
            echo json_encode(['success' => true, 'bookmarked' => $stmt->fetch() ? true : false]);
            break;

        case 'create_list':
            $input = json_decode(file_get_contents('php://input'), true);
            $name = trim($input['name'] ?? '');
            $description = trim($input['description'] ?? '');

            if (!$name) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên.']);
                exit();
            }

            $stmt = $pdo->prepare("INSERT INTO reading_list (user_id, name, description) VALUES (:uid, :name, :desc)");
            $stmt->execute([':uid' => $userId, ':name' => $name, ':desc' => $description]);
            echo json_encode(['success' => true, 'list_id' => $pdo->lastInsertId(), 'message' => 'Đã tạo danh sách']);
            break;

        case 'get_lists':
            $stmt = $pdo->prepare("
                SELECT rl.*,
                       (SELECT COUNT(*) FROM reading_list_item WHERE list_id = rl.list_id) as item_count
                FROM reading_list rl
                WHERE rl.user_id = :uid
                ORDER BY rl.created_at DESC
            ");
            $stmt->execute([':uid' => $userId]);
            echo json_encode(['success' => true, 'lists' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'get_list_items':
            $listId = intval($_GET['list_id'] ?? 0);
            if (!ownsReadingList($pdo, $listId, $userId)) {
                readingListDeny();
            }
            $stmt = $pdo->prepare("
                SELECT rli.*, t.title as task_title, t.description as task_description,
                       p.phase_name, tp.topic_name
                FROM reading_list_item rli
                INNER JOIN task t ON rli.task_id = t.task_id
                LEFT JOIN phase p ON t.phase_id = p.phase_id
                LEFT JOIN topic tp ON p.topic_id = tp.topic_id
                WHERE rli.list_id = :lid
                ORDER BY rli.added_at DESC
            ");
            $stmt->execute([':lid' => $listId]);
            echo json_encode(['success' => true, 'items' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'add_to_list':
            $input = json_decode(file_get_contents('php://input'), true);
            $listId = intval($input['list_id'] ?? 0);
            $taskId = intval($input['task_id'] ?? 0);

            if (!$listId || !$taskId) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin.']);
                exit();
            }
            if (!ownsReadingList($pdo, $listId, $userId) || !canSeeTask($pdo, $taskId, $userId)) {
                readingListDeny();
            }

            $stmt = $pdo->prepare("INSERT IGNORE INTO reading_list_item (list_id, task_id) VALUES (:lid, :tid)");
            $stmt->execute([':lid' => $listId, ':tid' => $taskId]);
            echo json_encode(['success' => true, 'message' => 'Đã thêm vào danh sách']);
            break;

        case 'remove_from_list':
            $input = json_decode(file_get_contents('php://input'), true);
            $listId = intval($input['list_id'] ?? 0);
            $taskId = intval($input['task_id'] ?? 0);
            if (!ownsReadingList($pdo, $listId, $userId)) {
                readingListDeny();
            }

            $stmt = $pdo->prepare("DELETE FROM reading_list_item WHERE list_id = :lid AND task_id = :tid");
            $stmt->execute([':lid' => $listId, ':tid' => $taskId]);
            echo json_encode(['success' => true, 'message' => 'Đã xóa khỏi danh sách']);
            break;

        case 'delete_list':
            $input = json_decode(file_get_contents('php://input'), true);
            $listId = intval($input['list_id'] ?? 0);
            // Kiểm tra chủ sở hữu TRƯỚC khi xoá item (bản cũ xoá item của mọi danh sách)
            if (!ownsReadingList($pdo, $listId, $userId)) {
                readingListDeny();
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM reading_list_item WHERE list_id = :lid");
            $stmt->execute([':lid' => $listId]);
            $stmt = $pdo->prepare("DELETE FROM reading_list WHERE list_id = :lid AND user_id = :uid");
            $stmt->execute([':lid' => $listId, ':uid' => $userId]);
            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Đã xóa danh sách']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Thao tác không hợp lệ.']);
            break;
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("reading_list error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu. Vui lòng thử lại.']);
}
