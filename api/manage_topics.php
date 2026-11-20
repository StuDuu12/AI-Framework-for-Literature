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
$class_id = isset($_SESSION['current_class_id']) ? intval($_SESSION['current_class_id']) : 0;

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

// Topic được thao tác phải thuộc đúng lớp hiện tại (chống sửa/xoá topic lớp khác)
require_once __DIR__ . '/../config/helper_phase.php';
$postedTopicId = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
if ($postedTopicId > 0 && getClassIdOfTopic($postedTopicId) !== $class_id) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Chủ đề không thuộc lớp này.']);
    exit;
}

try {
    switch ($action) {
        case 'create_topic':
            // Create new topic
            $topic_name = trim($_POST['topic_name'] ?? '');

            if (empty($topic_name)) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên chủ đề.']);
                exit;
            }

            $topic_id = createTopic($class_id, $topic_name, $user_id_request);
            if ($topic_id) {
                echo json_encode(['success' => true, 'topic_id' => $topic_id, 'message' => 'Đã tạo chủ đề.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không tạo được chủ đề.']);
            }
            break;

        case 'update_topic':
            // Update topic name
            $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
            $topic_name = trim($_POST['topic_name'] ?? '');

            if ($topic_id <= 0 || empty($topic_name)) {
                echo json_encode(['success' => false, 'message' => 'Thông tin gửi lên không hợp lệ.']);
                exit;
            }

            $result = updateTopicName($topic_id, $topic_name);
            echo json_encode(['success' => $result, 'message' => $result ? 'Đã cập nhật.' : 'Cập nhật không thành công.']);
            break;

        case 'delete_topic':
            // Delete topic
            $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;

            if ($topic_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Chủ đề không hợp lệ.']);
                exit;
            }

            $result = deleteTopic($topic_id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Đã xoá chủ đề.' : 'Xoá không thành công.']);
            break;

        case 'get_topic':
            // Get topic details
            $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;

            if ($topic_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Chủ đề không hợp lệ.']);
                exit;
            }

            $topic = getTopicDetails($topic_id);
            if ($topic) {
                echo json_encode(['success' => true, 'topic' => $topic]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy chủ đề.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Thao tác không hợp lệ.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . client_error_message($e)]);
}
exit;
