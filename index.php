<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/db.php';

$route = isset($_GET['route']) ? $_GET['route'] : '';
$route = trim($route, '/');

$routes = [
    // Default route
    '' => ['file' => 'views/Landing/landing_page.php', 'title' => 'Trang chủ', 'require_auth' => false],

    'templates' => ['file' => 'views/tmp/index.php', 'require_auth' => true],

    // Đăng nhập đăng xuất
    'login' => ['file' => 'views/account/login.php', 'title' => 'Đăng nhập', 'require_auth' => false],
    'logout' => ['file' => 'views/account/logout.php', 'title' => 'Đăng xuất', 'require_auth' => false],
    'register' => ['file' => 'views/account/register.php', 'title' => 'Đăng ký', 'require_auth' => false],
    'profile' => ['file' => 'views/account/profile.php', 'title' => 'Hồ sơ cá nhân', 'require_auth' => true],
    'forgot_password' => ['file' => 'views/account/forgot_password.php', 'title' => 'Quên mật khẩu', 'require_auth' => false],
    'reset_password' => ['file' => 'views/account/reset_password.php', 'title' => 'Đặt lại mật khẩu', 'require_auth' => false],

    'dashboard' => ['file' => 'views/dashboard.php', 'title' => 'Dashboard', 'require_auth' => true],
    'workspace' => ['file' => 'views/workspace/student_workspace.php', 'title' => 'Learning Workspace', 'require_auth' => true],
    'canon' => ['file' => 'views/canon/index.php', 'title' => 'Literary Canon', 'require_auth' => true],
    'canon/view' => ['file' => 'views/canon/view.php', 'title' => 'Literary Work', 'require_auth' => true],
    // 'admin' => ['file' => 'views/admin/dashboard.php', 'title' => 'Admin Dashboard', 'require_auth' => true],
    'joinclass' => ['file' => 'api/joinclass.php', 'require_auth' => true],
    'join' => ['file' => 'views/account/join_class.php', 'title' => 'Tham gia lớp học', 'require_auth' => true],

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
    'class/evaluate/student' => ['file' => 'views/evaluate/student_evaluate_new.php', 'title' => 'Đánh giá chéo', 'require_auth' => true],
    'class/evaluate/consensus' => ['file' => 'views/evaluate/consensus_evaluate.php', 'title' => 'Đánh giá đồng thuận nhóm', 'require_auth' => true],
    'class/evaluate/list' => ['file' => 'views/evaluate/list.php', 'title' => 'Danh sách đánh giá', 'require_auth' => true],
    'class/evaluate/teacher_grades' => ['file' => 'views/evaluate/teacher_grade_list.php', 'title' => 'Final grading', 'require_auth' => true],
    'class/evaluate/teacher_grade' => ['file' => 'views/evaluate/teacher_grade.php', 'title' => 'Teacher grade', 'require_auth' => true],

    // Gamification
    'leaderboard' => ['file' => 'views/gamification/leaderboard.php', 'title' => 'Bảng xếp hạng', 'require_auth' => true],

    // Reading List
    'reading-list' => ['file' => 'views/reading/index.php', 'title' => 'Danh sách đọc', 'require_auth' => true],

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

    // Admin (chỉ role admin truy cập được — enforced tại router)
    'admin/users' => ['file' => 'views/admin/manage_users.php', 'title' => 'Quản lý người dùng', 'require_auth' => true, 'roles' => ['admin']],
    'admin/users/edit' => ['file' => 'views/admin/edit_user.php', 'title' => 'Sửa tài khoản', 'require_auth' => true, 'roles' => ['admin']],
    'admin/users/create' => ['file' => 'views/admin/create_user.php', 'title' => 'Tạo tài khoản', 'require_auth' => true, 'roles' => ['admin']],
    'admin/users/import' => ['file' => 'views/admin/import_users.php', 'title' => 'Import Users', 'require_auth' => true, 'roles' => ['admin']],
    'admin/analytics' => ['file' => 'views/admin/analytics.php', 'title' => 'Analytics Dashboard', 'require_auth' => true, 'roles' => ['admin']],
    'admin/registrations' => ['file' => 'views/admin/pending_registrations.php', 'title' => 'Duyệt đăng ký', 'require_auth' => true, 'roles' => ['admin', 'teacher']],
    'admin/ai-settings' => ['file' => 'views/admin/ai_settings.php', 'title' => 'Cấu hình AI', 'require_auth' => true, 'roles' => ['admin']],
    

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

    // Kiểm tra role ngay tại router (nếu route khai báo 'roles')
    if (!empty($routeConfig['roles'])) {
        $currentRole = $_SESSION['user']['role'] ?? ($_SESSION['role'] ?? '');
        if (!in_array($currentRole, $routeConfig['roles'], true)) {
            http_response_code(403);
            header('Location: ' . URL_ROOT . '/403');
            exit;
        }
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
