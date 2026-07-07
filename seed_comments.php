<?php
/**
 * Seed rich teacher ↔ student comment threads for demo / real-time testing.
 * CLI only: php seed_comments.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die('CLI only');
}

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helper_homework.php';

$teacherIds = [];
$studentIds = [];

$teacherRows = $pdo->query("SELECT user_id FROM users WHERE role = 'teacher' ORDER BY user_id")->fetchAll(PDO::FETCH_COLUMN);
$studentRows = $pdo->query("SELECT user_id FROM users WHERE role = 'student' ORDER BY user_id LIMIT 12")->fetchAll(PDO::FETCH_COLUMN);

if (empty($teacherRows) || empty($studentRows)) {
    fwrite(STDERR, "Need at least one teacher and one student in the database.\n");
    exit(1);
}

$primaryTeacher = (int)$teacherRows[0];
$secondaryTeacher = (int)($teacherRows[1] ?? $teacherRows[0]);

/** Literary comment templates (English) keyed by topic hint in content preview */
$threadTemplates = [
    'lao_hac' => [
        ['role' => 'teacher', 'text' => 'You analyze Old Hac\'s tragedy well. Please add the scene where he weeps while selling Golden — that is the story\'s key psychological moment.'],
        ['role' => 'student', 'text' => 'Got it. I will add the passage where Old Hac "sobs like a child" to show his guilt over sacrificing his son\'s keepsake.'],
        ['role' => 'teacher', 'text' => 'Good. When quoting, clarify that this is inner monologue filtered through the teacher-narrator — not Old Hac\'s direct speech.'],
        ['role' => 'student', 'text' => 'I revised the narrative voice section and noted the mournful, sympathetic tone of the narrator.'],
        ['role' => 'teacher', 'text' => 'Nice work. Next, connect the character study to colonial-feudal society for stronger social realism.'],
        ['role' => 'student', 'text' => 'I added a paragraph on peasants pushed to the edge — Old Hac chooses death to preserve his dignity, not only because of hunger.'],
    ],
    'chan_que' => [
        ['role' => 'teacher', 'text' => 'Your homeland imagery is emotionally rich. Can you analyze the rhythm and the word "chân" (authentic/raw) in the title?'],
        ['role' => 'student', 'text' => 'I think "chân" suggests truth and plainness — the opposite of an idealized memory.'],
        ['role' => 'teacher', 'text' => 'Strong point. Illustrate with one concrete line and explain why that detail feels more "authentic" than nostalgia.'],
        ['role' => 'student', 'text' => 'I used images of the ferry and rice fields — daily labor, not postcard scenery.'],
        ['role' => 'teacher', 'text' => 'Good. Add a short comparison with Te Hanh\'s "Homeland" to highlight the difference in tone.'],
    ],
    'general' => [
        ['role' => 'teacher', 'text' => 'The analysis is well structured. Turn a few general observations into claims backed by specific textual evidence.'],
        ['role' => 'student', 'text' => 'I will review each section and add short quotes with explanation.'],
        ['role' => 'teacher', 'text' => 'Keep an academic tone but stay readable — avoid bullet-like lists without transitions.'],
        ['role' => 'student', 'text' => 'I added linking sentences and a mini-summary at the end of each paragraph.'],
        ['role' => 'teacher', 'text' => 'Well done. Submit the revised draft before the next class for more feedback.'],
    ],
    'character' => [
        ['role' => 'teacher', 'text' => 'You cover appearance and action. Go deeper into motivation — why does the character make that choice?'],
        ['role' => 'student', 'text' => 'I think the motive is love for his child and self-respect — two forces pulling in opposite directions.'],
        ['role' => 'teacher', 'text' => 'Exactly. Use the term "inner conflict" and illustrate it with one pivotal decision in the story.'],
        ['role' => 'student', 'text' => 'I use selling the dog and asking for poison as two turning points that show that conflict.'],
    ],
    'imagery' => [
        ['role' => 'teacher', 'text' => 'Imagery and symbolism are fairly complete. Can you distinguish recurring motifs from single symbols?'],
        ['role' => 'student', 'text' => 'Loneliness is a motif across space and action; the dog Golden is a symbol of attachment and loyalty.'],
        ['role' => 'teacher', 'text' => 'Very good. Add one sentence on the artistic effect when the motif returns at the end.'],
    ],
];

