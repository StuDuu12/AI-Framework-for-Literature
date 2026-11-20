<?php
/**
 * Class Invitation API — Create, verify, and manage class invitation codes.
 * POST actions: create_code, deactivate_code, verify_code
 * GET: list_codes
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$userId = $_SESSION['user']['user_id'] ?? null;
$role = $_SESSION['user']['role'] ?? '';

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

/** Admin, hoặc giáo viên (trong class_member) của chính lớp đó */
function canManageClassInvites(PDO $pdo, int $classId, int $userId, string $role): bool
{
    if ($classId <= 0) {
        return false;
    }
    if ($role === 'admin') {
        return true;
    }
    $stmt = $pdo->prepare("SELECT 1 FROM class_member WHERE class_id = :cid AND user_id = :uid AND role = 'teacher'");
    $stmt->execute([':cid' => $classId, ':uid' => $userId]);
    return (bool)$stmt->fetchColumn();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = '';

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list_codes';
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
}

switch ($action) {

    /* ---- Teacher: List invitation codes for a class ---- */
    case 'list_codes':
        $classId = (int)($_GET['class_id'] ?? 0);
        if ($classId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Thiếu class_id']);
            exit();
        }

        // Mã mời là bí mật của lớp — chỉ giáo viên lớp đó (hoặc admin) được xem
        if (!canManageClassInvites($pdo, $classId, (int)$userId, $role)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Không có quyền']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM class_invitations WHERE class_id = :cid ORDER BY created_at DESC");
            $stmt->execute([':cid' => $classId]);
            $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'codes' => $codes]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn']);
        }
        exit();

    /* ---- Teacher: Create invitation code ---- */
    case 'create_code':
        if (!in_array($role, ['admin', 'teacher'])) {
            echo json_encode(['success' => false, 'message' => 'Không có quyền']);
            exit();
        }

        $classId = (int)($input['class_id'] ?? 0);
        $maxUses = (int)($input['max_uses'] ?? 0);
        $expiresDays = (int)($input['expires_days'] ?? 7);

        if ($classId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Thiếu class_id']);
            exit();
        }

        // Verify teacher owns/manages this class
        $stmt = $pdo->prepare("SELECT class_id FROM class WHERE class_id = :cid");
        $stmt->execute([':cid' => $classId]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Lớp học không tồn tại']);
            exit();
        }
        if (!canManageClassInvites($pdo, $classId, (int)$userId, $role)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Không có quyền']);
            exit();
        }

        try {
            $code = strtoupper(bin2hex(random_bytes(4)));
            $expiresAt = $expiresDays > 0 ? date('Y-m-d H:i:s', strtotime("+{$expiresDays} days")) : null;
            $maxUsesVal = $maxUses > 0 ? $maxUses : null;

            $stmt = $pdo->prepare("INSERT INTO class_invitations (class_id, code, created_by, expires_at, max_uses) VALUES (:cid, :code, :uid, :expires, :max)");
            $stmt->execute([
                ':cid' => $classId,
                ':code' => $code,
                ':uid' => $userId,
                ':expires' => $expiresAt,
                ':max' => $maxUsesVal,
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Tạo mã mời thành công',
                'code' => $code,
                'expires_at' => $expiresAt,
                'invite_url' => URL_ROOT . "/join?code=" . $code,
            ]);
        } catch (PDOException $e) {
            error_log("Create invitation error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi tạo mã mời']);
        }
        exit();

    /* ---- Teacher: Deactivate invitation code ---- */
    case 'deactivate_code':
        if (!in_array($role, ['admin', 'teacher'])) {
            echo json_encode(['success' => false, 'message' => 'Không có quyền']);
            exit();
        }

        $codeId = (int)($input['code_id'] ?? 0);
        if ($codeId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Thiếu code_id']);
            exit();
        }

        // Mã phải thuộc lớp mà người gọi quản lý
        $ownStmt = $pdo->prepare("SELECT class_id FROM class_invitations WHERE id = :id");
        $ownStmt->execute([':id' => $codeId]);
        if (!canManageClassInvites($pdo, (int)$ownStmt->fetchColumn(), (int)$userId, $role)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Không có quyền']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("UPDATE class_invitations SET active = 0 WHERE id = :id");
            $stmt->execute([':id' => $codeId]);
            echo json_encode(['success' => true, 'message' => 'Đã vô hiệu hoá mã mời']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống']);
        }
        exit();

    /* ---- Student: Verify and join by code ---- */
    case 'verify_code':
        $code = trim($input['code'] ?? '');
        if ($code === '') {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã mời']);
            exit();
        }

        // Chống dò mã mời: tối đa 10 lần thử / 10 phút cho mỗi tài khoản
        rate_limit_enforce('verify_code', 10, 600, null, 'Bạn đã thử quá nhiều mã. Vui lòng đợi vài phút rồi thử lại.');

        try {
            $stmt = $pdo->prepare("SELECT ci.*, c.class_name FROM class_invitations ci JOIN class c ON ci.class_id = c.class_id WHERE ci.code = :code AND ci.active = 1");
            $stmt->execute([':code' => $code]);
            $inv = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$inv) {
                echo json_encode(['success' => false, 'message' => 'Mã mời không hợp lệ hoặc đã bị vô hiệu hoá']);
                exit();
            }

            if ($inv['expires_at'] && strtotime($inv['expires_at']) < time()) {
                echo json_encode(['success' => false, 'message' => 'Mã mời đã hết hạn']);
                exit();
            }

            if ($inv['max_uses'] && $inv['use_count'] >= $inv['max_uses']) {
                echo json_encode(['success' => false, 'message' => 'Mã mời đã hết lượt sử dụng']);
                exit();
            }

            // Check if already in class
            $stmt = $pdo->prepare("SELECT 1 FROM class_member WHERE class_id = :cid AND user_id = :uid");
            $stmt->execute([':cid' => $inv['class_id'], ':uid' => $userId]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => true, 'message' => 'Bạn đã là thành viên lớp này', 'class_id' => (int)$inv['class_id'], 'already_member' => true]);
                exit();
            }

            $pdo->beginTransaction();

            // Add to class
            $stmt = $pdo->prepare("INSERT INTO class_member (class_id, user_id, role, added_by) VALUES (:cid, :uid, 'student', :added_by)");
            $stmt->execute([':cid' => $inv['class_id'], ':uid' => $userId, ':added_by' => $inv['created_by']]);

            // Increment use count
            $stmt = $pdo->prepare("UPDATE class_invitations SET use_count = use_count + 1 WHERE id = :id");
            $stmt->execute([':id' => $inv['id']]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Tham gia lớp thành công!',
                'class_id' => (int)$inv['class_id'],
                'class_name' => $inv['class_name'],
            ]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Verify invitation error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống']);
        }
        exit();

    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
}
