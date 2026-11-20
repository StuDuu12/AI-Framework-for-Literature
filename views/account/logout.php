<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Chống CSRF đăng xuất: link đăng xuất là GET, nên một trang web khác có thể nhúng
 * <img src=".../logout"> để ép người dùng đăng xuất. Chỉ chấp nhận khi request đến
 * từ chính site này hoặc người dùng tự gõ URL.
 *  - Trình duyệt hiện đại gửi Sec-Fetch-Site: same-origin / same-site / none (gõ URL).
 *  - Trình duyệt cũ: so host của Referer (nếu có) với host hiện tại.
 */
$fetchSite = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$crossSite = false;
if ($fetchSite !== '') {
    $crossSite = !in_array($fetchSite, ['same-origin', 'same-site', 'none'], true);
} elseif ($referer !== '') {
    $crossSite = parse_url($referer, PHP_URL_HOST) !== ($_SERVER['HTTP_HOST'] ?? '');
}
if ($crossSite) {
    header('Location: ' . URL_ROOT . '/');
    exit;
}

// Dọn sạch dữ liệu session và vô hiệu cookie phiên cũ
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?: 'Lax',
    ]);
}
session_destroy();

header('Location: ' . URL_ROOT . '/login');
exit;
