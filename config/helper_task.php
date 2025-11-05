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
                review_task, 
                added_by
            ) VALUES (
                :phase_id, 
                :title, 
                :description, 
                :file_content, 
                :content_creator,
                :review_task, 
                :added_by
            )
        ");

        $stmt->execute([
            ':phase_id' => $data['phase_id'],
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':file_content' => $data['file_content'] ?? null,
            ':content_creator' => $data['content_creator'] ?? null,
            ':review_task' => $data['review_task'] ?? null,
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
                content_creator = :content_creator,
                review_task = :review_task
            WHERE task_id = :task_id
        ");

        return $stmt->execute([
            ':task_id' => $taskId,
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':file_content' => $data['file_content'] ?? null,
            ':content_creator' => $data['content_creator'] ?? null,
            ':review_task' => $data['review_task'] ?? null
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
                    WHEN d.type = 'group' THEN g.group_name
                    WHEN d.type = 'user' THEN u.fullname
                END as assigned_to_name
            FROM deliver d
            LEFT JOIN `group` g ON d.type = 'group' AND d.value = g.group_id
            LEFT JOIN users u ON d.type = 'user' AND d.value = u.user_id
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
 * Get task assignment statistics
 * @param int $taskId
 * @return array
 */
function getTaskAssignmentStats($taskId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_assigned,
                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as not_started,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as completed,
                COUNT(DISTINCT type) as assignment_types
            FROM deliver
            WHERE task_id = :task_id
        ");

        $stmt->execute([':task_id' => $taskId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get assignment type details
        $typeStmt = $pdo->prepare("
            SELECT 
                type,
                COUNT(*) as count,
                GROUP_CONCAT(
                    CASE 
                        WHEN type = 'group' THEN g.group_name
                        WHEN type = 'user' THEN u.fullname
                    END
                    SEPARATOR ', '
                ) as names
            FROM deliver d
            LEFT JOIN `group` g ON d.type = 'group' AND d.value = g.group_id
            LEFT JOIN users u ON d.type = 'user' AND d.value = u.user_id
            WHERE d.task_id = :task_id
            GROUP BY type
        ");

        $typeStmt->execute([':task_id' => $taskId]);
        $stats['types'] = $typeStmt->fetchAll(PDO::FETCH_ASSOC);

        return $stats;
    } catch (PDOException $e) {
        error_log("Error in getTaskAssignmentStats: " . $e->getMessage());
        return [
            'total_assigned' => 0,
            'not_started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'assignment_types' => 0,
            'types' => []
        ];
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

/**
 * Get tasks from PHASE_START and PHASE_BTVN for evaluation phases
 * @param int $topicId
 * @return array
 */
function getEvaluationSourceTasks($topicId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                t.task_id,
                t.title,
                t.description,
                p.phase_name,
                p.phase_number,
                mp.m_phase_name,
                mp.phase_code
            FROM task t
            INNER JOIN phase p ON t.phase_id = p.phase_id
            INNER JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
            WHERE p.topic_id = :topic_id
            AND mp.phase_code IN ('PHASE_START', 'PHASE_BTVN')
            ORDER BY p.phase_number ASC, t.created_at ASC
        ");

        $stmt->execute([':topic_id' => $topicId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getEvaluationSourceTasks: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if phase requires evaluation (based on phase_code)
 * @param string $phaseCode
 * @return bool
 */
function isEvaluationPhase($phaseCode)
{
    $evaluationPhaseCodes = ['PHASE_CHUABAI', 'PHASE_DANHGIANGUOIHOC', 'PHASE_CHAMDIEM'];
    return in_array($phaseCode, $evaluationPhaseCodes);
}

/**
 * Get tasks assigned to a student in a specific phase
 * @param int $phaseId
 * @param int $userId - Student user ID
 * @return array
 */
function getStudentAssignedTasks($phaseId, $userId)
{
    global $pdo;

    try {
        // Get student's groups
        $groupStmt = $pdo->prepare("
            SELECT group_id 
            FROM group_member 
            WHERE user_id = :user_id
        ");
        $groupStmt->execute([':user_id' => $userId]);
        $userGroups = $groupStmt->fetchAll(PDO::FETCH_COLUMN);

        // Build conditions based on what assignments exist
        $conditions = [];
        $params = [':phase_id' => $phaseId, ':user_id' => $userId];

        // Always include individual assignments
        $conditions[] = "(d.type = 'user' AND d.value = :user_id)";

        // Add group conditions if user is in any groups
        if (!empty($userGroups)) {
            $groupPlaceholders = [];
            foreach ($userGroups as $index => $groupId) {
                $key = ":group_$index";
                $groupPlaceholders[] = $key;
                $params[$key] = $groupId;
            }
            $conditions[] = "(d.type = 'group' AND d.value IN (" . implode(',', $groupPlaceholders) . "))";
        }

        // If no conditions (shouldn't happen), return empty
        if (empty($conditions)) {
            return [];
        }

        // Build and execute query
        $sql = "
            SELECT DISTINCT
                t.*,
                u.fullname as added_by_name,
                uc.fullname as content_creator_name,
                d.type as delivery_type,
                d.value as delivery_value,
                d.status as delivery_status,
                d.response as delivery_response,
                d.id_deliver,
                CASE 
                    WHEN d.type = 'group' THEN g.group_name
                    WHEN d.type = 'user' THEN u2.fullname
                END as assigned_to_name
            FROM task t
            INNER JOIN deliver d ON t.task_id = d.task_id
            LEFT JOIN users u ON t.added_by = u.user_id
            LEFT JOIN users uc ON t.content_creator = uc.user_id
            LEFT JOIN `group` g ON d.type = 'group' AND d.value = g.group_id
            LEFT JOIN users u2 ON d.type = 'user' AND d.value = u2.user_id
            WHERE t.phase_id = :phase_id
            AND (" . implode(' OR ', $conditions) . ")
            ORDER BY t.created_at DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);


        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getStudentAssignedTasks: " . $e->getMessage());
        return [];
    }
}

/**
 * Get deliver_id for a specific task and user
 * Checks if user is assigned to the task (directly or through group)
 * Note: Class-wide assignments have been removed from the system
 * 
 * @param int $taskId Task ID
 * @param int $userId User ID
 * @return int|null Deliver ID if found and user is authorized, null otherwise
 */
function getDeliverIdForUser($taskId, $userId)
{
    global $pdo;

    try {
        // First check if assigned directly to user
        $stmt = $pdo->prepare("
            SELECT id_deliver 
            FROM deliver 
            WHERE task_id = :task_id 
            AND type = 'user' 
            AND value = :user_id
        ");
        $stmt->execute(['task_id' => $taskId, 'user_id' => $userId]);
        $deliver = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($deliver) {
            return (int) $deliver['id_deliver'];
        }

        // Check if assigned to user's group
        $stmt = $pdo->prepare("
            SELECT d.id_deliver 
            FROM deliver d
            INNER JOIN group_member gm ON d.value = gm.group_id
            WHERE d.task_id = :task_id 
            AND d.type = 'group' 
            AND gm.user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute(['task_id' => $taskId, 'user_id' => $userId]);
        $deliver = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($deliver) {
            return (int) $deliver['id_deliver'];
        }

        return null;
    } catch (PDOException $e) {
        error_log("Error in getDeliverIdForUser: " . $e->getMessage());
        return null;
    }
}

/**
 * Validate if a user has access to a specific deliver
 * Checks if the user is the assigned recipient (directly or through group membership)
 * 
 * @param int $deliverId Deliver ID
 * @param int $userId User ID
 * @return bool True if user has access, false otherwise
 */
function validateUserDeliverAccess($deliverId, $userId)
{
    global $pdo;

    try {
        // Get deliver details
        $stmt = $pdo->prepare("
            SELECT type, value, task_id
            FROM deliver 
            WHERE id_deliver = :deliver_id
        ");
        $stmt->execute(['deliver_id' => $deliverId]);
        $deliver = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$deliver) {
            return false;
        }

        // Check based on assignment type
        if ($deliver['type'] === 'user') {
            // Direct assignment - check if value matches user_id
            return (int) $deliver['value'] === (int) $userId;
        } elseif ($deliver['type'] === 'group') {
            // Group assignment - check if user is member of the group
            $stmt = $pdo->prepare("
                SELECT 1 
                FROM group_member 
                WHERE group_id = :group_id 
                AND user_id = :user_id
            ");
            $stmt->execute([
                'group_id' => $deliver['value'],
                'user_id' => $userId
            ]);
            return $stmt->fetch() !== false;
        }

        return false;
    } catch (PDOException $e) {
        error_log("Error in validateUserDeliverAccess: " . $e->getMessage());
        return false;
    }
}

/**
 * Update deliver status and response
 * Updates the deliver record with response_id and/or status
 * 
 * @param int $deliverId Deliver ID
 * @param int|null $responseId Response ID (reference to response.id_response)
 * @param int|null $status Status: 0=assigned, 1=in_progress, 2=completed
 * @return bool True if updated successfully, false otherwise
 */
function updateDeliverStatus($deliverId, $responseId = null, $status = null)
{
    global $pdo;

    try {
        $updates = [];
        $params = ['deliver_id' => $deliverId];

        if ($responseId !== null) {
            $updates[] = "response = :response";
            $params['response'] = $responseId;
        }

        if ($status !== null) {
            $updates[] = "status = :status";
            $params['status'] = $status;
        }

        if (empty($updates)) {
            return false;
        }

        $sql = "UPDATE deliver SET " . implode(', ', $updates) . " WHERE id_deliver = :deliver_id";
        $stmt = $pdo->prepare($sql);
        
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Error in updateDeliverStatus: " . $e->getMessage());
        return false;
    }
}

/**
 * Get deliver details by ID
 * 
 * @param int $deliverId Deliver ID
 * @return array|null Deliver details or null if not found
 */
function getDeliverDetails($deliverId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT d.*, t.title as task_title
            FROM deliver d
            INNER JOIN task t ON d.task_id = t.task_id
            WHERE d.id_deliver = :deliver_id
        ");
        $stmt->execute(['deliver_id' => $deliverId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getDeliverDetails: " . $e->getMessage());
        return null;
    }
}


