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

// Get action from POST and data
$action = $_POST['action'] ?? '';
$class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;

if ($class_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Lớp không hợp lệ.']);
    exit;
}

// Check if user is teacher in this class
$userRole = getRoleInClass($class_id, $user_id_request);
if ($userRole !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Bạn không phải giáo viên của lớp này.', 'role' => $userRole]);
    exit;
}



try {
    switch ($action) {
        case 'search':
            // Search users by keyword
            $keyword = $_POST['keyword'] ?? '';
            if (strlen($keyword) < 2) {
                echo json_encode(['success' => false, 'message' => 'Từ khoá quá ngắn.']);
                exit;
            }

            $users = searchUsers($keyword, $class_id, 50);
            echo json_encode(['success' => true, 'users' => $users]);
            break;

        case 'add':
            // Add multiple members with roles
            $user_ids = isset($_POST['user_ids']) && is_array($_POST['user_ids']) ? $_POST['user_ids'] : [];
            $roles = isset($_POST['roles']) && is_array($_POST['roles']) ? $_POST['roles'] : [];

            if (empty($user_ids)) {
                echo json_encode(['success' => false, 'message' => 'Chưa chọn người dùng nào.']);
                exit;
            }

            $count = addClassMembers($class_id, $user_ids, $roles, $user_id_request);
            echo json_encode(['success' => true, 'count' => $count, 'message' => "Added $count members"]);
            break;

        case 'update_role':
            // Update member role
            $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            $role = $_POST['role'] ?? '';

            if ($user_id <= 0 || !in_array($role, ['student', 'teacher', 'admin'])) {
                echo json_encode(['success' => false, 'message' => 'Thông tin gửi lên không hợp lệ.']);
                exit;
            }

            $result = updateMemberRole($class_id, $user_id, $role);
            echo json_encode(['success' => $result, 'message' => $result ? 'Đã cập nhật.' : 'Cập nhật không thành công.']);
            break;

        case 'delete':
            // Remove member from class
            $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

            if ($user_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Người dùng không hợp lệ.']);
                exit;
            }

            $result = removeClassMember($class_id, $user_id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Đã xoá.' : 'Xoá không thành công.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Thao tác không hợp lệ.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . client_error_message($e)]);
}
exit;
