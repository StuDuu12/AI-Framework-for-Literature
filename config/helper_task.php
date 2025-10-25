<?php
/**
 * Helper functions for Task management
 */

/**
 * Get all tasks for a specific phase
 * @param int $phaseId
 * @return array
 */
function getPhaseTasks($phaseId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                t.*,
                u.fullname as added_by_name,
                uc.fullname as content_creator_name
            FROM task t
            LEFT JOIN users u ON t.added_by = u.user_id
            LEFT JOIN users uc ON t.content_creator = uc.user_id
            WHERE t.phase_id = :phase_id
            ORDER BY t.created_at DESC
        ");

        $stmt->execute([':phase_id' => $phaseId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getPhaseTasks: " . $e->getMessage());
        return [];
    }
}

/**
 * Get task details by ID
 * @param int $taskId
 * @return array|false
 */
function getTaskDetails($taskId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                t.*,
                u.fullname as added_by_name,
                uc.fullname as content_creator_name,
                p.phase_name,
                p.phase_number,
                tp.topic_name
            FROM task t
            LEFT JOIN users u ON t.added_by = u.user_id
            LEFT JOIN users uc ON t.content_creator = uc.user_id
            LEFT JOIN phase p ON t.phase_id = p.phase_id
            LEFT JOIN topic tp ON p.topic_id = tp.topic_id
            WHERE t.task_id = :task_id
        ");

        $stmt->execute([':task_id' => $taskId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getTaskDetails: " . $e->getMessage());
        return false;
    }
}

/**
 * Create a new task
 * @param array $data
 * @return int|false Task ID on success, false on failure
 */
function createTask($data)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO task (
                phase_id, 
                title, 
                description, 
                file_content, 
                content_creator, 
                added_by
            ) VALUES (
                :phase_id, 
                :title, 
                :description, 
                :file_content, 
                :content_creator, 
                :added_by
            )
        ");

        $stmt->execute([
            ':phase_id' => $data['phase_id'],
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':file_content' => $data['file_content'] ?? null,
            ':content_creator' => $data['content_creator'] ?? null,
            ':added_by' => $data['added_by']
        ]);

        $taskId = $pdo->lastInsertId();
        $pdo->commit();

        return $taskId;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error in createTask: " . $e->getMessage());
        return false;
    }
}

/**
 * Update an existing task
 * @param int $taskId
 * @param array $data
 * @return bool
 */
function updateTask($taskId, $data)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            UPDATE task SET
                title = :title,
                description = :description,
                file_content = :file_content,
                content_creator = :content_creator
            WHERE task_id = :task_id
        ");

        return $stmt->execute([
            ':task_id' => $taskId,
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':file_content' => $data['file_content'] ?? null,
            ':content_creator' => $data['content_creator'] ?? null
        ]);
    } catch (PDOException $e) {
        error_log("Error in updateTask: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a task
 * @param int $taskId
 * @return bool
 */
function deleteTask($taskId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("DELETE FROM task WHERE task_id = :task_id");
        return $stmt->execute([':task_id' => $taskId]);
    } catch (PDOException $e) {
        error_log("Error in deleteTask: " . $e->getMessage());
        return false;
    }
}

/**
 * Get phase details for task creation
 * @param int $phaseId
 * @return array|false
 */
function getPhaseForTask($phaseId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.*,
                t.topic_name,
                t.class_id
            FROM phase p
            JOIN topic t ON p.topic_id = t.topic_id
            WHERE p.phase_id = :phase_id
        ");

        $stmt->execute([':phase_id' => $phaseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getPhaseForTask: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if file content exists
 * @param string $filePath
 * @return bool
 */
function taskFileExists($filePath)
{
    if (empty($filePath)) {
        return false;
    }

    $fullPath = __DIR__ . '/../uploads/' . $filePath;
    return file_exists($fullPath);
}

/**
 * Read task file content
 * @param string $filePath
 * @return string|false
 */
function readTaskFileContent($filePath)
{
    if (!taskFileExists($filePath)) {
        return false;
    }

    $fullPath = __DIR__ . '/../uploads/' . $filePath;
    return file_get_contents($fullPath);
}

/**
 * Get all assignments (delivers) for a task
 * @param int $taskId
 * @return array
 */
function getTaskAssignments($taskId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                d.*,
                CASE 
                    WHEN d.type = 1 THEN c.class_name
                    WHEN d.type = 2 THEN g.group_name
                    WHEN d.type = 3 THEN u.fullname
                END as assigned_to_name
            FROM deliver d
            LEFT JOIN class c ON d.type = 1 AND d.value = c.class_id
            LEFT JOIN `group` g ON d.type = 2 AND d.value = g.group_id
            LEFT JOIN users u ON d.type = 3 AND d.value = u.user_id
            WHERE d.task_id = :task_id
            ORDER BY d.type, d.id_deliver
        ");

        $stmt->execute([':task_id' => $taskId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getTaskAssignments: " . $e->getMessage());
        return [];
    }
}

/**
 * Assign task to targets
 * @param int $taskId
 * @param int $type (1=class, 2=group, 3=individual)
 * @param array $values
 * @return bool
 */
function assignTask($taskId, $type, $values, $userId)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO deliver (task_id, type, value, added_by)
            VALUES (:task_id, :type, :value, :added_by)
        ");

        foreach ($values as $value) {
            $stmt->execute([
                ':task_id' => $taskId,
                ':type' => $type,
                ':value' => $value,
                ':added_by' => $userId
            ]);
        }

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error in assignTask: " . $e->getMessage());
        return false;
    }
}

/**
 * Remove task assignment
 * @param int $deliverId
 * @return bool
 */
function removeTaskAssignment($deliverId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("DELETE FROM deliver WHERE id_deliver = :id");
        return $stmt->execute([':id' => $deliverId]);
    } catch (PDOException $e) {
        error_log("Error in removeTaskAssignment: " . $e->getMessage());
        return false;
    }
}

/**
 * Get class members (students only)
 * @param int $classId
 * @return array
 */
function getClassStudents($classId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                u.user_id,
                u.fullname,
                u.username,
                u.email
            FROM class_member cm
            JOIN users u ON cm.user_id = u.user_id
            WHERE cm.class_id = :class_id 
            AND cm.role = 'student'
            ORDER BY u.fullname
        ");

        $stmt->execute([':class_id' => $classId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getClassStudents: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all groups in a class for task assignment
 * @param int $classId
 * @return array
 */
function getClassGroupsForTask($classId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                g.group_id,
                g.group_name,
                COUNT(gm.user_id) as member_count
            FROM `group` g
            LEFT JOIN group_member gm ON g.group_id = gm.group_id
            WHERE g.class_id = :class_id
            GROUP BY g.group_id, g.group_name
            ORDER BY g.group_name
        ");

        $stmt->execute([':class_id' => $classId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getClassGroupsForTask: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if task is already assigned to a target
 * @param int $taskId
 * @param int $type
 * @param int $value
 * @return bool
 */
function isTaskAssigned($taskId, $type, $value)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM deliver 
            WHERE task_id = :task_id 
            AND type = :type 
            AND value = :value
        ");

        $stmt->execute([
            ':task_id' => $taskId,
            ':type' => $type,
            ':value' => $value
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    } catch (PDOException $e) {
        error_log("Error in isTaskAssigned: " . $e->getMessage());
        return false;
    }
}

