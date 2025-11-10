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
                throw new Exception('Loại ticket không hợp lệ');
            }

            if (empty($title)) {
                throw new Exception('Vui lòng nhập tiêu đề');
            }

            if (empty($content)) {
                throw new Exception('Vui lòng nhập nội dung');
            }

            $ticketId = createReflectTicket($userId, $typeId, $title, $content);

            if (!$ticketId) {
                throw new Exception('Không thể tạo ticket. Vui lòng thử lại');
            }

            echo json_encode([
                'success' => true,
                'ticket_id' => $ticketId,
                'message' => 'Tạo ticket thành công'
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
                throw new Exception('Bạn không có quyền truy cập chức năng này');
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
                throw new Exception('Ticket ID không hợp lệ');
            }

            $ticket = getReflectTicketDetail($ticketId, $userId, $userRole);

            if (!$ticket) {
                throw new Exception('Không tìm thấy ticket hoặc bạn không có quyền truy cập');
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
                throw new Exception('Ticket ID không hợp lệ');
            }

            if (empty($content)) {
                throw new Exception('Vui lòng nhập nội dung phản hồi');
            }

            // Check if user has permission to respond
            $ticket = getReflectTicketDetail($ticketId, $userId, $userRole);
            if (!$ticket) {
                throw new Exception('Bạn không có quyền phản hồi ticket này');
            }

            $detailId = addReflectResponse($ticketId, $userId, $content, $parentDetailId);

            if (!$detailId) {
                throw new Exception('Không thể thêm phản hồi. Vui lòng thử lại');
            }

            echo json_encode([
                'success' => true,
                'detail_id' => $detailId,
                'message' => 'Thêm phản hồi thành công'
            ]);
            break;

        case 'update_status':
            // Update ticket status
            $ticketId = intval($input['ticket_id'] ?? 0);
            $status = intval($input['status'] ?? -1);

            if ($ticketId <= 0) {
                throw new Exception('Ticket ID không hợp lệ');
            }

            if ($status < 0 || $status > 3) {
                throw new Exception('Trạng thái không hợp lệ');
            }

            $success = updateReflectTicketStatus($ticketId, $status, $userId, $userRole);

            if (!$success) {
                throw new Exception('Không thể cập nhật trạng thái. Vui lòng thử lại');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Cập nhật trạng thái thành công'
            ]);
            break;

        case 'delete_ticket':
            // Delete ticket
            $ticketId = intval($input['ticket_id'] ?? 0);

            if ($ticketId <= 0) {
                throw new Exception('Ticket ID không hợp lệ');
            }

            $success = deleteReflectTicket($ticketId, $userId, $userRole);

            if (!$success) {
                throw new Exception('Không thể xóa ticket. Vui lòng thử lại');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Xóa ticket thành công'
            ]);
            break;

        default:
            throw new Exception('Action không hợp lệ');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
