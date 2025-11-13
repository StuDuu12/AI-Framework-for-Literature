<?php
require_once __DIR__ . '/db.php';

/**
 * Get all reflect types
 * @return array
 */
function getReflectTypes()
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT id_reflect_type, name 
            FROM reflect_type 
            ORDER BY id_reflect_type ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getReflectTypes: " . $e->getMessage());
        return [];
    }
}

/**
 * Create a new reflect ticket
 * @param int $userId - ID of user creating ticket
 * @param int $typeId - ID of reflect type
 * @param string $title - Ticket title
 * @param string $content - Initial content/message
 * @return int|false - Ticket ID or false on failure
 */
function createReflectTicket($userId, $typeId, $title, $content)
{
    global $pdo;
    try {
        $pdo->beginTransaction();

        // Insert ticket
        $stmt = $pdo->prepare("
            INSERT INTO reflect_ticket (id_user, id_reflect_type, title, status, created_at, updated_at)
            VALUES (:user_id, :type_id, :title, 0, NOW(), NOW())
        ");
        $stmt->execute([
            'user_id' => $userId,
            'type_id' => $typeId,
            'title' => $title
        ]);

        $ticketId = $pdo->lastInsertId();

        // Insert initial detail (first message)
        $stmt = $pdo->prepare("
            INSERT INTO reflect_detail (id_reflect, id_user, content, parent_reflect_detail, created_at, updated_at)
            VALUES (:ticket_id, :user_id, :content, NULL, NOW(), NOW())
        ");
        $stmt->execute([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'content' => $content
        ]);

        $pdo->commit();
        return $ticketId;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in createReflectTicket: " . $e->getMessage());
        return false;
    }
}

/**
 * Get tickets for a specific user
 * @param int $userId - ID of user
 * @return array
 */
function getMyReflectTickets($userId)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT 
                rt.id_reflect,
                rt.title,
                rt.status,
                rt.created_at,
                rt.updated_at,
                rtype.name AS type_name,
                u.fullname AS creator_name,
                u.email AS creator_email,
                u.role AS creator_role,
                (SELECT COUNT(*) FROM reflect_detail WHERE id_reflect = rt.id_reflect) AS response_count
            FROM reflect_ticket rt
            LEFT JOIN reflect_type rtype ON rt.id_reflect_type = rtype.id_reflect_type
            LEFT JOIN users u ON rt.id_user = u.user_id
            WHERE rt.id_user = :user_id
            ORDER BY rt.updated_at DESC, rt.created_at DESC
        ");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getMyReflectTickets: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all tickets (Admin only)
 * @return array
 */
function getAllReflectTickets()
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT 
                rt.id_reflect,
                rt.title,
                rt.status,
                rt.created_at,
                rt.updated_at,
                rtype.name AS type_name,
                u.fullname AS creator_name,
                u.email AS creator_email,
                u.role AS creator_role,
                (SELECT COUNT(*) FROM reflect_detail WHERE id_reflect = rt.id_reflect) AS response_count
            FROM reflect_ticket rt
            LEFT JOIN reflect_type rtype ON rt.id_reflect_type = rtype.id_reflect_type
            LEFT JOIN users u ON rt.id_user = u.user_id
            ORDER BY rt.updated_at DESC, rt.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getAllReflectTickets: " . $e->getMessage());
        return [];
    }
}

/**
 * Get ticket detail with all responses
 * @param int $ticketId - ID of ticket
 * @param int $userId - ID of current user (for permission check)
 * @param string $userRole - Role of current user (admin, teacher, student)
 * @return array|false - Ticket data with responses or false if no permission
 */
