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

        $deadline = !empty($data['deadline']) ? $data['deadline'] : null;

        $stmt = $pdo->prepare("
            INSERT INTO task (
                phase_id,
                title,
                description,
                file_content,
                content_creator,
                review_task,
                added_by,
                deadline
            ) VALUES (
                :phase_id,
                :title,
                :description,
                :file_content,
                :content_creator,
                :review_task,
                :added_by,
                :deadline
            )
        ");

        $stmt->execute([
            ':phase_id' => $data['phase_id'],
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':file_content' => $data['file_content'] ?? null,
            ':content_creator' => $data['content_creator'] ?? null,
            ':review_task' => $data['review_task'] ?? null,
            ':added_by' => $data['added_by'],
            ':deadline' => $deadline
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
        $deadline = !empty($data['deadline']) ? $data['deadline'] : null;

        $stmt = $pdo->prepare("
            UPDATE task SET
                title = :title,
                description = :description,
                file_content = :file_content,
                content_creator = :content_creator,
                review_task = :review_task,
                deadline = :deadline
            WHERE task_id = :task_id
        ");

        return $stmt->execute([
            ':task_id' => $taskId,
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':file_content' => $data['file_content'] ?? null,
            ':content_creator' => $data['content_creator'] ?? null,
            ':review_task' => $data['review_task'] ?? null,
            ':deadline' => $deadline
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
 * @param int $type (1=class, 2=group, 3=user)
 * @param array $values
 * @param int $userId
 * @return bool
 */
function assignTask($taskId, $type, $values, $userId)
{
    global $pdo;

    try {
        // Map numeric type to string type for database
        $typeMap = [
            1 => 'class',
            2 => 'group',
            3 => 'user'
        ];
        $deliverType = $typeMap[$type] ?? 'user';

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO deliver (task_id, type, value, added_by)
            VALUES (:task_id, :type, :value, :added_by)
        ");

        foreach ($values as $value) {
            $stmt->execute([
                ':task_id' => $taskId,
                ':type' => $deliverType,
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
    $evaluationPhaseCodes = ['PHASE_CHUABAI', 'PHASE_TAKENOTE', 'PHASE_DANHGIANGUOIHOC', 'PHASE_CHAMDIEM'];
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

        // Check if assigned to user's class
        $stmt = $pdo->prepare("
            SELECT d.id_deliver 
            FROM deliver d
            INNER JOIN class_member cm ON d.value = cm.class_id
            WHERE d.task_id = :task_id 
            AND d.type = 'class' 
            AND cm.user_id = :user_id
            AND cm.role = 'student'
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
 * Teachers can access all delivers in their classes
 * 
 * @param int $deliverId Deliver ID
 * @param int $userId User ID
 * @return bool True if user has access, false otherwise
 */
function validateUserDeliverAccess($deliverId, $userId)
{
    global $pdo;

    try {
        // Get deliver details with task and phase info
        $stmt = $pdo->prepare("
            SELECT d.type, d.value, d.task_id, p.topic_id, t.class_id
            FROM deliver d
            INNER JOIN task tk ON d.task_id = tk.task_id
            INNER JOIN phase p ON tk.phase_id = p.phase_id
            INNER JOIN topic t ON p.topic_id = t.topic_id
            WHERE d.id_deliver = :deliver_id
        ");
        $stmt->execute(['deliver_id' => $deliverId]);
        $deliver = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$deliver) {
            return false;
        }

        // Check if user is a teacher in this class
        $stmt = $pdo->prepare("
            SELECT role 
            FROM class_member 
            WHERE class_id = :class_id 
            AND user_id = :user_id
        ");
        $stmt->execute([
            'class_id' => $deliver['class_id'],
            'user_id' => $userId
        ]);
        $membership = $stmt->fetch(PDO::FETCH_ASSOC);

        // Teachers can access all delivers in their class
        if ($membership && $membership['role'] === 'teacher') {
            return true;
        }

        // For students, check based on assignment type
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
        } elseif ($deliver['type'] === 'class') {
            // Class assignment - check if user is member of the class
            return $membership !== false;
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

/**
 * Get response data from deliver
 * Returns the response record associated with a deliver, if exists
 * 
 * @param int $deliverId Deliver ID
 * @return array|null Response data with json_ai and id_response, or null if not found
 */
function getResponseFromDeliver($deliverId, $currentUserId = null, $depth = 0)
{
    global $pdo;

    if ($depth > 10) {
        error_log("getResponseFromDeliver: max recursion depth reached for deliver $deliverId");
        return null;
    }

    try {
        // First, try to get response directly from this deliver
        $stmt = $pdo->prepare("
            SELECT 
                r.id_response, 
                r.json_ai, 
                r.user_id, 
                r.owner_user_id,
                r.parent_response,
                r.created_at, 
                r.updated_at,
                r.task_id,
                t.title as task_title,
                t.description as task_description,
                p.phase_name,
                mp.m_phase_name,
                mp.phase_code,
                mp.Comment as allow_comment,
                u.fullname,
                u.fullname as user_fullname,
                owner.fullname as owner_fullname
            FROM deliver d
            INNER JOIN response r ON d.response = r.id_response
            INNER JOIN task t ON r.task_id = t.task_id
            INNER JOIN phase p ON t.phase_id = p.phase_id
            INNER JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
            INNER JOIN users u ON r.user_id = u.user_id
            LEFT JOIN users owner ON r.owner_user_id = owner.user_id
            WHERE d.id_deliver = :deliver_id AND d.response IS NOT NULL
        ");
        $stmt->execute(['deliver_id' => $deliverId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return $result;
        }

        // If no response found, try to find response from parent task (review chain)
        // Get current deliver details
        $deliverStmt = $pdo->prepare("
            SELECT d.id_deliver, d.task_id, d.type, d.value, t.review_task
            FROM deliver d
            INNER JOIN task t ON d.task_id = t.task_id
            WHERE d.id_deliver = :deliver_id
        ");
        $deliverStmt->execute(['deliver_id' => $deliverId]);
        $deliverInfo = $deliverStmt->fetch(PDO::FETCH_ASSOC);

        if (!$deliverInfo) {
            return null; // Deliver not found
        }

        // If no review_task, this is a direct task (not a review)
        // Auto-create response if currentUserId is provided
        if (!$deliverInfo['review_task']) {
            if ($currentUserId) {
                try {
                    $pdo->beginTransaction();

                    // Determine owner_user_id based on deliver type
                    $ownerUserId = $currentUserId;
                    if ($deliverInfo['type'] === 'user') {
                        $ownerUserId = $deliverInfo['value'];
                    } elseif ($deliverInfo['type'] === 'group') {
                        // For group delivery, owner is the current user (first to access)
                        $ownerUserId = $currentUserId;
                    }

                    // Create new response for this task
                    $createResponseStmt = $pdo->prepare("
                        INSERT INTO response (user_id, owner_user_id, parent_response, task_id, created_at, updated_at)
                        VALUES (:user_id, :owner_user_id, NULL, :task_id, NOW(), NOW())
                    ");
                    $createResponseStmt->execute([
                        ':user_id' => $currentUserId,
                        ':owner_user_id' => $ownerUserId,
                        ':task_id' => $deliverInfo['task_id']
                    ]);
                    $newResponseId = $pdo->lastInsertId();

                    // Update deliver with new response and set status = 1 (in progress)
                    $updateDeliverStmt = $pdo->prepare("
                        UPDATE deliver 
                        SET response = :response_id, status = 1
                        WHERE id_deliver = :deliver_id
                    ");
                    $updateDeliverStmt->execute([
                        ':response_id' => $newResponseId,
                        ':deliver_id' => $deliverId
                    ]);

                    $pdo->commit();

                    // Recursively call again to get the newly created response with all details
                    return getResponseFromDeliver($deliverId, null, $depth + 1);
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log("Error creating response for non-review deliver: " . $e->getMessage());
                    return null;
                }
            }

            // No currentUserId provided, cannot create response
            return null;
        }

        // This task is reviewing another task
        // Find deliver in parent task with same type and value
        $parentDeliverStmt = $pdo->prepare("
            SELECT id_deliver
            FROM deliver
            WHERE task_id = :parent_task_id
            AND type = :type
            AND value = :value
            LIMIT 1
        ");
        $parentDeliverStmt->execute([
            ':parent_task_id' => $deliverInfo['review_task'],
            ':type' => $deliverInfo['type'],
            ':value' => $deliverInfo['value']
        ]);
        $parentDeliver = $parentDeliverStmt->fetch(PDO::FETCH_ASSOC);

        if (!$parentDeliver) {
            return null;
        }

        // Recursively get response from parent deliver (pass null to NOT auto-create)
        $parentResponse = getResponseFromDeliver($parentDeliver['id_deliver'], null, $depth + 1);

        if (!$parentResponse) {
            return null;
        }

        // If currentUserId is provided, create a new response for current deliver
        // and link it to parent response via content blocks
        if ($currentUserId) {
            try {
                $pdo->beginTransaction();

                // Create new response for current task
                $createResponseStmt = $pdo->prepare("
                    INSERT INTO response (user_id, owner_user_id, parent_response, task_id, created_at, updated_at)
                    VALUES (:user_id, :owner_user_id, :parent_response, :task_id, NOW(), NOW())
                ");
                $createResponseStmt->execute([
                    ':user_id' => $currentUserId,
                    ':owner_user_id' => $parentResponse['owner_user_id'],
                    ':parent_response' => $parentResponse['id_response'],
                    ':task_id' => $deliverInfo['task_id']
                ]);
                $newResponseId = $pdo->lastInsertId();

                // Update deliver with new response and set status = 1 (in progress)
                $updateDeliverStmt = $pdo->prepare("
                    UPDATE deliver 
                    SET response = :response_id, status = 1
                    WHERE id_deliver = :deliver_id
                ");
                $updateDeliverStmt->execute([
                    ':response_id' => $newResponseId,
                    ':deliver_id' => $deliverId
                ]);

                // Do NOT copy content - will be retrieved recursively via parent_response

                $pdo->commit();

                // Recursively call again to get the newly created response with all details
                return getResponseFromDeliver($deliverId, null);
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Error creating response for deliver: " . $e->getMessage());
                // If creation fails, still return parent response for viewing
                return $parentResponse;
            }
        }

        return $parentResponse;
    } catch (PDOException $e) {
        error_log("Error in getResponseFromDeliver: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if there's a conflict when assigning a task
 * Checks for duplicate assignments
 * 
 * @param int $taskId Task ID
 * @param int $assignType Assignment type (1=class, 2=group, 3=user)
 * @param int $assignValue The ID to assign to (class_id, group_id, or user_id)
 * @return array ['has_conflict' => bool, 'message' => string, 'conflict_type' => string]
 */
function checkAssignmentConflict($taskId, $assignType, $assignValue)
{
    global $pdo;

    try {
        // Map assign type to deliver type string
        $typeMap = [
            1 => 'class',
            2 => 'group',
            3 => 'user'
        ];
        $deliverType = $typeMap[$assignType] ?? 'user';

        // Check if exact same assignment exists
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM deliver
            WHERE task_id = :task_id
            AND type = :type
            AND value = :value
        ");

        $stmt->execute([
            ':task_id' => $taskId,
            ':type' => $deliverType,
            ':value' => $assignValue
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            $nameMap = [
                'class' => 'class',
                'group' => 'group',
                'user' => 'student'
            ];

            return [
                'has_conflict' => true,
                'message' => 'Nhiệm vụ đã được giao cho ' . $nameMap[$deliverType],
                'conflict_type' => 'duplicate'
            ];
        }

        return [
            'has_conflict' => false,
            'message' => '',
            'conflict_type' => ''
        ];
    } catch (PDOException $e) {
        error_log("Error in checkAssignmentConflict: " . $e->getMessage());
        return [
            'has_conflict' => true,
            'message' => 'Lỗi khi kiểm tra trùng lặp: ' . $e->getMessage(),
            'conflict_type' => 'error'
        ];
    }
}

/**
 * Get group submission for a deliver
 * For group assignments, all members share the same response
 * @param int $taskId
 * @param int $deliverId
 * @return array|null
 */
function getGroupSubmission($taskId, $deliverId)
{
    global $pdo;

    try {
        // Bài nộp của một deliver được liên kết qua deliver.response (bảng response
        // không có cột deliver_id — truy vấn cũ luôn lỗi và trả null).
        $stmt = $pdo->prepare("
            SELECT
                r.id_response,
                r.created_at as submitted_at,
                r.user_id
            FROM deliver d
            INNER JOIN response r ON r.id_response = d.response
            WHERE d.id_deliver = :deliver_id
            AND r.task_id = :task_id
            LIMIT 1
        ");

        $stmt->execute([
            ':task_id' => $taskId,
            ':deliver_id' => $deliverId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getGroupSubmission: " . $e->getMessage());
        return null;
    }
}

/**
 * Get all task IDs belonging to a topic.
 */
function getTopicTaskIds($topicId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT t.task_id
            FROM task t
            INNER JOIN phase p ON t.phase_id = p.phase_id
            WHERE p.topic_id = :topic_id
            ORDER BY p.phase_number, t.task_id
        ");
        $stmt->execute([':topic_id' => $topicId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (PDOException $e) {
        error_log("Error in getTopicTaskIds: " . $e->getMessage());
        return [];
    }
}

/**
 * Check whether a student has any submission in a topic (any phase).
 */
function studentHasSubmissionsInTopic($topicId, $studentUserId)
{
    global $pdo;

    $taskIds = getTopicTaskIds($topicId);
    if (empty($taskIds)) {
        return false;
    }

    try {
        $placeholders = implode(',', array_fill(0, count($taskIds), '?'));
        $params = array_merge($taskIds, [$studentUserId]);
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM response
            WHERE task_id IN ($placeholders)
            AND user_id = ?
            AND parent_response IS NULL
        ");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log("Error in studentHasSubmissionsInTopic: " . $e->getMessage());
        return false;
    }
}

/**
 * Collect a response tree (root + descendants) for one task/user pair.
 */
function collectResponseTreeIdsForTaskUser($taskId, $studentUserId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            WITH RECURSIVE resp_tree AS (
                SELECT id_response
                FROM response
                WHERE task_id = :task_id
                  AND user_id = :user_id
                  AND parent_response IS NULL
                UNION ALL
                SELECT r.id_response
                FROM response r
                INNER JOIN resp_tree rt ON r.parent_response = rt.id_response
            )
            SELECT id_response FROM resp_tree
        ");
        $stmt->execute([
            ':task_id' => $taskId,
            ':user_id' => $studentUserId,
        ]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (PDOException $e) {
        error_log("Error in collectResponseTreeIdsForTaskUser: " . $e->getMessage());
        return [];
    }
}

/**
 * Deliver rows that apply to a student on a given task.
 */
function getDeliversForStudentOnTask($taskId, $studentUserId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT d.id_deliver, d.response, d.type, d.value
            FROM deliver d
            WHERE d.task_id = :task_id
            AND (
                (d.type = 'user' AND d.value = :user_id)
                OR (d.type = 'class' AND d.value IN (
                    SELECT cm.class_id FROM class_member cm WHERE cm.user_id = :user_id
                ))
                OR (d.type = 'group' AND d.value IN (
                    SELECT gm.group_id FROM group_member gm WHERE gm.user_id = :user_id
                ))
            )
        ");
        $stmt->execute([
            ':task_id' => $taskId,
            ':user_id' => $studentUserId,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error in getDeliversForStudentOnTask: " . $e->getMessage());
        return [];
    }
}

/**
 * Delete evaluation/submission artifacts for response IDs (keeps deliver assignment).
 */
function deleteResponsesAndRelated(array $responseIds)
{
    global $pdo;

    $responseIds = array_values(array_unique(array_filter(array_map('intval', $responseIds))));
    if (empty($responseIds)) {
        return;
    }

    $placeholders = implode(',', array_fill(0, count($responseIds), '?'));

    $pdo->prepare("DELETE FROM evaluate WHERE response_id IN ($placeholders)")
        ->execute($responseIds);

    $pdo->prepare("DELETE FROM response WHERE id_response IN ($placeholders)")
        ->execute($responseIds);
}

/**
 * Clear submission state on a deliver while keeping the assignment row.
 */
function clearDeliverSubmission($deliverId, $studentUserId, $taskId)
{
    global $pdo;

    $deliverId = (int) $deliverId;
    $studentUserId = (int) $studentUserId;
    $taskId = (int) $taskId;

    $pdo->prepare("DELETE FROM peer_review_consensus WHERE id_deliver = :did")
        ->execute([':did' => $deliverId]);
    $pdo->prepare("DELETE FROM deliver_evaluate WHERE id_deliver = :did")
        ->execute([':did' => $deliverId]);
    $pdo->prepare("DELETE FROM evaluate WHERE deliver_id = :did")
        ->execute([':did' => $deliverId]);

    $pdo->prepare("
        DELETE FROM analysis_draft
        WHERE user_id = :uid AND task_id = :tid AND deliver_id = :did
    ")->execute([
        ':uid' => $studentUserId,
        ':tid' => $taskId,
        ':did' => $deliverId,
    ]);

    $pdo->prepare("
        DELETE FROM analysis_history
        WHERE user_id = :uid AND task_id = :tid AND deliver_id = :did
    ")->execute([
        ':uid' => $studentUserId,
        ':tid' => $taskId,
        ':did' => $deliverId,
    ]);

    $pdo->prepare("
        UPDATE deliver
        SET response = NULL, status = 0, updated_at = NOW()
        WHERE id_deliver = :did
    ")->execute([':did' => $deliverId]);
}

/**
 * Reset all submissions for one student across every phase/task in a topic.
 * Assignments (deliver rows) are preserved.
 */
function resetStudentSubmissionsInTopic($topicId, $studentUserId)
{
    global $pdo;

    $topicId = (int) $topicId;
    $studentUserId = (int) $studentUserId;

    if (!$topicId || !$studentUserId) {
        return ['success' => false, 'message' => 'Thông tin không hợp lệ.'];
    }

    $taskIds = getTopicTaskIds($topicId);
    if (empty($taskIds)) {
        return ['success' => false, 'message' => 'Chủ đề này chưa có nhiệm vụ.'];
    }

    try {
        $pdo->beginTransaction();

        $clearedResponses = 0;
        $clearedDelivers = 0;

        foreach ($taskIds as $taskId) {
            $delivers = getDeliversForStudentOnTask($taskId, $studentUserId);
            foreach ($delivers as $deliver) {
                clearDeliverSubmission((int) $deliver['id_deliver'], $studentUserId, $taskId);
                $clearedDelivers++;
            }

            $responseIds = collectResponseTreeIdsForTaskUser($taskId, $studentUserId);
            if (!empty($responseIds)) {
                deleteResponsesAndRelated($responseIds);
                $clearedResponses += count($responseIds);
            }
        }

        $pdo->commit();

        if ($clearedResponses === 0 && $clearedDelivers === 0) {
            return ['success' => false, 'message' => 'Không có bài nộp nào để đặt lại.'];
        }

        return [
            'success' => true,
            'message' => 'Đã đặt lại bài nộp của học sinh ở mọi giai đoạn.',
            'cleared_responses' => $clearedResponses,
            'cleared_delivers' => $clearedDelivers,
        ];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in resetStudentSubmissionsInTopic: " . $e->getMessage());
        return ['success' => false, 'message' => 'Không đặt lại được bài nộp.'];
    }
}

/**
 * Reset group submission across all phases in a topic (keeps assignments).
 */
function resetGroupSubmissionsInTopic($topicId, $groupId)
{
    global $pdo;

    $topicId = (int) $topicId;
    $groupId = (int) $groupId;

    if (!$topicId || !$groupId) {
        return ['success' => false, 'message' => 'Thông tin không hợp lệ.'];
    }

    $taskIds = getTopicTaskIds($topicId);
    if (empty($taskIds)) {
        return ['success' => false, 'message' => 'Chủ đề này chưa có nhiệm vụ.'];
    }

    try {
        $pdo->beginTransaction();

        $clearedResponses = 0;
        $clearedDelivers = 0;

        foreach ($taskIds as $taskId) {
            $stmt = $pdo->prepare("
                SELECT id_deliver, response
                FROM deliver
                WHERE task_id = :task_id AND type = 'group' AND value = :group_id
            ");
            $stmt->execute([':task_id' => $taskId, ':group_id' => $groupId]);
            $delivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($delivers as $deliver) {
                $deliverId = (int) $deliver['id_deliver'];
                $responseId = (int) ($deliver['response'] ?? 0);

                $memberStmt = $pdo->prepare("
                    SELECT user_id FROM group_member WHERE group_id = :group_id
                ");
                $memberStmt->execute([':group_id' => $groupId]);
                $memberIds = array_map('intval', $memberStmt->fetchAll(PDO::FETCH_COLUMN));

                foreach ($memberIds as $memberId) {
                    clearDeliverSubmission($deliverId, $memberId, $taskId);
                }

                $pdo->prepare("
                    UPDATE deliver
                    SET response = NULL, status = 0, updated_at = NOW()
                    WHERE id_deliver = :did
                ")->execute([':did' => $deliverId]);

                $clearedDelivers++;

                if ($responseId > 0) {
                    $childStmt = $pdo->prepare("
                        WITH RECURSIVE resp_tree AS (
                            SELECT id_response FROM response WHERE id_response = :root
                            UNION ALL
                            SELECT r.id_response
                            FROM response r
                            INNER JOIN resp_tree rt ON r.parent_response = rt.id_response
                        )
                        SELECT id_response FROM resp_tree
                    ");
                    $childStmt->execute([':root' => $responseId]);
                    $responseIds = array_map('intval', $childStmt->fetchAll(PDO::FETCH_COLUMN));
                    if (!empty($responseIds)) {
                        deleteResponsesAndRelated($responseIds);
                        $clearedResponses += count($responseIds);
                    }
                }

                foreach ($memberIds as $memberId) {
                    $memberResponses = collectResponseTreeIdsForTaskUser($taskId, $memberId);
                    if (!empty($memberResponses)) {
                        deleteResponsesAndRelated($memberResponses);
                        $clearedResponses += count($memberResponses);
                    }
                }
            }
        }

        $pdo->commit();

        if ($clearedResponses === 0 && $clearedDelivers === 0) {
            return ['success' => false, 'message' => 'Không có bài nộp nào để đặt lại.'];
        }

        return [
            'success' => true,
            'message' => 'Đã đặt lại bài nộp của nhóm ở mọi giai đoạn.',
            'cleared_responses' => $clearedResponses,
            'cleared_delivers' => $clearedDelivers,
        ];
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in resetGroupSubmissionsInTopic: " . $e->getMessage());
        return ['success' => false, 'message' => 'Không đặt lại được bài nộp.'];
    }
}

/**
 * SQL: deliver rows visible to a student (direct, group, or whole-class assignment).
 */
function studentDeliverVisibilitySql(string $alias = 'd'): string
{
    return "(
        ({$alias}.type = 'user' AND {$alias}.value = :uid)
        OR ({$alias}.type = 'group' AND EXISTS (
            SELECT 1 FROM group_member gm
            WHERE gm.group_id = {$alias}.value AND gm.user_id = :uid
        ))
        OR ({$alias}.type = 'class' AND EXISTS (
            SELECT 1 FROM class_member cm
            WHERE cm.class_id = {$alias}.value AND cm.user_id = :uid AND cm.role = 'student'
        ))
    )";
}

/** Count distinct deliver assignments for a student. */
function countStudentAssignedDelivers(PDO $pdo, int $userId, bool $completedOnly = false): int
{
    try {
        $completed = $completedOnly ? ' AND d.response IS NOT NULL AND d.response > 0' : '';
        $sql = 'SELECT COUNT(DISTINCT d.id_deliver) FROM deliver d WHERE '
            . studentDeliverVisibilitySql('d') . $completed;
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':uid' => $userId]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('countStudentAssignedDelivers: ' . $e->getMessage());
        return 0;
    }
}
