<?php
/**
 * Lớp bảo vệ chung: CSRF token cho form và API.
 *
 * - csrf_token():  lấy/tạo token gắn với session hiện tại
 * - csrf_field():  in hidden input cho form POST truyền thống
 * - csrf_verify(): kiểm tra token từ POST body hoặc header X-CSRF-Token
 *
 * File này được require từ config/db.php nên tự động có mặt ở mọi API.
 * Với request POST tới các file trong /api/, token được kiểm tra tự động.
 */

// Cookie phiên an toàn (HttpOnly/SameSite/Secure) cho mọi request đi qua db.php
require_once __DIR__ . '/session.php';

if (!function_exists('client_error_message')) {
    /**
     * Thông báo lỗi an toàn để trả về client.
     * Lỗi DB (PDOException) chứa tên bảng/cột/câu SQL => chỉ ghi log, trả thông báo chung.
     * Exception nghiệp vụ (do code tự throw, vd "Access denied") => giữ nguyên thông báo.
     */
    function client_error_message(Throwable $e, string $fallback = 'Lỗi hệ thống. Vui lòng thử lại sau.'): string
    {
        if ($e instanceof PDOException) {
            error_log('[DB] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return $fallback;
        }
        return $e->getMessage();
    }
}

if (!function_exists('csrf_token')) {

    function csrf_token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
    }

    function csrf_verify(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }
        $sent = $_POST['csrf_token']
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        return is_string($sent) && $sent !== '' && hash_equals($_SESSION['csrf_token'], $sent);
    }

    /**
     * Tự động chặn POST không có token hợp lệ đối với các endpoint trong /api/.
     * Chỉ áp dụng khi đã đăng nhập (session tồn tại) để không phá luồng login.
     */
    function csrf_auto_enforce(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (strpos($script, '/api/') === false) {
            return; // form view tự gọi csrf_verify() thủ công
        }
        if (csrf_verify()) {
            return;
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token. Please reload the page.']);
        exit;
    }

    csrf_auto_enforce();

    // Rate limiting for API POST requests
    require_once __DIR__ . '/rate_limiter.php';
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (strpos($script, '/api/') !== false) {
            $endpoint = basename($script, '.php');
            // Giới hạn tính theo từng user đã đăng nhập (IP nếu chưa đăng nhập).
            // Endpoint gọi AI tốn chi phí nên chặt hơn.
            $aiEndpoints = ['call_api', 'analyze_task'];
            if (in_array($endpoint, $aiEndpoints, true)) {
                rate_limit_enforce('ai', 20, 60);
            } else {
                rate_limit_enforce($endpoint, 120, 60);
            }
        }
    }
}
