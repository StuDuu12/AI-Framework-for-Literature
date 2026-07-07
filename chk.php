<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); die('CLI only'); }
require 'config/db.php';
$stmt = $pdo->query("SELECT t.task_id, tp.class_id, p.topic_id FROM task t JOIN phase p ON t.phase_id = p.phase_id JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id JOIN topic tp ON p.topic_id = tp.topic_id WHERE mp.phase_code = 'PHASE_DANHGIANGUOIHOC'");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $classId = $row['class_id'];
    $rStmt = $pdo->query("SELECT count(*) FROM response r JOIN task tr ON r.task_id = tr.task_id JOIN phase pr ON tr.phase_id = pr.phase_id JOIN topic tpr ON pr.topic_id = tpr.topic_id WHERE tpr.class_id = $classId");
    echo "Task: " . $row['task_id'] . " | Class: " . $classId . " | Reponses in class: " . $rStmt->fetchColumn() . "\n";
}
