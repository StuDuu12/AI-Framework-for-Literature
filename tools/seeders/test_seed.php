<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); die('CLI only'); }
require_once __DIR__ . '/../../config/db.php';
$stmt = $pdo->query("SELECT d.id_deliver, d.task_id, d.type, d.value, d.status as d_status, de.response_review, r.user_id as r_user FROM deliver d JOIN deliver_evaluate de ON d.id_deliver = de.id_deliver JOIN response r ON de.response_review = r.id_response ORDER BY d.id_deliver DESC LIMIT 10");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data, JSON_PRETTY_PRINT);
?>


