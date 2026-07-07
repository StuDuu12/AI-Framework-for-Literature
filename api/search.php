<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper_task.php';

$user_id = $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? 0;
$role = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? '';
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'results' => []]);
    exit;
}

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode(['success' => true, 'results' => []]);
    exit;
}

$like = '%' . $q . '%';
$results = [];

try {
    $stmt = $pdo->prepare("
        SELECT c.class_id, c.class_name, 'class' AS type
        FROM class_member cm
        JOIN class c ON c.class_id = cm.class_id
        WHERE cm.user_id = :uid AND c.class_name LIKE :q
        LIMIT 8
    ");
    $stmt->execute([':uid' => $user_id, ':q' => $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $results[] = [
            'type' => 'class',
            'label' => $row['class_name'],
            'url' => URL_ROOT . '/joinclass?id=' . $row['class_id'],
        ];
    }

    $stmt = $pdo->prepare("
        SELECT t.topic_id, t.topic_name, t.class_id, 'topic' AS type
        FROM topic t
        JOIN class_member cm ON cm.class_id = t.class_id AND cm.user_id = :uid
        WHERE t.topic_name LIKE :q
        LIMIT 8
    ");
    $stmt->execute([':uid' => $user_id, ':q' => $like]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $results[] = [
            'type' => 'topic',
            'label' => $row['topic_name'],
            'url' => URL_ROOT . '/class/phases?topic_id=' . $row['topic_id'],
        ];
    }

    if ($role === 'student') {
        $visibility = studentDeliverVisibilitySql('d');
        $stmt = $pdo->prepare("
            SELECT DISTINCT tk.task_id, tk.title AS task_title, p.topic_id, ph.phase_id, d.id_deliver AS deliver_id, 'task' AS type
            FROM deliver d
            JOIN task tk ON tk.task_id = d.task_id
            JOIN phase ph ON ph.phase_id = tk.phase_id
            JOIN topic p ON p.topic_id = ph.topic_id
            WHERE {$visibility} AND tk.title LIKE :q
            LIMIT 8
        ");
        $stmt->execute([':uid' => $user_id, ':q' => $like]);
    } else {
        $stmt = $pdo->prepare("
            SELECT tk.task_id, tk.title AS task_title, p.topic_id, ph.phase_id, 'task' AS type
            FROM task tk
            JOIN phase ph ON ph.phase_id = tk.phase_id
            JOIN topic p ON p.topic_id = ph.topic_id
            JOIN class_member cm ON cm.class_id = p.class_id AND cm.user_id = :uid
            WHERE tk.title LIKE :q
            LIMIT 8
        ");
        $stmt->execute([':uid' => $user_id, ':q' => $like]);
    }

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $topicId = (int)($row['topic_id'] ?? 0);
        $phaseId = (int)($row['phase_id'] ?? 0);
        $taskId = (int)$row['task_id'];

        if ($role === 'student' && !empty($row['deliver_id'])) {
            $url = URL_ROOT . '/class/task/perform?task_id=' . $taskId
                . '&topic_id=' . $topicId . '&phase_id=' . $phaseId
                . '&deliver_id=' . (int)$row['deliver_id'];
        } elseif ($role === 'teacher') {
            $url = URL_ROOT . '/class/task/view?task_id=' . $taskId
                . '&topic_id=' . $topicId . '&phase_id=' . $phaseId;
        } else {
            $url = URL_ROOT . '/class/my_tasks';
        }

        $results[] = [
            'type' => 'task',
            'label' => $row['task_title'],
            'url' => $url,
        ];
    }

    echo json_encode(['success' => true, 'results' => array_slice($results, 0, 15)]);
} catch (Exception $e) {
    error_log('search: ' . $e->getMessage());
    echo json_encode(['success' => true, 'results' => []]);
}
