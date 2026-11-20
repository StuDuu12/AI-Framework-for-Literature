<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_reflect.php';

// Check authentication
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
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
                throw new Exception('Loại yêu cầu không hợp lệ.');
            }

            if (empty($title)) {
                throw new Exception('Vui lòng nhập tiêu đề.');
            }

            if (empty($content)) {
                throw new Exception('Vui lòng nhập nội dung.');
            }

            $ticketId = createReflectTicket($userId, $typeId, $title, $content);

            if (!$ticketId) {
                throw new Exception('Không tạo được yêu cầu. Vui lòng thử lại.');
            }

            echo json_encode([
                'success' => true,
                'ticket_id' => $ticketId,
                'message' => 'Đã gửi yêu cầu hỗ trợ.'
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
                throw new Exception('Bạn không có quyền dùng chức năng này.');
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
                throw new Exception('Yêu cầu không hợp lệ.');
            }

            $ticket = getReflectTicketDetail($ticketId, $userId, $userRole);

            if (!$ticket) {
                throw new Exception('Không tìm thấy yêu cầu hoặc bạn không có quyền xem.');
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
                throw new Exception('Yêu cầu không hợp lệ.');
            }

            if (empty($content)) {
                throw new Exception('Vui lòng nhập nội dung trả lời.');
            }

            // Check if user has permission to respond
            $ticket = getReflectTicketDetail($ticketId, $userId, $userRole);
            if (!$ticket) {
                throw new Exception('Bạn không có quyền trả lời yêu cầu này.');
            }

            $detailId = addReflectResponse($ticketId, $userId, $content, $parentDetailId);

            if (!$detailId) {
                throw new Exception('Không gửi được phản hồi. Vui lòng thử lại.');
            }

            echo json_encode([
                'success' => true,
                'detail_id' => $detailId,
                'message' => 'Đã gửi phản hồi.'
            ]);
            break;

        case 'update_status':
            // Update ticket status
            $ticketId = intval($input['ticket_id'] ?? 0);
            $status = intval($input['status'] ?? -1);

            if ($ticketId <= 0) {
                throw new Exception('Yêu cầu không hợp lệ.');
            }

            if ($status < 0 || $status > 3) {
                throw new Exception('Trạng thái không hợp lệ.');
            }

            $success = updateReflectTicketStatus($ticketId, $status, $userId, $userRole);

            if (!$success) {
                throw new Exception('Không cập nhật được trạng thái. Vui lòng thử lại.');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái.'
            ]);
            break;

        case 'delete_ticket':
            // Delete ticket
            $ticketId = intval($input['ticket_id'] ?? 0);

            if ($ticketId <= 0) {
                throw new Exception('Yêu cầu không hợp lệ.');
            }

            $success = deleteReflectTicket($ticketId, $userId, $userRole);

            if (!$success) {
                throw new Exception('Không xoá được yêu cầu. Vui lòng thử lại.');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Đã xoá yêu cầu hỗ trợ.'
            ]);
            break;

        case 'delete_response':
            // Delete response (Admin only)
            $detailId = intval($input['detail_id'] ?? 0);

            if ($detailId <= 0) {
                throw new Exception('Bài làm không hợp lệ.');
            }

            $success = deleteReflectResponse($detailId, $userId, $userRole);

            if (!$success) {
                throw new Exception('Chỉ quản trị viên được xoá phản hồi.');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Đã xoá phản hồi.'
            ]);
            break;

        default:
            throw new Exception('Thao tác không hợp lệ.');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => client_error_message($e)
    ]);
}