$generalComments = [
    ['role' => 'teacher', 'text' => 'Overall, you show a solid AI-assisted workflow with selective editing. Keep adding your own interpretive voice.'],
    ['role' => 'student', 'text' => 'Thank you. I will add more Take Note entries after class.'],
    ['role' => 'teacher', 'text' => 'Please respond to each section you revised — do not submit raw AI output only.'],
    ['role' => 'student', 'text' => 'I highlighted self-written paragraphs in a different color in my submission.'],
    ['role' => 'teacher', 'text' => 'Good. Next time, label which parts are AI suggestions and which are your own analysis.'],
    ['role' => 'student', 'text' => 'I will add a short "Sources of ideas" section at the end.'],
];

function pickTemplate(string $preview): string
{
    $p = mb_strtolower($preview, 'UTF-8');
    if (str_contains($p, 'lão') || str_contains($p, 'lao hac') || str_contains($p, 'old hac') || str_contains($p, 'nam cao')) {
        return 'lao_hac';
    }
    if (str_contains($p, 'chân quê') || str_contains($p, 'chan que') || str_contains($p, 'nguyễn bính') || str_contains($p, 'homeland')) {
        return 'chan_que';
    }
    if (str_contains($p, 'nhân vật') || str_contains($p, 'character')) {
        return 'character';
    }
    if (str_contains($p, 'hình ảnh') || str_contains($p, 'imagery') || str_contains($p, 'symbol') || str_contains($p, 'motif')) {
        return 'imagery';
    }
    return 'general';
}

function resolveUserId(string $role, int $studentId, int $teacherId, int $altTeacherId, int $msgIndex): int
{
    if ($role === 'teacher') {
        return ($msgIndex % 4 === 0) ? $altTeacherId : $teacherId;
    }
    return $studentId;
}

