<?php
/**
 * Helper functions for peer evaluation system
 * Handles logic for student peer evaluations
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
            // Individual assignment: check if user_id matches value
            if ($deliver['value'] == $userId) {
                error_log("checkDeliverAccess: User $userId has access (individual)");
                return $deliver;
            } else {
                error_log("checkDeliverAccess: User $userId != deliver value {$deliver['value']}");
                return false;
            }
        } 
        elseif ($deliver['type'] === 'group') {
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
            
            if ($memberStmt->fetch()) {
                error_log("checkDeliverAccess: User $userId has access (group member of {$deliver['value']})");
                return $deliver;
            } else {
                error_log("checkDeliverAccess: User $userId is not member of group {$deliver['value']}");
                return false;
            }
        }
        
        error_log("checkDeliverAccess: Unknown deliver type: {$deliver['type']}");
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
        
        error_log("getResponseToEvaluate: Found response {$response['id_response']} with " . count($response['contents']) . " content blocks");
        
        return $response;
        
    } catch (PDOException $e) {
        error_log("getResponseToEvaluate ERROR: " . $e->getMessage());
        return false;
    }
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
                user_id,
                created_at
            FROM evaluate
            WHERE deliver_id = :deliver_id AND user_id = :user_id
        ");
        
        $stmt->execute([
            ':deliver_id' => $deliverId,
            ':user_id' => $userId
        ]);
        
        $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($evaluation) {
            error_log("getOrCreateEvaluation: Found existing evaluation {$evaluation['id_evaluate']}");
            
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
            INSERT INTO evaluate (deliver_id, user_id, response_id, status, total_score, created_at)
            VALUES (:deliver_id, :user_id, :response_id, 1, 0, NOW())
        ");
        
        $insertStmt->execute([
            ':deliver_id' => $deliverId,
            ':user_id' => $userId,
            ':response_id' => $responseId
        ]);
        
        $evaluateId = $pdo->lastInsertId();
        error_log("getOrCreateEvaluation: Created new evaluation $evaluateId");
        
        // Initialize detail_evaluate with NULL scores for all criteria
        $criteriaStmt = $pdo->prepare("
            SELECT tc.tc_id
            FROM task_evaluate te
            INNER JOIN tieu_chi tc ON te.nhom_tc_id = tc.nhom_tc_id
            INNER JOIN deliver d ON te.task_id = d.task_id
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
        
        error_log("getOrCreateEvaluation: Initialized " . count($criteria) . " detail records");
        
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
        error_log("getOrCreateEvaluation ERROR: " . $e->getMessage());
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
            INNER JOIN tieu_chi tc ON te.nhom_tc_id = tc.nhom_tc_id
            WHERE te.task_id = :task_id
            ORDER BY tc.tc_id ASC
        ");
        
        $stmt->execute([':task_id' => $taskId]);
        $criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("getEvaluationCriteria: Found " . count($criteria) . " criteria for task $taskId");
        
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
        $scores = $data['scores']; // Array of tc_id => score
        $status = $data['status']; // 0=draft, 2=submit
        
        // Calculate total score
        $totalScore = array_sum($scores);
        
        // Update evaluate record
        $updateStmt = $pdo->prepare("
            UPDATE evaluate
            SET status = :status, total_score = :total_score, updated_at = NOW()
            WHERE id_evaluate = :evaluate_id
        ");
        
        $updateStmt->execute([
            ':status' => $status,
            ':total_score' => $totalScore,
            ':evaluate_id' => $evaluateId
        ]);
        
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
                e.total_score
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
