<?php
session_start();
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'delete_user':
            $userId = $input['user_id'] ?? 0;

            // Không cho phép xóa chính mình
            if ($userId == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Không thể xóa tài khoản của chính bạn']);
                exit();
            }

            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :id");
            $stmt->execute([':id' => $userId]);

            echo json_encode(['success' => true, 'message' => 'Xóa người dùng thành công']);
            break;

        case 'respond_feedback':
            $feedbackId = $input['feedback_id'] ?? 0;
            $adminResponse = $input['admin_response'] ?? '';
            $status = $input['status'] ?? 'reviewed';

            $stmt = $pdo->prepare("UPDATE feedback SET admin_response = :response, status = :status, updated_at = NOW() WHERE feedback_id = :id");
            $stmt->execute([
                ':response' => $adminResponse,
                ':status' => $status,
                ':id' => $feedbackId
            ]);

            echo json_encode(['success' => true, 'message' => 'Phản hồi thành công']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
            break;
    }
} catch (PDOException $e) {
    error_log("Admin action error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
}
