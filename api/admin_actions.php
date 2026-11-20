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
            $generated = []; // mật khẩu được sinh ngẫu nhiên — trả về để admin phát cho người dùng

            // Sinh mật khẩu ngẫu nhiên dễ đọc (bỏ các ký tự dễ nhầm 0/O, 1/l/I)
            $makePassword = static function (int $len = 10): string {
                $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
                $out = '';
                for ($k = 0; $k < $len; $k++) {
                    $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
                }
                return $out;
            };

            foreach ($users as $i => $row) {
                $fullname = trim($row['fullname'] ?? '');
                $username = trim($row['username'] ?? '');
                $email = trim($row['email'] ?? '');
                $userRole = strtolower(trim($row['role'] ?? 'student'));
                $password = trim($row['password'] ?? '');

                $lineNum = $i + 2;

                if ($fullname === '' || $username === '') {
                    $errors[] = "Dòng {$lineNum}: Thiếu họ tên hoặc username";
                    continue;
                }
                if ($password !== '' && mb_strlen($password) < 6) {
                    $errors[] = "Dòng {$lineNum}: Mật khẩu của '{$username}' phải có ít nhất 6 ký tự";
                    continue;
                }
                // Không còn mật khẩu mặc định '1' dùng chung: để trống thì sinh ngẫu nhiên riêng
                $isGenerated = ($password === '');
                if ($isGenerated) {
                    $password = $makePassword();
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
                    if ($isGenerated) {
                        $generated[] = ['fullname' => $fullname, 'username' => $username, 'password' => $password];
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $errors[] = "Dòng {$lineNum}: Username '{$username}' đã tồn tại";
                    } else {
                        error_log("import_users line {$lineNum}: " . $e->getMessage());
                        $errors[] = "Dòng {$lineNum}: Lỗi cơ sở dữ liệu";
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
                'generated' => $generated,
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
