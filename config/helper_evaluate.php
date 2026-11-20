<?php
/**
 * Helper functions for peer evaluation system
 * Handles logic for student peer evaluations
 * Last updated: 2025-11-06 17:30
 */

require_once __DIR__ . '/db.php';

/**
 * STEP 1: Check if user has access to a deliver
 * Checks if deliver is assigned to user (individual) or user's group
 * 
 * @param int $userId User ID from session
 * @param int $deliverId Deliver ID from URL
 * @return array|false Returns deliver info if access granted, false otherwise
 */
function checkDeliverAccess($userId, $deliverId)
{
    global $pdo;

    try {
        // Get deliver info
        $stmt = $pdo->prepare("
            SELECT 
                id_deliver,
                task_id,
                type,
                value
            FROM deliver
            WHERE id_deliver = :deliver_id
        ");

        $stmt->execute([':deliver_id' => $deliverId]);
        $deliver = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$deliver) {
            error_log("checkDeliverAccess: Deliver $deliverId not found");
            return false;
        }

        // Check access based on type
        if ($deliver['type'] === 'user') {
            return ($deliver['value'] == $userId) ? $deliver : false;
        } elseif ($deliver['type'] === 'group') {
            // Group assignment: check if user is member of group
            $memberStmt = $pdo->prepare("
                SELECT 1 
                FROM group_member 
                WHERE group_id = :group_id AND user_id = :user_id
            ");

            $memberStmt->execute([
                ':group_id' => $deliver['value'],
                ':user_id' => $userId
            ]);

            return $memberStmt->fetch() ? $deliver : false;
        }

        return false;

    } catch (PDOException $e) {
        error_log("checkDeliverAccess ERROR: " . $e->getMessage());
        return false;
    }
}

/**
 * Get response that needs to be evaluated for this deliver
 * 
 * @param int $deliverId Deliver ID
 * @return array|false Response info with content
 */
function getResponseToEvaluate($deliverId)
{
    global $pdo;

    try {
        // Get response_review from deliver_evaluate
        $stmt = $pdo->prepare("
            SELECT 
                de.response_review as id_response,
                r.user_id as response_user_id,
                r.task_id as response_task_id,
                r.created_at as response_created_at,
                u.fullname as response_author
            FROM deliver_evaluate de
            INNER JOIN response r ON de.response_review = r.id_response
            LEFT JOIN users u ON r.user_id = u.user_id
            WHERE de.id_deliver = :deliver_id
        ");

        $stmt->execute([':deliver_id' => $deliverId]);
        $response = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$response) {
            error_log("getResponseToEvaluate: No response found for deliver $deliverId");
            return false;
        }

        // Get response content blocks
        $contentStmt = $pdo->prepare("
            SELECT 
                content_id,
                content,
                `order`,
                type_create
            FROM content
            WHERE response_id = :response_id
            ORDER BY `order` ASC
        ");

        $contentStmt->execute([':response_id' => $response['id_response']]);
        $response['contents'] = $contentStmt->fetchAll(PDO::FETCH_ASSOC);

        return $response;

    } catch (PDOException $e) {
        error_log("getResponseToEvaluate ERROR: " . $e->getMessage());
        return false;
    }
}

/**
 * Load a submission response directly by ID (teacher grading / review).
 */
function getResponseForGrading(int $responseId): array|false
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT
                r.id_response,
                r.user_id AS response_user_id,
                r.task_id AS response_task_id,
                r.created_at AS response_created_at,
                u.fullname AS response_author
            FROM response r
            LEFT JOIN users u ON r.user_id = u.user_id
            WHERE r.id_response = :response_id
        ");
        $stmt->execute([':response_id' => $responseId]);
        $response = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$response) {
            return false;
        }

        $contentStmt = $pdo->prepare("
            SELECT content_id, content, `order`, type_create
            FROM content
            WHERE response_id = :response_id
            ORDER BY `order` ASC
        ");
        $contentStmt->execute([':response_id' => $responseId]);
        $response['contents'] = $contentStmt->fetchAll(PDO::FETCH_ASSOC);

        return $response;
    } catch (PDOException $e) {
        error_log('getResponseForGrading ERROR: ' . $e->getMessage());
        return false;
    }
}

/**
 * Ensure a response belongs to the grading task submission queue.
 */
