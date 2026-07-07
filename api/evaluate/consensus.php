<?php
/**
 * API GĐ 4 — Cổng Đồng thuận (Consensus Gate) cho đánh giá chéo theo nhóm.
 *
 * Actions (POST):
 *   - state        : trạng thái phiên (dùng polling 4s) — deliver_id
 *   - submit_score : nộp/sửa điểm cá nhân — deliver_id, score, comment
 *   - send_chat    : gửi tin nhắn thảo luận — deliver_id, message
 */

ob_start();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/helper_evaluate.php';
require_once __DIR__ . '/../../config/helper_consensus.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in.']);
    exit;
}

$userId    = (int)$_SESSION['user']['user_id'];
$action    = $_POST['action'] ?? '';
$deliverId = isset($_POST['deliver_id']) ? intval($_POST['deliver_id']) : 0;

if (!$deliverId) {
    echo json_encode(['success' => false, 'message' => 'Missing deliver_id.']);
    exit;
}

// Chỉ thành viên nhóm của deliver mới được thao tác
if (!isConsensusMember($deliverId, $userId)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You are not a member of this evaluation group.']);
    exit;
}

// Bài cần chấm + consensus record
$responseInfo = getResponseToEvaluate($deliverId);
if (!$responseInfo) {
    echo json_encode(['success' => false, 'message' => 'No submission assigned to this group.']);
    exit;
}

$consensus = getOrCreateConsensus($deliverId, $responseInfo['id_response']);

// S_Max từ bộ tiêu chí của task
global $pdo;
$taskStmt = $pdo->prepare("SELECT task_id FROM deliver WHERE id_deliver = :id");
$taskStmt->execute([':id' => $deliverId]);
$taskId = (int)$taskStmt->fetchColumn();
$sMax = getMaxScoreForTask($taskId);
if ($sMax <= 0) {
    $sMax = 10; // fallback khi task chưa gắn tiêu chí
}

switch ($action) {
    case 'state':
        $state = getConsensusState($consensus['id'], $userId);
        echo json_encode([
            'success' => true,
            'data'    => $state,
            's_max'   => $sMax,
        ]);
        break;

    case 'submit_score':
        $comment = trim($_POST['comment'] ?? '');
        $criterionScores = [];

        if (isset($_POST['scores']) && is_array($_POST['scores'])) {
            foreach ($_POST['scores'] as $tcId => $val) {
                $criterionScores[(int)$tcId] = (float)$val;
            }
        } elseif (!empty($_POST['scores_json'])) {
            $decoded = json_decode($_POST['scores_json'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $tcId => $val) {
                    $criterionScores[(int)$tcId] = (float)$val;
                }
            }
        }

        if (empty($criterionScores)) {
            echo json_encode(['success' => false, 'message' => 'Please score every criterion before submitting.']);
            break;
        }

        if (mb_strlen($comment) > 500) {
            echo json_encode(['success' => false, 'message' => 'Comment must be 500 characters or fewer.']);
            break;
        }

        $result = submitConsensusScore($consensus['id'], $userId, $criterionScores, $comment, $taskId, $sMax);
        if ($result['success']) {
            $result['data'] = getConsensusState($consensus['id'], $userId);
        }
        echo json_encode($result);
        break;

    case 'send_chat':
        $message = $_POST['message'] ?? '';
        $result = addConsensusChat($consensus['id'], $userId, $message);
        if ($result['success']) {
            $result['data'] = getConsensusState($consensus['id'], $userId);
        }
        echo json_encode($result);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}
