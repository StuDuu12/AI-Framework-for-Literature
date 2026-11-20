<?php
/**
 * Cấu hình cookie phiên an toàn: HttpOnly (JS không đọc được session id),
 * SameSite=Lax (giảm CSRF từ site khác), Secure khi chạy HTTPS.
 *
 * - Được nạp ở index.php (trước session_start) và ở security.php (mọi API qua db.php).
 * - Nhiều API gọi session_start() trước khi nạp config; khi đó phiên đã mở với
 *   cookie mặc định, nên ta phát lại cookie với cùng session id kèm đủ cờ bảo mật.
 */

if (!function_exists('app_session_cookie_options')) {

    function app_session_cookie_options(): array
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        return [
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    if (PHP_SAPI !== 'cli') {
        $opts = app_session_cookie_options();

        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', '1');   // từ chối session id không do server cấp
            ini_set('session.use_only_cookies', '1');
            session_set_cookie_params($opts);
        } elseif (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            setcookie(session_name(), session_id(), [
                'expires'  => 0,
                'path'     => $opts['path'],
                'secure'   => $opts['secure'],
                'httponly' => $opts['httponly'],
                'samesite' => $opts['samesite'],
            ]);
        }
    }
}

/*
 * Flash message: lưu thông báo vào session để hiện (dạng toast) ở trang kế tiếp.
 *   flash_set('error', 'Bạn chưa nộp bài.'); header('Location: ...'); exit;
 * views/templates/assets.php in ra <meta name="app-flash">, ui.js hiển thị.
 */
if (!function_exists('flash_set')) {
    function flash_set(string $type, string $message): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $type = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    function flash_consume(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return [];
        }
        // Khoá cũ một số trang còn dùng: chuyển thành flash để hiện ra toast
        foreach (['error_message' => 'error', 'success_message' => 'success'] as $legacy => $type) {
            if (!empty($_SESSION[$legacy]) && is_string($_SESSION[$legacy])) {
                $_SESSION['_flash'][] = ['type' => $type, 'message' => $_SESSION[$legacy]];
            }
            unset($_SESSION[$legacy]);
        }
        if (empty($_SESSION['_flash'])) {
            return [];
        }
        $all = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $all;
    }

    /** Chuyển hướng kèm flash; dự phòng bằng JS nếu header đã gửi. */
    function flash_redirect(string $url, string $type, string $message): void
    {
        flash_set($type, $message);
        if (!headers_sent()) {
            header('Location: ' . $url);
        } else {
            echo '<script>location.href=' . json_encode($url) . ';</script>';
        }
        exit;
    }
}
