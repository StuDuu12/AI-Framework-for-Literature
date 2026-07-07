<?php
/**
 * Seed data for PHASE_CHAMDIEM (Final Grading).
 * Run: php seed_grading_phase.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('CLI only');
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helper_evaluate.php';
require_once __DIR__ . '/config/helper_homework.php';

function resolveCriteriaGroupId(PDO $pdo, int $sourceTaskId): int
{
    $stmt = $pdo->prepare('SELECT title, file_content FROM task WHERE task_id = :id');
    $stmt->execute([':id' => $sourceTaskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) {
        return 1;
    }
    $hay = strtolower(($task['title'] ?? '') . ' ' . ($task['file_content'] ?? ''));
    if (preg_match('/\b(story|truyện|lão hạc|nam cao|narrative|tale)\b/u', $hay)) {
        return 3;
    }
    if (preg_match('/\b(drama|play|kịch|tuồng|hồn trương)\b/u', $hay)) {
        return 3;
    }
    return 1;
}

function resolveTopicTeacherId(PDO $pdo, int $topicId): int
{
    $stmt = $pdo->prepare("
        SELECT u.user_id
        FROM topic t
        INNER JOIN class_member cm ON cm.class_id = t.class_id
        INNER JOIN users u ON u.user_id = cm.user_id
        WHERE t.topic_id = :tid AND u.role = 'teacher'
        ORDER BY cm.user_id ASC
        LIMIT 1
    ");
    $stmt->execute([':tid' => $topicId]);
    $id = (int)$stmt->fetchColumn();
    if ($id) {
        return $id;
    }
    $fallback = (int)$pdo->query("SELECT user_id FROM users WHERE role = 'teacher' ORDER BY user_id LIMIT 1")->fetchColumn();
    return $fallback ?: 2;
}

function findPeerEvalTaskId(PDO $pdo, int $topicId, int $gradingPhaseNumber): ?int
{
    $stmt = $pdo->prepare("
        SELECT t.task_id
        FROM task t
        INNER JOIN phase p ON t.phase_id = p.phase_id
        INNER JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
        WHERE p.topic_id = :tid
          AND mp.phase_code = 'PHASE_DANHGIANGUOIHOC'
          AND p.phase_number < :grading_num
        ORDER BY p.phase_number DESC
        LIMIT 1
    ");
    $stmt->execute([':tid' => $topicId, ':grading_num' => $gradingPhaseNumber]);
    $id = $stmt->fetchColumn();
    return $id ? (int)$id : null;
}

function ensureTaskEvaluateBinding(PDO $pdo, int $gradingTaskId, int $groupId): void
{
    $check = $pdo->prepare('SELECT 1 FROM task_evaluate WHERE id_task = :tid AND id_group_tc = :gid');
    $check->execute([':tid' => $gradingTaskId, ':gid' => $groupId]);
    if (!$check->fetchColumn()) {
        $pdo->prepare('INSERT INTO task_evaluate (id_task, id_group_tc) VALUES (:tid, :gid)')
            ->execute([':tid' => $gradingTaskId, ':gid' => $groupId]);
        echo "  + task_evaluate: task {$gradingTaskId} -> criteria group {$groupId}\n";
    }
}

function ensureDeliverEvaluate(PDO $pdo, int $evaluatorDeliverId, int $responseReviewId): void
{
    $check = $pdo->prepare('SELECT 1 FROM deliver_evaluate WHERE id_deliver = :did AND response_review = :rid');
    $check->execute([':did' => $evaluatorDeliverId, ':rid' => $responseReviewId]);
    if (!$check->fetchColumn()) {
        $pdo->prepare('INSERT INTO deliver_evaluate (id_deliver, response_review) VALUES (:did, :rid)')
            ->execute([':did' => $evaluatorDeliverId, ':rid' => $responseReviewId]);
    }
}

function ensurePeerEvaluation(PDO $pdo, int $deliverId, int $responseId, int $evaluatorUserId, array $scoresByTc, int $status = 2): void
{
    $check = $pdo->prepare('
        SELECT id_evaluate FROM evaluate
        WHERE deliver_id = :did AND response_id = :rid AND user_id = :uid AND is_teacher = 0
        LIMIT 1
    ');
    $check->execute([':did' => $deliverId, ':rid' => $responseId, ':uid' => $evaluatorUserId]);
    $existingId = (int)$check->fetchColumn();

    if ($existingId) {
        return;
    }

    $total = array_sum($scoresByTc);
    $pdo->prepare('
        INSERT INTO evaluate (deliver_id, user_id, response_id, status, total_score, is_teacher, accuracy_score)
        VALUES (:did, :uid, :rid, :status, :total, 0, :acc)
    ')->execute([
        ':did' => $deliverId,
        ':uid' => $evaluatorUserId,
        ':rid' => $responseId,
        ':status' => $status,
        ':total' => $total,
        ':acc' => 80 + (mt_rand(0, 15)),
    ]);
    $evaluateId = (int)$pdo->lastInsertId();

    foreach ($scoresByTc as $tcId => $score) {
        $pdo->prepare('INSERT INTO detail_evaluate (tc_id, diem, id_evaluate) VALUES (:tc, :diem, :eid)')
            ->execute([':tc' => $tcId, ':diem' => $score, ':eid' => $evaluateId]);
    }
    echo "    + peer evaluate #{$evaluateId} (user {$evaluatorUserId} -> response {$responseId}, total {$total})\n";
}

function ensureTeacherEvaluation(
    PDO $pdo,
    int $teacherId,
    int $gradingTaskId,
    int $deliverId,
    int $responseId,
    array $scoresByTc,
    int $status
): void {
    $check = $pdo->prepare('
        SELECT id_evaluate, status FROM evaluate
        WHERE response_id = :rid AND user_id = :uid AND is_teacher = 1
        LIMIT 1
    ');
    $check->execute([':rid' => $responseId, ':uid' => $teacherId]);
    $row = $check->fetch(PDO::FETCH_ASSOC);

    if ($row && (int)$row['status'] === 2) {
        return;
    }

    if ($row) {
        $evaluateId = (int)$row['id_evaluate'];
        $pdo->prepare('DELETE FROM detail_evaluate WHERE id_evaluate = :eid')->execute([':eid' => $evaluateId]);
    } else {
        $pdo->prepare('
            INSERT INTO evaluate (deliver_id, user_id, response_id, status, total_score, is_teacher)
            VALUES (:did, :uid, :rid, :status, 0, 1)
        ')->execute([
            ':did' => $deliverId,
            ':uid' => $teacherId,
            ':rid' => $responseId,
            ':status' => $status,
        ]);
        $evaluateId = (int)$pdo->lastInsertId();
    }

    $total = array_sum($scoresByTc);
    foreach ($scoresByTc as $tcId => $score) {
        $pdo->prepare('INSERT INTO detail_evaluate (tc_id, diem, id_evaluate) VALUES (:tc, :diem, :eid)')
            ->execute([':tc' => $tcId, ':diem' => $score, ':eid' => $evaluateId]);
    }
    $pdo->prepare('UPDATE evaluate SET total_score = :total, status = :status WHERE id_evaluate = :eid')
        ->execute([':total' => $total, ':status' => $status, ':eid' => $evaluateId]);

    $label = $status === 2 ? 'final' : 'draft';
    echo "    + teacher evaluate #{$evaluateId} ({$label}, total {$total})\n";
}

function storyScoreSet(int $variant = 0): array
{
    $sets = [
        [9 => 8, 10 => 8, 11 => 12, 12 => 13, 13 => 8],
        [9 => 9, 10 => 7, 11 => 14, 12 => 12, 13 => 9],
        [9 => 7, 10 => 9, 11 => 11, 12 => 14, 13 => 7],
        [9 => 8, 10 => 8, 11 => 13, 12 => 11, 13 => 8],
    ];
    return $sets[$variant % count($sets)];
}

function poetryScoreSet(int $variant = 0): array
{
    $sets = [
        [1 => 2, 2 => 2, 3 => 2, 4 => 1],
        [1 => 1, 2 => 3, 3 => 2, 4 => 2],
        [1 => 2, 2 => 2, 3 => 3, 4 => 1],
        [1 => 2, 2 => 1, 3 => 2, 4 => 2],
    ];
    return $sets[$variant % count($sets)];
}

echo "=== Seed PHASE_CHAMDIEM (Final Grading) ===\n";

$gradingTasks = $pdo->query("
    SELECT t.task_id, t.title, t.review_task, p.topic_id, p.phase_id, p.phase_number
    FROM task t
    INNER JOIN phase p ON t.phase_id = p.phase_id
    INNER JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
    WHERE mp.phase_code = 'PHASE_CHAMDIEM'
    ORDER BY t.task_id
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($gradingTasks)) {
    echo "No grading tasks found.\n";
    exit(1);
}

$pdo->beginTransaction();
try {
    $pdo->exec("
        UPDATE task t
        INNER JOIN phase p ON t.phase_id = p.phase_id
        INNER JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
        SET t.title = 'Final grading'
        WHERE mp.phase_code = 'PHASE_CHAMDIEM'
          AND (t.title LIKE '%Cross-grade%' OR t.title LIKE '%Grade the homework%' OR t.title = 'Week 1 grading')
    ");

    foreach ($gradingTasks as $gt) {
        $gradingTaskId = (int)$gt['task_id'];
        $sourceTaskId = (int)($gt['review_task'] ?: $gradingTaskId);
        $topicId = (int)$gt['topic_id'];
        $teacherId = resolveTopicTeacherId($pdo, $topicId);
        $criteriaGroup = resolveCriteriaGroupId($pdo, $sourceTaskId);
        $isStory = ($criteriaGroup === 3);

        echo "\nGrading task #{$gradingTaskId} \"{$gt['title']}\" (source task {$sourceTaskId}, topic {$topicId}, teacher {$teacherId})\n";

        ensureTaskEvaluateBinding($pdo, $gradingTaskId, $criteriaGroup);

        $submissions = getResponsesForEvaluation($gradingTaskId);
        if (empty($submissions)) {
            echo "  ! No submissions on source task — skip.\n";
            continue;
        }

        $peerTaskId = findPeerEvalTaskId($pdo, $topicId, (int)$gt['phase_number']);
        $peerDelivers = [];
        if ($peerTaskId) {
            $pd = $pdo->prepare('SELECT id_deliver, type, value FROM deliver WHERE task_id = :tid');
            $pd->execute([':tid' => $peerTaskId]);
            $peerDelivers = $pd->fetchAll(PDO::FETCH_ASSOC);
            echo "  Peer eval task: {$peerTaskId} (" . count($peerDelivers) . " evaluator delivers)\n";
        }

        $idx = 0;
        foreach ($submissions as $sub) {
            $deliverId = (int)$sub['id_deliver'];
            $responseId = (int)($sub['response'] ?? 0);
            if (!$responseId) {
                continue;
            }

            $pdo->prepare('UPDATE deliver SET status = 2 WHERE id_deliver = :did AND response IS NOT NULL')
                ->execute([':did' => $deliverId]);

            $submitterId = (int)($sub['submitter_id'] ?? 0);
            echo "  Submission deliver {$deliverId} / response {$responseId} ({$sub['submitter_name']})\n";

            if ($peerTaskId && !empty($peerDelivers)) {
                $evaluatorDeliver = $peerDelivers[$idx % count($peerDelivers)];
                $evalDeliverId = (int)$evaluatorDeliver['id_deliver'];
                ensureDeliverEvaluate($pdo, $evalDeliverId, $responseId);

                $evaluatorUserId = (int)$evaluatorDeliver['value'];
                if ($evaluatorDeliver['type'] === 'group') {
                    $gm = $pdo->prepare('SELECT user_id FROM group_member WHERE group_id = :gid LIMIT 1');
                    $gm->execute([':gid' => $evaluatorUserId]);
                    $evaluatorUserId = (int)$gm->fetchColumn() ?: 390;
                }

                $peerScores = $isStory ? storyScoreSet($idx) : poetryScoreSet($idx);
                ensurePeerEvaluation($pdo, $evalDeliverId, $responseId, $evaluatorUserId, $peerScores, 2);

                if (count($peerDelivers) > 1) {
                    $second = $peerDelivers[($idx + 1) % count($peerDelivers)];
                    $secondDeliverId = (int)$second['id_deliver'];
                    ensureDeliverEvaluate($pdo, $secondDeliverId, $responseId);
                    $secondUserId = (int)$second['value'];
                    if ($second['type'] === 'group') {
                        $gm = $pdo->prepare('SELECT user_id FROM group_member WHERE group_id = :gid LIMIT 1 OFFSET 1');
                        $gm->execute([':gid' => $secondUserId]);
                        $secondUserId = (int)$gm->fetchColumn() ?: 391;
                    }
                    if ($secondUserId !== $evaluatorUserId) {
                        ensurePeerEvaluation($pdo, $secondDeliverId, $responseId, $secondUserId, $isStory ? storyScoreSet($idx + 2) : poetryScoreSet($idx + 2), 2);
                    }
                }
            }

            $teacherScores = $isStory ? storyScoreSet($idx + 1) : poetryScoreSet($idx + 1);
            $teacherStatus = ($idx < 2) ? 2 : 1;
            ensureTeacherEvaluation($pdo, $teacherId, $gradingTaskId, $deliverId, $responseId, $teacherScores, $teacherStatus);

            $idx++;
        }
    }

    $pdo->commit();
    echo "\nDone. Log in as teacher (gvl/gv1) and open Final Grading on each topic.\n";
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
