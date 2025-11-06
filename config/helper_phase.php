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
