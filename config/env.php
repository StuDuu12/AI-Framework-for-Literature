<?php 
date_default_timezone_set('Asia/Ho_Chi_Minh');
define('URL_ROOT', 'http://literaturever2.test');
define('PATH_ROOT', __DIR__ . '/..');

function loadDotEnvFile(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        $parts = explode('=', $trimmed, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $name = trim($parts[0]);
        $value = trim($parts[1]);
        if ($name === '' || defined($name)) {
            continue;
        }

        putenv("{$name}={$value}");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
        define($name, $value);
    }
}

loadDotEnvFile(__DIR__ . '/../.env');

/*
 * Hiển thị lỗi PHP: chỉ bật khi phát triển local. Trên server thật, lỗi in ra trình duyệt
 * làm lộ đường dẫn file, tên bảng/cột... => chỉ ghi vào log.
 * Local = APP_ENV là local/development trong .env, hoặc chạy trên *.test / localhost (Laragon).
 */
if (!function_exists('app_is_local')) {
    function app_is_local(): bool
    {
        $env = strtolower((string)(getenv('APP_ENV') ?: ''));
        if ($env !== '') {
            return in_array($env, ['local', 'development', 'dev'], true);
        }
        if (PHP_SAPI === 'cli') {
            return true;
        }
        $host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
        return $host === 'localhost' || $host === '127.0.0.1' || str_ends_with($host, '.test');
    }
}
ini_set('display_errors', app_is_local() ? '1' : '0');
ini_set('log_errors', '1');

// Nạp cấu hình bí mật theo máy (DB, API key...) — file này không nằm trong git
if (file_exists(__DIR__ . '/env.local.php')) {
    require_once __DIR__ . '/env.local.php';
}