function getReflectTicketDetail($ticketId, $userId, $userRole)
{
    global $pdo;
    try {
        // Get ticket info
        $stmt = $pdo->prepare("
            SELECT 
                rt.*,
                rtype.name AS type_name,
                u.fullname AS creator_name,
                u.email AS creator_email,
                u.role AS creator_role
            FROM reflect_ticket rt
            LEFT JOIN reflect_type rtype ON rt.id_reflect_type = rtype.id_reflect_type
            LEFT JOIN users u ON rt.id_user = u.user_id
            WHERE rt.id_reflect = :ticket_id
        ");
        $stmt->execute(['ticket_id' => $ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            return false;
        }

        // Check permission: Admin sees all, others see only their own
        if ($userRole !== 'admin' && $ticket['id_user'] != $userId) {
            return false;
        }

        // Get all responses/details
        $stmt = $pdo->prepare("
            SELECT 
                rd.*,
                u.fullname AS user_name,
                u.email AS user_email,
                u.role AS user_role
            FROM reflect_detail rd
            LEFT JOIN users u ON rd.id_user = u.user_id
            WHERE rd.id_reflect = :ticket_id
            ORDER BY rd.created_at ASC
        ");
        $stmt->execute(['ticket_id' => $ticketId]);
        $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'ticket' => $ticket,
            'responses' => $responses
        ];
    } catch (PDOException $e) {
        error_log("Error in getReflectTicketDetail: " . $e->getMessage());
        return false;
    }
}

/**
 * Add a response to a ticket
 * @param int $ticketId - ID of ticket
 * @param int $userId - ID of user responding
 * @param string $content - Response content
 * @param int|null $parentDetailId - Parent detail ID for nested replies
 * @return int|false - Detail ID or false on failure
 */
function addReflectResponse($ticketId, $userId, $content, $parentDetailId = null)
{
    global $pdo;
    try {
        $pdo->beginTransaction();

        // Insert response
        $stmt = $pdo->prepare("
            INSERT INTO reflect_detail (id_reflect, id_user, content, parent_reflect_detail, created_at, updated_at)
            VALUES (:ticket_id, :user_id, :content, :parent_id, NOW(), NOW())
        ");
        $stmt->execute([
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'content' => $content,
            'parent_id' => $parentDetailId
        ]);

        $detailId = $pdo->lastInsertId();

        // Update ticket's updated_at
        $stmt = $pdo->prepare("
            UPDATE reflect_ticket 
            SET updated_at = NOW() 
            WHERE id_reflect = :ticket_id
        ");
        $stmt->execute(['ticket_id' => $ticketId]);

        $pdo->commit();
        return $detailId;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in addReflectResponse: " . $e->getMessage());
        return false;
    }
}

/**
 * Update ticket status
 * @param int $ticketId - ID of ticket
 * @param int $status - New status (0=New, 1=In Progress, 2=Resolved, 3=Closed)
 * @param int $userId - ID of user updating (for permission check)
 * @param string $userRole - Role of user
 * @return bool
 */
function updateReflectTicketStatus($ticketId, $status, $userId, $userRole)
{
    global $pdo;
    try {
        // Get ticket to check permission
        $stmt = $pdo->prepare("SELECT id_user FROM reflect_ticket WHERE id_reflect = :ticket_id");
        $stmt->execute(['ticket_id' => $ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            return false;
        }

        // Only admin or ticket owner can update status
        if ($userRole !== 'admin' && $ticket['id_user'] != $userId) {
            return false;
        }

        $stmt = $pdo->prepare("
            UPDATE reflect_ticket 
            SET status = :status, updated_at = NOW() 
            WHERE id_reflect = :ticket_id
        ");
        return $stmt->execute([
            'status' => $status,
            'ticket_id' => $ticketId
        ]);
    } catch (PDOException $e) {
        error_log("Error in updateReflectTicketStatus: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a ticket (Admin or owner only)
 * @param int $ticketId - ID of ticket
 * @param int $userId - ID of user deleting
 * @param string $userRole - Role of user
 * @return bool
 */
function deleteReflectTicket($ticketId, $userId, $userRole)
{
    global $pdo;
    try {
        // Get ticket to check permission
        $stmt = $pdo->prepare("SELECT id_user FROM reflect_ticket WHERE id_reflect = :ticket_id");
        $stmt->execute(['ticket_id' => $ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            return false;
        }

        // Only admin or ticket owner can delete
        if ($userRole !== 'admin' && $ticket['id_user'] != $userId) {
            return false;
        }

        $pdo->beginTransaction();

        // Delete all responses first
        $stmt = $pdo->prepare("DELETE FROM reflect_detail WHERE id_reflect = :ticket_id");
        $stmt->execute(['ticket_id' => $ticketId]);

        // Delete ticket
        $stmt = $pdo->prepare("DELETE FROM reflect_ticket WHERE id_reflect = :ticket_id");
        $stmt->execute(['ticket_id' => $ticketId]);

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in deleteReflectTicket: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a reflect response
 * @param int $detailId - ID of response to delete
 * @param int $userId - Current user ID
 * @param string $userRole - Current user role
 * @return bool
 */
function deleteReflectResponse($detailId, $userId, $userRole)
{
    global $pdo;
    try {
        // Only admin can delete responses
        if ($userRole !== 'admin') {
            return false;
        }

        // Get ticket ID before deleting
        $stmt = $pdo->prepare("SELECT id_reflect FROM reflect_detail WHERE id_reflect_detail = :detail_id");
        $stmt->execute(['detail_id' => $detailId]);
        $response = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$response) {
            return false;
        }
        
        $ticketId = $response['id_reflect'];

        $pdo->beginTransaction();

        // Check if this response has child replies
        $stmt = $pdo->prepare("SELECT COUNT(*) as reply_count FROM reflect_detail WHERE parent_reflect_detail = :detail_id");
        $stmt->execute(['detail_id' => $detailId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['reply_count'] > 0) {
            // Delete all child replies first
            $stmt = $pdo->prepare("DELETE FROM reflect_detail WHERE parent_reflect_detail = :detail_id");
            $stmt->execute(['detail_id' => $detailId]);
        }

        // Delete the response
        $stmt = $pdo->prepare("DELETE FROM reflect_detail WHERE id_reflect_detail = :detail_id");
        $stmt->execute(['detail_id' => $detailId]);

        // Update ticket updated_at
        $stmt = $pdo->prepare("UPDATE reflect_ticket SET updated_at = NOW() WHERE id_reflect = :ticket_id");
        $stmt->execute(['ticket_id' => $ticketId]);

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in deleteReflectResponse: " . $e->getMessage());
        return false;
    }
}

/**
 * Get status label
 * @param int $status
 * @return string
 */
function getReflectStatusLabel($status)
{
    $labels = [
        0 => 'Mới tạo',
        1 => 'Đang xử lý',
        2 => 'Đã giải quyết',
        3 => 'Đã đóng'
    ];
    return $labels[$status] ?? 'Không xác định';
}

/**
 * Get status badge class
 * @param int $status
 * @return string
 */
function getReflectStatusBadgeClass($status)
{
    $classes = [
        0 => 'bg-info',
        1 => 'bg-warning',
        2 => 'bg-success',
        3 => 'bg-secondary'
    ];
    return $classes[$status] ?? 'bg-secondary';
}
