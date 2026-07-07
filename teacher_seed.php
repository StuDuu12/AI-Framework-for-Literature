<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); die('CLI only'); }
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helper_task.php';

try {
    $pdo->beginTransaction();

    // 1. Fetch Phase 3 tasks
    $stmt = $pdo->prepare("
        SELECT t.task_id, tp.class_id, p.topic_id 
        FROM task t 
        JOIN phase p ON t.phase_id = p.phase_id 
        JOIN main_phase mp ON p.m_phase_id = mp.m_phase_id
        JOIN topic tp ON p.topic_id = tp.topic_id
        WHERE mp.phase_code = 'PHASE_DANHGIANGUOIHOC' 
        ORDER BY t.task_id DESC
    ");
    $stmt->execute();
    $task3List = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($task3List)) {
        throw new Exception("Không tìm thấy bài tập Phase 3 nào. Hãy vào lớp tạo bài tập đánh giá chéo trước.");
    }
    
    $totalSeeded = 0;

    foreach ($task3List as $task3) {
        $taskId = $task3['task_id'];
        $classId = $task3['class_id'];
        
        // Find Phase 1 responses
        // First find previous phase task
        $sourceTaskStmt = $pdo->prepare("
            SELECT t.task_id
            FROM task t
            INNER JOIN phase p ON t.phase_id = p.phase_id
            WHERE p.topic_id = :topic_id
            AND p.phase_number < (SELECT phase_number FROM phase WHERE p.topic_id = :topic_id2 AND phase_id = (SELECT phase_id FROM task WHERE task_id = :task_id))
            ORDER BY p.phase_number DESC
            LIMIT 1
        ");
        $sourceTaskStmt->execute([
            ':topic_id' => $task3['topic_id'],
            ':topic_id2' => $task3['topic_id'],
            ':task_id' => $taskId
        ]);
        $sourceTask = $sourceTaskStmt->fetchColumn();
        
        if ($sourceTask) {
            $respStmt = $pdo->prepare("
                SELECT r.id_response, r.user_id 
                FROM response r
                WHERE r.task_id = :task_id AND r.parent_response IS NULL
                LIMIT 20
            ");
            $respStmt->execute([':task_id' => $sourceTask]);
            $responses = $respStmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $responses = [];
        }
        
        // Aggressive fallback: if still no responses, grab ANY response in the same class
        if (empty($responses)) {
            $respFallback = $pdo->prepare("
                SELECT r.id_response, r.user_id 
                FROM response r
                JOIN task t ON r.task_id = t.task_id
                JOIN phase p ON t.phase_id = p.phase_id
                JOIN topic tp ON p.topic_id = tp.topic_id
                WHERE tp.class_id = :class_id AND r.parent_response IS NULL
                LIMIT 20
            ");
            $respFallback->execute([':class_id' => $classId]);
            $responses = $respFallback->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if (empty($responses)) {
            echo "Skipping Task $taskId: No Phase 1 responses found to evaluate.<br>";
            continue;
        }

        // Get 8 random students in class
        $stuStmt = $pdo->prepare("
            SELECT u.user_id 
            FROM class_member cm
            JOIN users u ON cm.user_id = u.user_id
            WHERE cm.class_id = :class_id AND cm.role = 'student'
            LIMIT 15
        ");
        $stuStmt->execute([':class_id' => $classId]);
        $students = $stuStmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($students)) {
            // Failsafe, create 8 dummy users if not enough students
            for ($i = 1; $i <= 8; $i++) {
                $dummyUsername = 'student_dummy_' . time() . '_' . $i;
                $pdo->exec("INSERT INTO users (username, password, fullname, role, created_at) VALUES ('$dummyUsername', '123', 'Học sinh mẫu $i', 'student', NOW())");
                $dummyId = $pdo->lastInsertId();
                $pdo->exec("INSERT INTO class_member (class_id, user_id, role, joined_at) VALUES ($classId, $dummyId, 'student', NOW())");
                $students[] = $dummyId;
            }
        }

        // Assign exactly 8 random assignments and complete them
        $count = 0;
        foreach ($students as $stuId) {
            if ($count >= 8) break;
            
            // Pick a random response
            $randResp = $responses[array_rand($responses)];
            if ($randResp['user_id'] == $stuId) {
                // don't review own response, try next
                continue;
            }
            $responseId = $randResp['id_response'];

            // Check if already assigned
            $checkDel = $pdo->prepare("
                SELECT d.id_deliver, e.id_evaluate 
                FROM deliver d
                JOIN deliver_evaluate de ON d.id_deliver = de.id_deliver
                LEFT JOIN evaluate e ON d.id_deliver = e.deliver_id
                WHERE d.task_id = :task_id AND d.type = 'user' AND d.value = :user_id AND de.response_review = :response_id
            ");
            $checkDel->execute([
                ':task_id' => $taskId,
                ':user_id' => $stuId,
                ':response_id' => $responseId
            ]);
            
            $existing = $checkDel->fetch(PDO::FETCH_ASSOC);
            $deliverId = false;

            if (!$existing) {
                // Create deliver
                $insDel = $pdo->prepare("
                    INSERT INTO deliver (task_id, type, value, status, created_at, added_by)
                    VALUES (:task_id, 'user', :user_id, 2, NOW(), :added_by)
                ");
                // assuming teacher's ID is the first teacher in class
                $tchStmt = $pdo->query("SELECT user_id FROM class_member WHERE class_id = $classId AND role = 'teacher' LIMIT 1");
                $tchId = $tchStmt->fetchColumn() ?: 1;
                
                $insDel->execute([
                    ':task_id' => $taskId,
                    ':user_id' => $stuId,
                    ':added_by' => $tchId
                ]);
                $deliverId = $pdo->lastInsertId();

                // Create deliver_evaluate
                $insDe = $pdo->prepare("INSERT INTO deliver_evaluate (id_deliver, response_review) VALUES (:did, :rid)");
                $insDe->execute([':did' => $deliverId, ':rid' => $responseId]);
            } else {
                $deliverId = $existing['id_deliver'];
                if ($existing['id_evaluate']) {
                    // already evaluated
                    continue; 
                }
            }

            // Create evaluate with random score
            $randomScore = rand(50, 100) / 10;
            
            $insEval = $pdo->prepare("
                INSERT INTO evaluate (deliver_id, user_id, response_id, status, total_score, is_teacher)
                VALUES (:deliver_id, :user_id, :response_id, 2, :total_score, 0)
            ");
            $insEval->execute([
                ':deliver_id' => $deliverId,
                ':user_id' => $stuId,
                ':response_id' => $responseId,
                ':total_score' => $randomScore
            ]);
            
            // update deliver status
            $pdo->exec("UPDATE deliver SET status = 2 WHERE id_deliver = $deliverId");

            $totalSeeded++;
            $count++;
        }
    }

    $pdo->commit();
    echo "<h3>Hoàn tất!</h3>";
    echo "Đã tạo <strong>$totalSeeded</strong> dữ liệu học sinh chấm bài có điểm số.<br>";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    file_put_contents('teacher_seed_error.txt', "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    echo "Error check teacher_seed_error.txt";
}