function isResponseInGradingTask(int $gradingTaskId, int $responseId): bool
{
    foreach (getGradingSubmissions($gradingTaskId) as $row) {
        if ((int)($row['response'] ?? 0) === $responseId) {
            return true;
        }
    }
    return false;
}

/**
 * Resolve the source-task deliver_id for a graded response.
 */
function resolveSubmissionDeliverForGrading(int $gradingTaskId, int $responseId): ?int
{
    foreach (getGradingSubmissions($gradingTaskId) as $row) {
        if ((int)($row['response'] ?? 0) === $responseId) {
            return (int)($row['id_deliver'] ?? 0) ?: null;
        }
    }
    return null;
}

/**
 * STEP 2: Get or create evaluation record
 * For group delivers, each member gets their own evaluate record
 * 
 * @param int $userId Current user ID
 * @param int $deliverId Deliver ID
 * @param int $responseId Response ID being evaluated
 * @return array|false Evaluation record
 */
function getOrCreateEvaluation($userId, $deliverId, $responseId)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        // Check if evaluation exists for this user + deliver combination
        $stmt = $pdo->prepare("
            SELECT 
                id_evaluate,
                status,
                total_score,
                response_id,
                deliver_id,
                user_id
            FROM evaluate
            WHERE deliver_id = :deliver_id AND user_id = :user_id
        ");

        $stmt->execute([
            ':deliver_id' => $deliverId,
            ':user_id' => $userId
        ]);

        $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($evaluation) {
            // Get detail scores
            $detailStmt = $pdo->prepare("
                SELECT 
                    id_detail_eval,
                    tc_id,
                    diem
                FROM detail_evaluate
                WHERE id_evaluate = :evaluate_id
            ");

            $detailStmt->execute([':evaluate_id' => $evaluation['id_evaluate']]);
            $evaluation['details'] = $detailStmt->fetchAll(PDO::FETCH_ASSOC);

            $pdo->commit();
            return $evaluation;
        }

        // Create new evaluation with status = 1 (in progress)
        $insertStmt = $pdo->prepare("
            INSERT INTO evaluate (deliver_id, user_id, response_id, status, total_score, is_teacher)
            VALUES (:deliver_id, :user_id, :response_id, 1, 0, 0)
        ");

        $insertStmt->execute([
            ':deliver_id' => $deliverId,
            ':user_id' => $userId,
            ':response_id' => $responseId
        ]);

        $evaluateId = $pdo->lastInsertId();

        // Initialize detail_evaluate with NULL scores for all criteria
        $criteriaStmt = $pdo->prepare("
            SELECT tc.tc_id
            FROM task_evaluate te
            INNER JOIN tieu_chi tc ON te.id_group_tc = tc.nhom_tc_id
            INNER JOIN deliver d ON te.id_task = d.task_id
            WHERE d.id_deliver = :deliver_id
        ");

        $criteriaStmt->execute([':deliver_id' => $deliverId]);
        $criteria = $criteriaStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($criteria as $criterion) {
            $detailInsertStmt = $pdo->prepare("
                INSERT INTO detail_evaluate (tc_id, diem, id_evaluate)
                VALUES (:tc_id, NULL, :evaluate_id)
            ");

            $detailInsertStmt->execute([
                ':tc_id' => $criterion['tc_id'],
                ':evaluate_id' => $evaluateId
            ]);
        }

        $pdo->commit();

        // Return newly created evaluation
        return [
            'id_evaluate' => $evaluateId,
            'status' => 1,
            'total_score' => 0,
            'response_id' => $responseId,
            'deliver_id' => $deliverId,
            'user_id' => $userId,
            'details' => []
        ];

    } catch (PDOException $e) {
        $pdo->rollBack();
        $errorMsg = "getOrCreateEvaluation ERROR: " . $e->getMessage();
        error_log($errorMsg);
        error_log("SQL State: " . $e->getCode());
        error_log("Stack trace: " . $e->getTraceAsString());

        // Store error in session for display
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['last_db_error'] = $errorMsg;
        }

        return false;
    }
}

/**
 * Get evaluation criteria for a task
 * 
 * @param int $taskId Task ID
 * @return array List of criteria
 */
function getEvaluationCriteria($taskId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                tc.tc_id,
                tc.name_tc,
                tc.description,
                tc.min_score,
                tc.max_score,
                tc.nhom_tc_id
            FROM task_evaluate te
            INNER JOIN tieu_chi tc ON te.id_group_tc = tc.nhom_tc_id
            WHERE te.id_task = :task_id
            ORDER BY tc.tc_id ASC
        ");

        $stmt->execute([':task_id' => $taskId]);
        $criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $criteria;

    } catch (PDOException $e) {
        error_log("getEvaluationCriteria ERROR: " . $e->getMessage());
        return [];
    }
}

/**
 * Get task information
 * 
 * @param int $taskId Task ID
 * @return array|false Task info
 */
function getTaskInfo($taskId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                task_id,
                title,
                description,
                phase_id
            FROM task
            WHERE task_id = :task_id
        ");

        $stmt->execute([':task_id' => $taskId]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);

        return $task ?: false;

    } catch (PDOException $e) {
        error_log("getTaskInfo ERROR: " . $e->getMessage());
        return false;
    }
}

