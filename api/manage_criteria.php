<?php
// Prevent any output before JSON
ob_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_account.php';
require_once __DIR__ . '/../config/helper_class.php';
require_once __DIR__ . '/../config/helper_criteria.php';

if (session_status() === PHP_SESSION_NONE)
    session_start();

// Clear any output buffer and set JSON header
ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// Check login
if (!is_login()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Not logged in']);
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$user_role = $_SESSION['user']['role'];

// Only teachers can manage criteria
if ($user_role !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Only teachers can manage criteria']);
    exit;
}

// Read JSON input once and store it
$jsonInput = json_decode(file_get_contents('php://input'), true);

// Get action from POST/GET or JSON body
$action = $_POST['action'] ?? $_GET['action'] ?? ($jsonInput['action'] ?? '');

// Debug logging
error_log("Manage Criteria - Action: " . $action);
error_log("Manage Criteria - JSON Input: " . print_r($jsonInput, true));
error_log("Manage Criteria - POST: " . print_r($_POST, true));
error_log("Manage Criteria - GET: " . print_r($_GET, true));

try {
    switch ($action) {
        // ==================== CRITERIA GROUP ACTIONS ====================

        case 'get_groups':
            // Get all groups for a class or specific group by ID
            if (isset($_GET['nhom_tc_id'])) {
                $nhom_tc_id = intval($_GET['nhom_tc_id']);
                $result = getNhomTCById($nhom_tc_id);
            } else {
                $class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
                if ($class_id <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
                    exit;
                }
                $result = getNhomTCWithCount($class_id, $user_id);
            }
            echo json_encode($result);
            break;

        case 'create_group':
            // Create new criteria group
            $class_id = isset($_POST['class_id']) ? intval($_POST['class_id']) : 0;
            $ten_nhom_tc = trim($_POST['ten_nhom_tc'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($class_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid class ID']);
                exit;
            }

            if (empty($ten_nhom_tc)) {
                echo json_encode(['success' => false, 'message' => 'Tên nhóm tiêu chí không được để trống']);
                exit;
            }

            $result = createNhomTC($class_id, $ten_nhom_tc, $description, $user_id);
            echo json_encode($result);
            break;

        case 'update_group':
            // Update existing criteria group
            $nhom_tc_id = isset($_POST['nhom_tc_id']) ? intval($_POST['nhom_tc_id']) : 0;
            $ten_nhom_tc = trim($_POST['ten_nhom_tc'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if ($nhom_tc_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
                exit;
            }

            if (empty($ten_nhom_tc)) {
                echo json_encode(['success' => false, 'message' => 'Tên nhóm tiêu chí không được để trống']);
                exit;
            }

            $result = updateNhomTC($nhom_tc_id, $ten_nhom_tc, $description);
            echo json_encode($result);
            break;

        case 'delete_group':
            // Delete criteria group
            $nhom_tc_id = isset($jsonInput['nhom_tc_id']) ? intval($jsonInput['nhom_tc_id']) : (isset($_POST['nhom_tc_id']) ? intval($_POST['nhom_tc_id']) : 0);

            if ($nhom_tc_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
                exit;
            }

            $result = deleteNhomTC($nhom_tc_id);
            echo json_encode($result);
            break;

        // ==================== CRITERIA ACTIONS ====================

        case 'get_criteria':
            // Get all criteria for a group or specific criterion by ID
            if (isset($_GET['tc_id'])) {
                $tc_id = intval($_GET['tc_id']);
                $result = getTieuChiById($tc_id);
            } else if (isset($_GET['nhom_tc_id'])) {
                $nhom_tc_id = intval($_GET['nhom_tc_id']);
                $result = getTieuChiByNhomTC($nhom_tc_id);
            } else {
                echo json_encode(['success' => false, 'message' => 'Missing required parameter (tc_id or nhom_tc_id)']);
                exit;
            }
            echo json_encode($result);
            break;

        case 'create_criteria':
            // Create new criterion
            $nhom_tc_id = isset($_POST['nhom_tc_id']) ? intval($_POST['nhom_tc_id']) : 0;
            $name_tc = trim($_POST['name_tc'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $min_score = isset($_POST['min_score']) ? intval($_POST['min_score']) : 0;
            $max_score = isset($_POST['max_score']) ? intval($_POST['max_score']) : 10;

            if ($nhom_tc_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid group ID']);
                exit;
            }

            if (empty($name_tc)) {
                echo json_encode(['success' => false, 'message' => 'Tên tiêu chí không được để trống']);
                exit;
            }

            $result = createTieuChi($nhom_tc_id, $name_tc, $description, $min_score, $max_score);
            echo json_encode($result);
            break;

        case 'update_criteria':
            // Update existing criterion
            $tc_id = isset($_POST['tc_id']) ? intval($_POST['tc_id']) : 0;
            $name_tc = trim($_POST['name_tc'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $min_score = isset($_POST['min_score']) ? intval($_POST['min_score']) : 0;
            $max_score = isset($_POST['max_score']) ? intval($_POST['max_score']) : 10;

            if ($tc_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid criteria ID']);
                exit;
            }

            if (empty($name_tc)) {
                echo json_encode(['success' => false, 'message' => 'Tên tiêu chí không được để trống']);
                exit;
            }

            $result = updateTieuChi($tc_id, $name_tc, $description, $min_score, $max_score);
            echo json_encode($result);
            break;

        case 'delete_criteria':
            // Delete criterion
            $tc_id = isset($jsonInput['tc_id']) ? intval($jsonInput['tc_id']) : (isset($_POST['tc_id']) ? intval($_POST['tc_id']) : 0);

            if ($tc_id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid criteria ID',
                    'debug' => [
                        'tc_id' => $tc_id,
                        'jsonInput' => $jsonInput,
                        'post' => $_POST
                    ]
                ]);
                exit;
            }

            $result = deleteTieuChi($tc_id);
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Error in manage_criteria.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