try {
    $pdo->beginTransaction();

    // Ensure hs1 deliver for Lão Hạc is viewable by teacher
    $pdo->exec("UPDATE deliver SET status = 2 WHERE id_deliver = 8 AND response = 28");

    $blocksStmt = $pdo->query("
        SELECT c.content_id, c.response_id, c.content, r.task_id, r.user_id AS student_id,
               t.title AS task_title, mp.Comment AS allow_comment
        FROM content c
        INNER JOIN response r ON c.response_id = r.id_response
        INNER JOIN task t ON r.task_id = t.task_id
        INNER JOIN phase p ON t.phase_id = p.phase_id
        INNER JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
        WHERE mp.Comment = 1
          AND (c.type_create IS NULL OR c.type_create <> 'note')
          AND r.id_response IN (
              SELECT DISTINCT d.response FROM deliver d WHERE d.response IS NOT NULL
          )
        ORDER BY r.id_response, c.`order` ASC
    ");
    $blocks = $blocksStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($blocks)) {
        throw new RuntimeException('No commentable content blocks found.');
    }

    // Clear previous demo comments on targeted responses (optional clean re-seed)
    $responseIds = array_values(array_unique(array_column($blocks, 'response_id')));
    $placeholders = implode(',', array_fill(0, count($responseIds), '?'));
    $contentIdStmt = $pdo->prepare("SELECT content_id FROM content WHERE response_id IN ($placeholders)");
    $contentIdStmt->execute($responseIds);
    $contentIds = $contentIdStmt->fetchAll(PDO::FETCH_COLUMN);

    if ($contentIds) {
        $cPh = implode(',', array_fill(0, count($contentIds), '?'));
        $pdo->prepare("DELETE FROM comment WHERE content_id IN ($cPh)")->execute($contentIds);
    }
    $pdo->prepare("DELETE FROM comment WHERE reponse_general IN ($placeholders)")->execute($responseIds);

    $inserted = 0;
    $baseTime = strtotime('-5 days');
    $timeOffset = 0;
    $generalDone = [];

    // Seed per content block — every block gets a thread; priority responses get fuller threads
    foreach ($blocks as $idx => $block) {
        $contentId = (int)$block['content_id'];
        $taskId = (int)$block['task_id'];
        $studentId = (int)$block['student_id'];
        $responseId = (int)$block['response_id'];
        $preview = (string)$block['content'];

        $templateKey = pickTemplate($preview);
        $messages = $threadTemplates[$templateKey];

        // Richer threads for primary demo responses (28 = hs1 Lão Hạc, 14 = Chân quê)
        if (!in_array($responseId, [28, 14, 16, 15], true) && $idx % 2 === 1) {
            $messages = array_slice($messages, 0, 3);
        }

        $lastCommentId = null;
        $rootId = null;
        foreach ($messages as $mIdx => $msg) {
            $userId = resolveUserId($msg['role'], $studentId, $primaryTeacher, $secondaryTeacher, $mIdx);
            $createdAt = date('Y-m-d H:i:s', $baseTime + ($timeOffset * 180));
            $timeOffset++;

            if ($mIdx === 0) {
                $replyTo = null;
            } elseif ($mIdx <= 2) {
                $replyTo = $lastCommentId;
            } else {
                $replyTo = ($mIdx % 2 === 1) ? $rootId : $lastCommentId;
            }

            $commentId = insertComment(
                $contentId,
                $userId,
                $taskId,
                $msg['text'],
                $replyTo,
                null
            );

            $pdo->prepare('UPDATE comment SET created_at = :t WHERE comment_id = :id')
                ->execute([':t' => $createdAt, ':id' => $commentId]);

            if ($mIdx === 0) {
                $rootId = $commentId;
            }
            $lastCommentId = $commentId;
            $inserted++;
        }

        if ($idx % 3 === 0 && $rootId) {
            $nestedText = ($idx % 2 === 0)
                ? 'I re-read your feedback and tightened the opening paragraph.'
                : 'This revision reads better — keep this voice in the remaining sections.';
            $nestedUser = ($idx % 2 === 0) ? $studentId : $primaryTeacher;
            $nestedId = insertComment($contentId, $nestedUser, $taskId, $nestedText, $rootId, null);
            $pdo->prepare('UPDATE comment SET created_at = :t WHERE comment_id = :id')
                ->execute([':t' => date('Y-m-d H:i:s', $baseTime + ($timeOffset * 180)), ':id' => $nestedId]);
            $timeOffset++;
            $inserted++;
        }
    }

    // General comments per response
    foreach ($responseIds as $rIdx => $responseId) {
        if (isset($generalDone[$responseId])) {
            continue;
        }
        $generalDone[$responseId] = true;

        $meta = $pdo->prepare('SELECT r.task_id, r.user_id FROM response r WHERE r.id_response = :id LIMIT 1');
        $meta->execute([':id' => $responseId]);
        $row = $meta->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            continue;
        }

        $taskId = (int)$row['task_id'];
        $studentId = (int)$row['user_id'];
        $lastGeneralId = null;
        $generalRootId = null;
        foreach ($generalComments as $gIdx => $msg) {
            if (!in_array($responseId, [28, 14, 16, 15], true) && $gIdx > 2) {
                break;
            }
            $userId = resolveUserId($msg['role'], $studentId, $primaryTeacher, $secondaryTeacher, $gIdx);
            $createdAt = date('Y-m-d H:i:s', $baseTime + ($timeOffset * 240));
            $timeOffset++;

            if ($gIdx === 0) {
                $replyTo = null;
            } elseif ($gIdx <= 2) {
                $replyTo = $lastGeneralId;
            } else {
                $replyTo = ($gIdx % 2 === 1) ? $generalRootId : $lastGeneralId;
            }

            $commentId = insertComment(null, $userId, $taskId, $msg['text'], $replyTo, $responseId);
            $pdo->prepare('UPDATE comment SET created_at = :t WHERE comment_id = :id')
                ->execute([':t' => $createdAt, ':id' => $commentId]);

            if ($gIdx === 0) {
                $generalRootId = $commentId;
            }
            $lastGeneralId = $commentId;
            $inserted++;
        }
    }

    $pdo->commit();

    $total = (int)$pdo->query('SELECT COUNT(*) FROM comment')->fetchColumn();
    echo "Seeded {$inserted} comments. Total in DB: {$total}\n\n";
    echo "Demo pages (open as teacher gv1 or student hs1):\n";

    $demoStmt = $pdo->query("
        SELECT d.id_deliver, d.task_id, d.response, p.topic_id, p.phase_id, t.title, u.username
        FROM deliver d
        JOIN response r ON d.response = r.id_response
        JOIN task t ON d.task_id = t.task_id
        JOIN phase p ON t.phase_id = p.phase_id
        JOIN users u ON r.user_id = u.user_id
        WHERE d.response IN (28, 14, 16, 15)
        ORDER BY d.id_deliver
    ");
    foreach ($demoStmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
        echo sprintf(
            "  %s — %s: /class/homework/comment?task_id=%d&deliver_id=%d&topic_id=%d&phase_id=%d\n",
            $d['username'],
            $d['title'],
            $d['task_id'],
            $d['id_deliver'],
            $d['topic_id'],
            $d['phase_id']
        );
    }
    echo "\nRe-run anytime: php seed_comments.php\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}
