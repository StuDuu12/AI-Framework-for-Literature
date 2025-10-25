<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/env.php';
require_once 'config/db.php';

$route = isset($_GET['route']) ? $_GET['route'] : '';
$route = trim($route, '/');

$routes = [
    'templates' => ['file' => 'views/tmp/index.php', 'require_auth' => true],

    // Đăng nhập đăng xuất
    'login' => ['file' => 'views/account/login.php', 'title' => 'Đăng nhập', 'require_auth' => false],
    'logout' => ['file' => 'views/account/logout.php', 'title' => 'Đăng ký', 'require_auth' => false],

    '' => ['file' => 'views/index.php', 'title' => 'Dashboard', 'require_auth' => true],
    'joinclass' => ['file' => 'api/joinclass.php', 'require_auth' => true],

    'class' => ['file' => 'views/homeclass.php', 'title' => 'Trang lớp học', 'require_auth' => true],

    'class/participants' => ['file' => 'views/participants_groups/participants.php', 'title' => 'Quản lý thành viên lớp học', 'require_auth' => true],

    'literary_analysis' => ['file' => 'views/literary_analysis/literary_analysis.php', 'title' => 'Phân tích Văn học', 'require_auth' => true],

    // Error pages
    '400' => ['file' => 'views/errors/400.php', 'title' => '400 Bad Request', 'require_auth' => false],
    '403' => ['file' => 'views/errors/403.php', 'title' => '403 Forbidden', 'require_auth' => false],
    '404' => ['file' => 'views/errors/404.php', 'title' => '404 Not Found', 'require_auth' => false],
    '500' => ['file' => 'views/errors/500.php', 'title' => '500 Internal Server Error', 'require_auth' => false],
    '503' => ['file' => 'views/errors/503.php', 'title' => '503 Service Unavailable', 'require_auth' => false],


    // '' => ['file' => '', 'require_auth' => false]
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
