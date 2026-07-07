<?php
session_start();
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Kiểm tra quyền admin (hỗ trợ cả session key mới và cũ)
$_adminId   = $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? null;
$_adminRole = $_SESSION['user']['role']    ?? $_SESSION['role']    ?? null;
if (!$_adminId || $_adminRole !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'No access permission']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'delete_user':
            $userId = $input['user_id'] ?? 0;

            // Không cho phép xóa chính mình
            if ($userId == $_adminId) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
                exit();
            }

            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = :id");
            $stmt->execute([':id' => $userId]);

            echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            break;

        case 'import_users':
            $users = $input['users'] ?? [];
            if (empty($users)) {
                echo json_encode(['success' => false, 'message' => 'Không có dữ liệu để import']);
                exit();
            }

            $imported = 0;
            $errors = [];

            foreach ($users as $i => $row) {
                $fullname = trim($row['fullname'] ?? '');
                $username = trim($row['username'] ?? '');
                $email = trim($row['email'] ?? '');
                $userRole = strtolower(trim($row['role'] ?? 'student'));
                $password = trim($row['password'] ?? '') ?: '1';

                $lineNum = $i + 2;

                if ($fullname === '' || $username === '') {
                    $errors[] = "Dòng {$lineNum}: Thiếu họ tên hoặc username";
                    continue;
                }
                if (!in_array($userRole, ['student', 'teacher'])) {
                    $userRole = 'student';
                }

                try {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (fullname, username, email, password_hash, role) VALUES (:fullname, :username, :email, :hash, :role)");
                    $stmt->execute([
                        ':fullname' => $fullname,
                        ':username' => $username,
                        ':email' => $email !== '' ? $email : null,
                        ':hash' => $hash,
                        ':role' => $userRole,
                    ]);
                    $imported++;
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $errors[] = "Dòng {$lineNum}: Username '{$username}' đã tồn tại";
                    } else {
                        $errors[] = "Dòng {$lineNum}: Lỗi DB - " . $e->getMessage();
                    }
                }
            }

            $msg = "Đã import thành công {$imported} tài khoản";
            if (count($errors) > 0) {
                $msg .= ". Có " . count($errors) . " lỗi";
            }

            echo json_encode([
                'success' => $imported > 0,
                'message' => $msg,
                'imported' => $imported,
                'errors' => $errors,
            ]);
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

            echo json_encode(['success' => true, 'message' => 'Response successful']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    error_log("Admin action error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Đã xảy ra lỗi, vui lòng thử lại.']);
}
