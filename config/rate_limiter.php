<?php
/**
 * Rate Limiter — giới hạn số request theo cửa sổ thời gian cố định, lưu trong DB.
 *
 * Usage:
 *   rate_limit_check('save_draft', 120, 60);            // 120 req / 60s cho user hiện tại
 *   rate_limit_check('login', 10, 300, 'u:' . $name);    // khoá tuỳ chỉnh (vd: theo username)
 *
 * Khác bản cũ:
 *  - Đếm theo USER khi đã đăng nhập (cả lớp học dùng chung một IP NAT không còn bị chặn chung);
 *    chỉ dùng IP khi chưa đăng nhập.
 *  - Bảng có UNIQUE KEY (scope, endpoint, window) nên ON DUPLICATE KEY thật sự cộng dồn
 *    (bản cũ không có unique key => mỗi request thêm 1 dòng).
 *  - Không chạy CREATE TABLE + DELETE ở mỗi request: chỉ tạo bảng khi thiếu,
 *    dọn bản ghi cũ ngẫu nhiên ~1% số lần.
 */

if (!function_exists('rate_limit_check')) {

    /** Khoá phạm vi mặc định: user đang đăng nhập, nếu không thì IP */
    function rate_limit_scope(): string
    {
        $uid = $_SESSION['user']['user_id'] ?? ($_SESSION['user_id'] ?? null);
        if ($uid) {
            return 'u:' . (int)$uid;
        }
        return 'ip:' . ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    function rate_limit_ensure_table(PDO $pdo): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `api_rate_limit_buckets` (
            `scope` varchar(120) NOT NULL,
            `endpoint` varchar(100) NOT NULL,
            `window_start` int unsigned NOT NULL,
            `hits` int unsigned NOT NULL DEFAULT 1,
            PRIMARY KEY (`scope`, `endpoint`, `window_start`),
            KEY `idx_rl_window` (`window_start`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    /**
     * @return array ['allowed' => bool, 'remaining' => int, 'retry_after' => int]
     */
    function rate_limit_check(string $endpoint, int $maxRequests = 60, int $windowSeconds = 60, ?string $scope = null): array
    {
        global $pdo;

        $scope = mb_substr($scope ?? rate_limit_scope(), 0, 120);
        $endpoint = mb_substr($endpoint, 0, 100);
        $now = time();
        $window = intdiv($now, $windowSeconds) * $windowSeconds;

        $hit = function () use ($pdo, $scope, $endpoint, $window): int {
            $stmt = $pdo->prepare("
                INSERT INTO api_rate_limit_buckets (scope, endpoint, window_start, hits)
                VALUES (:s, :e, :w, 1)
                ON DUPLICATE KEY UPDATE hits = hits + 1
            ");
            $stmt->execute([':s' => $scope, ':e' => $endpoint, ':w' => $window]);

            $stmt = $pdo->prepare("SELECT hits FROM api_rate_limit_buckets WHERE scope = :s AND endpoint = :e AND window_start = :w");
            $stmt->execute([':s' => $scope, ':e' => $endpoint, ':w' => $window]);
            return (int)$stmt->fetchColumn();
        };

        try {
            try {
                $hits = $hit();
            } catch (PDOException $e) {
                if ($e->getCode() !== '42S02') { // 42S02 = bảng chưa tồn tại
                    throw $e;
                }
                rate_limit_ensure_table($pdo);
                $hits = $hit();
            }

            // Dọn các cửa sổ cũ thỉnh thoảng (không phải mỗi request)
            if (random_int(1, 100) === 1) {
                $pdo->prepare("DELETE FROM api_rate_limit_buckets WHERE window_start < :cutoff")
                    ->execute([':cutoff' => $now - 3600]);
            }

            if ($hits > $maxRequests) {
                return ['allowed' => false, 'remaining' => 0, 'retry_after' => max(1, $window + $windowSeconds - $now)];
            }
            return ['allowed' => true, 'remaining' => $maxRequests - $hits, 'retry_after' => 0];
        } catch (PDOException $e) {
            // Lỗi DB thì cho qua (fail open) để không làm sập tính năng
            error_log('rate_limit_check: ' . $e->getMessage());
            return ['allowed' => true, 'remaining' => $maxRequests, 'retry_after' => 0];
        }
    }

    /** Trả 429 JSON và dừng nếu vượt giới hạn */
    function rate_limit_enforce(string $endpoint, int $maxRequests, int $windowSeconds, ?string $scope = null, string $message = 'Quá nhiều yêu cầu. Vui lòng thử lại sau.'): void
    {
        $result = rate_limit_check($endpoint, $maxRequests, $windowSeconds, $scope);
        if ($result['allowed']) {
            return;
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
        header('Retry-After: ' . $result['retry_after']);
        echo json_encode(['success' => false, 'message' => $message, 'error' => $message]);
        exit;
    }
}
