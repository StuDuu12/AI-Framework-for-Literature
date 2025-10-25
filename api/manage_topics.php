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
$class_id = isset($_SESSION['current_class_id']) ? intval($_SESSION['current_class_id']) : 0;

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

try {
    switch ($action) {
        case 'create_topic':
            // Create new topic
            $topic_name = trim($_POST['topic_name'] ?? '');

            if (empty($topic_name)) {
                echo json_encode(['success' => false, 'message' => 'Topic name is required']);
                exit;
            }

            $topic_id = createTopic($class_id, $topic_name, $user_id_request);
            if ($topic_id) {
                echo json_encode(['success' => true, 'topic_id' => $topic_id, 'message' => 'Tạo chủ đề thành công']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể tạo chủ đề']);
            }
            break;

        case 'update_topic':
            // Update topic name
            $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
            $topic_name = trim($_POST['topic_name'] ?? '');

            if ($topic_id <= 0 || empty($topic_name)) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit;
            }

            $result = updateTopicName($topic_id, $topic_name);
            echo json_encode(['success' => $result, 'message' => $result ? 'Cập nhật thành công' : 'Cập nhật thất bại']);
            break;

        case 'delete_topic':
            // Delete topic
            $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;

            if ($topic_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid topic ID']);
                exit;
            }

            $result = deleteTopic($topic_id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Xóa chủ đề thành công' : 'Xóa thất bại']);
            break;

        case 'get_topic':
            // Get topic details
            $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;

            if ($topic_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid topic ID']);
                exit;
            }

            $topic = getTopicDetails($topic_id);
            if ($topic) {
                echo json_encode(['success' => true, 'topic' => $topic]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Topic not found']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
exit;
