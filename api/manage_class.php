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

try {
    switch ($action) {
        case 'create_class':
            // Create new class
            $class_name = trim($_POST['class_name'] ?? '');

            if (empty($class_name)) {
                echo json_encode(['success' => false, 'message' => 'Class name is required']);
                exit;
            }

            // Check if user is teacher (optional - you may want any logged user to create class)
            // For now, allowing any logged user to create class

            $class_id = createClass($class_name, $user_id_request);
            if ($class_id) {
                echo json_encode(['success' => true, 'class_id' => $class_id, 'message' => 'Class created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Cannot create class']);
            }
            break;

        case 'update_class_name':
            // Update class name
            $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
            $class_name = trim($_POST['class_name'] ?? '');

            if ($class_id <= 0 || empty($class_name)) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit;
            }

            // Check if user is teacher in this class
            $userRole = getRoleInClass($class_id, $user_id_request);
            if ($userRole !== 'teacher') {
                echo json_encode(['success' => false, 'message' => 'Unauthorized - Not a teacher in this class']);
                exit;
            }

            $result = updateClassName($class_id, $class_name);
            echo json_encode(['success' => $result, 'message' => $result ? 'Class name updated successfully' : 'Update failed']);
            break;

        case 'delete_class':
            // Delete class
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

            $result = deleteClass($class_id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Class deleted successfully' : 'Delete class failed']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
exit;
