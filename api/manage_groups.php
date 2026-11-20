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
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Not logged in']);
    exit;
}
$user_id_request = $_SESSION['user']['user_id'];

// Get action from POST
$action = $_POST['action'] ?? '';
$class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;

if ($class_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
    exit;
}

// Check if user is teacher in this class
$userRole = getRoleInClass($class_id, $user_id_request);
if ($userRole !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Not a teacher in this class']);
    exit;
}

// Nhóm được thao tác phải thuộc đúng lớp đã kiểm tra quyền (chống sửa/xoá nhóm lớp khác)
require_once __DIR__ . '/../config/helper_phase.php';
$postedGroupId = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
if ($postedGroupId > 0 && getClassIdOfGroup($postedGroupId) !== $class_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Group does not belong to this class']);
    exit;
}

try {
    switch ($action) {
        case 'create_group':
            // Create new group
            $group_name = trim($_POST['group_name'] ?? '');

            if (empty($group_name)) {
                echo json_encode(['success' => false, 'message' => 'Group name is required']);
                exit;
            }

            $group_id = createGroup($class_id, $group_name);
            if ($group_id) {
                echo json_encode(['success' => true, 'group_id' => $group_id, 'message' => 'Group created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Cannot create group']);
            }
            break;

        case 'delete_group':
            // Delete group
            $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;

            if ($group_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
                exit;
            }

            $result = deleteGroup($group_id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Group deleted successfully' : 'Delete group failed']);
            break;

        case 'update_group_name':
            // Update group name
            $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
            $group_name = trim($_POST['group_name'] ?? '');

            if ($group_id <= 0 || empty($group_name)) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit;
            }

            $result = updateGroupName($group_id, $group_name);
            echo json_encode(['success' => $result, 'message' => $result ? 'Group name updated successfully' : 'Update failed']);
            break;

        case 'get_group_details':
            // Get group details with members
            $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;

            if ($group_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
                exit;
            }

            $group = getGroupDetails($group_id);
            if ($group) {
                echo json_encode(['success' => true, 'group' => $group]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Group not found']);
            }
            break;

        case 'search_users':
            // Search users not in group
            $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
            $keyword = $_POST['keyword'] ?? '';

            if ($group_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
                exit;
            }

            if (strlen($keyword) < 2) {
                echo json_encode(['success' => false, 'message' => 'Keyword too short']);
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
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
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
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit;
            }

            $result = removeGroupMember($group_id, $user_id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Member removed successfully' : 'Remove member failed']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . client_error_message($e)]);
}
exit;
