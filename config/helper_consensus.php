<?php
/**
 * Helper cho GĐ 4 — Peer Review với Cổng Đồng thuận (Consensus Gate).
 *
 * Luồng:
 *   1. Mỗi thành viên nhóm chấm ẩn một điểm tổng (0 → S_Max) kèm lý do ngắn.
 *      Điểm được giấu cho đến khi TẤT CẢ thành viên đã nộp.
 *   2. Khi đủ điểm: nếu chênh lệch max-min <= CONSENSUS_EPSILON → đồng thuận
 *      ngay (final = trung bình). Ngược lại → trạng thái 'discussing':
 *      lộ điểm + lý do, mở chat, thành viên được sửa điểm đến khi khớp.
 *   3. Khi đồng thuận: ghi final_score, đồng bộ sang bảng `evaluate`
 *      (mỗi thành viên 1 dòng, cùng tổng điểm, status = 2) để pipeline
 *      S_Acc hiện có (so với điểm giáo viên) hoạt động không cần sửa —
 *      cả nhóm tự động nhận cùng một S_Acc.
 *
 * Bảng: peer_review_consensus / peer_review_individual_score / peer_review_chat
 * (database/add_consensus_workflow.sql)
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helper_evaluate.php';

// Ngưỡng dung sai đồng thuận: chênh lệch điểm lớn nhất giữa các thành viên
// còn được coi là "đã đồng thuận". 0 = yêu cầu trùng tuyệt đối như plan gốc.
if (!defined('CONSENSUS_EPSILON')) {
    define('CONSENSUS_EPSILON', 0.5);
}

/**
 * Đảm bảo 3 bảng consensus tồn tại (idempotent — chạy 1 lần mỗi request khi cần).
 * Schema gốc: database/add_consensus_workflow.sql
 */
function ensureConsensusTables()
{
    global $pdo;
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    try {
        $exists = $pdo->query("SHOW TABLES LIKE 'peer_review_consensus'")->fetchColumn();
        if ($exists) {
            ensureConsensusCriterionColumn();
            return;
        }
        $sqlFile = __DIR__ . '/../database/add_consensus_workflow.sql';
        if (file_exists($sqlFile)) {
            // Chạy từng statement (PDO exec với multi-statement không đáng tin cậy)
            $statements = array_filter(array_map('trim', explode(';', file_get_contents($sqlFile))));
            foreach ($statements as $statement) {
                if ($statement !== '') {
                    $pdo->exec($statement);
                }
            }
            error_log('ensureConsensusTables: created peer_review_* tables');
        }
        ensureConsensusCriterionColumn();
    } catch (PDOException $e) {
        error_log('ensureConsensusTables ERROR: ' . $e->getMessage());
    }
}

/**
 * Add criterion_scores JSON column when missing (idempotent).
 */
function ensureConsensusCriterionColumn()
{
    global $pdo;
    static $columnChecked = false;
    if ($columnChecked) {
        return;
    }
    $columnChecked = true;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM peer_review_individual_score LIKE 'criterion_scores'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE peer_review_individual_score ADD COLUMN criterion_scores JSON NULL COMMENT 'Per-criterion scores' AFTER score");
        }
    } catch (PDOException $e) {
        error_log('ensureConsensusCriterionColumn ERROR: ' . $e->getMessage());
    }
}

/**
 * Validate per-criterion scores against task rubric; returns total sum.
 */
function validateConsensusCriterionScores(int $taskId, array $criterionScores): array
{
    $criteria = getEvaluationCriteria($taskId);
    if (empty($criteria)) {
        return ['valid' => false, 'total' => 0, 'message' => 'Nhiệm vụ này chưa có tiêu chí đánh giá.'];
    }

    $total = 0.0;
    foreach ($criteria as $criterion) {
        $tcId = (int)$criterion['tc_id'];
        if (!array_key_exists($tcId, $criterionScores) && !array_key_exists((string)$tcId, $criterionScores)) {
            return ['valid' => false, 'total' => 0, 'message' => 'Hãy chấm đủ các tiêu chí.'];
        }
        $val = (float)($criterionScores[$tcId] ?? $criterionScores[(string)$tcId]);
        $min = (float)$criterion['min_score'];
        $max = (float)$criterion['max_score'];
        if ($val < $min || $val > $max) {
            return [
                'valid' => false,
                'total' => 0,
                'message' => sprintf('Score for "%s" must be between %s and %s.', $criterion['name_tc'], $min, $max),
            ];
        }
        $total += $val;
    }

    return ['valid' => true, 'total' => round($total, 1), 'message' => ''];
}

