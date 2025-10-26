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
        'parent_response' => $option['parent_response'] ?? null,
        'json_ai' => $option['json_ai'] ?? null,
    ];

    if (is_array($payload['json_ai'])) {
        $payload['json_ai'] = json_encode($payload['json_ai'], JSON_UNESCAPED_UNICODE);
    }

    $stmt = $pdo->prepare("
        INSERT INTO response (task_id, user_id, parent_response, json_ai)
        VALUES (:task_id, :user_id, :parent_response, :json_ai)
    ");

    $stmt->bindValue(':task_id', $payload['task_id'], PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $payload['user_id'], PDO::PARAM_INT);
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