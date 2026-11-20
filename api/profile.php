<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

$userId = $_SESSION['user']['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

/* ================================================================
 * GET /api/profile.php?action=stats
 * ================================================================ */
if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'stats') {
        try {
            // Số bài phân tích = số bài gốc (response cấp đầu) do user tạo
            // (bảng content không có user_id — truy vấn cũ luôn lỗi)
            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM response WHERE user_id = :uid AND parent_response IS NULL");
            $stmt->execute([':uid' => $userId]);
            $analysisCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;

            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT cm.class_id) as cnt FROM class_member cm WHERE cm.user_id = :uid");
            $stmt->execute([':uid' => $userId]);
            $classCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;

            // Số lượt đánh giá đã nộp (status 2); người chấm lưu ở evaluate.user_id
            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM evaluate WHERE user_id = :uid AND status = 2");
            $stmt->execute([':uid' => $userId]);
            $evalCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] ?? 0;

            echo json_encode([
                'success' => true,
                'analysis_count' => (int)$analysisCount,
                'class_count' => (int)$classCount,
                'evaluation_count' => (int)$evalCount,
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn']);
        }
        exit();
    }
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
    exit();
}

/* ================================================================
 * POST /api/profile.php  (JSON body)
 * ================================================================ */
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {

        /* ---- Cập nhật thông tin cá nhân ---- */
        case 'update_info':
            $fullname = trim($input['fullname'] ?? '');
            $email = trim($input['email'] ?? '');

            if ($fullname === '') {
                echo json_encode(['success' => false, 'message' => 'Họ và tên không được để trống']);
                exit();
            }
            if (mb_strlen($fullname) > 255) {
                echo json_encode(['success' => false, 'message' => 'Họ và tên tối đa 255 ký tự']);
                exit();
            }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE users SET fullname = :fullname, email = :email WHERE user_id = :uid");
            $stmt->execute([
                ':fullname' => $fullname,
                ':email' => $email,
                ':uid' => $userId,
            ]);

            $_SESSION['user']['fullname'] = $fullname;
            $_SESSION['user']['email'] = $email;

            echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
            break;

        /* ---- Đổi mật khẩu ---- */
        case 'change_password':
            $currentPass = $input['current_password'] ?? '';
            $newPass = $input['new_password'] ?? '';

            if ($currentPass === '' || $newPass === '') {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ mật khẩu']);
                exit();
            }
            if (mb_strlen($newPass) < 6) {
                echo json_encode(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự']);
                exit();
            }

            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = :uid");
            $stmt->execute([':uid' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản']);
                exit();
            }

            $stored = (string)$row['password_hash'];
            $valid = false;

            if (password_get_info($stored)['algo']) {
                $valid = password_verify($currentPass, $stored);
            } else {
                $valid = hash_equals($stored, $currentPass);
                if ($valid) {
                    $newHash = password_hash($currentPass, PASSWORD_DEFAULT);
                    $upd = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE user_id = :uid");
                    $upd->execute([':hash' => $newHash, ':uid' => $userId]);
                }
            }

            if (!$valid) {
                echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng']);
                exit();
            }

            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE user_id = :uid");
            $stmt->execute([':hash' => $newHash, ':uid' => $userId]);

            echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
    }
} catch (PDOException $e) {
    error_log("Profile API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống. Vui lòng thử lại.']);
}
