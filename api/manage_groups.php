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

// Nhóm được thao tác phải thuộc đúng lớp đã kiểm tra quyền (chống sửa/xoá nhóm lớp khác)
require_once __DIR__ . '/../config/helper_phase.php';
$postedGroupId = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
if ($postedGroupId > 0 && getClassIdOfGroup($postedGroupId) !== $class_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Nhóm không thuộc lớp này.']);
    exit;
}

try {
    switch ($action) {
        case 'create_group':
            // Create new group
            $group_name = trim($_POST['group_name'] ?? '');

            if (empty($group_name)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên nhóm.']);
                exit;
            }

            $group_id = createGroup($class_id, $group_name);
            if ($group_id) {
                echo json_encode(['success' => true, 'group_id' => $group_id, 'message' => 'Đã tạo nhóm.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không tạo được nhóm.']);
            }
            break;

        case 'delete_group':
            // Delete group
            $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;

            if ($group_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Nhóm không hợp lệ.']);
                exit;
            }

            $result = deleteGroup($group_id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Đã xoá nhóm.' : 'Không xoá được nhóm.']);
            break;

        case 'update_group_name':
            // Update group name
            $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
            $group_name = trim($_POST['group_name'] ?? '');

            if ($group_id <= 0 || empty($group_name)) {
                echo json_encode(['success' => false, 'message' => 'Thông tin gửi lên không hợp lệ.']);
                exit;
            }

            $result = updateGroupName($group_id, $group_name);
            echo json_encode(['success' => $result, 'message' => $result ? 'Đã đổi tên nhóm.' : 'Cập nhật không thành công.']);
            break;

        case 'get_group_details':
            // Get group details with members
            $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;

            if ($group_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Nhóm không hợp lệ.']);
                exit;
            }

            $group = getGroupDetails($group_id);
            if ($group) {
                echo json_encode(['success' => true, 'group' => $group]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy nhóm.']);
            }
            break;

        case 'search_users':
            // Search users not in group
            $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
            $keyword = $_POST['keyword'] ?? '';

            if ($group_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Nhóm không hợp lệ.']);
                exit;
            }

            if (strlen($keyword) < 2) {
                echo json_encode(['success' => false, 'message' => 'Từ khoá quá ngắn.']);
                exit;
            }

            $users = searchUsersNotInGroup($keyword, $class_id, $group_id, 50);
            echo json_encode(['success' => true, 'users' => $users]);
            break;

        case 'add_members':
            // Add members to group
            $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
            $user_ids = isset($_POST['user_ids']) && is_array($_POST['user_ids']) ? $_POST['user_ids'] : [];

            if ($group_id <= 0 || empty($user_ids)) {
                echo json_encode(['success' => false, 'message' => 'Thông tin gửi lên không hợp lệ.']);
                exit;
            }

            $count = addGroupMembers($group_id, $user_ids, $user_id_request);
            echo json_encode(['success' => true, 'count' => $count, 'message' => "Added $count member(s)"]);
            break;

        case 'remove_member':
            // Remove member from group
            $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
            $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

            if ($group_id <= 0 || $user_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Thông tin gửi lên không hợp lệ.']);
                exit;
            }

            $result = removeGroupMember($group_id, $user_id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Đã xoá thành viên.' : 'Không xoá được thành viên.']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Thao tác không hợp lệ.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . client_error_message($e)]);
}
exit;
