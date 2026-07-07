<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_homework.php';

// Auth check
if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User information not found']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {

        case 'save_note':
            $response_id = intval($input['response_id'] ?? 0);
            $note_text   = trim($input['note_text'] ?? '');

            if ($response_id <= 0) throw new Exception('Invalid response_id');
            if (empty($note_text)) throw new Exception('Note content cannot be empty');

            // Verify user owns this response
            $stmt = $pdo->prepare("SELECT id_response FROM response WHERE id_response = :id AND user_id = :uid");
            $stmt->execute([':id' => $response_id, ':uid' => $user_id]);
            if (!$stmt->fetch()) throw new Exception('Access denied');

            // Get next order
            $orderStmt = $pdo->prepare("SELECT COALESCE(MAX(`order`), 0) + 1 FROM content WHERE response_id = :rid");
            $orderStmt->execute([':rid' => $response_id]);
            $nextOrder = (int) $orderStmt->fetchColumn();

            // Insert note
            $insertStmt = $pdo->prepare("
                INSERT INTO content (response_id, content, `order`, type_create, added_by)
                VALUES (:response_id, :content, :order, 'note', :added_by)
            ");
            $insertStmt->execute([
                ':response_id' => $response_id,
                ':content'     => $note_text,
                ':order'       => $nextOrder,
                ':added_by'    => $user_id,
            ]);
            $content_id = $pdo->lastInsertId();

            // Return the created note with timestamp
            $noteStmt = $pdo->prepare("SELECT content_id, content, created_at FROM content WHERE content_id = :id");
            $noteStmt->execute([':id' => $content_id]);
            $note = $noteStmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'note' => $note, 'message' => 'Note saved successfully']);
            break;

        case 'load_notes':
            $response_id = intval($input['response_id'] ?? 0);
            if ($response_id <= 0) {
                throw new Exception('Invalid response_id');
            }

            if (!canViewResponseNotes($response_id, (int)$user_id)) {
                throw new Exception('Access denied');
            }

            $notes = getNotesByResponse($response_id);
            echo json_encode(['success' => true, 'notes' => $notes]);
            break;

        case 'delete_note':
            $content_id = intval($input['content_id'] ?? 0);
            if ($content_id <= 0) throw new Exception('Invalid content_id');

            // Verify ownership (must own the parent response and be a note)
            $stmt = $pdo->prepare("
                SELECT c.content_id FROM content c
                JOIN response r ON c.response_id = r.id_response
                WHERE c.content_id = :cid AND r.user_id = :uid AND c.type_create = 'note'
            ");
            $stmt->execute([':cid' => $content_id, ':uid' => $user_id]);
            if (!$stmt->fetch()) throw new Exception('Note not found or no permission to delete');

            $delStmt = $pdo->prepare("DELETE FROM content WHERE content_id = :cid");
            $delStmt->execute([':cid' => $content_id]);

            echo json_encode(['success' => true, 'message' => 'Note deleted successfully']);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
