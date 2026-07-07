<?php
/**
 * Rate Limiter — Simple in-memory + DB-backed rate limiting for API endpoints.
 * Usage: rate_limit_check('endpoint_name', 60, 60); // 60 requests per 60 seconds per IP
 */

if (!function_exists('rate_limit_check')) {

    /**
     * Check if the current request exceeds the rate limit.
     * @param string $endpoint  Endpoint identifier
     * @param int    $maxRequests  Max requests allowed in the window
     * @param int    $windowSeconds  Time window in seconds
     * @return array ['allowed' => bool, 'remaining' => int, 'retry_after' => int]
     */
    function rate_limit_check(string $endpoint, int $maxRequests = 60, int $windowSeconds = 60): array
    {
        global $pdo;

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $windowStart = date('Y-m-d H:i:s', time() - $windowSeconds);

        try {
            // Ensure table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS `api_rate_limits` (
                `id` int NOT NULL AUTO_INCREMENT,
                `ip_address` varchar(45) NOT NULL,
                `endpoint` varchar(100) NOT NULL,
                `request_count` int DEFAULT 1,
                `window_start` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_rl_lookup` (`ip_address`, `endpoint`, `window_start`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            // Cleanup old entries (older than 2x window)
            $cleanup = $pdo->prepare("DELETE FROM api_rate_limits WHERE window_start < :cutoff");
            $cleanup->execute([':cutoff' => date('Y-m-d H:i:s', time() - $windowSeconds * 2)]);

            // Count current requests in window
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(request_count), 0) as total FROM api_rate_limits WHERE ip_address = :ip AND endpoint = :ep AND window_start >= :start");
            $stmt->execute([':ip' => $ip, ':ep' => $endpoint, ':start' => $windowStart]);
            $total = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $remaining = max(0, $maxRequests - $total);

            if ($total >= $maxRequests) {
                $retry = $windowSeconds;
                return ['allowed' => false, 'remaining' => 0, 'retry_after' => $retry];
            }

            // Increment counter
            $stmt = $pdo->prepare("INSERT INTO api_rate_limits (ip_address, endpoint, request_count) VALUES (:ip, :ep, 1) ON DUPLICATE KEY UPDATE request_count = request_count + 1");
            $stmt->execute([':ip' => $ip, ':ep' => $endpoint]);

            return ['allowed' => true, 'remaining' => $remaining - 1, 'retry_after' => 0];
        } catch (PDOException $e) {
            // On error, allow the request (fail open)
            return ['allowed' => true, 'remaining' => $maxRequests, 'retry_after' => 0];
        }
    }
}