/**
 * Save or update evaluation scores
 * 
 * @param array $data Evaluation data
 * @return bool Success status
 */
function saveEvaluation($data)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        $evaluateId = $data['evaluate_id'];
        $scores     = $data['scores'];              // Array of tc_id => score
        $status     = $data['status'];              // 0=draft, 2=submit
        $isTeacher  = isset($data['is_teacher']) ? intval($data['is_teacher']) : null;

        // Calculate total score (S_Peer)
        $totalScore = array_sum($scores);

        // Update evaluate record; stamp is_teacher only when explicitly provided
        if ($isTeacher !== null) {
            $updateStmt = $pdo->prepare("
                UPDATE evaluate
                SET status = :status, total_score = :total_score, is_teacher = :is_teacher
                WHERE id_evaluate = :evaluate_id
            ");
            $updateStmt->execute([
                ':status'      => $status,
                ':total_score' => $totalScore,
                ':is_teacher'  => $isTeacher,
                ':evaluate_id' => $evaluateId,
            ]);
        } else {
            $updateStmt = $pdo->prepare("
                UPDATE evaluate
                SET status = :status, total_score = :total_score
                WHERE id_evaluate = :evaluate_id
            ");
            $updateStmt->execute([
                ':status'      => $status,
                ':total_score' => $totalScore,
                ':evaluate_id' => $evaluateId,
            ]);
        }

        // Update detail_evaluate scores
        foreach ($scores as $tcId => $score) {
            $detailStmt = $pdo->prepare("
                UPDATE detail_evaluate
                SET diem = :diem
                WHERE id_evaluate = :evaluate_id AND tc_id = :tc_id
            ");

            $detailStmt->execute([
                ':diem' => $score,
                ':evaluate_id' => $evaluateId,
                ':tc_id' => $tcId
            ]);
        }

        $pdo->commit();

        error_log("saveEvaluation: Updated evaluation $evaluateId with total score $totalScore");

        return true;

    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("saveEvaluation ERROR: " . $e->getMessage());
        return false;
    }
}

/**
 * Get list of all evaluations assigned to user in a task
 * For navigation/dashboard purposes
 * 
 * @param int $userId User ID
 * @param int $taskId Task ID
 * @return array List of assigned evaluations
 */
function getAssignedEvaluations($userId, $taskId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT 
                d.id_deliver,
                d.type as deliver_type,
                d.value as deliver_value,
                de.response_review,
                r.id_response,
                r.user_id as response_user_id,
                u.fullname as response_author,
                e.id_evaluate,
                e.status as eval_status,
                e.total_score,
                e.accuracy_score
            FROM deliver d
            INNER JOIN deliver_evaluate de ON d.id_deliver = de.id_deliver
            INNER JOIN response r ON de.response_review = r.id_response
            LEFT JOIN users u ON r.user_id = u.user_id
            LEFT JOIN evaluate e ON d.id_deliver = e.deliver_id AND e.user_id = :user_id
            WHERE d.task_id = :task_id
            AND (
                (d.type = 'user' AND d.value = :user_id_check)
                OR (
                    d.type = 'group' 
                    AND EXISTS (
                        SELECT 1 
                        FROM group_member gm 
                        WHERE gm.group_id = d.value 
                        AND gm.user_id = :user_id_group
                    )
                )
            )
            ORDER BY d.id_deliver ASC
        ");

        $stmt->execute([
            ':task_id' => $taskId,
            ':user_id' => $userId,
            ':user_id_check' => $userId,
            ':user_id_group' => $userId
        ]);

        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        error_log("getAssignedEvaluations: Found " . count($assignments) . " assignments for user $userId in task $taskId");

        return $assignments;

    } catch (PDOException $e) {
        error_log("getAssignedEvaluations ERROR: " . $e->getMessage());
        return [];
    }
}

