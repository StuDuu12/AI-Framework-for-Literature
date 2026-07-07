<?php 
$classid = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($classid <= 0) {
    header("Location: /404");
    exit;
}

$userId = $_SESSION['user']['user_id'] ?? ($_SESSION['user_id'] ?? 0);
$globalRole = $_SESSION['user']['role'] ?? ($_SESSION['role'] ?? '');

global $pdo;

// Chỉ cho phép vào lớp khi là thành viên của lớp (admin được phép xem mọi lớp)
if ($globalRole !== 'admin') {
    $memberStmt = $pdo->prepare("SELECT 1 FROM `class_member` WHERE class_id = :classid AND user_id = :userid");
    $memberStmt->execute([':classid' => $classid, ':userid' => $userId]);
    if (!$memberStmt->fetch()) {
        http_response_code(403);
        header("Location: /403");
        exit;
    }
}

$get_name_class = $pdo->prepare("SELECT * FROM `class` WHERE class_id = :classid");
$get_name_class->bindParam(':classid', $classid);
$get_name_class->execute();
$classInfo = $get_name_class->fetch(PDO::FETCH_ASSOC);

if (!$classInfo) {
    header("Location: /404");
    exit;
}

$_SESSION['current_class_id'] = $classid;
$_SESSION['current_class_name'] = $classInfo['class_name'];
unset($_SESSION['nav_topic_id'], $_SESSION['nav_phase_id']);

header("Location: /class");
exit;
