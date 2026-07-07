<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_homework.php';
require_once __DIR__ . '/../config/helper_task.php';

// Check authentication
if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Please log in']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Get user info from session
if (isset($_SESSION['user']['user_id'])) {
    $user_id = $_SESSION['user']['user_id'];
    $user_role = $_SESSION['user']['role'] ?? 'student';
} else {
    $user_id = $_SESSION['user_id'] ?? 0;
    $user_role = $_SESSION['role'] ?? 'student';
}

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'User information not found']);
    exit;
}

// Validate required fields
$task_id = isset($input['task_id']) ? intval($input['task_id']) : 0;
$deliver_id = isset($input['deliver_id']) ? intval($input['deliver_id']) : 0;
$action_type = $input['action_type'] ?? ''; // 'analyze', 'chat', 'slideshow', 'quiz', 'tracking'
$ai_response = $input['ai_response'] ?? null;
$user_content = $input['user_content'] ?? '';
$parent_response_id = isset($input['parent_response_id']) ? intval($input['parent_response_id']) : null;
$target_user_id = isset($input['target_user_id']) ? intval($input['target_user_id']) : $user_id; // Default to current user
$tracking_data = $input['tracking_data'] ?? null; // New: User behavior tracking data

if ($task_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid task_id', 'message' => 'Invalid task ID']);
    exit;
}

if ($deliver_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid deliver_id', 'message' => 'Invalid deliver ID']);
    exit;
}

// Validate deliver access
if (!validateUserDeliverAccess($deliver_id, $user_id)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden', 'message' => 'You do not have permission to access this assignment']);
    exit;
}

if (empty($action_type)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing action_type', 'message' => 'Missing action type']);
    exit;
}

try {
    // Verify task exists and user has permission
    $taskData = getInfoTask($task_id);

    if (!$taskData) {
        http_response_code(404);
        echo json_encode(['error' => 'Task not found', 'message' => 'Task not found']);
        exit;
    }

    // Check student permission
    if ($user_role === 'student') {
        if (!checkTaskAssignedToStudent($task_id, $user_id)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden', 'message' => 'You do not have permission to access this task']);
            exit;
        }
    }

    // Handle tracking data separately
    if ($action_type === 'tracking' && $tracking_data) {
        // Get existing response for this deliver
        $stmt = $pdo->prepare("SELECT response FROM deliver WHERE id_deliver = :deliver_id");
        $stmt->execute([':deliver_id' => $deliver_id]);
        $deliverInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($deliverInfo && $deliverInfo['response']) {
            $existing_response_id = $deliverInfo['response'];
            
            // Update tracking data in response
            $tracking_json = json_encode($tracking_data, JSON_UNESCAPED_UNICODE);
            $stmt = $pdo->prepare("
                UPDATE response 
                SET tracking_data = :tracking_data,
                    tracking_updated_at = NOW()
                WHERE id_response = :response_id
            ");
            $stmt->execute([
                ':tracking_data' => $tracking_json,
                ':response_id' => $existing_response_id
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Tracking data saved successfully',
                'data' => [
                    'response_id' => $existing_response_id,
                    'action_type' => $action_type,
                    'tracking_summary' => $tracking_data['globalSummary'] ?? null
                ]
            ]);
            exit;
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'No response found', 'message' => 'No response found to save tracking data']);
            exit;
        }
    }

    // Insert response record with owner_user_id
    $response_id = insertResponse($task_id, $user_id, [
        'owner_user_id' => $target_user_id, // Set owner (student whose work this is)
        'json_ai' => $ai_response // Store AI response as JSON
    ]);

    if (!$response_id) {
        throw new Exception('Failed to insert response');
    }

    // Insert content if provided
    $content_ids = [];
    if (!empty($user_content)) {
        $content_ids = insertContent($response_id, $user_content, $user_id);
    }

    // Update deliver with response_id and status = 1 (in progress)
    updateDeliverStatus($deliver_id, $response_id, 1);

    // Return success with IDs
    echo json_encode([
        'success' => true,
        'message' => 'Results saved successfully',
        'data' => [
            'response_id' => $response_id,
            'content_ids' => $content_ids,
            'action_type' => $action_type,
            'deliver_id' => $deliver_id
        ]
    ]);

} catch (Exception $e) {
    error_log("Error saving response: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => 'An error occurred while saving results. Please try again.'
    ]);
}
