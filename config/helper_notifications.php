<?php
/**
 * User notifications helper
 */

function appLanguageFromRequest(): string
{
    $lang = $_GET['lang'] ?? ($_COOKIE['app_language'] ?? 'en');
    return in_array($lang, ['en', 'vi'], true) ? $lang : 'en';
}

function notificationStrings(string $lang): array
{
    if ($lang === 'vi') {
        return [
            'assignment_title' => 'Bài tập mới được giao',
            'assignment_message' => '%s giao bài "%s" — %s · %s',
            'teacher_fallback' => 'Giáo viên',
            'evaluate_pending_title' => 'Bạn có %d bài cần đánh giá',
            'evaluate_pending_message' => 'Hoàn thành đánh giá chéo để không bị trễ tiến độ.',
            'peer_eval_title' => 'Bạn được giao đánh giá chéo',
            'peer_eval_message' => 'Đánh giá bài nộp cho nhiệm vụ "%s" — %s',
            'task_fallback' => 'Nhiệm vụ',
        ];
    }

    return [
        'assignment_title' => 'New assignment',
        'assignment_message' => '%s assigned "%s" — %s · %s',
        'teacher_fallback' => 'Teacher',
        'evaluate_pending_title' => 'You have %d assignment(s) to evaluate',
        'evaluate_pending_message' => 'Complete peer evaluation so you do not fall behind.',
        'peer_eval_title' => 'Peer evaluation assigned',
        'peer_eval_message' => 'Review a submission for "%s" — %s',
        'task_fallback' => 'Task',
    ];
}

function encodeNotificationPayload(array $data): string
{
    return json_encode($data, JSON_UNESCAPED_UNICODE);
}

function decodeAssignmentPayload(string $message): ?array
{
    $decoded = json_decode($message, true);
    if (is_array($decoded) && !empty($decoded['task'])) {
        return [
            'teacher' => $decoded['teacher'] ?? '',
            'task' => $decoded['task'] ?? '',
            'phase' => $decoded['phase'] ?? '',
            'topic' => $decoded['topic'] ?? '',
        ];
    }

    if (preg_match('/^(.+?)\s+giao bài\s+"(.+?)"\s+—\s+(.+?)\s+·\s+(.+)$/u', $message, $m)) {
        return ['teacher' => $m[1], 'task' => $m[2], 'phase' => $m[3], 'topic' => $m[4]];
    }

    if (preg_match('/^(.+?)\s+assigned\s+"(.+?)"\s+—\s+(.+?)\s+·\s+(.+)$/u', $message, $m)) {
        return ['teacher' => $m[1], 'task' => $m[2], 'phase' => $m[3], 'topic' => $m[4]];
    }

    return null;
}

function decodePeerEvalPayload(string $message): ?array
{
    $decoded = json_decode($message, true);
    if (is_array($decoded) && !empty($decoded['task'])) {
        return [
            'task' => $decoded['task'] ?? '',
            'phase' => $decoded['phase'] ?? '',
        ];
    }

    if (preg_match('/^Đánh giá bài nộp cho nhiệm vụ "(.+?)" — (.+)$/u', $message, $m)) {
        return ['task' => $m[1], 'phase' => $m[2]];
    }

    if (preg_match('/^Review a submission for "(.+?)" — (.+)$/u', $message, $m)) {
        return ['task' => $m[1], 'phase' => $m[2]];
    }

    return null;
}

function formatAssignmentNotification(array $payload, string $lang): array
{
    $s = notificationStrings($lang);
    $teacher = $payload['teacher'] ?: $s['teacher_fallback'];
    return [
        'title' => $s['assignment_title'],
        'message' => sprintf(
            $s['assignment_message'],
            $teacher,
            $payload['task'] ?? $s['task_fallback'],
            $payload['phase'] ?? '',
            $payload['topic'] ?? ''
        ),
    ];
}