/**
 * Get the maximum possible total score for a task (S_Max).
 * S_Max = sum of max_score across all criteria linked to the task.
 *
 * @param int $taskId Task ID
 * @return float Maximum possible total score (0 if no criteria found)
 */
function getMaxScoreForTask($taskId)
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(tc.max_score), 0) AS s_max
            FROM task_evaluate te
            INNER JOIN tieu_chi tc ON te.id_group_tc = tc.nhom_tc_id
            WHERE te.id_task = :task_id
        ");
        $stmt->execute([':task_id' => $taskId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($row['s_max'] ?? 0);
    } catch (PDOException $e) {
        error_log("getMaxScoreForTask ERROR: " . $e->getMessage());
        return 0;
    }
}

/**
 * Recalculate and persist accuracy_score (S_Acc) for every peer evaluator
 * of a given response, using the teacher's submitted evaluation as ground truth.
 *
 * Formula:
 *   Δ         = |S_Peer - S_Teacher|
 *   S_Acc     = max(0, S_Max - α * Δ)   (linear decay)
 *
 * This function is called automatically whenever a teacher evaluation for
 * the response is submitted (status = 2, is_teacher = 1).
 *
 * @param int   $responseId  Response ID that was evaluated
 * @param int   $taskId      Task ID (used to derive S_Max)
 * @param float $alpha       Penalty coefficient α (default 1.0 → 1 pt deducted per pt of deviation)
 * @return int Number of peer evaluate records updated
 */
