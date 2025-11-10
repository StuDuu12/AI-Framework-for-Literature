<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/env.php';
require_once 'config/db.php';

$route = isset($_GET['route']) ? $_GET['route'] : '';
$route = trim($route, '/');

$routes = [
    // Default route
    '' => ['file' => 'views/Landing/landing_page.php', 'title' => 'Trang chủ', 'require_auth' => false],

    'templates' => ['file' => 'views/tmp/index.php', 'require_auth' => true],

    'landing' => ['file' => 'views/Landing/landing_page.html', 'require_auth' => false],
    // Đăng nhập đăng xuất
    'login' => ['file' => 'views/account/login.php', 'title' => 'Đăng nhập', 'require_auth' => false],
    'logout' => ['file' => 'views/account/logout.php', 'title' => 'Đăng ký', 'require_auth' => false],

    'dashboard' => ['file' => 'views/index.php', 'title' => 'Dashboard', 'require_auth' => true],
    'joinclass' => ['file' => 'api/joinclass.php', 'require_auth' => true],

    'class' => ['file' => 'views/homeclass.php', 'title' => 'Trang lớp học', 'require_auth' => true],

    'class/participants' => ['file' => 'views/participants_groups/participants.php', 'title' => 'Quản lý thành viên lớp học', 'require_auth' => true],
    'class/groups' => ['file' => 'views/participants_groups/groups.php', 'title' => 'Quản lý nhóm lớp học', 'require_auth' => true],
    'class/phases' => ['file' => 'views/phase/index.php', 'title' => 'Quản lý pha học tập', 'require_auth' => true],
    'class/phases/create' => ['file' => 'views/phase/create.php', 'title' => 'Tạo pha học tập', 'require_auth' => true],
    'class/phases/edit' => ['file' => 'views/phase/edit.php', 'title' => 'Chỉnh sửa pha học tập', 'require_auth' => true],
    'class/task' => ['file' => 'views/tasks/index.php', 'title' => 'Quản lý nhiệm vụ', 'require_auth' => true],
    'class/task/create' => ['file' => 'views/tasks/create.php', 'title' => 'Tạo nhiệm vụ', 'require_auth' => true],
    'class/task/edit' => ['file' => 'views/tasks/edit.php', 'title' => 'Chỉnh sửa nhiệm vụ', 'require_auth' => true],
    'class/task/assign' => ['file' => 'views/tasks/assign.php', 'title' => 'Phân công nhiệm vụ', 'require_auth' => true],
    'class/task/perform' => ['file' => 'views/tasks/perform.php', 'title' => 'Thực hiện nhiệm vụ', 'require_auth' => true],
    'class/task/view' => ['file' => 'views/tasks/view.php', 'title' => 'Xem nhiệm vụ', 'require_auth' => true],
    'class/task/review' => ['file' => 'views/tasks/review_submissions.php', 'title' => 'Xem bài phân tích học sinh', 'require_auth' => true],
    'class/my_tasks' => ['file' => 'views/tasks/my_tasks.php', 'title' => 'Nhiệm vụ của tôi', 'require_auth' => true],
    'class/homework/ai' => ['file' => 'views/homework/literary_analysis2.php', 'title' => 'Trợ lý AI', 'require_auth' => true],
    'class/homework/analysis_start' => ['file' => 'views/homework/genAI.php', 'title' => 'Phân tích Văn học - Bắt đầu', 'require_auth' => true],
    'class/homework/genAI' => ['file' => 'views/homework/genAI.php', 'title' => 'Phân tích Văn học', 'require_auth' => true],
    'class/my_groups' => ['file' => 'views/participants_groups/my_groups.php', 'title' => 'Nhóm của tôi', 'require_auth' => true],
    'class/homework/analysis_chat' => ['file' => 'views/homework/genAI_BTVN.php', 'title' => 'Phân tích Văn học - Trò chuyện', 'require_auth' => true],
    'class/homework/chat' => ['file' => 'views/homework/genAI_BTVN.php', 'title' => 'Phân tích Văn học - Chat', 'require_auth' => true],
    'class/criteria' => ['file' => 'views/criteria/index.php', 'title' => 'Tiêu chí đánh giá', 'require_auth' => true],
    'class/task/setting_evaluation' => ['file' => 'views/evaluate/setting.php', 'title' => 'Cài đặt đánh giá nhiệm vụ', 'require_auth' => true],
    'class/evaluate/student' => ['file' => 'views/evaluate/student_evaluate.php', 'title' => 'Đánh giá chéo', 'require_auth' => true],
    'class/evaluate/list' => ['file' => 'views/evaluate/list.php', 'title' => 'Danh sách đánh giá', 'require_auth' => true],

    // Reflect (Support Ticket) routes
    'reflect' => ['file' => 'views/reflect/index.php', 'title' => 'Quản lý Reflect', 'require_auth' => true],
    'reflect/detail' => ['file' => 'views/reflect/detail.php', 'title' => 'Chi tiết Reflect', 'require_auth' => true],

    // Gen AI and Comment routes
    'class/homework/view' => ['file' => 'views/homework/view.php', 'title' => 'Xem chi tiết phân tích', 'require_auth' => true],
    'class/homework/view_analysis' => ['file' => 'views/homework/view.php', 'title' => 'Xem phân tích học sinh', 'require_auth' => true],
    'class/homework/comment' => [
        'file' => 'views/homework/comment.php',
        'title' => 'Bình luận phân tích',
        'require_auth' => true
    ],

    'literary_analysis' => ['file' => 'views/literary_analysis/literary_analysis.php', 'title' => 'Phân tích Văn học', 'require_auth' => true],

    // Error pages
    '400' => ['file' => 'views/errors/400.php', 'title' => '400 Bad Request', 'require_auth' => false],
    '403' => ['file' => 'views/errors/403.php', 'title' => '403 Forbidden', 'require_auth' => false],
    '404' => ['file' => 'views/errors/404.php', 'title' => '404 Not Found', 'require_auth' => false],
    '500' => ['file' => 'views/errors/500.php', 'title' => '500 Internal Server Error', 'require_auth' => false],
    '503' => ['file' => 'views/errors/503.php', 'title' => '503 Service Unavailable', 'require_auth' => false],

];

function checkAuth()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

$is_bao_tri = false;
if ($is_bao_tri) {
    $route = '503';
}

$routeConfig = null;
if (isset($routes[$route])) {
    $routeConfig = $routes[$route];
} else {
    header("Location: /404");
    exit;
}

if (isset($routeConfig['require_auth']) && $routeConfig['require_auth']) {
    if (!checkAuth()) {
        header('Location: ' . URL_ROOT . '/login');
        exit;
    }
}

$filePath = PATH_ROOT . '/' . $routeConfig['file'];

if (file_exists($filePath)) {
    if (isset($routeConfig['title'])) {
        define('PAGE_TITLE', $routeConfig['title']);
    }
    require_once $filePath;
} else {
    http_response_code(500);
    die("Lỗi: File không tồn tại - " . htmlspecialchars($routeConfig['file']));
}
