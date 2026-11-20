<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

$userId = $_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user']['role'] ?? $_SESSION['role'] ?? null;
if (!$userId || $userRole !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập.']);
    exit();
}

try {
    // User stats
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $students = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
    $teachers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn();
    $admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

    // Pending registrations
    $pendingRegistrations = $pdo->query("SELECT COUNT(*) FROM registration_requests WHERE status = 'pending'")->fetchColumn();

    // Class stats
    $totalClasses = $pdo->query("SELECT COUNT(*) FROM class")->fetchColumn();
    $totalTopics = $pdo->query("SELECT COUNT(*) FROM topic")->fetchColumn();
    $totalPhases = $pdo->query("SELECT COUNT(*) FROM phase")->fetchColumn();

    // Task stats
    $totalTasks = $pdo->query("SELECT COUNT(*) FROM task")->fetchColumn();
    $totalDelivers = $pdo->query("SELECT COUNT(*) FROM deliver")->fetchColumn();
    $completedDelivers = $pdo->query("SELECT COUNT(*) FROM deliver WHERE status = 1")->fetchColumn();

    // Evaluation stats
    $totalEvaluations = $pdo->query("SELECT COUNT(*) FROM evaluate")->fetchColumn();
    $completedEvaluations = $pdo->query("SELECT COUNT(*) FROM evaluate WHERE status = 2")->fetchColumn();

    // Recent registrations (last 30 days)
    $stmtRecent = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM registration_requests 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $recentRegistrations = $stmtRecent->fetchAll(PDO::FETCH_ASSOC);

    // Task completion rate by class
    $stmtClassStats = $pdo->query("
        SELECT c.class_name,
               COUNT(DISTINCT t.task_id) as task_count,
               COUNT(d.id_deliver) as deliver_count,
               SUM(CASE WHEN d.status = 1 THEN 1 ELSE 0 END) as completed_count
        FROM class c
        LEFT JOIN topic tp ON c.class_id = tp.class_id
        LEFT JOIN phase p ON tp.topic_id = p.topic_id
        LEFT JOIN task t ON p.phase_id = t.phase_id
        LEFT JOIN deliver d ON t.task_id = d.task_id
        GROUP BY c.class_id, c.class_name
        ORDER BY task_count DESC
    ");
    $classStats = $stmtClassStats->fetchAll(PDO::FETCH_ASSOC);

    // Top teachers by task count
    $stmtTopTeachers = $pdo->query("
        SELECT u.fullname, COUNT(t.task_id) as task_count
        FROM users u
        INNER JOIN task t ON u.user_id = t.added_by
        WHERE u.role = 'teacher'
        GROUP BY u.user_id, u.fullname
        ORDER BY task_count DESC
        LIMIT 5
    ");
    $topTeachers = $stmtTopTeachers->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'stats' => [
            'users' => [
                'total' => (int)$totalUsers,
                'students' => (int)$students,
                'teachers' => (int)$teachers,
                'admins' => (int)$admins,
                'pending_registrations' => (int)$pendingRegistrations,
            ],
            'classes' => [
                'total' => (int)$totalClasses,
                'topics' => (int)$totalTopics,
                'phases' => (int)$totalPhases,
            ],
            'tasks' => [
                'total' => (int)$totalTasks,
                'delivers' => (int)$totalDelivers,
                'completed' => (int)$completedDelivers,
                'completion_rate' => $totalDelivers > 0 ? round(($completedDelivers / $totalDelivers) * 100, 1) : 0,
            ],
            'evaluations' => [
                'total' => (int)$totalEvaluations,
                'completed' => (int)$completedEvaluations,
            ],
            'recent_registrations' => $recentRegistrations,
            'class_stats' => $classStats,
            'top_teachers' => $topTeachers,
        ]
    ]);
} catch (PDOException $e) {
    error_log("admin_analytics error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu. Vui lòng thử lại.']);
}
