<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helper_class.php';


function getInfoTask($taskId)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT 
            t.task_id, t.title AS task_title, t.description AS task_description, t.file_content,
            p.phase_name, p.phase_number, p.topic_id, p.start_time, p.end_time, p.flag_active,
            mp.m_phase_id, mp.m_phase_name, mp.phase_code, mp.AI, mp.Comment, mp.Edit, mp.teacher, mp.student,
            tp.topic_name, tp.class_id
        FROM `task` t
        LEFT JOIN `phase` p ON p.phase_id = t.phase_id
        LEFT JOIN `main_phase` mp ON p.m_phase_id = mp.m_phase_id
        LEFT JOIN `topic` tp ON p.topic_id = tp.topic_id
        WHERE t.`task_id` = :task_id
    ");
    $stmt->execute(['task_id' => $taskId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function checkTaskAssignedToStudent($taskId, $studentId)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS so_lan_duoc_giao
        FROM deliver d
        WHERE d.task_id = :task_id
        AND (
            (d.type = 'user' AND d.value = :user_id)
            OR (d.type = 'class' AND EXISTS (
                    SELECT 1 FROM class_member cm 
                    WHERE cm.class_id = d.value AND cm.user_id = :user_id
                ))
            OR (d.type = 'group' AND EXISTS (
                    SELECT 1 FROM group_member gm 
                    WHERE gm.group_id = d.value AND gm.user_id = :user_id
                ))
        );
    ");
    $stmt->execute(['task_id' => $taskId, 'user_id' => $studentId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['so_lan_duoc_giao'] > 0;
}

function getAllUserAssignedToTask($taskId)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            COALESCE(u.user_id, uc.user_id, ug.user_id) AS user_id,
            COALESCE(u.fullname, uc.fullname, ug.fullname) AS fullname,
            COALESCE(u.email, uc.email, ug.email) AS email,
            CASE
                WHEN u.user_id IS NOT NULL THEN 'user'
                WHEN uc.user_id IS NOT NULL THEN 'class'
                WHEN ug.user_id IS NOT NULL THEN 'group'
            END AS loai_giao
        FROM deliver d
        LEFT JOIN users u 
            ON (d.type = 'user' AND d.value = u.user_id)
        LEFT JOIN class_member cm 
            ON (d.type = 'class' AND d.value = cm.class_id)
        LEFT JOIN users uc 
            ON cm.user_id = uc.user_id
        LEFT JOIN group_member gm 
            ON (d.type = 'group' AND d.value = gm.group_id)
        LEFT JOIN users ug 
            ON gm.user_id = ug.user_id
        WHERE d.task_id = :task_id
        AND COALESCE(u.user_id, uc.user_id, ug.user_id) IS NOT NULL;
    ");
    $stmt->execute(['task_id' => $taskId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function insertResponse($taskId, $userId, $option = [])
{
    global $pdo;

    $payload = [
        'task_id' => $taskId,
        'user_id' => $userId,
        'owner_user_id' => $option['owner_user_id'] ?? null,
        'parent_response' => $option['parent_response'] ?? null,
        'json_ai' => $option['json_ai'] ?? null,
    ];

    if (is_array($payload['json_ai'])) {
        $payload['json_ai'] = json_encode($payload['json_ai'], JSON_UNESCAPED_UNICODE);
    }

    $stmt = $pdo->prepare("
        INSERT INTO response (task_id, user_id, owner_user_id, parent_response, json_ai)
        VALUES (:task_id, :user_id, :owner_user_id, :parent_response, :json_ai)
    ");

    $stmt->bindValue(':task_id', $payload['task_id'], PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $payload['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(
        ':owner_user_id',
        $payload['owner_user_id'],
        $payload['owner_user_id'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT
    );
    $stmt->bindValue(
        ':parent_response',
        $payload['parent_response'],
        $payload['parent_response'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR
    );
    $stmt->bindValue(
        ':json_ai',
        $payload['json_ai'],
        $payload['json_ai'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR
    );

    $stmt->execute();

    return $pdo->lastInsertId();
}

function insertContent($responseId, $content, $userId)
{
    global $pdo;
    if (!is_array($content)) {
        $content = [$content];
    }

    $stmt = $pdo->prepare('SELECT COALESCE(MAX(`order`), 0) + 1 FROM content WHERE response_id = :response_id');
    $stmt->execute([':response_id' => $responseId]);
    $nextOrder = (int) $stmt->fetchColumn();

    $insertStmt = $pdo->prepare('
        INSERT INTO content (response_id, content, `order`, type_create, added_by)
        VALUES (:response_id, :content, :order, :type_create, :added_by)
    ');

    $ids = [];
    $startedTransaction = !$pdo->inTransaction();

    try {
        if ($startedTransaction) {
            $pdo->beginTransaction();
        }

        foreach ($content as $offset => $item) {
            $value = is_scalar($item) ? (string) $item : json_encode($item, JSON_UNESCAPED_UNICODE);

            $insertStmt->execute([
                ':response_id' => $responseId,
                ':content' => $value,
                ':order' => $nextOrder + $offset,
                ':type_create' => 'user',
                ':added_by' => $userId,
            ]);

            $ids[] = $pdo->lastInsertId();
        }

        if ($startedTransaction) {
            $pdo->commit();
        }
    } catch (Throwable $e) {
        if ($startedTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return $ids;
}

function getResponseUserByTask($taskId, $userId)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT *
        FROM `response`
        WHERE task_id = :task_id AND user_id = :user_id
        ORDER BY `id_response` DESC
        LIMIT 1
    ");
    $stmt->execute(['task_id' => $taskId, 'user_id' => $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all content blocks for a response (for comment page)
 * Recursively retrieves content from parent_response if current response has no content
 * @param int $responseId - ID of the response
 * @return array - Array of content blocks ordered by 'order' field
 */
function getContentByResponse($responseId)
{
    global $pdo;

    // First, try to get content for this response
    $stmt = $pdo->prepare("
        SELECT content_id, response_id, content, `order`, type_create, added_by, created_at
        FROM content
        WHERE response_id = :response_id
        ORDER BY `order` ASC
    ");
    $stmt->execute(['response_id' => $responseId]);
    $content = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If content exists, return it
    if (!empty($content)) {
        return $content;
    }

    // If no content, check if this response has a parent_response
    $parentStmt = $pdo->prepare("
        SELECT parent_response
        FROM response
        WHERE id_response = :response_id
        AND parent_response IS NOT NULL
    ");
    $parentStmt->execute(['response_id' => $responseId]);
    $parentData = $parentStmt->fetch(PDO::FETCH_ASSOC);

    // If has parent, recursively get content from parent
    if ($parentData && $parentData['parent_response']) {
        return getContentByResponse($parentData['parent_response']);
    }

    // No content and no parent - return empty array
    return [];
}

/**
 * Get content block with user info
 * @param int $contentId - ID of the content
 * @return array|false - Content data with user info
 */
function getContentById($contentId)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.*, u.fullname, u.email
        FROM content c
        LEFT JOIN users u ON c.added_by = u.user_id
        WHERE c.content_id = :content_id
    ");
    $stmt->execute(['content_id' => $contentId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Insert a comment for a content block (Max 3 levels)
 * @param int $contentId - ID of the content being commented
 * @param int $userId - ID of the user making the comment
 * @param int $taskId - ID of the task
 * @param string $commentText - The comment text
 * @param int|null $replyCommentId - ID of parent comment (for replies)
 * @return int - ID of the inserted comment
 * @throws Exception if exceeds 3 levels
 */
function insertComment($contentId, $userId, $taskId, $commentText, $replyCommentId = null)
{
    global $pdo;

    // Check depth level if replying to a comment
    if ($replyCommentId) {
        $depth = getCommentDepth($replyCommentId);
        if ($depth >= 2) {
            throw new Exception('Chỉ cho phép tối đa 3 cấp bình luận');
        }
    }

    $stmt = $pdo->prepare("
        INSERT INTO comment (content_id, user_id, task_id, comment_text, reply_comment, created_at)
        VALUES (:content_id, :user_id, :task_id, :comment_text, :reply_comment, NOW())
    ");
    $stmt->execute([
        'content_id' => $contentId,
        'user_id' => $userId,
        'task_id' => $taskId,
        'comment_text' => $commentText,
        'reply_comment' => $replyCommentId ?? 0
    ]);
    return $pdo->lastInsertId();
}

/**
 * Get comment depth level (0 = root, 1 = reply, 2 = reply of reply)
 * @param int $commentId - ID of the comment
 * @return int - Depth level
 */
function getCommentDepth($commentId)
{
    global $pdo;
    $depth = 0;
    $currentId = $commentId;

    while ($currentId > 0 && $depth < 10) { // Max 10 iterations to prevent infinite loop
        $stmt = $pdo->prepare("SELECT reply_comment FROM comment WHERE comment_id = :comment_id");
        $stmt->execute(['comment_id' => $currentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result || $result['reply_comment'] == 0) {
            break;
        }

        $currentId = $result['reply_comment'];
        $depth++;
    }

    return $depth;
}

/**
 * Get all comments for a content block
 * @param int $contentId - ID of the content
 * @return array - Array of comments with user info
 */
function getCommentsByContent($contentId)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.*, u.fullname, u.email
        FROM comment c
        LEFT JOIN users u ON c.user_id = u.user_id
        WHERE c.content_id = :content_id
        ORDER BY c.created_at ASC
    ");
    $stmt->execute(['content_id' => $contentId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add default avatar to all comments
    foreach ($comments as &$comment) {
        $comment['avatar'] = 'https://i.pinimg.com/564x/92/26/5c/92265c40c8e428122e0b32adc1994594.jpg';
    }
    unset($comment);

    return $comments;
}

/**
 * Get hierarchical comments (parent and replies) with depth info
 * @param int $contentId - ID of the content
 * @return array - Array of comments organized hierarchically with depth
 */
function getCommentsHierarchical($contentId)
{
    global $pdo;

    // Get all comments for this content with user role and phase info
    $stmt = $pdo->prepare("
        SELECT c.*, u.fullname, u.email, u.role,
               t.phase_id, p.phase_name, mp.m_phase_name
        FROM comment c
        LEFT JOIN users u ON c.user_id = u.user_id
        LEFT JOIN task t ON c.task_id = t.task_id
        LEFT JOIN phase p ON t.phase_id = p.phase_id
        LEFT JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
        WHERE c.content_id = :content_id
        ORDER BY c.created_at ASC
    ");
    $stmt->execute(['content_id' => $contentId]);
    $allComments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add default avatar and depth to all comments
    foreach ($allComments as &$comment) {
        $comment['avatar'] = 'https://i.pinimg.com/564x/92/26/5c/92265c40c8e428122e0b32adc1994594.jpg';
        $comment['depth'] = getCommentDepth($comment['comment_id']);
        $comment['role_label'] = $comment['role'] === 'teacher' ? 'Giáo viên' : 'Học sinh';
        $comment['phase_label'] = $comment['m_phase_name'] ?? 'Không rõ';
    }
    unset($comment);

    // Organize into parent and child structure
    $organized = [];
    $commentMap = [];

    foreach ($allComments as $comment) {
        $comment['replies'] = [];
        $commentMap[$comment['comment_id']] = $comment;
    }

    foreach ($commentMap as $commentId => $comment) {
        if ($comment['reply_comment'] == 0 || $comment['reply_comment'] == null) {
            // Top level comment
            $organized[] = &$commentMap[$commentId];
        } else {
            // Reply to another comment
            if (isset($commentMap[$comment['reply_comment']])) {
                $commentMap[$comment['reply_comment']]['replies'][] = &$commentMap[$commentId];
            }
        }
    }

    return $organized;
}

/**
 * Delete a comment
 * @param int $commentId - ID of the comment to delete
 * @param int $userId - ID of the user attempting deletion (for permission check)
 * @return bool - Success status
 */
function deleteComment($commentId, $userId)
{
    global $pdo;

    // Check if user owns the comment or is teacher
    $stmt = $pdo->prepare("
        SELECT user_id FROM comment WHERE comment_id = :comment_id
    ");
    $stmt->execute(['comment_id' => $commentId]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$comment) {
        return false;
    }

    // Check permission
    $isOwner = ($comment['user_id'] == $userId);
    $isTeacher = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher';

    if (!$isOwner && !$isTeacher) {
        return false;
    }

    // Delete the comment and its replies
    $stmt = $pdo->prepare("
        DELETE FROM comment 
        WHERE comment_id = :comment_id OR reply_comment = :comment_id
    ");
    $stmt->execute(['comment_id' => $commentId]);

    return true;
}

/**
 * Get response with task info for comment page
 * @param int $responseId - ID of the response
 * @return array|false - Response data with task info
 */
function getResponseWithTaskInfo($responseId)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT r.*, 
               t.title AS task_title, t.description AS task_description,
               p.phase_name, mp.m_phase_name, mp.phase_code, mp.Comment as allow_comment,
               u.fullname as user_fullname, u.email as user_email,
               owner.fullname as owner_fullname, owner.email as owner_email
        FROM response r
        LEFT JOIN task t ON r.task_id = t.task_id
        LEFT JOIN phase p ON t.phase_id = p.phase_id
        LEFT JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
        LEFT JOIN users u ON r.user_id = u.user_id
        LEFT JOIN users owner ON r.owner_user_id = owner.user_id
        WHERE r.id_response = :response_id
    ");
    $stmt->execute(['response_id' => $responseId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get root response for a task and user (student's work)
 * This handles both regular tasks and evaluation tasks (with review_task)
 * Uses owner_user_id to find the student's response chain
 * Then finds the specific response created by the viewing user (teacher or student)
 * 
 * @param int $taskId Task ID
 * @param int $targetUserId Target user ID (student whose work we're viewing)
 * @param int $currentUserId Current viewing user ID (teacher or student viewing the work)
 * @return array|null Response data with task info or null if not found
 */
function getRootResponseForTaskAndUser($taskId, $targetUserId, $currentUserId = null)
{
    global $pdo;

    // If currentUserId not provided, use targetUserId (viewing own work)
    if ($currentUserId === null) {
        $currentUserId = $targetUserId;
    }

    try {
        // First, check if task has review_task (evaluation phase)
        $taskStmt = $pdo->prepare("
            SELECT task_id, review_task 
            FROM task 
            WHERE task_id = :task_id
        ");
        $taskStmt->execute([':task_id' => $taskId]);
        $task = $taskStmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            return null;
        }

        // Determine which task to look for responses
        $searchTaskId = $task['review_task'] ?? $taskId;

        // Find response for this target user (owner) and current user (creator)
        // owner_user_id = student whose work this is (always the student being viewed)
        // user_id = person who created this response (could be teacher or student)
        $responseStmt = $pdo->prepare("
            SELECT 
                r.id_response,
                r.user_id,
                r.owner_user_id,
                r.parent_response,
                r.task_id,
                r.created_at,
                r.updated_at,
                t.title as task_title,
                t.description as task_description,
                p.phase_name,
                mp.m_phase_name,
                mp.phase_code,
                mp.Comment as allow_comment,
                u.fullname as user_fullname,
                owner.fullname as owner_fullname
            FROM response r
            INNER JOIN task t ON r.task_id = t.task_id
            INNER JOIN phase p ON t.phase_id = p.phase_id
            INNER JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
            INNER JOIN users u ON r.user_id = u.user_id
            LEFT JOIN users owner ON r.owner_user_id = owner.user_id
            WHERE r.task_id = :task_id 
            AND r.user_id = :current_user_id
            AND (r.owner_user_id = :target_user_id OR (r.owner_user_id IS NULL AND r.user_id = :target_user_id))
            ORDER BY r.created_at DESC
            LIMIT 1
        ");

        $responseStmt->execute([
            ':task_id' => $searchTaskId,
            ':current_user_id' => $currentUserId,
            ':target_user_id' => $targetUserId
        ]);

        $response = $responseStmt->fetch(PDO::FETCH_ASSOC);

        if (!$response) {
            return null;
        }

        // Follow parent_response chain to find root
        $currentResponse = $response;
        $iterations = 0;
        $maxIterations = 10; // Prevent infinite loop

        while ($currentResponse['parent_response'] !== null && $iterations < $maxIterations) {
            $parentStmt = $pdo->prepare("
                SELECT 
                    r.id_response,
                    r.user_id,
                    r.owner_user_id,
                    r.parent_response,
                    r.task_id,
                    r.created_at,
                    r.updated_at,
                    t.title as task_title,
                    t.description as task_description,
                    p.phase_name,
                    mp.m_phase_name,
                    mp.phase_code,
                    mp.Comment as allow_comment,
                    u.fullname as user_fullname,
                    owner.fullname as owner_fullname
                FROM response r
                INNER JOIN task t ON r.task_id = t.task_id
                INNER JOIN phase p ON t.phase_id = p.phase_id
                INNER JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
                INNER JOIN users u ON r.user_id = u.user_id
                LEFT JOIN users owner ON r.owner_user_id = owner.user_id
                WHERE r.id_response = :parent_id
            ");

            $parentStmt->execute([':parent_id' => $currentResponse['parent_response']]);
            $parentResponse = $parentStmt->fetch(PDO::FETCH_ASSOC);

            if (!$parentResponse) {
                break; // Parent not found, current is root
            }

            $currentResponse = $parentResponse;
            $iterations++;
        }

        return $currentResponse;
    } catch (PDOException $e) {
        error_log("Error in getRootResponseForTaskAndUser: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all students who submitted responses for a task
 * Includes response status and submission time
 * Now uses owner_user_id to identify students (owners of the work)
 * 
 * @param int $taskId Task ID
 * @return array List of students with their response info
 */
function getStudentsWithResponsesForTask($taskId)
{
    global $pdo;

    try {
        // Check if task has review_task
        $taskStmt = $pdo->prepare("
            SELECT task_id, review_task 
            FROM task 
            WHERE task_id = :task_id
        ");
        $taskStmt->execute([':task_id' => $taskId]);
        $task = $taskStmt->fetch(PDO::FETCH_ASSOC);

        if (!$task) {
            return [];
        }

        // Determine which task to look for responses
        $searchTaskId = $task['review_task'] ?? $taskId;

        // Get all students who own responses for this task
        // Use owner_user_id to identify students OR user_id when owner_user_id is NULL (legacy data)
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                COALESCE(r.owner_user_id, r.user_id) as user_id,
                u.fullname,
                u.email,
                MIN(r.id_response) as id_response,
                MIN(r.created_at) as response_created_at,
                MAX(r.updated_at) as response_updated_at,
                (SELECT COUNT(*) FROM content WHERE response_id IN (
                    SELECT id_response FROM response 
                    WHERE task_id = :task_id 
                    AND (owner_user_id = COALESCE(r.owner_user_id, r.user_id) 
                         OR (owner_user_id IS NULL AND user_id = COALESCE(r.owner_user_id, r.user_id)))
                )) as content_count,
                (SELECT COUNT(*) FROM comment c 
                 INNER JOIN content ct ON c.content_id = ct.content_id 
                 WHERE ct.response_id IN (
                    SELECT id_response FROM response 
                    WHERE task_id = :task_id 
                    AND (owner_user_id = COALESCE(r.owner_user_id, r.user_id) 
                         OR (owner_user_id IS NULL AND user_id = COALESCE(r.owner_user_id, r.user_id)))
                )) as comment_count
            FROM response r
            INNER JOIN users u ON COALESCE(r.owner_user_id, r.user_id) = u.user_id
            WHERE r.task_id = :task_id
            GROUP BY COALESCE(r.owner_user_id, r.user_id), u.fullname, u.email
            ORDER BY u.fullname ASC
        ");

        $stmt->execute([':task_id' => $searchTaskId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getStudentsWithResponsesForTask: " . $e->getMessage());
        return [];
    }
}

// ==================== PEER EVALUATION HELPER FUNCTIONS ====================

/**
 * Get current criteria group for a task
 * @param int $taskId
 * @return array|false
 */
function getTaskCriteriaGroup($taskId)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT te.*, ntc.ten_nhom_tc, ntc.description
            FROM task_evaluate te
            LEFT JOIN nhom_tc ntc ON te.id_group_tc = ntc.nhom_tc_id
            WHERE te.id_task = :task_id
        ");
        $stmt->execute([':task_id' => $taskId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getTaskCriteriaGroup: " . $e->getMessage());
        return false;
    }
}

/**
 * Set or update criteria group for a task
 * @param int $taskId
 * @param int $nhomTcId
 * @return bool
 */
function setTaskCriteriaGroup($taskId, $nhomTcId)
{
    global $pdo;
    try {
        // Check if already exists
        $stmt = $pdo->prepare("SELECT id_task FROM task_evaluate WHERE id_task = :task_id");
        $stmt->execute([':task_id' => $taskId]);
        $exists = $stmt->fetch();

        if ($exists) {
            // Update
            $stmt = $pdo->prepare("
                UPDATE task_evaluate 
                SET id_group_tc = :nhom_tc_id
                WHERE id_task = :task_id
            ");
        } else {
            // Insert
            $stmt = $pdo->prepare("
                INSERT INTO task_evaluate (id_task, id_group_tc)
                VALUES (:task_id, :nhom_tc_id)
            ");
        }

        return $stmt->execute([
            ':task_id' => $taskId,
            ':nhom_tc_id' => $nhomTcId
        ]);
    } catch (PDOException $e) {
        error_log("Error in setTaskCriteriaGroup: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if any evaluations exist for a task
 * @param int $taskId
 * @return bool
 */
function hasTaskEvaluations($taskId)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM deliver_evaluate de
            JOIN deliver d ON de.id_deliver = d.id_deliver
            WHERE d.task_id = :task_id
        ");
        $stmt->execute([':task_id' => $taskId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    } catch (PDOException $e) {
        error_log("Error in hasTaskEvaluations: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all delivers (assignments) that need to be evaluated for a task
 * If task has review_task, get delivers from that task instead
 * @param int $taskId - Evaluation task ID
 * @return array
 */
function getResponsesForEvaluation($taskId)
{
    global $pdo;
    try {
        // First, check if this task has a review_task (is an evaluation task)
        $taskStmt = $pdo->prepare("SELECT review_task FROM task WHERE task_id = :task_id");
        $taskStmt->execute([':task_id' => $taskId]);
        $taskInfo = $taskStmt->fetch(PDO::FETCH_ASSOC);

        // Use review_task if it exists, otherwise use the current task_id
        $sourceTaskId = $taskInfo['review_task'] ?? $taskId;

        $stmt = $pdo->prepare("
            SELECT 
                d.id_deliver,
                d.type as deliver_type,
                d.value as deliver_value,
                d.response,
                d.status as deliver_status,
                d.created_at,
                d.updated_at,
                CASE 
                    WHEN d.type = 'user' THEN u.fullname
                    WHEN d.type = 'group' THEN g.group_name
                    ELSE 'Cả lớp'
                END as submitter_name,
                CASE 
                    WHEN d.type = 'user' THEN u.user_id
                    WHEN d.type = 'group' THEN g.group_id
                    ELSE NULL
                END as submitter_id
            FROM deliver d
            LEFT JOIN users u ON d.type = 'user' AND d.value = u.user_id
            LEFT JOIN `group` g ON d.type = 'group' AND d.value = g.group_id
            WHERE d.task_id = :task_id
            AND (d.status = 1 OR d.status = 2)
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([':task_id' => $sourceTaskId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getResponsesForEvaluation: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all evaluator assignments for a task
 * @param int $taskId
 * @return array Keyed by response_id
 */
function getEvaluatorAssignments($taskId)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT 
                de.response_review,
                de.id_deliver as evaluator_deliver_id,
                d.type as evaluator_type,
                d.value as evaluator_value,
                CASE 
                    WHEN d.type = 'user' THEN u.fullname
                    WHEN d.type = 'group' THEN g.group_name
                    ELSE 'Cả lớp'
                END as evaluator_name
            FROM deliver_evaluate de
            JOIN deliver d ON de.id_deliver = d.id_deliver
            LEFT JOIN users u ON d.type = 'user' AND d.value = u.user_id
            LEFT JOIN `group` g ON d.type = 'group' AND d.value = g.group_id
            WHERE d.task_id = :task_id
        ");
        $stmt->execute([':task_id' => $taskId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Group by response_review
        $assignments = [];
        foreach ($results as $row) {
            $responseId = $row['response_review'];
            if (!isset($assignments[$responseId])) {
                $assignments[$responseId] = [];
            }
            $assignments[$responseId][] = $row;
        }

        return $assignments;
    } catch (PDOException $e) {
        error_log("Error in getEvaluatorAssignments: " . $e->getMessage());
        return [];
    }
}

/**
 * Assign evaluator to a response
 * @param int $taskId
 * @param int $responseId Response to be evaluated
 * @param string $evaluatorType 'user' or 'group'
 * @param int $evaluatorValue user_id or group_id
 * @param int $addedBy Teacher user_id
 * @return bool|string True on success, error message on failure
 */
function assignEvaluator($taskId, $responseId, $evaluatorType, $evaluatorValue, $addedBy)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Check if evaluator is trying to evaluate their own work
        $stmt = $pdo->prepare("
            SELECT d.type, d.value, r.user_id
            FROM deliver d
            JOIN response r ON d.response = r.id_response
            WHERE r.id_response = :response_id
        ");
        $stmt->execute([':response_id' => $responseId]);
        $submission = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$submission) {
            $pdo->rollBack();
            return "Response không tồn tại";
        }

        // Prevent self-evaluation
        if ($evaluatorType === 'user' && $submission['type'] === 'user' && $submission['value'] == $evaluatorValue) {
            $pdo->rollBack();
            return "Không thể phân công người nộp bài tự chấm bài của chính họ";
        }

        if ($evaluatorType === 'group' && $submission['type'] === 'group' && $submission['value'] == $evaluatorValue) {
            $pdo->rollBack();
            return "Không thể phân công nhóm nộp bài tự chấm bài của chính họ";
        }

        // Find or create deliver record for evaluator
        $stmt = $pdo->prepare("
            SELECT id_deliver 
            FROM deliver 
            WHERE task_id = :task_id 
            AND type = :type 
            AND value = :value
        ");
        $stmt->execute([
            ':task_id' => $taskId,
            ':type' => $evaluatorType,
            ':value' => $evaluatorValue
        ]);
        $deliverRecord = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$deliverRecord) {
            // Create new deliver record for evaluator
            $stmt = $pdo->prepare("
                INSERT INTO deliver (task_id, type, value, status, added_by)
                VALUES (:task_id, :type, :value, 0, :added_by)
            ");
            $stmt->execute([
                ':task_id' => $taskId,
                ':type' => $evaluatorType,
                ':value' => $evaluatorValue,
                ':added_by' => $addedBy
            ]);
            $evaluatorDeliverId = $pdo->lastInsertId();
        } else {
            $evaluatorDeliverId = $deliverRecord['id_deliver'];
        }

        // Check if assignment already exists
        $stmt = $pdo->prepare("
            SELECT 1 FROM deliver_evaluate 
            WHERE id_deliver = :id_deliver 
            AND response_review = :response_review
        ");
        $stmt->execute([
            ':id_deliver' => $evaluatorDeliverId,
            ':response_review' => $responseId
        ]);

        if (!$stmt->fetch()) {
            // Create deliver_evaluate record
            $stmt = $pdo->prepare("
                INSERT INTO deliver_evaluate (id_deliver, response_review)
                VALUES (:id_deliver, :response_review)
            ");
            $stmt->execute([
                ':id_deliver' => $evaluatorDeliverId,
                ':response_review' => $responseId
            ]);
        }

        $pdo->commit();
        return true;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error in assignEvaluator: " . $e->getMessage());
        return "Lỗi database: " . $e->getMessage();
    }
}

/**
 * Remove evaluator assignment from a response
 * @param int $evaluatorDeliverId
 * @param int $responseId
 * @return bool
 */
function removeEvaluatorAssignment($evaluatorDeliverId, $responseId)
{
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            DELETE FROM deliver_evaluate 
            WHERE id_deliver = :id_deliver 
            AND response_review = :response_review
        ");
        return $stmt->execute([
            ':id_deliver' => $evaluatorDeliverId,
            ':response_review' => $responseId
        ]);
    } catch (PDOException $e) {
        error_log("Error in removeEvaluatorAssignment: " . $e->getMessage());
        return false;
    }
}
