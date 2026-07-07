<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); die('CLI only'); }
require_once __DIR__ . '/../../config/db.php';
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
}

$user_id = $_SESSION['user']['user_id'] ?? null;
if (!$user_id && php_sapi_name() === 'cli') {
    // If run from CLI, find the first student in the class
    $stmt = $pdo->query("SELECT user_id FROM users WHERE role = 'student' LIMIT 1");
    $user_id = $stmt->fetchColumn();
    // But wait, the user is likely 'teacher' or a specific student. Let's just pick the user who is viewing.
    // Let's just pick user_id = 2 as a fallback, or query all users and assign it to every student in the class.
}

if (!$user_id) {
    die("Please log in first to generate sample data for your user.");
}

echo "<h2>Debug Tracker: Seed Evaluations</h2>";
echo "Your User ID: $user_id<br>";

try {
    $pdo->beginTransaction(); // Start transaction here

    // 1. Find Phase 3 tasks
    $stmt = $pdo->prepare("
        SELECT t.task_id, p.topic_id 
        FROM task t 
        JOIN phase p ON t.phase_id = p.phase_id 
        JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
        WHERE mp.phase_code = 'PHASE_DANHGIANGUOIHOC' 
        ORDER BY t.task_id DESC 
    ");
    $stmt->execute();
    $task3List = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($task3List)) {
        throw new Exception("No Phase 3 task found! Create a Peer Evaluation task inside the class first.");
    }
    
    $totalAssigned = 0;
    $totalAlready = 0;

    foreach ($task3List as $task3) {
        $taskId = $task3['task_id'];
        $topicId = $task3['topic_id'];
        echo "<hr><h3>Processing Phase 3 Task ID: $taskId, Topic ID: $topicId</h3>";

        // 2. Find Phase 1 responses from OTHER users
    $stmt = $pdo->prepare("
        SELECT r.id_response, r.user_id, u.fullname 
        FROM response r
        JOIN users u ON r.user_id = u.user_id
        JOIN task t ON r.task_id = t.task_id
        JOIN phase p ON t.phase_id = p.phase_id
        JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
        WHERE mp.phase_code = 'PHASE_START' AND r.user_id != :uid
        LIMIT 3
    ");
    $stmt->execute([':uid' => $user_id]);
    $responsesToEvaluate = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($responsesToEvaluate) . " existing Phase 1 responses from other users.<br>";

    if (empty($responsesToEvaluate)) {
        echo "Creating dummy Phase 1 responses...<br>";
        
        $stmt = $pdo->prepare("
            SELECT t.task_id 
            FROM task t 
            JOIN phase p ON t.phase_id = p.phase_id 
            JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id 
            WHERE p.topic_id = :topic_id AND mp.phase_code = 'PHASE_START' 
            LIMIT 1
        ");
        $stmt->execute([':topic_id' => $topicId]);
        $phase1_task_id = $stmt->fetchColumn();

        if (!$phase1_task_id) {
            throw new Exception("Could not find Phase 1 task in the same topic to attach dummy responses.");
        }
        echo "Found Phase 1 Task ID: $phase1_task_id<br>";

        $dummyNames = ['Nguyá»…n VÄƒn A', 'Tráº§n Thá»‹ B', 'LÃª VÄƒn C'];
        foreach ($dummyNames as $i => $name) {
            $dummyLabel = 'dummy' . time() . '_' . $i; // Ensure unique username
            
            // Check if dummy user exists (optional, but good for idempotency if not using time in username)
            // For this script, we're creating new ones each time if empty($responsesToEvaluate)
            $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, role) VALUES (:uname, '123456', :fname, 'student')");
            $stmt->execute([':uname' => $dummyLabel, ':fname' => $name]);
            $dummyId = $pdo->lastInsertId();
            echo "Created dummy user: $name (ID $dummyId)<br>";

            $stmt = $pdo->prepare("INSERT INTO response (user_id, task_id, created_at, status) VALUES (:uid, :tid, NOW(), 1)");
            $stmt->execute([':uid' => $dummyId, ':tid' => $phase1_task_id]);
            $responseId = $pdo->lastInsertId();
            echo "Created response ID: $responseId<br>";

            $sampleContent = "ÄÃ¢y lÃ  bÃ i phÃ¢n tÃ­ch máº«u cá»§a $name. TÃ¡c pháº©m mang giÃ¡ trá»‹ nhÃ¢n Ä‘áº¡o sÃ¢u sáº¯c, thiáº¿t thá»±c.";
            $stmt = $pdo->prepare("INSERT INTO content (response_id, content, `order`, type_create, added_by) VALUES (:rid, :content, 1, 'text', :uid)");
            $stmt->execute([':rid' => $responseId, ':content' => $sampleContent, ':uid' => $dummyId]);

            $responsesToEvaluate[] = [
                'id_response' => $responseId,
                'fullname' => $name
            ];
        }
    }

    $assignedCount = 0;
    $alreadyAssignedCount = 0;

    foreach ($responsesToEvaluate as $resp) {
        $response_id = $resp['id_response'];
        
        $stmt = $pdo->prepare("
            SELECT d.id_deliver 
            FROM deliver d
            JOIN deliver_evaluate de ON d.id_deliver = de.id_deliver
            WHERE d.task_id = :tid AND d.type = 'user' AND d.value = :uid AND de.response_review = :rid
        ");
        $stmt->execute([
            ':tid' => $taskId,
            ':uid' => $user_id,
            ':rid' => $response_id
        ]);

        if ($stmt->fetch()) {
            $alreadyAssignedCount++;
            echo "Response ID $response_id is ALREADY assigned to you.<br>";
        } else {
            // Assign it
            // status 0 means 'pending' or 'not yet evaluated'
            $stmt = $pdo->prepare("INSERT INTO deliver (task_id, type, value, status, created_at, added_by) VALUES (:tid, 'user', :uid, 0, NOW(), :uid)");
            $stmt->execute([
                ':tid' => $taskId,
                ':uid' => $user_id
            ]);
            $deliverId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO deliver_evaluate (id_deliver, response_review) VALUES (:did, :rid)");
            $stmt->execute([
                ':did' => $deliverId,
                ':rid' => $response_id
            ]);
            
            echo "Assigned Response ID $response_id to you (Deliver ID $deliverId).<br>";
            $assignedCount++;
        }
    }
    
    $totalAssigned += $assignedCount;
    $totalAlready += $alreadyAssignedCount;
    } // End foreach task3List

    $pdo->commit(); // Commit transaction here
    echo "<h3>HoÃ n táº¥t!</h3>";
    echo "ÄÃ£ phÃ¢n cÃ´ng má»›i: <strong>$totalAssigned</strong> bÃ i.<br>";
    echo "ÄÃ£ Ä‘Æ°á»£c phÃ¢n cÃ´ng tá»« trÆ°á»›c: <strong>$totalAlready</strong> bÃ i.<br>";
    echo "<br><a href='/class/evaluate/list?task_id=$taskId&topic_id=$topicId' style='padding:10px; background:#0d6efd; color:white; text-decoration:none; border-radius:5px;'>Äáº¿n trang danh sÃ¡ch Ä‘Ã¡nh giÃ¡</a>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Rollback on error
    }
    echo "<br><h3 style='color:red;'>Lá»—i há»‡ thá»‘ng:</h3>";
    echo $e->getMessage();
}
?>

