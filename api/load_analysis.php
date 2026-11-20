<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_homework.php';
require_once __DIR__ . '/../config/helper_task.php';

// Check authentication
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Please log in']);
    exit;
}

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Get user info from session
$user_id = $_SESSION['user']['user_id'];

// Get deliver_id from query params (required)
$deliver_id = isset($_GET['deliver_id']) ? intval($_GET['deliver_id']) : 0;

if ($deliver_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid deliver_id', 'message' => 'Invalid Deliver ID']);
    exit;
}

try {
    // Validate user access to deliver
    if (!validateUserDeliverAccess($deliver_id, $user_id)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden', 'message' => 'You do not have access']);
        exit;
    }

    // Get response from deliver
    $responseData = getResponseFromDeliver($deliver_id, null); // Don't auto-create, just get existing

    if (!$responseData || empty($responseData['json_ai'])) {
        // No saved data yet
        echo json_encode([
            'success' => true,
            'has_data' => false,
            'message' => 'No saved data yet'
        ]);
        exit;
    }

    // Use the response from deliver
    $response = $responseData;

    // Get all content for this response
    $contents = getContentByResponse($response['id_response']);

    // Parse AI history from json_ai
    $ai_history = null;
    if (!empty($response['json_ai'])) {
        $ai_history = json_decode($response['json_ai'], true);
    }

    // Extract title from first content (if starts with #)
    $title = '';
    $sections = [];

    foreach ($contents as $content) {
        $text = $content['content'];

        // Check if first content is title (starts with #)
        if ($content['order'] == 0 && strpos(trim($text), '#') === 0) {
            $title = trim(str_replace('#', '', $text));
        } else {
            $sections[] = [
                'content' => $text,
                'order' => $content['order'],
                'content_id' => $content['content_id']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'has_data' => true,
        'data' => [
            'response_id' => $response['id_response'],
            'title' => $title,
            'sections' => $sections,
            'ai_history' => $ai_history,
            'created_at' => $response['created_at'],
            'updated_at' => $response['updated_at'] ?? $response['created_at']
        ]
    ]);

} catch (Exception $e) {
    error_log("Error loading analysis: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => 'An error occurred while loading data: ' . client_error_message($e)
    ]);
}
