<?php
// Prevent any output before JSON
ob_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_account.php';
require_once __DIR__ . '/../config/helper_class.php';

if (session_status() === PHP_SESSION_NONE)
    session_start();

// Clear any output buffer and set JSON header
ob_end_clean();
header('Content-Type: application/json');

// Check login
if (!is_login()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
    exit;
}
$user_id_request = $_SESSION['user']['user_id'];

// Get action from POST
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'create_class':
            // Create new class
            $class_name = trim($_POST['class_name'] ?? '');

            if (empty($class_name)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên lớp.']);
                exit;
            }

            // Chỉ giáo viên/admin (role toàn cục) mới được tạo lớp — nếu không, học sinh
            // tự tạo lớp sẽ trở thành "teacher" trong class_member và leo thang quyền.
            $globalRole = $_SESSION['user']['role'] ?? ($_SESSION['role'] ?? '');
            if (!in_array($globalRole, ['teacher', 'admin'], true)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Chỉ giáo viên được tạo lớp.']);
                exit;
            }

            $class_id = createClass($class_name, $user_id_request);
            if ($class_id) {
                echo json_encode(['success' => true, 'class_id' => $class_id, 'message' => 'Đã tạo lớp.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không tạo được lớp.']);
            }
            break;

        case 'update_class_name':
            // Update class name
            $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
            $class_name = trim($_POST['class_name'] ?? '');

            if ($class_id <= 0 || empty($class_name)) {
                echo json_encode(['success' => false, 'message' => 'Thông tin gửi lên không hợp lệ.']);
                exit;
            }

            // Check if user is teacher in this class
            $userRole = getRoleInClass($class_id, $user_id_request);
            if ($userRole !== 'teacher') {
                echo json_encode(['success' => false, 'message' => 'Bạn không phải giáo viên của lớp này.']);
                exit;
            }

            $result = updateClassName($class_id, $class_name);
            echo json_encode(['success' => $result, 'message' => $result ? 'Đã đổi tên lớp.' : 'Cập nhật không thành công.']);
            break;

        case 'delete_class':
            // Delete class
            $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;

            if ($class_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Lớp không hợp lệ.']);
                exit;
            }

            // Check if user is teacher in this class
            $userRole = getRoleInClass($class_id, $user_id_request);
            if ($userRole !== 'teacher') {
                echo json_encode(['success' => false, 'message' => 'Bạn không phải giáo viên của lớp này.']);
                exit;
            }

            $result = deleteClass($class_id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Đã xoá lớp.' : 'Không xoá được lớp.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Thao tác không hợp lệ.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . client_error_message($e)]);
}
exit;