function calculateAndSaveAccuracyScores($responseId, $taskId, $alpha = 1.0)
{
    global $pdo;

    try {
        // 1. Find the teacher's submitted evaluation for this response
        $teacherStmt = $pdo->prepare("
            SELECT total_score
            FROM evaluate
            WHERE response_id = :response_id
              AND is_teacher  = 1
              AND status      = 2
            ORDER BY id_evaluate DESC
            LIMIT 1
        ");
        $teacherStmt->execute([':response_id' => $responseId]);
        $teacherRow = $teacherStmt->fetch(PDO::FETCH_ASSOC);

        if (!$teacherRow) {
            // No teacher evaluation yet — nothing to calculate
            error_log("calculateAndSaveAccuracyScores: No teacher eval found for response $responseId");
            return 0;
        }

        $sTeacher = (float)$teacherRow['total_score'];
        $sMax     = getMaxScoreForTask($taskId);

        // 2. Fetch all peer (non-teacher) submitted evaluations for this response
        $peerStmt = $pdo->prepare("
            SELECT id_evaluate, total_score
            FROM evaluate
            WHERE response_id = :response_id
              AND is_teacher  = 0
              AND status      = 2
        ");
        $peerStmt->execute([':response_id' => $responseId]);
        $peers = $peerStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($peers)) {
            error_log("calculateAndSaveAccuracyScores: No peer evals to update for response $responseId");
            return 0;
        }

        // 3. Compute and persist S_Acc for each peer
        $updateStmt = $pdo->prepare("
            UPDATE evaluate
            SET accuracy_score = :accuracy_score
            WHERE id_evaluate  = :id_evaluate
        ");

        $updatedCount = 0;
        foreach ($peers as $peer) {
            $sPeer    = (float)$peer['total_score'];
            $delta    = abs($sPeer - $sTeacher);
            $sAcc     = max(0.0, $sMax - $alpha * $delta);

            $updateStmt->execute([
                ':accuracy_score' => round($sAcc, 2),
                ':id_evaluate'    => $peer['id_evaluate'],
            ]);
            $updatedCount++;
        }

        error_log("calculateAndSaveAccuracyScores: Updated $updatedCount peer evals for response $responseId (S_Teacher=$sTeacher, S_Max=$sMax, alpha=$alpha)");
        return $updatedCount;

    } catch (PDOException $e) {
        error_log("calculateAndSaveAccuracyScores ERROR: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get or create teacher ground-truth evaluation (is_teacher = 1).
 */
function getOrCreateTeacherEvaluation(int $teacherId, int $responseId, int $deliverId, int $gradingTaskId)
{
    global $pdo;

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT id_evaluate, deliver_id, user_id, response_id, status, total_score, is_teacher
            FROM evaluate
            WHERE response_id = :response_id AND user_id = :user_id AND is_teacher = 1
            LIMIT 1
        ");
        $stmt->execute([':response_id' => $responseId, ':user_id' => $teacherId]);
        $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($evaluation) {
            $detailStmt = $pdo->prepare("SELECT id_detail_eval, tc_id, diem FROM detail_evaluate WHERE id_evaluate = :id");
            $detailStmt->execute([':id' => $evaluation['id_evaluate']]);
            $evaluation['details'] = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
            $pdo->commit();
            return $evaluation;
        }

        $insertStmt = $pdo->prepare("
            INSERT INTO evaluate (deliver_id, user_id, response_id, status, total_score, is_teacher)
            VALUES (:deliver_id, :user_id, :response_id, 1, 0, 1)
        ");
        $insertStmt->execute([
            ':deliver_id' => $deliverId,
            ':user_id' => $teacherId,
            ':response_id' => $responseId,
        ]);
        $evaluateId = (int)$pdo->lastInsertId();

        $criteriaStmt = $pdo->prepare("
            SELECT tc.tc_id
            FROM task_evaluate te
            INNER JOIN tieu_chi tc ON te.id_group_tc = tc.nhom_tc_id
            WHERE te.id_task = :task_id
        ");
        $criteriaStmt->execute([':task_id' => $gradingTaskId]);
        foreach ($criteriaStmt->fetchAll(PDO::FETCH_ASSOC) as $criterion) {
            $pdo->prepare("INSERT INTO detail_evaluate (tc_id, diem, id_evaluate) VALUES (:tc_id, NULL, :eid)")
                ->execute([':tc_id' => $criterion['tc_id'], ':eid' => $evaluateId]);
        }

        $pdo->commit();
        return [
            'id_evaluate' => $evaluateId,
            'deliver_id' => $deliverId,
            'user_id' => $teacherId,
            'response_id' => $responseId,
            'status' => 1,
            'total_score' => 0,
            'is_teacher' => 1,
            'details' => [],
        ];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('getOrCreateTeacherEvaluation: ' . $e->getMessage());
        return false;
    }
}

/**
 * Peer evaluations summary for a response (for teacher grading view).
 */
function getPeerEvaluationsForResponse(int $responseId): array
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT e.id_evaluate, e.total_score, e.accuracy_score, e.status, u.fullname AS evaluator_name
        FROM evaluate e
        INNER JOIN users u ON e.user_id = u.user_id
        WHERE e.response_id = :response_id AND e.is_teacher = 0 AND e.status = 2
        ORDER BY e.total_score DESC
    ");
    $stmt->execute([':response_id' => $responseId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Submissions queue for PHASE_CHAMDIEM (teacher final grading).
 */
function getGradingSubmissions(int $gradingTaskId): array
{
    require_once __DIR__ . '/helper_homework.php';
    $rows = getResponsesForEvaluation($gradingTaskId);
    foreach ($rows as &$row) {
        $responseId = (int)($row['response'] ?? 0);
        $row['peer_evaluations'] = $responseId ? getPeerEvaluationsForResponse($responseId) : [];
        if ($responseId) {
            global $pdo;
            $t = $pdo->prepare("SELECT id_evaluate, status, total_score FROM evaluate WHERE response_id = :rid AND is_teacher = 1 LIMIT 1");
            $t->execute([':rid' => $responseId]);
            $row['teacher_evaluation'] = $t->fetch(PDO::FETCH_ASSOC) ?: null;
        } else {
            $row['teacher_evaluation'] = null;
        }
        if ($responseId) {
            $row['system_evaluation'] = getSystemEvaluationForResponse($responseId);
        } else {
            $row['system_evaluation'] = null;
        }
        $peers = $row['peer_evaluations'] ?? [];
        $row['peer_avg'] = count($peers)
            ? round(array_sum(array_column($peers, 'total_score')) / count($peers), 2)
            : null;
        $accuracies = array_values(array_filter(
            array_map(static fn($p) => $p['accuracy_score'] ?? null, $peers),
            static fn($v) => $v !== null && $v !== ''
        ));
        $row['peer_accuracy_avg'] = count($accuracies)
            ? round(array_sum($accuracies) / count($accuracies), 2)
            : null;
    }
    return $rows;
}

/**
 * System-side evaluation metrics for instructor dashboard (Fig. 18).
 */
function getSystemEvaluationForResponse(int $responseId): ?array
{
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT json_ai, tracking_data
            FROM response
            WHERE id_response = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $responseId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $tracking = null;
        if (!empty($row['tracking_data'])) {
            $tracking = is_array($row['tracking_data'])
                ? $row['tracking_data']
                : json_decode((string)$row['tracking_data'], true);
        }

        $jsonAi = null;
        if (!empty($row['json_ai'])) {
            $jsonAi = is_array($row['json_ai'])
                ? $row['json_ai']
                : json_decode((string)$row['json_ai'], true);
        }

        $global = is_array($tracking) ? ($tracking['globalSummary'] ?? []) : [];
        $validation = is_array($jsonAi) ? ($jsonAi['_validation'] ?? null) : null;

        return [
            'outline_score' => is_array($validation) ? (int)($validation['score'] ?? 0) : null,
            'outline_passed' => is_array($validation) ? !empty($validation['passed']) : null,
            'avg_edit_change_percent' => isset($global['averageEditChangePercent'])
                ? (float)$global['averageEditChangePercent'] : null,
            'sections_edited' => isset($global['sectionsEdited'])
                ? (int)$global['sectionsEdited'] : null,
            'total_copy_percent' => isset($global['totalCopyPercent'])
                ? (float)$global['totalCopyPercent'] : null,
            'session_duration_secs' => isset($global['sessionDuration'])
                ? (int)$global['sessionDuration'] : null,
        ];
    } catch (PDOException $e) {
        error_log('getSystemEvaluationForResponse ERROR: ' . $e->getMessage());
        return null;
    }
}

/**
 * Summary metrics for instructor review dashboard (Fig. 18).
 */
function getInstructorReviewDashboardSummary(array $submissions): array
{
    $peerCount = 0;
    $peerScoreSum = 0.0;
    $accuracySum = 0.0;
    $accuracyCount = 0;
    $teacherGraded = 0;
    $outlineScoreSum = 0;
    $outlineCount = 0;
    $editSum = 0.0;
    $editCount = 0;

    foreach ($submissions as $row) {
        $peers = $row['peer_evaluations'] ?? [];
        $peerCount += count($peers);
        foreach ($peers as $peer) {
            $peerScoreSum += (float)($peer['total_score'] ?? 0);
            if ($peer['accuracy_score'] !== null && $peer['accuracy_score'] !== '') {
                $accuracySum += (float)$peer['accuracy_score'];
                $accuracyCount++;
            }
        }

        $teacherEval = $row['teacher_evaluation'] ?? null;
        if ($teacherEval && (int)($teacherEval['status'] ?? 0) === 2) {
            $teacherGraded++;
        }

        $system = $row['system_evaluation'] ?? null;
        if (is_array($system)) {
            if ($system['outline_score'] !== null) {
                $outlineScoreSum += (int)$system['outline_score'];
                $outlineCount++;
            }
            if ($system['avg_edit_change_percent'] !== null) {
                $editSum += (float)$system['avg_edit_change_percent'];
                $editCount++;
            }
        }
    }

    return [
        'submission_count' => count($submissions),
        'peer_review_count' => $peerCount,
        'peer_avg_score' => $peerCount > 0 ? round($peerScoreSum / $peerCount, 2) : null,
        'peer_accuracy_avg' => $accuracyCount > 0 ? round($accuracySum / $accuracyCount, 2) : null,
        'teacher_graded_count' => $teacherGraded,
        'system_outline_avg' => $outlineCount > 0 ? round($outlineScoreSum / $outlineCount, 1) : null,
        'system_edit_avg' => $editCount > 0 ? round($editSum / $editCount, 1) : null,
    ];
}

/**
 * Hiển thị nội dung bài nộp dạng markdown đơn giản (#, ##, ###, **đậm**, *nghiêng*) một cách an toàn:
 * escape HTML trước rồi mới chuyển định dạng, nên nội dung học sinh không thể chèn script.
 */
if (!function_exists('renderSubmissionMarkdown')) {
    function renderSubmissionMarkdown(?string $text): string
    {
        $html = htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8', false); // không escape lại thực thể đã có sẵn trong DB
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
        $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/(?<![*\w])\*(?!\s)(.+?)(?<!\s)\*(?![*\w])/', '<em>$1</em>', $html);
        // Xuống dòng, trừ ngay sau thẻ tiêu đề
        $html = nl2br($html);
        return preg_replace('~(</h[1-3]>)<br\s*/?>~', '$1', $html);
    }
}