function formatPeerEvalNotification(array $payload, string $lang): array
{
    $s = notificationStrings($lang);
    return [
        'title' => $s['peer_eval_title'],
        'message' => sprintf(
            $s['peer_eval_message'],
            $payload['task'] ?? $s['task_fallback'],
            $payload['phase'] ?? ''
        ),
    ];
}

function localizeNotification(array $row, string $lang): array
{
    $type = $row['type'] ?? 'info';
    $message = (string)($row['message'] ?? '');

    if ($type === 'assignment') {
        $payload = decodeAssignmentPayload($message);
        if ($payload) {
            $texts = formatAssignmentNotification($payload, $lang);
            $row['title'] = $texts['title'];
            $row['message'] = $texts['message'];
            return $row;
        }
    }

    if ($type === 'evaluate' && empty($row['dynamic'])) {
        $payload = decodePeerEvalPayload($message);
        if ($payload) {
            $texts = formatPeerEvalNotification($payload, $lang);
            $row['title'] = $texts['title'];
            $row['message'] = $texts['message'];
            return $row;
        }
    }

    // Legacy Vietnamese titles when EN is requested
    if ($lang === 'en') {
        $viToEnTitles = [
            'Bài tập mới được giao' => 'New assignment',
            'Bạn được giao đánh giá chéo' => 'Peer evaluation assigned',
        ];
        if (isset($viToEnTitles[$row['title'] ?? ''])) {
            $row['title'] = $viToEnTitles[$row['title']];
        }
    }

    return $row;
}

function localizeNotificationsList(array $rows, string $lang): array
{
    return array_map(static fn($row) => localizeNotification($row, $lang), $rows);
}

