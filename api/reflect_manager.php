<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_reflect.php';

// Check authentication
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please login']);
    exit;
}

// Get user info from session
$currentUser = $_SESSION['user'];
$userId = $currentUser['user_id'];
$userRole = $currentUser['role']; // admin, teacher, student

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'get_types':
            // Get all reflect types
            $types = getReflectTypes();

            echo json_encode([
                'success' => true,
                'data' => $types
            ]);
            break;

        case 'create_ticket':
            // Create new ticket
            $typeId = intval($input['type_id'] ?? 0);
            $title = trim($input['title'] ?? '');
            $content = trim($input['content'] ?? '');

            if ($typeId <= 0) {
                throw new Exception('Invalid ticket type');
            }

            if (empty($title)) {
                throw new Exception('Please enter a title');
            }

            if (empty($content)) {
                throw new Exception('Please enter content');
            }

            $ticketId = createReflectTicket($userId, $typeId, $title, $content);

            if (!$ticketId) {
                throw new Exception('Cannot create ticket. Please try again');
            }

            echo json_encode([
                'success' => true,
                'ticket_id' => $ticketId,
                'message' => 'Ticket created successfully'
            ]);
            break;

        case 'get_my_tickets':
            // Get tickets of current user
            $tickets = getMyReflectTickets($userId);

            echo json_encode([
                'success' => true,
                'data' => $tickets,
                'user_role' => $userRole
            ]);
            break;

        case 'get_all_tickets':
            // Get all tickets (Admin only)
            if ($userRole !== 'admin') {
                throw new Exception('You do not have permission to access this feature');
            }

            $tickets = getAllReflectTickets();

            echo json_encode([
                'success' => true,
                'data' => $tickets,
                'user_role' => $userRole
            ]);
            break;

        case 'get_ticket_detail':
            // Get ticket detail with responses
            $ticketId = intval($input['ticket_id'] ?? 0);

            if ($ticketId <= 0) {
                throw new Exception('Invalid Ticket ID');
            }

            $ticket = getReflectTicketDetail($ticketId, $userId, $userRole);

            if (!$ticket) {
                throw new Exception('Ticket not found or you do not have access');
            }

            echo json_encode([
                'success' => true,
                'data' => $ticket,
                'current_user_id' => $userId,
                'user_role' => $userRole
            ]);
            break;

        case 'add_response':
            // Add response to ticket
            $ticketId = intval($input['ticket_id'] ?? 0);
            $content = trim($input['content'] ?? '');
            $parentDetailId = isset($input['parent_detail_id']) ? intval($input['parent_detail_id']) : null;

            if ($ticketId <= 0) {
                throw new Exception('Invalid Ticket ID');
            }

            if (empty($content)) {
                throw new Exception('Please enter response content');
            }

            // Check if user has permission to respond
            $ticket = getReflectTicketDetail($ticketId, $userId, $userRole);
            if (!$ticket) {
                throw new Exception('You do not have permission to respond to this ticket');
            }

            $detailId = addReflectResponse($ticketId, $userId, $content, $parentDetailId);

            if (!$detailId) {
                throw new Exception('Cannot add response. Please try again');
            }

            echo json_encode([
                'success' => true,
                'detail_id' => $detailId,
                'message' => 'Response added successfully'
            ]);
            break;

        case 'update_status':
            // Update ticket status
            $ticketId = intval($input['ticket_id'] ?? 0);
            $status = intval($input['status'] ?? -1);

            if ($ticketId <= 0) {
                throw new Exception('Invalid Ticket ID');
            }

            if ($status < 0 || $status > 3) {
                throw new Exception('Invalid status');
            }

            $success = updateReflectTicketStatus($ticketId, $status, $userId, $userRole);

            if (!$success) {
                throw new Exception('Cannot update status. Please try again');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Status updated successfully'
            ]);
            break;

        case 'delete_ticket':
            // Delete ticket
            $ticketId = intval($input['ticket_id'] ?? 0);

            if ($ticketId <= 0) {
                throw new Exception('Invalid Ticket ID');
            }

            $success = deleteReflectTicket($ticketId, $userId, $userRole);

            if (!$success) {
                throw new Exception('Cannot delete ticket. Please try again');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Ticket deleted successfully'
            ]);
            break;

        case 'delete_response':
            // Delete response (Admin only)
            $detailId = intval($input['detail_id'] ?? 0);

            if ($detailId <= 0) {
                throw new Exception('Invalid Response ID');
            }

            $success = deleteReflectResponse($detailId, $userId, $userRole);

            if (!$success) {
                throw new Exception('Cannot delete response. Only admin can delete.');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Response deleted successfully'
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
