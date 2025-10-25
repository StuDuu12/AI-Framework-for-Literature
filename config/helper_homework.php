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
            mp.m_phase_id, mp.m_phase_name, mp.phase_code, mp.AI, mp.Comment, mp.Edit, mp.teacher, mp.student
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

