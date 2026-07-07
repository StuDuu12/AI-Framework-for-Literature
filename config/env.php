<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
define('URL_ROOT', 'http://literaturever2.test');
define('PATH_ROOT', __DIR__ . '/..');

// Nạp cấu hình bí mật theo máy (DB, API key...) — file này không nằm trong git
if (file_exists(__DIR__ . '/env.local.php')) {
    require_once __DIR__ . '/env.local.php';
}
