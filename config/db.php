<?php
// Nạp hằng số chung (URL_ROOT, PATH_ROOT, .env) nếu API gọi thẳng db.php mà chưa nạp env.php.
// Thiếu bước này, các helper dùng URL_ROOT (vd: thông báo giao bài) gây Fatal error.
if (!defined('URL_ROOT')) {
    require_once __DIR__ . '/env.php';
}

// Nạp cấu hình bí mật nếu chưa được nạp (các API require db.php trực tiếp, không qua index.php)
if (!defined('DB_NAME') && file_exists(__DIR__ . '/env.local.php')) {
    require_once __DIR__ . '/env.local.php';
}

// Fallback cho môi trường chưa có env.local.php
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'diemdanh_literature');

$pdo = new PDO(
    "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASS,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
    ]
);

// Lớp bảo vệ chung (CSRF...) — áp dụng cho mọi file require db.php
require_once __DIR__ . '/security.php';