/**
 * Average per-criterion scores across submitted members.
 * @return array<int,float> tc_id => average
 */
function computeConsensusFinalCriterionAverages(int $consensusId): array
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT criterion_scores FROM peer_review_individual_score
        WHERE consensus_id = :cid AND criterion_scores IS NOT NULL
    ");
    $stmt->execute([':cid' => $consensusId]);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($rows)) {
        return [];
    }

    $sums = [];
    $counts = [];
    foreach ($rows as $json) {
        $decoded = is_string($json) ? json_decode($json, true) : $json;
        if (!is_array($decoded)) {
            continue;
        }
        foreach ($decoded as $tcId => $score) {
            $tcId = (int)$tcId;
            $sums[$tcId] = ($sums[$tcId] ?? 0) + (float)$score;
            $counts[$tcId] = ($counts[$tcId] ?? 0) + 1;
        }
    }

    $averages = [];
    foreach ($sums as $tcId => $sum) {
        $averages[$tcId] = round($sum / $counts[$tcId], 1);
    }
    return $averages;
}

/**
 * Lấy danh sách thành viên nhóm của một deliver (type = 'group').
 */
function getConsensusGroupMembers($deliverId)
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT gm.user_id, u.fullname
        FROM deliver d
        INNER JOIN group_member gm ON gm.group_id = d.value
        INNER JOIN users u ON u.user_id = gm.user_id
        WHERE d.id_deliver = :deliver_id AND d.type = 'group'
        ORDER BY u.fullname ASC
    ");
    $stmt->execute([':deliver_id' => $deliverId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Lấy (hoặc tạo) bản ghi consensus cho cặp deliver + response.
 */
function getOrCreateConsensus($deliverId, $responseId)
{
    global $pdo;

    ensureConsensusTables();

    $stmt = $pdo->prepare("
        SELECT * FROM peer_review_consensus
        WHERE id_deliver = :deliver_id AND response_review = :response_id
    ");
    $stmt->execute([':deliver_id' => $deliverId, ':response_id' => $responseId]);
    $consensus = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($consensus) {
        return $consensus;
    }

    $insert = $pdo->prepare("
        INSERT INTO peer_review_consensus (id_deliver, response_review, status)
        VALUES (:deliver_id, :response_id, 'pending')
    ");
    $insert->execute([':deliver_id' => $deliverId, ':response_id' => $responseId]);

    $stmt->execute([':deliver_id' => $deliverId, ':response_id' => $responseId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Nộp / sửa điểm cá nhân (Tầng 1 — chấm ẩn).
 * - Trạng thái 'pending': mỗi người nộp 1 lần, không sửa (điểm bí mật).
 * - Trạng thái 'discussing': được sửa điểm để tiến tới đồng thuận.
 * - Trạng thái 'agreed'/'timeout': khoá.
 *
 * @return array ['success' => bool, 'message' => string]
 */
function submitConsensusScore($consensusId, $userId, array $criterionScores, $comment, $taskId, $sMax)
{
    global $pdo;

    ensureConsensusCriterionColumn();

    $consensus = getConsensusById($consensusId);
    if (!$consensus) {
        return ['success' => false, 'message' => 'Không tìm thấy phiên chấm theo nhóm.'];
    }
    if (in_array($consensus['status'], ['agreed', 'timeout'], true)) {
        return ['success' => false, 'message' => 'Phiếu chấm đã chốt, không đổi được điểm.'];
    }

    $validation = validateConsensusCriterionScores($taskId, $criterionScores);
    if (!$validation['valid']) {
        return ['success' => false, 'message' => $validation['message']];
    }

    $score = $validation['total'];
    if ($score < 0 || $score > $sMax) {
        return ['success' => false, 'message' => "Total score must be between 0 and {$sMax}."];
    }

    $normalized = [];
    foreach ($criterionScores as $tcId => $val) {
        $normalized[(int)$tcId] = (float)$val;
    }
    $jsonScores = json_encode($normalized, JSON_UNESCAPED_UNICODE);

    try {
        $pdo->beginTransaction();

        $existing = $pdo->prepare("
            SELECT id FROM peer_review_individual_score
            WHERE consensus_id = :cid AND user_id = :uid
        ");
        $existing->execute([':cid' => $consensusId, ':uid' => $userId]);
        $row = $existing->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            if ($consensus['status'] === 'pending') {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Bạn đã gửi điểm. Đang chờ các bạn khác trong nhóm.'];
            }
            $upd = $pdo->prepare("
                UPDATE peer_review_individual_score
                SET score = :score, criterion_scores = :criterion_scores, comment = :comment, submitted_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $upd->execute([
                ':score' => $score,
                ':criterion_scores' => $jsonScores,
                ':comment' => $comment,
                ':id' => $row['id'],
            ]);
        } else {
            $ins = $pdo->prepare("
                INSERT INTO peer_review_individual_score (consensus_id, user_id, score, criterion_scores, comment)
                VALUES (:cid, :uid, :score, :criterion_scores, :comment)
            ");
            $ins->execute([
                ':cid' => $consensusId,
                ':uid' => $userId,
                ':score' => $score,
                ':criterion_scores' => $jsonScores,
                ':comment' => $comment,
            ]);
        }

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('submitConsensusScore ERROR: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Không lưu được điểm.'];
    }

    $progress = evaluateConsensusProgress($consensusId);

    return ['success' => true, 'message' => 'Đã lưu điểm của bạn.', 'status' => $progress];
}

function getConsensusById($consensusId)
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM peer_review_consensus WHERE id = :id");
    $stmt->execute([':id' => $consensusId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Kiểm tra tiến độ đồng thuận sau mỗi lần nộp/sửa điểm.
 * @return string trạng thái mới của consensus
 */
function evaluateConsensusProgress($consensusId)
{
    global $pdo;

    $consensus = getConsensusById($consensusId);
    if (!$consensus || in_array($consensus['status'], ['agreed', 'timeout'], true)) {
        return $consensus ? $consensus['status'] : 'pending';
    }

    $members = getConsensusGroupMembers($consensus['id_deliver']);
    $memberCount = count($members);
    if ($memberCount === 0) {
        return $consensus['status'];
    }

    $scoreStmt = $pdo->prepare("
        SELECT score FROM peer_review_individual_score WHERE consensus_id = :cid
    ");
    $scoreStmt->execute([':cid' => $consensusId]);
    $scores = array_map('floatval', $scoreStmt->fetchAll(PDO::FETCH_COLUMN));

    if (count($scores) < $memberCount) {
        return $consensus['status']; // còn người chưa nộp → giữ nguyên
    }

    $spread = max($scores) - min($scores);

    if ($spread <= CONSENSUS_EPSILON) {
        $avgCriteria = computeConsensusFinalCriterionAverages($consensusId);
        if (!empty($avgCriteria)) {
            $final = round(array_sum($avgCriteria), 1);
        } else {
            $final = round(array_sum($scores) / count($scores), 1);
        }
        $upd = $pdo->prepare("
            UPDATE peer_review_consensus
            SET status = 'agreed', final_score = :final, resolved_at = CURRENT_TIMESTAMP,
                started_at = COALESCE(started_at, CURRENT_TIMESTAMP)
            WHERE id = :id
        ");
        $upd->execute([':final' => $final, ':id' => $consensusId]);

        applyConsensusToEvaluations($consensusId);
        return 'agreed';
    }

    // Có bất đồng → mở giai đoạn thảo luận
    if ($consensus['status'] !== 'discussing') {
        $upd = $pdo->prepare("
            UPDATE peer_review_consensus
            SET status = 'discussing', started_at = COALESCE(started_at, CURRENT_TIMESTAMP)
            WHERE id = :id
        ");
        $upd->execute([':id' => $consensusId]);
    }
    return 'discussing';
}

/**
 * Khi nhóm đã đồng thuận: đồng bộ điểm chung sang bảng `evaluate` cho từng
 * thành viên (status = 2) để pipeline S_Acc so với điểm giáo viên áp dụng
 * cho cả nhóm — mọi thành viên nhận cùng S_Acc đúng như thiết kế plan.
 */
function applyConsensusToEvaluations($consensusId)
{
    global $pdo;

    $consensus = getConsensusById($consensusId);
    if (!$consensus || $consensus['status'] !== 'agreed') {
        return false;
    }

    $deliverId  = (int)$consensus['id_deliver'];
    $responseId = (int)$consensus['response_review'];
    $final      = (float)$consensus['final_score'];

    // Lấy task_id từ deliver để tính S_Acc
    $taskStmt = $pdo->prepare("SELECT task_id FROM deliver WHERE id_deliver = :id");
    $taskStmt->execute([':id' => $deliverId]);
    $taskId = (int)$taskStmt->fetchColumn();

    $members = getConsensusGroupMembers($deliverId);
    $avgCriteria = computeConsensusFinalCriterionAverages($consensusId);

    try {
        $pdo->beginTransaction();

        foreach ($members as $member) {
            $uid = (int)$member['user_id'];

            $check = $pdo->prepare("
                SELECT id_evaluate FROM evaluate
                WHERE deliver_id = :did AND user_id = :uid
            ");
            $check->execute([':did' => $deliverId, ':uid' => $uid]);
            $evalId = $check->fetchColumn();

            if ($evalId) {
                $upd = $pdo->prepare("
                    UPDATE evaluate
                    SET total_score = :score, status = 2, is_teacher = 0, response_id = :rid
                    WHERE id_evaluate = :eid
                ");
                $upd->execute([':score' => $final, ':rid' => $responseId, ':eid' => $evalId]);
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO evaluate (deliver_id, user_id, response_id, status, total_score, is_teacher)
                    VALUES (:did, :uid, :rid, 2, :score, 0)
                ");
                $ins->execute([':did' => $deliverId, ':uid' => $uid, ':rid' => $responseId, ':score' => $final]);
                $evalId = (int)$pdo->lastInsertId();
            }

            if ($evalId && !empty($avgCriteria)) {
                foreach ($avgCriteria as $tcId => $avgScore) {
                    $detailCheck = $pdo->prepare("
                        SELECT id_detail_eval FROM detail_evaluate
                        WHERE id_evaluate = :eid AND tc_id = :tc
                    ");
                    $detailCheck->execute([':eid' => $evalId, ':tc' => $tcId]);
                    if ($detailCheck->fetchColumn()) {
                        $pdo->prepare("UPDATE detail_evaluate SET diem = :d WHERE id_evaluate = :eid AND tc_id = :tc")
                            ->execute([':d' => $avgScore, ':eid' => $evalId, ':tc' => $tcId]);
                    } else {
                        $pdo->prepare("INSERT INTO detail_evaluate (tc_id, diem, id_evaluate) VALUES (:tc, :d, :eid)")
                            ->execute([':tc' => $tcId, ':d' => $avgScore, ':eid' => $evalId]);
                    }
                }
            }
        }

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('applyConsensusToEvaluations ERROR: ' . $e->getMessage());
        return false;
    }

    // Nếu giáo viên đã chấm bài này → cập nhật S_Acc ngay cho cả nhóm
    if ($taskId) {
        calculateAndSaveAccuracyScores($responseId, $taskId);
    }
    return true;
}

/**
 * Gửi tin nhắn thảo luận (chỉ khi đang ở trạng thái 'discussing').
 */
function addConsensusChat($consensusId, $userId, $message)
{
    global $pdo;

    $consensus = getConsensusById($consensusId);
    if (!$consensus) {
        return ['success' => false, 'message' => 'Không tìm thấy phiên đồng thuận.'];
    }
    if ($consensus['status'] !== 'discussing') {
        return ['success' => false, 'message' => 'Chỉ thảo luận được khi nhóm còn bất đồng điểm.'];
    }

    $message = trim($message);
    if ($message === '' || mb_strlen($message) > 2000) {
        return ['success' => false, 'message' => 'Tin nhắn không hợp lệ.'];
    }

    $ins = $pdo->prepare("
        INSERT INTO peer_review_chat (consensus_id, user_id, message)
        VALUES (:cid, :uid, :msg)
    ");
    $ins->execute([':cid' => $consensusId, ':uid' => $userId, ':msg' => $message]);

    return ['success' => true];
}

/**
 * Trạng thái đầy đủ của phiên đồng thuận cho 1 thành viên (dùng cho polling).
 * Điểm của người khác chỉ lộ khi status != 'pending'.
 */
function getConsensusState($consensusId, $userId)
{
    global $pdo;

    $consensus = getConsensusById($consensusId);
    if (!$consensus) {
        return false;
    }

    $members = getConsensusGroupMembers($consensus['id_deliver']);

    $scoreStmt = $pdo->prepare("
        SELECT user_id, score, criterion_scores, comment, submitted_at
        FROM peer_review_individual_score
        WHERE consensus_id = :cid
    ");
    $scoreStmt->execute([':cid' => $consensusId]);
    $scoreRows = [];
    foreach ($scoreStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!empty($row['criterion_scores'])) {
            $decoded = json_decode($row['criterion_scores'], true);
            $row['criterion_scores'] = is_array($decoded) ? $decoded : null;
        } else {
            $row['criterion_scores'] = null;
        }
        $scoreRows[(int)$row['user_id']] = $row;
    }

    $revealScores = $consensus['status'] !== 'pending';

    $memberStates = [];
    foreach ($members as $member) {
        $uid = (int)$member['user_id'];
        $hasScore = isset($scoreRows[$uid]);
        $entry = [
            'user_id'   => $uid,
            'fullname'  => $member['fullname'],
            'is_me'     => $uid === (int)$userId,
            'submitted' => $hasScore,
            'score'     => null,
            'criterion_scores' => null,
            'comment'   => null,
        ];
        if ($hasScore && ($revealScores || $uid === (int)$userId)) {
            $entry['score']   = (float)$scoreRows[$uid]['score'];
            $entry['comment'] = $scoreRows[$uid]['comment'];
            $entry['criterion_scores'] = $scoreRows[$uid]['criterion_scores'];
        }
        $memberStates[] = $entry;
    }

    // Chat (lộ toàn bộ khi đã qua giai đoạn pending)
    $chat = [];
    if ($revealScores) {
        $chatStmt = $pdo->prepare("
            SELECT c.user_id, u.fullname, c.message, c.sent_at
            FROM peer_review_chat c
            INNER JOIN users u ON u.user_id = c.user_id
            WHERE c.consensus_id = :cid
            ORDER BY c.id ASC
        ");
        $chatStmt->execute([':cid' => $consensusId]);
        $chat = $chatStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $submittedCount = count($scoreRows);

    return [
        'id'              => (int)$consensus['id'],
        'status'          => $consensus['status'],
        'final_score'     => $consensus['final_score'] !== null ? (float)$consensus['final_score'] : null,
        'started_at'      => $consensus['started_at'],
        'resolved_at'     => $consensus['resolved_at'],
        'members'         => $memberStates,
        'member_count'    => count($members),
        'submitted_count' => $submittedCount,
        'epsilon'         => CONSENSUS_EPSILON,
        'chat'            => $chat,
    ];
}

/**
 * Kiểm tra user có phải thành viên nhóm của deliver không.
 */
function isConsensusMember($deliverId, $userId)
{
    foreach (getConsensusGroupMembers($deliverId) as $member) {
        if ((int)$member['user_id'] === (int)$userId) {
            return true;
        }
    }
    return false;
}