/** Upgrade plain-text notification rows to JSON payloads (one-time per row). */
function upgradeLegacyNotifications(PDO $pdo, int $userId): void
{
    try {
        $stmt = $pdo->prepare("
            SELECT id, type, message FROM user_notification
            WHERE user_id = :uid AND type IN ('assignment', 'evaluate')
        ");
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $update = $pdo->prepare('UPDATE user_notification SET message = :msg WHERE id = :id AND user_id = :uid');

        foreach ($rows as $row) {
            $message = (string)$row['message'];
            if (json_decode($message, true) !== null) {
                continue;
            }

            if ($row['type'] === 'assignment') {
                $payload = decodeAssignmentPayload($message);
                if ($payload) {
                    $update->execute([
                        ':msg' => encodeNotificationPayload($payload),
                        ':id' => $row['id'],
                        ':uid' => $userId,
                    ]);
                }
            } elseif ($row['type'] === 'evaluate') {
                $payload = decodePeerEvalPayload($message);
                if ($payload) {
                    $update->execute([
                        ':msg' => encodeNotificationPayload($payload),
                        ':id' => $row['id'],
                        ':uid' => $userId,
                    ]);
                }
            }
        }
    } catch (PDOException $e) {
        error_log('upgradeLegacyNotifications: ' . $e->getMessage());
    }
}

function ensureNotificationsTable(PDO $pdo): bool
{
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `user_notification` (
              `id` int NOT NULL AUTO_INCREMENT,
              `user_id` int NOT NULL,
              `type` varchar(32) NOT NULL DEFAULT 'info',
              `title` varchar(255) NOT NULL,
              `message` text,
              `link` varchar(512) DEFAULT NULL,
              `is_read` tinyint NOT NULL DEFAULT '0',
              `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_notif_user` (`user_id`,`is_read`,`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        return true;
    } catch (PDOException $e) {
        error_log('ensureNotificationsTable: ' . $e->getMessage());
        return false;
    }
}

function createNotification(PDO $pdo, int $userId, string $title, string $message = '', string $link = '', string $type = 'info'): bool
{
    try {
        ensureNotificationsTable($pdo);
        $stmt = $pdo->prepare('
            INSERT INTO user_notification (user_id, type, title, message, link)
            VALUES (:uid, :type, :title, :msg, :link)
        ');
        return $stmt->execute([
            ':uid' => $userId,
            ':type' => $type,
            ':title' => $title,
            ':msg' => $message,
            ':link' => $link,
        ]);
    } catch (PDOException $e) {
        error_log('createNotification: ' . $e->getMessage());
        return false;
    }
}

function assignmentNotificationExists(PDO $pdo, int $userId, int $taskId): bool
{
    try {
        $stmt = $pdo->prepare("
            SELECT 1 FROM user_notification
            WHERE user_id = :uid AND type = 'assignment'
              AND link LIKE :pattern
            LIMIT 1
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':pattern' => '%task_id=' . $taskId . '%',
        ]);
        return (bool)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

function buildTaskPerformLink(int $taskId, int $topicId, int $phaseId): string
{
    return URL_ROOT . '/class/task/perform?task_id=' . $taskId
        . '&topic_id=' . $topicId
        . '&phase_id=' . $phaseId;
}

/**
 * Resolve student user IDs from assignment targets.
 * @param int $assignType 1=class, 2=group, 3=user
 */
function getAssignmentRecipientIds(PDO $pdo, int $assignType, array $values): array
{
    $ids = [];

    try {
        if ($assignType === 3) {
            foreach ($values as $value) {
                $uid = (int)$value;
                if ($uid > 0) {
                    $ids[] = $uid;
                }
            }
        } elseif ($assignType === 2) {
            $stmt = $pdo->prepare('SELECT user_id FROM group_member WHERE group_id = :gid');
            foreach ($values as $value) {
                $stmt->execute([':gid' => (int)$value]);
                foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $uid) {
                    $ids[] = (int)$uid;
                }
            }
        } elseif ($assignType === 1) {
            require_once __DIR__ . '/helper_task.php';
            foreach ($values as $value) {
                $students = getClassStudents((int)$value);
                foreach ($students as $student) {
                    $ids[] = (int)$student['user_id'];
                }
            }
        }
    } catch (PDOException $e) {
        error_log('getAssignmentRecipientIds: ' . $e->getMessage());
    }

    return array_values(array_unique(array_filter($ids)));
}

/**
 * Notify students when a teacher assigns a task.
 */
function notifyTaskAssignment(PDO $pdo, int $taskId, int $assignType, array $values, int $assignedByUserId): int
{
    require_once __DIR__ . '/helper_task.php';

    $task = getTaskDetails($taskId);
    if (!$task) {
        return 0;
    }

    $phaseId = (int)($task['phase_id'] ?? 0);
    $topicId = 0;
    if ($phaseId > 0) {
        $phaseStmt = $pdo->prepare('SELECT topic_id, phase_name FROM phase WHERE phase_id = :pid LIMIT 1');
        $phaseStmt->execute([':pid' => $phaseId]);
        $phaseRow = $phaseStmt->fetch(PDO::FETCH_ASSOC);
        if ($phaseRow) {
            $topicId = (int)$phaseRow['topic_id'];
            if (empty($task['phase_name'])) {
                $task['phase_name'] = $phaseRow['phase_name'];
            }
        }
    }
    if (!$topicId || !$phaseId) {
        return 0;
    }

    $taskTitle = $task['title'] ?? 'Nhiệm vụ mới';
    $phaseName = $task['phase_name'] ?? '';
    $topicName = $task['topic_name'] ?? '';
    $teacherName = $task['added_by_name'] ?? 'Giáo viên';
    $link = buildTaskPerformLink($taskId, $topicId, $phaseId);

    $recipientIds = getAssignmentRecipientIds($pdo, $assignType, $values);
    $created = 0;

    foreach ($recipientIds as $userId) {
        if ($userId === $assignedByUserId) {
            continue;
        }
        if (assignmentNotificationExists($pdo, $userId, $taskId)) {
            continue;
        }

        $title = 'New assignment';
        $message = encodeNotificationPayload([
            'teacher' => $teacherName,
            'task' => $taskTitle,
            'phase' => $phaseName,
            'topic' => $topicName,
        ]);

        if (createNotification($pdo, $userId, $title, $message, $link, 'assignment')) {
            $created++;
        }
    }

    return $created;
}

/**
 * Backfill notifications for existing assignments not yet stored.
 */
function syncAssignmentNotifications(PDO $pdo, int $userId): int
{
    ensureNotificationsTable($pdo);
    $created = 0;

    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                t.task_id,
                t.title AS task_title,
                p.phase_id,
                p.phase_name,
                top.topic_id,
                top.topic_name,
                d.created_at,
                teacher.fullname AS teacher_name
            FROM deliver d
            INNER JOIN task t ON d.task_id = t.task_id
            INNER JOIN phase p ON t.phase_id = p.phase_id
            INNER JOIN topic top ON p.topic_id = top.topic_id
            LEFT JOIN users teacher ON d.added_by = teacher.user_id
            WHERE (
                (d.type = 'user' AND d.value = :uid)
                OR (d.type = 'group' AND EXISTS (
                    SELECT 1 FROM group_member gm
                    WHERE gm.group_id = d.value AND gm.user_id = :uid
                ))
                OR (d.type = 'class' AND EXISTS (
                    SELECT 1 FROM class_member cm
                    WHERE cm.class_id = d.value AND cm.user_id = :uid AND cm.role = 'student'
                ))
            )
            ORDER BY d.created_at DESC
            LIMIT 30
        ");
        $stmt->execute([':uid' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $taskId = (int)$row['task_id'];
            if (assignmentNotificationExists($pdo, $userId, $taskId)) {
                continue;
            }

            $link = buildTaskPerformLink(
                $taskId,
                (int)$row['topic_id'],
                (int)$row['phase_id']
            );

            $title = 'New assignment';
            $message = encodeNotificationPayload([
                'teacher' => $row['teacher_name'] ?? 'Teacher',
                'task' => $row['task_title'],
                'phase' => $row['phase_name'],
                'topic' => $row['topic_name'],
            ]);

            if (createNotification($pdo, $userId, $title, $message, $link, 'assignment')) {
                $created++;
            }
        }
    } catch (PDOException $e) {
        error_log('syncAssignmentNotifications: ' . $e->getMessage());
    }

    return $created;
}

function getUnreadNotificationCount(PDO $pdo, int $userId): int
{
    try {
        ensureNotificationsTable($pdo);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM user_notification WHERE user_id = :uid AND is_read = 0');
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function getNotifications(PDO $pdo, int $userId, int $limit = 20): array
{
    try {
        ensureNotificationsTable($pdo);
        $stmt = $pdo->prepare('
            SELECT id, type, title, message, link, is_read, created_at
            FROM user_notification
            WHERE user_id = :uid
            ORDER BY created_at DESC
            LIMIT :lim
        ');
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

function markNotificationRead(PDO $pdo, int $userId, int $notificationId): bool
{
    $stmt = $pdo->prepare('UPDATE user_notification SET is_read = 1 WHERE id = :id AND user_id = :uid');
    return $stmt->execute([':id' => $notificationId, ':uid' => $userId]);
}

function markAllNotificationsRead(PDO $pdo, int $userId): bool
{
    $stmt = $pdo->prepare('UPDATE user_notification SET is_read = 1 WHERE user_id = :uid AND is_read = 0');
    return $stmt->execute([':uid' => $userId]);
}

/** Generate dynamic alerts for pending evaluations (not stored in DB) */
function getPendingEvaluationAlerts(PDO $pdo, int $userId, string $lang = 'en'): array
{
    $alerts = [];
    $s = notificationStrings($lang);
    try {
        $stmt = $pdo->prepare('
            SELECT COUNT(*) AS cnt FROM evaluate
            WHERE user_id = :uid AND status < 2
        ');
        $stmt->execute([':uid' => $userId]);
        $cnt = (int)$stmt->fetchColumn();
        if ($cnt > 0) {
            $alerts[] = [
                'id' => 'pending-eval',
                'type' => 'evaluate',
                'title' => sprintf($s['evaluate_pending_title'], $cnt),
                'message' => $s['evaluate_pending_message'],
                'link' => URL_ROOT . '/class/evaluate/list',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'dynamic' => true,
            ];
        }
    } catch (PDOException $e) {
        // ignore
    }
    return $alerts;
}

/**
 * Notify evaluator(s) when assigned to peer-review a submission.
 */
function notifyPeerEvaluationAssignment(PDO $pdo, int $taskId, string $evaluatorType, int $evaluatorValue, int $assignedBy): void
{
    require_once __DIR__ . '/helper_task.php';

    $task = getTaskDetails($taskId);
    if (!$task) {
        return;
    }

    $phaseId = (int)($task['phase_id'] ?? 0);
    $phaseStmt = $pdo->prepare('SELECT topic_id, phase_name FROM phase WHERE phase_id = :pid LIMIT 1');
    $phaseStmt->execute([':pid' => $phaseId]);
    $phaseRow = $phaseStmt->fetch(PDO::FETCH_ASSOC);
    $topicId = (int)($phaseRow['topic_id'] ?? 0);
    if (!$topicId || !$phaseId) {
        return;
    }

    $typeMap = ['user' => 3, 'group' => 2];
    $assignType = $typeMap[$evaluatorType] ?? 3;
    $recipientIds = getAssignmentRecipientIds($pdo, $assignType, [$evaluatorValue]);
    $link = URL_ROOT . '/class/evaluate/list';
    $title = 'Peer evaluation assigned';
    $message = encodeNotificationPayload([
        'task' => $task['title'] ?? 'Task',
        'phase' => $phaseRow['phase_name'] ?? '',
    ]);

    foreach ($recipientIds as $userId) {
        if ($userId === $assignedBy) {
            continue;
        }
        createNotification($pdo, $userId, $title, $message, $link, 'evaluate');
    }
}

function notificationTypeIcon(string $type): string
{
    return match ($type) {
        'assignment' => 'bi-journal-plus',
        'task' => 'bi-clipboard-check',
        'evaluate' => 'bi-star',
        'warning' => 'bi-exclamation-triangle',
        'success' => 'bi-check-circle',
        default => 'bi-bell',
    };
}

function notificationTypeColor(string $type): string
{
    return match ($type) {
        'assignment' => 'notif-icon--assignment',
        'evaluate' => 'notif-icon--evaluate',
        'warning' => 'notif-icon--warning',
        'success' => 'notif-icon--success',
        default => 'notif-icon--info',
    };
}

/**
 * Get deadline reminder alerts for tasks assigned to the user that are due within 24 hours.
 */
function getDeadlineReminderAlerts(PDO $pdo, int $userId, string $lang = 'en'): array
{
    $alerts = [];
    try {
        $stmt = $pdo->prepare('
            SELECT t.task_id, t.title, t.deadline, d.id_deliver
            FROM task t
            INNER JOIN deliver d ON t.task_id = d.task_id
            WHERE t.deadline IS NOT NULL
              AND t.deadline > NOW()
              AND t.deadline <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
              AND (
                  (d.type = "user" AND d.value = :uid)
                  OR (d.type = "group" AND d.value IN (
                      SELECT group_id FROM group_member WHERE user_id = :uid2
                  ))
              )
              AND d.status = 0
            ORDER BY t.deadline ASC
        ');
        $stmt->execute([':uid' => $userId, ':uid2' => $userId]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tasks as $t) {
            $hoursLeft = round((strtotime($t['deadline']) - time()) / 3600, 1);
            $timeLabel = $hoursLeft < 1
                ? round($hoursLeft * 60) . ' phút'
                : round($hoursLeft) . ' giờ';

            $alerts[] = [
                'id' => 'deadline-' . $t['task_id'],
                'type' => 'warning',
                'title' => 'Sắp hết hạn: ' . $t['title'],
                'message' => "Còn {$timeLabel} nữa là hết hạn. Hãy nộp bài ngay!",
                'link' => URL_ROOT . '/class/task/perform?task_id=' . $t['task_id'] . '&deliver_id=' . $t['id_deliver'],
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'dynamic' => true,
            ];
        }
    } catch (PDOException $e) {
        // ignore
    }
    return $alerts;
}
