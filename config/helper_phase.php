<?php
/**
 * Helper functions for Phase management
 */

/**
 * Get all phases of a topic
 */
function getTopicPhases($topicId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            mp.m_phase_name,
            mp.phase_code,
            mp.AI,
            mp.Comment,
            mp.Edit,
            mp.description as phase_description,
            u.fullname as added_by_name
        FROM phase p
        INNER JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
        LEFT JOIN users u ON p.added_by = u.user_id
        WHERE p.topic_id = ?
        ORDER BY p.phase_number ASC
    ");

    $stmt->execute([$topicId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get phase details by ID
 */
function getPhaseDetails($phaseId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            mp.m_phase_name,
            mp.phase_code,
            mp.AI,
            mp.Comment,
            mp.Edit,
            mp.description as phase_description
        FROM phase p
        INNER JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
        WHERE p.phase_id = ?
    ");

    $stmt->execute([$phaseId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get all main phase types
 */
function getAllMainPhases()
{
    global $pdo;

    $stmt = $pdo->query("
        SELECT * FROM main_phase
        ORDER BY m_phase_id ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get main phase by ID
 */
function getMainPhaseById($mainPhaseId)
{
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM main_phase WHERE m_phase_id = ?");
    $stmt->execute([$mainPhaseId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Check if topic has an active phase
 */
function hasActivePhase($topicId, $excludePhaseId = null)
{
    global $pdo;

    $sql = "SELECT COUNT(*) FROM phase WHERE topic_id = ? AND flag_active = 1";
    $params = [$topicId];

    if ($excludePhaseId !== null) {
        $sql .= " AND phase_id != ?";
        $params[] = $excludePhaseId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

/**
 * Get next phase order number for a topic
 */
function getNextPhaseOrder($topicId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT COALESCE(MAX(phase_number), 0) + 1 as next_order
        FROM phase
        WHERE topic_id = ?
    ");

    $stmt->execute([$topicId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['next_order'];
}

/**
 * Create a new phase
 */
function createPhase($topicId, $phaseName, $mainPhaseId, $startTime, $endTime, $flagActive, $addedBy)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        // If flagActive is true, deactivate other phases in the same topic
        if ($flagActive) {
            $stmt = $pdo->prepare("UPDATE phase SET flag_active = 0 WHERE topic_id = ?");
            $stmt->execute([$topicId]);
        }

        // Get next order
        $phaseNumber = getNextPhaseOrder($topicId);

        // Insert new phase
        $stmt = $pdo->prepare("
            INSERT INTO phase (topic_id, phase_name, m_phase_id, phase_number, start_time, end_time, flag_active, added_by, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $topicId,
            $phaseName,
            $mainPhaseId,
            $phaseNumber,
            $startTime,
            $endTime,
            $flagActive ? 1 : 0,
            $addedBy
        ]);

        $phaseId = $pdo->lastInsertId();
        $pdo->commit();

        return $phaseId;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Update phase
 */
function updatePhase($phaseId, $phaseName, $mainPhaseId, $startTime, $endTime, $flagActive)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Get phase info
        $phase = getPhaseDetails($phaseId);
        if (!$phase) {
            throw new Exception("Phase not found");
        }

        // If flagActive is true, deactivate other phases in the same topic
        if ($flagActive) {
            $stmt = $pdo->prepare("UPDATE phase SET flag_active = 0 WHERE topic_id = ? AND phase_id != ?");
            $stmt->execute([$phase['topic_id'], $phaseId]);
        }

        // Update phase
        $stmt = $pdo->prepare("
            UPDATE phase 
            SET phase_name = ?, m_phase_id = ?, start_time = ?, end_time = ?, flag_active = ?, updated_at = NOW()
            WHERE phase_id = ?
        ");

        $stmt->execute([
            $phaseName,
            $mainPhaseId,
            $startTime,
            $endTime,
            $flagActive ? 1 : 0,
            $phaseId
        ]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Delete phase
 */
function deletePhase($phaseId)
{
    global $pdo;

    $stmt = $pdo->prepare("DELETE FROM phase WHERE phase_id = ?");
    return $stmt->execute([$phaseId]);
}

/**
 * Check if user is teacher in the class (via topic)
 */
function isTeacherInTopicClass($topicId, $userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT cm.role
        FROM topic t
        INNER JOIN class_member cm ON t.class_id = cm.class_id
        WHERE t.topic_id = ? AND cm.user_id = ?
    ");

    $stmt->execute([$topicId, $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result && $result['role'] === 'teacher';
}

/* ------------------------------------------------------------------
 * Phân quyền theo đối tượng (chống IDOR xuyên lớp)
 *
 * Luôn suy ra topic/class từ CHÍNH đối tượng bị thao tác (task, deliver,
 * phase, group...) thay vì tin topic_id/class_id do client gửi lên.
 * ------------------------------------------------------------------ */

/** topic_id thực của một task (0 nếu không tồn tại) */
function getTopicIdOfTask($taskId): int
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.topic_id
        FROM task t
        INNER JOIN phase p ON t.phase_id = p.phase_id
        WHERE t.task_id = ?
    ");
    $stmt->execute([(int)$taskId]);
    return (int)($stmt->fetchColumn() ?: 0);
}

/** topic_id thực của một phase (0 nếu không tồn tại) */
function getTopicIdOfPhase($phaseId): int
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT topic_id FROM phase WHERE phase_id = ?");
    $stmt->execute([(int)$phaseId]);
    return (int)($stmt->fetchColumn() ?: 0);
}

/** topic_id thực của một deliver (qua task) */
function getTopicIdOfDeliver($deliverId): int
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.topic_id
        FROM deliver d
        INNER JOIN task t ON d.task_id = t.task_id
        INNER JOIN phase p ON t.phase_id = p.phase_id
        WHERE d.id_deliver = ?
    ");
    $stmt->execute([(int)$deliverId]);
    return (int)($stmt->fetchColumn() ?: 0);
}

/** class_id thực của một topic */
function getClassIdOfTopic($topicId): int
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT class_id FROM topic WHERE topic_id = ?");
    $stmt->execute([(int)$topicId]);
    return (int)($stmt->fetchColumn() ?: 0);
}

/** class_id thực của một nhóm học sinh */
function getClassIdOfGroup($groupId): int
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT class_id FROM `group` WHERE group_id = ?");
    $stmt->execute([(int)$groupId]);
    return (int)($stmt->fetchColumn() ?: 0);
}

/** User là giáo viên của lớp chứa task này */
function isTeacherOfTask($taskId, $userId): bool
{
    $topicId = getTopicIdOfTask($taskId);
    return $topicId > 0 && isTeacherInTopicClass($topicId, $userId);
}

/** Task có thực sự thuộc topic này không */
function taskBelongsToTopic($taskId, $topicId): bool
{
    $real = getTopicIdOfTask($taskId);
    return $real > 0 && $real === (int)$topicId;
}

/**
 * Dùng trong view: các trang kiểm tra quyền giáo viên theo topic_id trên URL,
 * nên task_id / phase_id trên URL bắt buộc phải thuộc đúng topic đó.
 * Nếu không khớp => chuyển tới trang 403 và dừng.
 */
function requireIdsInTopic(int $topicId, int $taskId = 0, int $phaseId = 0): void
{
    $ok = $topicId > 0
        && ($taskId <= 0 || getTopicIdOfTask($taskId) === $topicId)
        && ($phaseId <= 0 || getTopicIdOfPhase($phaseId) === $topicId);

    if ($ok) {
        return;
    }

    $target = (defined('URL_ROOT') ? URL_ROOT : '') . '/403';
    if (!headers_sent()) {
        header('Location: ' . $target);
    } else {
        echo '<script>location.href=' . json_encode($target) . ';</script>';
    }
    exit;
}

/**
 * Check if user is teacher in the class (directly by class_id)
 */
function isTeacherInClass($classId, $userId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT role
        FROM class_member
        WHERE class_id = ? AND user_id = ?
    ");

    $stmt->execute([$classId, $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result && $result['role'] === 'teacher';
}

/**
 * Nhãn tiếng Việt + icon cho loại giai đoạn (main_phase.phase_code).
 * Dùng chung cho trang Hôm nay, timeline chủ đề, thẻ nhiệm vụ.
 */
if (!function_exists('phaseTypeMeta')) {
    function phaseTypeMeta(?string $code, int $phaseNumber = 0): array
    {
        $map = [
            'PHASE_START' => ['Phân tích cùng AI', 'bi-stars'],
            'PHASE_BTVN' => ['Bài tập về nhà', 'bi-house-door'],
            'PHASE_CHUABAI' => ['Chữa bài', 'bi-pencil-square'],
            'PHASE_DANHGIANGUOIHOC' => ['Đánh giá chéo', 'bi-people'],
            'PHASE_CHAMDIEM' => ['Chấm điểm', 'bi-award'],
            'PHASE_TAKENOTE' => ['Ghi chép kiến thức', 'bi-journal-text'],
        ];
        return $map[$code ?? ''] ?? ['Giai đoạn ' . $phaseNumber, 'bi-flag'];
    }
}

/**
 * Nhiệm vụ của một học sinh trong một chủ đề, nhóm theo phase_id.
 * Bao gồm bài giao cá nhân, cho nhóm của học sinh, hoặc cả lớp.
 */
if (!function_exists('getStudentTasksByPhase')) {
    function getStudentTasksByPhase(int $topicId, int $userId): array
    {
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT d.id_deliver, d.status, d.type AS deliver_type, t.task_id, t.title, t.phase_id,
                   COALESCE(d.deadline, t.deadline, p.end_time) AS due_at
            FROM deliver d
            JOIN task t ON t.task_id = d.task_id
            JOIN phase p ON p.phase_id = t.phase_id
            JOIN topic top ON top.topic_id = p.topic_id
            WHERE p.topic_id = :topic
              AND (
                    (d.type = 'user' AND d.value = :u1)
                 OR (d.type = 'class' AND d.value = top.class_id)
                 OR (d.type = 'group' AND EXISTS (SELECT 1 FROM group_member gm WHERE gm.group_id = d.value AND gm.user_id = :u2))
              )
            ORDER BY t.task_id
        ");
        $stmt->execute([':topic' => $topicId, ':u1' => $userId, ':u2' => $userId]);
        $byPhase = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $byPhase[(int)$row['phase_id']][] = $row;
        }
        return $byPhase;
    }
}
