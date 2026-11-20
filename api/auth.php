<?php
/**
 * Auth API — Registration management (admin/teacher) + Forgot/Reset password
 * POST actions: approve_registration, reject_registration, forgot_password, reset_password
 */
session_start();
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = '';

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_POST['action'] ?? '';
}

/* ================================================================
 * GET: List pending registrations (admin/teacher only)
 * ================================================================ */
if ($method === 'GET' && ($action === '' || $action === 'list')) {
    $userId = $_SESSION['user']['user_id'] ?? null;
    $role = $_SESSION['user']['role'] ?? '';
    if (!$userId || !in_array($role, ['admin', 'teacher'])) {
        echo json_encode(['success' => false, 'message' => 'Không có quyền']);
        exit();
    }

    try {
        $filter = $_GET['status'] ?? 'pending';
        if (!in_array($filter, ['pending', 'approved', 'rejected', 'all'])) $filter = 'pending';

        if ($filter === 'all') {
            $stmt = $pdo->query("SELECT id, fullname, username, email, role, status, reviewed_by, reviewed_at, reject_reason, created_at FROM registration_requests ORDER BY created_at DESC LIMIT 100");
        } else {
            $stmt = $pdo->prepare("SELECT id, fullname, username, email, role, status, reviewed_by, reviewed_at, reject_reason, created_at FROM registration_requests WHERE status = :status ORDER BY created_at DESC LIMIT 100");
            $stmt->execute([':status' => $filter]);
        }
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'requests' => $requests]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn']);
    }
    exit();
}

/* ================================================================
 * POST actions
 * ================================================================ */
if ($method !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$userId = $_SESSION['user']['user_id'] ?? null;
$role = $_SESSION['user']['role'] ?? '';

switch ($action) {

    /* ---- Admin/Teacher: Approve registration ---- */
    case 'approve_registration':
        if (!$userId || !in_array($role, ['admin', 'teacher'])) {
            echo json_encode(['success' => false, 'message' => 'Không có quyền']);
            exit();
        }

        $requestId = (int)($input['request_id'] ?? 0);
        if ($requestId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
            exit();
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT * FROM registration_requests WHERE id = :id AND status = 'pending'");
            $stmt->execute([':id' => $requestId]);
            $req = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$req) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Yêu cầu không tồn tại hoặc đã được xử lý']);
                exit();
            }

            $stmt = $pdo->prepare("INSERT INTO users (fullname, username, email, password_hash, role) VALUES (:fullname, :username, :email, :hash, :role)");
            $stmt->execute([
                ':fullname' => $req['fullname'],
                ':username' => $req['username'],
                ':email' => $req['email'],
                ':hash' => $req['password_hash'],
                ':role' => $req['role'],
            ]);
            $newUserId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("UPDATE registration_requests SET status = 'approved', reviewed_by = :uid, reviewed_at = NOW() WHERE id = :id");
            $stmt->execute([':uid' => $userId, ':id' => $requestId]);

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => 'Đã duyệt và tạo tài khoản thành công', 'new_user_id' => (int)$newUserId]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                echo json_encode(['success' => false, 'message' => 'Tên đăng nhập đã tồn tại trong hệ thống']);
            } else {
                error_log("Approve registration error: " . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống']);
            }
        }
        break;

    /* ---- Admin/Teacher: Reject registration ---- */
    case 'reject_registration':
        if (!$userId || !in_array($role, ['admin', 'teacher'])) {
            echo json_encode(['success' => false, 'message' => 'Không có quyền']);
            exit();
        }

        $requestId = (int)($input['request_id'] ?? 0);
        $reason = trim($input['reason'] ?? '');

        if ($requestId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("UPDATE registration_requests SET status = 'rejected', reviewed_by = :uid, reviewed_at = NOW(), reject_reason = :reason WHERE id = :id AND status = 'pending'");
            $stmt->execute([':uid' => $userId, ':reason' => $reason, ':id' => $requestId]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Đã từ chối yêu cầu']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Yêu cầu không tồn tại hoặc đã xử lý']);
            }
        } catch (PDOException $e) {
            error_log("Reject registration error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống']);
        }
        break;

    /* ---- Forgot password ---- */
    case 'forgot_password':
        $email = trim($input['email'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("SELECT user_id, fullname FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                echo json_encode(['success' => true, 'message' => 'Nếu email tồn tại, hướng dẫn đặt lại mật khẩu đã được gửi.']);
                exit();
            }

            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = :uid");
            $stmt->execute([':uid' => $user['user_id']]);

            $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (:uid, :token, :expires)");
            // Chỉ lưu hash của token — token gốc chỉ nằm trong link gửi qua email
            $stmt->execute([':uid' => $user['user_id'], ':token' => hash('sha256', $token), ':expires' => $expires]);

            $resetUrl = URL_ROOT . "/reset_password?token=" . $token;

            // Tuyệt đối không trả link reset trong response (chống chiếm tài khoản).
            if (function_exists('sendPasswordResetEmail')) {
                sendPasswordResetEmail($user['fullname'], $email, $resetUrl);
            } else {
                error_log('Forgot password: chưa cấu hình sendPasswordResetEmail(), không gửi được email đặt lại mật khẩu.');
            }

            // Cùng thông báo với nhánh "email không tồn tại" để không lộ tài khoản
            echo json_encode(['success' => true, 'message' => 'Nếu email tồn tại, hướng dẫn đặt lại mật khẩu đã được gửi.']);
        } catch (PDOException $e) {
            error_log("Forgot password error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống']);
        }
        break;

    /* ---- Reset password ---- */
    case 'reset_password':
        $token = trim($input['token'] ?? '');
        $newPassword = $input['new_password'] ?? '';

        if ($token === '' || $newPassword === '') {
            echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
            exit();
        }
        if (mb_strlen($newPassword) < 6) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự']);
            exit();
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = :token AND used = 0 AND expires_at > NOW()");
            $stmt->execute([':token' => hash('sha256', $token)]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                echo json_encode(['success' => false, 'message' => 'Liên kết không hợp lệ hoặc đã hết hạn']);
                exit();
            }

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE user_id = :uid");
            $stmt->execute([':hash' => $newHash, ':uid' => $reset['user_id']]);

            $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = :id");
            $stmt->execute([':id' => $reset['id']]);

            echo json_encode(['success' => true, 'message' => 'Đặt lại mật khẩu thành công. Bạn có thể đăng nhập ngay.']);
        } catch (PDOException $e) {
            error_log("Reset password error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
}
