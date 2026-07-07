<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); die('CLI only'); }
require 'config/db.php';
// find all Phase 3 tasks
$stmt = $pdo->query("SELECT t.task_id, tp.class_id, p.topic_id 
        FROM task t 
        JOIN phase p ON t.phase_id = p.phase_id 
        JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
        JOIN topic tp ON p.topic_id = tp.topic_id
        WHERE mp.phase_code = 'PHASE_DANHGIANGUOIHOC'");
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($tasks as $task) {
    $taskId = $task['task_id'];
    $classId = $task['class_id'];
    
    // Get all users in the class
    $users = $pdo->query("SELECT user_id FROM class_member WHERE class_id = $classId")->fetchAll(PDO::FETCH_COLUMN);
    
    // fallback logic: get any response in class
    $resps = $pdo->query("SELECT r.id_response, r.user_id FROM response r JOIN task t ON r.task_id = t.task_id JOIN phase p ON t.phase_id = p.phase_id JOIN topic tp ON p.topic_id = tp.topic_id WHERE tp.class_id = $classId AND r.parent_response IS NULL LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($resps)) continue;
    
    foreach ($users as $uid) {
        $validResps = array_filter($resps, function($r) use ($uid) { return $r['user_id'] != $uid; });
        if (empty($validResps)) $validResps = $resps;
        $resp = $validResps[array_rand($validResps)];
        $respId = $resp['id_response'];
        
        // check if assigned
        $chk = $pdo->prepare("SELECT id_deliver FROM deliver WHERE task_id = ? AND type = 'user' AND value = ?");
        $chk->execute([$taskId, $uid]);
        if (!$chk->fetchColumn()) {
            $pdo->query("INSERT INTO deliver (task_id, type, value, status, created_at, added_by) VALUES ($taskId, 'user', $uid, 0, NOW(), 1)");
            $did = $pdo->lastInsertId();
            $pdo->query("INSERT INTO deliver_evaluate (id_deliver, response_review) VALUES ($did, $respId)");
        }
    }
}
echo "Assigned to everyone!";
