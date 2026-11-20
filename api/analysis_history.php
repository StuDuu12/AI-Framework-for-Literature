<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_task.php';
require_once __DIR__ . '/../config/helper_analysis.php';

$user_id = $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $task_id = intval($_GET['task_id'] ?? 0);
    $deliver_id = intval($_GET['deliver_id'] ?? 0);
    if (!$task_id || !$deliver_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }
    if (!validateUserDeliverAccess($deliver_id, $user_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    try {
        $deliverStmt = $pdo->prepare("SELECT status FROM deliver WHERE id_deliver = :did");
        $deliverStmt->execute([':did' => $deliver_id]);
        $deliverStatus = (int)($deliverStmt->fetchColumn() ?: 0);

        $stmt = $pdo->prepare("
            SELECT id, label, analysis_data, created_at, is_confirmed, confirmed_at
            FROM analysis_history
            WHERE user_id = :uid AND task_id = :tid AND deliver_id = :did
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $stmt->execute([':uid' => $user_id, ':tid' => $task_id, ':did' => $deliver_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['analysis_data'] = json_decode($r['analysis_data'], true);
            $r['is_confirmed'] = (int)($r['is_confirmed'] ?? 0);
        }
        echo json_encode([
            'success' => true,
            'history' => $rows,
            'deliver_status' => $deliverStatus,
            'confirmed_history_id' => getConfirmedHistoryId($user_id, $task_id, $deliver_id),
        ]);
    } catch (PDOException $e) {
        try {
            $stmt = $pdo->prepare("
                SELECT id, label, analysis_data, created_at
                FROM analysis_history
                WHERE user_id = :uid AND task_id = :tid AND deliver_id = :did
                ORDER BY created_at DESC
                LIMIT 20
            ");
            $stmt->execute([':uid' => $user_id, ':tid' => $task_id, ':did' => $deliver_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $r['analysis_data'] = json_decode($r['analysis_data'], true);
                $r['is_confirmed'] = 0;
            }
            echo json_encode([
                'success' => true,
                'history' => $rows,
                'deliver_status' => $deliverStatus ?? 0,
                'confirmed_history_id' => null,
            ]);
        } catch (PDOException $e2) {
            echo json_encode(['success' => true, 'history' => [], 'deliver_status' => 0, 'confirmed_history_id' => null]);
        }
    }
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? 'save';
    $task_id = intval($input['task_id'] ?? 0);
    $deliver_id = intval($input['deliver_id'] ?? 0);

    if (!$task_id || !$deliver_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }
    if (!validateUserDeliverAccess($deliver_id, $user_id)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }

    try {
        if ($action === 'delete') {
            $id = intval($input['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM analysis_history WHERE id = :id AND user_id = :uid");
            $stmt->execute([':id' => $id, ':uid' => $user_id]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'clear_all') {
            clearDeliverSubmission($deliver_id, $user_id, $task_id);
            $responseIds = collectResponseTreeIdsForTaskUser($task_id, $user_id);
            if (!empty($responseIds)) {
                deleteResponsesAndRelated($responseIds);
            }
            echo json_encode(['success' => true, 'message' => 'History and submission cleared']);
            exit;
        }

        if ($action === 'confirm') {
            $history_id = intval($input['history_id'] ?? 0) ?: null;
            $analysis_data = $input['analysis_data'] ?? null;

            try {
                $result = confirmAnalysisHistoryEntry(
                    $user_id,
                    $task_id,
                    $deliver_id,
                    $history_id,
                    is_array($analysis_data) ? $analysis_data : null
                );
                echo json_encode([
                    'success' => true,
                    'message' => 'Final submission confirmed. Your teacher will see this version.',
                    'data' => $result,
                ]);
            } catch (InvalidArgumentException $e) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => client_error_message($e)]);
            }
            exit;
        }

        if ($action === 'compare') {
            $id_a = intval($input['id_a'] ?? 0);
            $id_b = intval($input['id_b'] ?? 0);
            $stmt = $pdo->prepare("SELECT id, label, analysis_data, created_at FROM analysis_history WHERE id IN (:a, :b) AND user_id = :uid");
            $stmt->execute([':a' => $id_a, ':b' => $id_b, ':uid' => $user_id]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as &$r) {
                $r['analysis_data'] = json_decode($r['analysis_data'], true);
            }
            echo json_encode(['success' => true, 'entries' => $rows]);
            exit;
        }

        // save
        $label = trim($input['label'] ?? '') ?: date('d/m/Y H:i');
        $analysis_data = $input['analysis_data'] ?? null;
        if ($analysis_data === null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing analysis_data']);
            exit;
        }
        $json = json_encode($analysis_data, JSON_UNESCAPED_UNICODE);
        $stmt = $pdo->prepare("
            INSERT INTO analysis_history (user_id, task_id, deliver_id, label, analysis_data)
            VALUES (:uid, :tid, :did, :label, :data)
        ");
        $stmt->execute([
            ':uid' => $user_id,
            ':tid' => $task_id,
            ':did' => $deliver_id,
            ':label' => $label,
            ':data' => $json
        ]);
        $newId = (int)$pdo->lastInsertId();
        // Keep max 20 per deliver
        $pdo->prepare("
            DELETE FROM analysis_history WHERE user_id = :uid AND task_id = :tid AND deliver_id = :did
            AND id NOT IN (
                SELECT id FROM (
                    SELECT id FROM analysis_history
                    WHERE user_id = :uid2 AND task_id = :tid2 AND deliver_id = :did2
                    ORDER BY created_at DESC LIMIT 20
                ) t
            )
        ")->execute([':uid' => $user_id, ':tid' => $task_id, ':did' => $deliver_id, ':uid2' => $user_id, ':tid2' => $task_id, ':did2' => $deliver_id]);

        echo json_encode(['success' => true, 'id' => $newId, 'label' => $label]);
    } catch (PDOException $e) {
        error_log('analysis_history: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'History not available. Run database/add_analysis_features.sql']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
